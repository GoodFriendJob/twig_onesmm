<?php
/**
 * Local Twig Preview Server for Perfect Panel templates.
 *
 * Usage:
 *   php -S localhost:8000 preview.php
 *
 * Then visit:
 *   http://localhost:8000/                     → singin.twig (homepage)
 *   http://localhost:8000/singin               → singin.twig
 *   http://localhost:8000/signup                → signup.twig
 *   http://localhost:8000/buy-instagram-followers → buy-instagram-followers.twig
 *   http://localhost:8000/free-telegram-members   → free-telegram-members.twig
 *   http://localhost:8000/layout                → layout.twig (standalone)
 *   http://localhost:8000/our-story             → our-story.twig
 *   http://localhost:8000/careers               → careers.twig
 *   ... any filename from css-files/ without .twig
 *
 * Static files (css, images, etc.) are served directly.
 */

require_once __DIR__ . '/vendor/autoload.php';

// ── Serve static files directly ──
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$staticPath = __DIR__ . $requestUri;

// Serve css-files/style.css and images directly
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|webp|avif|mp4|webm)$/i', $requestUri)) {
    // Try exact path first
    if (is_file($staticPath)) {
        return false; // Let PHP built-in server handle it
    }
    // Try inside css-files/
    $cssPath = __DIR__ . '/css-files' . $requestUri;
    if (is_file($cssPath)) {
        $mimeTypes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
        ];
        $ext = strtolower(pathinfo($cssPath, PATHINFO_EXTENSION));
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        readfile($cssPath);
        return;
    }
    // Try root-level images
    if (is_file(__DIR__ . $requestUri)) {
        return false;
    }
    http_response_code(404);
    echo "Static file not found: $requestUri";
    return;
}

// ── Twig setup ──
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/css-files');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
    'strict_variables' => false,
    'autoescape' => false,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

// ── Mock `lang()` function ──
$twig->addFunction(new \Twig\TwigFunction('lang', function ($key) {
    // Return the key itself (same behavior as Perfect Panel when translation missing)
    return $key;
}));

// ── Mock PHP math functions that Perfect Panel exposes to Twig ──
$twig->addFunction(new \Twig\TwigFunction('ceil', 'ceil'));
$twig->addFunction(new \Twig\TwigFunction('floor', 'floor'));
$twig->addFunction(new \Twig\TwigFunction('round', 'round'));

// ── Mock `page_url()` function ──
$twig->addFunction(new \Twig\TwigFunction('page_url', function ($route) {
    $routes = [
        'index'         => '/',
        'signin'        => '/singin',
        'signup'        => '/signup',
        'services'      => '/services',
        'neworder'      => '/neworder',
        'orders'        => '/orders',
        'addfunds'      => '/addfunds',
        'blog'          => '/blog',
        'terms'         => '/terms',
        'contact'       => '/contact',
        'api'           => '/api',
        'tickets'       => '/tickets',
        'account'       => '/account',
        'affiliates'    => '/affiliates',
        'massorder'     => '/massorder',
        'drip_feed'     => '/drip_feed',
        'refill'        => '/refill',
        'child_panel'   => '/child_panel',
        'subscriptions' => '/subscriptions',
        'forgot'        => '/forgot',
    ];
    return $routes[$route] ?? '/' . $route;
}));

// ── Determine which template to render ──
$slug = trim($requestUri, '/');
if ($slug === '' || $slug === 'index') {
    $slug = 'singin';
}

// Handle blog post sub-URLs: /blog/some-slug → blogpost.twig
$isBlogPost = false;
$blogPostSlug = '';
if (preg_match('#^blog/(.+)$#', $slug, $m)) {
    $isBlogPost = true;
    $blogPostSlug = $m[1];
    $slug = 'blog-post';
}

$templateFile = $slug . '.twig';
if (!file_exists(__DIR__ . '/css-files/' . $templateFile)) {
    http_response_code(404);
    echo "<h1>Template not found: css-files/{$templateFile}</h1>";
    echo "<h2>Available templates:</h2><ul>";
    foreach (glob(__DIR__ . '/css-files/*.twig') as $f) {
        $name = basename($f, '.twig');
        echo "<li><a href=\"/{$name}\">{$name}</a></li>";
    }
    echo "</ul>";
    return;
}

// ── Mock Perfect Panel global variables ──
$isAuth = isset($_GET['auth']) && $_GET['auth'] === '1';

$context = [
    'site' => [
        'name'           => 'OneSMM',
        'iso_lang_code'  => $_GET['lang'] ?? 'en',
        'rtl'            => in_array($_GET['lang'] ?? 'en', ['fa', 'ar']),
        'favicon'        => '/favicon.ico',
        'logo'           => 'https://onesmm.com/uploads/logo/onesmm-logo.png',
        'seo_key'        => 'smm panel, buy instagram followers, buy telegram members',
        'seo_desc'       => 'OneSMM — #1 SMM Panel with 5,000+ services. Buy Instagram followers, Telegram members, TikTok views & more.',
        'custom_header'  => '',
        'custom_footer'  => '',
        'styles'         => [
            ['href' => '/style.css'],
        ],
        'scripts'        => [],
        'menu'           => [
            ['name' => 'New Order',     'link' => '/neworder',  'icon' => 'fa-plus',           'active' => false, 'external' => false],
            ['name' => 'Services',      'link' => '/services',  'icon' => 'fa-list',           'active' => false, 'external' => false],
            ['name' => 'Orders',        'link' => '/orders',    'icon' => 'fa-shopping-cart',  'active' => false, 'external' => false],
            ['name' => 'Add Funds',     'link' => '/addfunds',  'icon' => 'fa-credit-card',    'active' => false, 'external' => false],
            ['name' => 'API',           'link' => '/api',       'icon' => 'fa-code',           'active' => false, 'external' => false],
            ['name' => 'Support',       'link' => '/tickets',   'icon' => 'fa-life-ring',      'active' => false, 'external' => false],
            ['name' => 'Blog',          'link' => '/blog',      'icon' => 'fa-newspaper-o',    'active' => false, 'external' => false],
        ],
        'account_menu'   => [
            ['name' => 'Account',  'link' => '/account'],
            ['name' => '$100.00',  'link' => null],
        ],
        'languages'      => [
            ['name' => 'English',  'url' => '/?lang=en',  'active' => true],
            ['name' => 'فارسی',    'url' => '/?lang=fa',  'active' => false],
            ['name' => 'Русский',  'url' => '/?lang=ru',  'active' => false],
            ['name' => 'Türkçe',   'url' => '/?lang=tr',  'active' => false],
            ['name' => 'Español',  'url' => '/?lang=es',  'active' => false],
        ],
        'currencies'     => [
            'USD' => ['symbol' => '$', 'code' => 'USD'],
            'EUR' => ['symbol' => '€', 'code' => 'EUR'],
        ],
    ],
    'user' => [
        'auth'              => $isAuth,
        'balance_formatted' => '$100.00',
    ],
    'page' => [
        'title' => ucwords(str_replace('-', ' ', $slug)),
        'url'   => 'https://onesmm.com/' . $slug,
    ],
    'csrftoken'    => 'mock-csrf-' . bin2hex(random_bytes(16)),

    // singin.twig specific
    'registration' => true,
    'captcha'      => false,
    'success'      => false,
    'error'        => false,
    'successMessage' => '',
    'errorMessage'   => '',
    'authText'       => '',
    'googleSignIn'          => true,
    'googleClientId'        => 'mock-client-id.apps.googleusercontent.com',
    'googleSignInRedirectUrl' => '/signin/google',
];

// ── Add site.protocol and site.domain (needed by api.twig and others) ──
$context['site']['protocol'] = 'https';
$context['site']['domain'] = 'onesmm.com';
$context['site']['average_time'] = true;
$context['site']['captcha'] = false;
$context['site']['currency'] = ['label' => 'USD', 'symbol' => '$'];

// ── Page-specific mock data ──

if ($slug === 'signup') {
    $context['fields'] = [
        ['code' => 'username',       'label' => 'signup.username',       'type' => 'text',     'value' => '', 'required' => true],
        ['code' => 'email',          'label' => 'signup.email',          'type' => 'email',    'value' => '', 'required' => true],
        ['code' => 'password',       'label' => 'signup.password',       'type' => 'password', 'value' => '', 'required' => true],
        ['code' => 'password_confirm','label' => 'signup.password_confirm','type' => 'password','value' => '', 'required' => true],
        ['code' => 'first_name',     'label' => 'signup.first_name',     'type' => 'text',     'value' => '', 'required' => false],
        ['code' => 'last_name',      'label' => 'signup.last_name',      'type' => 'text',     'value' => '', 'required' => false],
        ['code' => 'skype',          'label' => 'signup.skype',          'type' => 'text',     'value' => '', 'required' => false],
    ];
    $context['check_agreement'] = true;
    $context['captchaCode'] = '';
    $context['success'] = false;
    $context['successText'] = '';
    $context['error'] = false;
    $context['errorMessage'] = '';
}

if ($slug === 'blog') {
    $context['blog'] = '<p>Grow smarter — tips, guides, and strategies from the OneSMM team.</p>';
    $context['posts'] = [
        [
            'title'   => 'How to Buy Instagram Followers Safely in 2026',
            'url'     => 'how-to-buy-instagram-followers-safely',
            'image'   => 'https://placehold.co/800x420/1a1a2e/e0e0e0?text=Instagram+Growth',
            'content' => '<p>A comprehensive guide to growing your Instagram audience using SMM panels — what to look for, what to avoid, and how to protect your account.</p>',
        ],
        [
            'title'   => 'Telegram Channel Growth: The Complete Strategy',
            'url'     => 'telegram-channel-growth-strategy',
            'image'   => 'https://placehold.co/800x420/0088cc/ffffff?text=Telegram+Growth',
            'content' => '<p>Everything you need to know about building a thriving Telegram channel — from your first 100 members to scaling past 10K.</p>',
        ],
        [
            'title'   => 'SMM Panel Pricing Comparison: What You\'re Really Paying For',
            'url'     => 'smm-panel-pricing-comparison',
            'image'   => 'https://placehold.co/800x420/2d1b69/e0e0e0?text=Pricing+Guide',
            'content' => '<p>We break down what separates a $0.01 service from a $0.10 one — quality tiers, refill policies, and the hidden costs of going cheap.</p>',
        ],
        [
            'title'   => 'YouTube Watch Time: How SMM Panels Can Help Monetization',
            'url'     => 'youtube-watch-time-smm-panels',
            'image'   => 'https://placehold.co/800x420/cc0000/ffffff?text=YouTube+Watch+Time',
            'content' => '<p>Understanding how watch time services work, their role in reaching the 4,000 hour threshold, and how to use them alongside organic content.</p>',
        ],
        [
            'title'   => 'Twitter/X Growth Strategy for 2026',
            'url'     => 'twitter-growth-strategy-2026',
            'image'   => 'https://placehold.co/800x420/1da1f2/ffffff?text=Twitter+Growth',
            'content' => '<p>X (Twitter) is underserved by SMM panels. Here\'s how to build real engagement with a hybrid organic+panel approach.</p>',
        ],
        [
            'title'   => 'TikTok Followers & Likes: What Actually Works',
            'url'     => 'tiktok-followers-likes-guide',
            'image'   => 'https://placehold.co/800x420/010101/fe2c55?text=TikTok+Guide',
            'content' => '<p>TikTok\'s algorithm rewards engagement velocity. Learn how timed follower and like boosts can amplify your organic reach.</p>',
        ],
    ];
    $context['pagination'] = [
        'count'   => 6,
        'current' => 1,
        'pages'   => 1,
        'next'    => null,
        'last'    => null,
    ];
}

if ($isBlogPost || $slug === 'blog-post' || $slug === 'blogpost') {
    $context['post'] = [
        'title'      => 'How to Buy Instagram Followers Safely in 2026',
        'image'      => 'https://placehold.co/1200x630/1a1a2e/e0e0e0?text=Instagram+Growth+Guide',
        'date'       => '2026-05-15',
        'updated_at' => '2026-05-20',
        'content'    => '
            <h2>Why People Buy Instagram Followers</h2>
            <p>Social proof is the currency of the digital age. When a new visitor lands on your profile and sees 50 followers versus 5,000, their perception shifts instantly. This is not vanity — it is <strong>conversion psychology</strong>.</p>
            <p>For businesses, influencers, and creators, an established follower count signals credibility. Brands are more likely to collaborate, and organic users are more likely to follow an account that already has traction.</p>

            <h2>How SMM Panels Work</h2>
            <p>An SMM (Social Media Marketing) panel is a web-based platform where you can purchase engagement services — followers, likes, views, comments — across multiple social platforms. Think of it as a wholesale marketplace for social signals.</p>
            <p>Here\'s the typical flow:</p>
            <ol>
                <li>Create an account on the panel</li>
                <li>Add funds to your balance</li>
                <li>Select the service you need (e.g., "Instagram Followers — High Quality")</li>
                <li>Enter your profile link and quantity</li>
                <li>Submit the order — delivery starts automatically</li>
            </ol>

            <h2>Quality Tiers Explained</h2>
            <p>Not all follower services are created equal. Most panels offer multiple tiers:</p>
            <table>
                <thead><tr><th>Tier</th><th>Profile Quality</th><th>Drop Rate</th><th>Price Range</th></tr></thead>
                <tbody>
                    <tr><td>Low Quality</td><td>No avatar, no posts</td><td>30-60%</td><td>$0.01-0.03/1K</td></tr>
                    <tr><td>Medium Quality</td><td>Avatars, some posts</td><td>10-20%</td><td>$0.05-0.15/1K</td></tr>
                    <tr><td>High Quality</td><td>Full profiles, real-looking</td><td>5-10%</td><td>$0.20-0.50/1K</td></tr>
                    <tr><td>Real/Active</td><td>Real users, engagement</td><td>2-5%</td><td>$1.00-5.00/1K</td></tr>
                </tbody>
            </table>

            <h2>Safety Best Practices</h2>
            <div class="highlight-box">
                <p><strong>Golden rule:</strong> Never buy more than 10-15% of your current follower count in a single day. Gradual growth looks natural to Instagram\'s algorithm.</p>
            </div>
            <p>Additional safety tips:</p>
            <ul>
                <li>Use drip-feed delivery to spread followers over days</li>
                <li>Mix purchased followers with organic growth efforts</li>
                <li>Never share your Instagram password with any service</li>
                <li>Choose services with a refill guarantee</li>
                <li>Start with a small test order before scaling up</li>
            </ul>

            <h2>Frequently Asked Questions</h2>
            <div class="faq-box">
                <p><strong>Will buying followers get my account banned?</strong></p>
                <p>When done responsibly (gradual delivery, quality providers), the risk is minimal. Instagram primarily targets aggressive bot behavior, not passive follower gains.</p>
            </div>
            <div class="faq-box">
                <p><strong>How long does delivery take?</strong></p>
                <p>Most services begin within 0-1 hours. Full delivery depends on quantity — 1K followers typically completes in 1-6 hours.</p>
            </div>
        ',
    ];
}

if ($slug === 'services') {
    $context['serviceCategoryList'] = [
        [
            'id' => 1,
            'name' => 'Instagram - Followers',
            'icon' => ['icon_type' => 'emoji', 'icon' => '📸', 'url' => ''],
            'services' => [
                ['id' => 101, 'name' => 'Instagram Followers - Real & Active', 'rate' => '$2.50', 'original_rate' => '$2.50', 'min' => 100, 'max' => 100000, 'average_time' => '2 hours', 'description' => 'High Quality Followers<br><br>✔ Real-looking profiles<br>Avatars and posts<br><br>✔ Refill guarantee<br>30-day automatic refill<br><br>✔ Gradual delivery<br>Natural growth pattern', 'favorite' => false],
                ['id' => 102, 'name' => 'Instagram Followers - Premium [Max 500K]', 'rate' => '$3.80', 'original_rate' => '$3.80', 'min' => 50, 'max' => 500000, 'average_time' => '6 hours', 'description' => 'Premium tier with lowest drop rate', 'favorite' => false],
                ['id' => 103, 'name' => 'Instagram Followers - Ultra Fast', 'rate' => '$1.20', 'original_rate' => '$1.20', 'min' => 100, 'max' => 50000, 'average_time' => '30 min', 'description' => 'Fastest delivery, medium quality profiles', 'favorite' => false],
                ['id' => 104, 'name' => 'Instagram Followers - Drip Feed', 'rate' => '$4.00', 'original_rate' => '$4.00', 'min' => 500, 'max' => 200000, 'average_time' => '48 hours', 'description' => 'Slow drip delivery over 2-7 days', 'favorite' => true],
            ],
        ],
        [
            'id' => 2,
            'name' => 'Instagram - Likes',
            'icon' => ['icon_type' => 'emoji', 'icon' => '❤️', 'url' => ''],
            'services' => [
                ['id' => 201, 'name' => 'Instagram Likes - Instant [Max 50K]', 'rate' => '$0.80', 'original_rate' => '$0.80', 'min' => 50, 'max' => 50000, 'average_time' => '15 min', 'description' => 'Fast likes from worldwide profiles', 'favorite' => false],
                ['id' => 202, 'name' => 'Instagram Likes - Real & Active', 'rate' => '$1.50', 'original_rate' => '$1.50', 'min' => 100, 'max' => 100000, 'average_time' => '1 hour', 'description' => 'Higher quality, real engagement', 'favorite' => false],
                ['id' => 203, 'name' => 'Instagram Auto Likes - Per Post', 'rate' => '$5.00', 'original_rate' => '$5.00', 'min' => 50, 'max' => 5000, 'average_time' => '10 min', 'description' => 'Automatic likes on new posts for 30 days', 'favorite' => false],
            ],
        ],
        [
            'id' => 3,
            'name' => 'Instagram - Views & Reels',
            'icon' => ['icon_type' => 'emoji', 'icon' => '👁️', 'url' => ''],
            'services' => [
                ['id' => 301, 'name' => 'Instagram Reel Views - Fast', 'rate' => '$0.30', 'original_rate' => '$0.30', 'min' => 100, 'max' => 1000000, 'average_time' => '30 min', 'description' => '', 'favorite' => false],
                ['id' => 302, 'name' => 'Instagram Story Views', 'rate' => '$0.50', 'original_rate' => '$0.50', 'min' => 100, 'max' => 100000, 'average_time' => '1 hour', 'description' => '', 'favorite' => false],
            ],
        ],
        [
            'id' => 4,
            'name' => 'Telegram - Members',
            'icon' => ['icon_type' => 'emoji', 'icon' => '✈️', 'url' => ''],
            'services' => [
                ['id' => 401, 'name' => 'Telegram Channel Members - Real & Active', 'rate' => '$3.00', 'original_rate' => '$3.00', 'min' => 100, 'max' => 100000, 'average_time' => '4 hours', 'description' => 'Real Telegram users with activity', 'favorite' => false],
                ['id' => 402, 'name' => 'Telegram Group Members', 'rate' => '$2.00', 'original_rate' => '$2.00', 'min' => 100, 'max' => 200000, 'average_time' => '6 hours', 'description' => '', 'favorite' => false],
                ['id' => 403, 'name' => 'Telegram Channel Subscribers - Premium', 'rate' => '$5.50', 'original_rate' => '$5.50', 'min' => 500, 'max' => 50000, 'average_time' => '12 hours', 'description' => 'Lowest drop, premium quality', 'favorite' => true],
            ],
        ],
        [
            'id' => 5,
            'name' => 'Telegram - Views & Reactions',
            'icon' => ['icon_type' => 'emoji', 'icon' => '👀', 'url' => ''],
            'services' => [
                ['id' => 501, 'name' => 'Telegram Post Views', 'rate' => '$0.10', 'original_rate' => '$0.10', 'min' => 100, 'max' => 1000000, 'average_time' => '10 min', 'description' => '', 'favorite' => false],
                ['id' => 502, 'name' => 'Telegram Reactions - All Types', 'rate' => '$0.60', 'original_rate' => '$0.60', 'min' => 50, 'max' => 50000, 'average_time' => '30 min', 'description' => '', 'favorite' => false],
            ],
        ],
        [
            'id' => 6,
            'name' => 'YouTube - Subscribers',
            'icon' => ['icon_type' => 'emoji', 'icon' => '🎬', 'url' => ''],
            'services' => [
                ['id' => 601, 'name' => 'YouTube Subscribers - Real', 'rate' => '$8.00', 'original_rate' => '$8.00', 'min' => 50, 'max' => 50000, 'average_time' => '24 hours', 'description' => 'Real-looking subscriber accounts', 'favorite' => false],
                ['id' => 602, 'name' => 'YouTube Views - High Retention', 'rate' => '$1.50', 'original_rate' => '$1.50', 'min' => 500, 'max' => 1000000, 'average_time' => '12 hours', 'description' => '60-90% retention rate, worldwide', 'favorite' => false],
                ['id' => 603, 'name' => 'YouTube Watch Time - 4000 Hours', 'rate' => '$40.00', 'original_rate' => '$40.00', 'min' => 1, 'max' => 10, 'average_time' => '7 days', 'description' => 'Monetization-ready watch time package', 'favorite' => false],
            ],
        ],
        [
            'id' => 7,
            'name' => 'YouTube - Likes & Comments',
            'icon' => ['icon_type' => 'emoji', 'icon' => '👍', 'url' => ''],
            'services' => [
                ['id' => 701, 'name' => 'YouTube Likes', 'rate' => '$1.20', 'original_rate' => '$1.20', 'min' => 50, 'max' => 100000, 'average_time' => '2 hours', 'description' => '', 'favorite' => false],
                ['id' => 702, 'name' => 'YouTube Comments - Custom', 'rate' => '$10.00', 'original_rate' => '$10.00', 'min' => 5, 'max' => 1000, 'average_time' => '6 hours', 'description' => 'Custom comments from real profiles', 'favorite' => false],
            ],
        ],
        [
            'id' => 8,
            'name' => 'Twitter/X - Followers',
            'icon' => ['icon_type' => 'emoji', 'icon' => '🐦', 'url' => ''],
            'services' => [
                ['id' => 801, 'name' => 'Twitter/X Followers - High Quality', 'rate' => '$3.00', 'original_rate' => '$3.00', 'min' => 100, 'max' => 100000, 'average_time' => '6 hours', 'description' => 'Profiles with avatars and tweet history', 'favorite' => false],
                ['id' => 802, 'name' => 'Twitter/X Likes', 'rate' => '$1.00', 'original_rate' => '$1.00', 'min' => 50, 'max' => 50000, 'average_time' => '1 hour', 'description' => '', 'favorite' => false],
                ['id' => 803, 'name' => 'Twitter/X Retweets', 'rate' => '$1.50', 'original_rate' => '$1.50', 'min' => 50, 'max' => 50000, 'average_time' => '2 hours', 'description' => '', 'favorite' => false],
            ],
        ],
        [
            'id' => 9,
            'name' => 'TikTok - Followers & Likes',
            'icon' => ['icon_type' => 'emoji', 'icon' => '🎵', 'url' => ''],
            'services' => [
                ['id' => 901, 'name' => 'TikTok Followers', 'rate' => '$2.80', 'original_rate' => '$2.80', 'min' => 100, 'max' => 500000, 'average_time' => '4 hours', 'description' => '', 'favorite' => false],
                ['id' => 902, 'name' => 'TikTok Likes', 'rate' => '$0.50', 'original_rate' => '$0.50', 'min' => 100, 'max' => 500000, 'average_time' => '1 hour', 'description' => '', 'favorite' => false],
                ['id' => 903, 'name' => 'TikTok Views', 'rate' => '$0.15', 'original_rate' => '$0.15', 'min' => 500, 'max' => 10000000, 'average_time' => '30 min', 'description' => '', 'favorite' => false],
            ],
        ],
        [
            'id' => 10,
            'name' => 'Facebook - Followers & Likes',
            'icon' => ['icon_type' => 'emoji', 'icon' => '📘', 'url' => ''],
            'services' => [
                ['id' => 1001, 'name' => 'Facebook Page Likes', 'rate' => '$4.00', 'original_rate' => '$4.00', 'min' => 100, 'max' => 100000, 'average_time' => '12 hours', 'description' => '', 'favorite' => false],
                ['id' => 1002, 'name' => 'Facebook Post Likes', 'rate' => '$0.80', 'original_rate' => '$0.80', 'min' => 50, 'max' => 50000, 'average_time' => '2 hours', 'description' => '', 'favorite' => false],
                ['id' => 1003, 'name' => 'Facebook Followers', 'rate' => '$3.50', 'original_rate' => '$3.50', 'min' => 100, 'max' => 100000, 'average_time' => '12 hours', 'description' => '', 'favorite' => false],
            ],
        ],
    ];
    $context['converted'] = false;
    $context['serviceDescription'] = true;
    $context['servicesText'] = '<h2>Why Choose OneSMM?</h2><p>OneSMM offers 5,000+ services across all major social platforms with instant delivery, 24/7 support, and the most competitive pricing in the market. Our services include Instagram followers, Telegram members, YouTube subscribers, TikTok views, and more.</p>';
    $context['user']['favorite_services'] = $isAuth;
}

if ($slug === 'api') {
    $context['methods'] = [
        'add' => [
            'title' => 'Add order',
            'types' => [
                'default' => 'Default',
                'package' => 'Package',
                'custom_comments' => 'Custom Comments',
                'subscription' => 'Subscription',
            ],
            'parameters' => [
                'default' => [
                    'key'      => 'Your API key',
                    'action'   => '"add"',
                    'service'  => 'Service ID',
                    'link'     => 'Link to page',
                    'quantity' => 'Needed quantity',
                ],
                'package' => [
                    'key'     => 'Your API key',
                    'action'  => '"add"',
                    'service' => 'Service ID',
                    'link'    => 'Link to page',
                ],
                'custom_comments' => [
                    'key'      => 'Your API key',
                    'action'   => '"add"',
                    'service'  => 'Service ID',
                    'link'     => 'Link to page',
                    'comments' => 'Comments list separated by \\n',
                ],
                'subscription' => [
                    'key'      => 'Your API key',
                    'action'   => '"add"',
                    'service'  => 'Service ID',
                    'link'     => 'Link to page',
                    'quantity' => 'Needed quantity',
                    'runs'     => 'Number of runs to execute',
                    'interval' => 'Interval in minutes',
                ],
            ],
            'examples' => json_encode([
                'order' => 12345,
            ], JSON_PRETTY_PRINT),
        ],
        'status' => [
            'title' => 'Order status',
            'parameters' => [
                'key'    => 'Your API key',
                'action' => '"status"',
                'order'  => 'Order ID',
            ],
            'examples' => json_encode([
                'charge'     => '0.50',
                'start_count'=> '1000',
                'status'     => 'Completed',
                'remains'    => '0',
                'currency'   => 'USD',
            ], JSON_PRETTY_PRINT),
        ],
        'multiStatus' => [
            'title' => 'Multiple orders status',
            'parameters' => [
                'key'    => 'Your API key',
                'action' => '"status"',
                'orders' => 'Order IDs (comma separated)',
            ],
            'examples' => json_encode([
                '1' => ['charge' => '0.50', 'start_count' => '1000', 'status' => 'Completed', 'remains' => '0', 'currency' => 'USD'],
                '2' => ['charge' => '1.20', 'start_count' => '500', 'status' => 'In progress', 'remains' => '200', 'currency' => 'USD'],
            ], JSON_PRETTY_PRINT),
        ],
        'services' => [
            'title' => 'Services list',
            'parameters' => [
                'key'    => 'Your API key',
                'action' => '"services"',
            ],
            'examples' => json_encode([
                ['service' => 1, 'name' => 'Instagram Followers - Real & Active', 'type' => 'Default', 'category' => 'Instagram - Followers', 'rate' => '2.50', 'min' => 100, 'max' => 100000, 'refill' => true, 'cancel' => true],
                ['service' => 2, 'name' => 'Telegram Channel Members', 'type' => 'Default', 'category' => 'Telegram - Members', 'rate' => '3.00', 'min' => 100, 'max' => 100000, 'refill' => false, 'cancel' => true],
            ], JSON_PRETTY_PRINT),
        ],
        'balance' => [
            'title' => 'User balance',
            'parameters' => [
                'key'    => 'Your API key',
                'action' => '"balance"',
            ],
            'examples' => json_encode([
                'balance'  => '100.00',
                'currency' => 'USD',
            ], JSON_PRETTY_PRINT),
        ],
        'refill' => [
            'title' => 'Refill order',
            'parameters' => [
                'key'    => 'Your API key',
                'action' => '"refill"',
                'order'  => 'Order ID',
            ],
            'examples' => json_encode([
                'refill' => 1,
            ], JSON_PRETTY_PRINT),
        ],
        'cancel' => [
            'title' => 'Cancel order',
            'parameters' => [
                'key'    => 'Your API key',
                'action' => '"cancel"',
                'order'  => 'Order ID',
            ],
            'examples' => json_encode([
                'cancel' => 1,
            ], JSON_PRETTY_PRINT),
        ],
    ];
}

// ── Render page template ──
// Perfect Panel renders layout.twig as the shell and injects page content
// via {{ content }}. We replicate that here: render the page template first,
// then pass its output as `content` into layout.twig.

if ($slug === 'layout') {
    // Render layout.twig on its own (for inspecting the shell)
    echo $twig->render($templateFile, $context);
} else {
    $pageHtml = $twig->render($templateFile, $context);

    // Check if the template is self-contained (has its own <!DOCTYPE or <html>)
    if (preg_match('/<!DOCTYPE|<html/i', substr($pageHtml, 0, 500))) {
        echo $pageHtml;
    } else {
        // Wrap with layout.twig — inject page HTML as {{ content }}, just like Perfect Panel
        $context['content'] = $pageHtml;
        echo $twig->render('layout.twig', $context);
    }
}
