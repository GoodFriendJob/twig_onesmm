<?php
/**
 * Local Twig Preview Server for Perfect Panel templates.
 *
 * Usage:
 *   php -S 0.0.0.0:8000 preview.php
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
        'notifications' => '/account#notifications',
        'refunds'       => '/refunds',
        'updates'       => '/updates',
        'child_panel_order' => '/child_panel_order',
    ];
    return $routes[$route] ?? '/' . $route;
}));

// ── Mock `sliceUrl()` function (used in viewtickets.twig for file name display) ──
$twig->addFunction(new \Twig\TwigFunction('sliceUrl', function ($url) {
    $name = basename($url);
    return strlen($name) > 30 ? substr($name, 0, 27) . '...' : $name;
}));

// ── Dashboard pages (auto-authenticate) ──
$dashboardPages = [
    'neworder', 'account', 'addfunds', 'orders', 'tickets', 'viewtickets',
    'services', 'api', 'massorder', 'drip_feed', 'refill', 'refunds',
    'subscriptions', 'affiliates', 'child_panel', 'child_panel_order', 'updates',
];

// ── Determine which template to render ──
$slug = trim($requestUri, '/');
if ($slug === '' || $slug === 'index') {
    $slug = 'neworder'; // Dashboard is the default landing page
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
// Dashboard pages are always authenticated; use ?auth=0 to force public view
$isAuth = in_array($slug, $dashboardPages)
    ? !(isset($_GET['auth']) && $_GET['auth'] === '0')
    : (isset($_GET['auth']) && $_GET['auth'] === '1');

$context = [
    'site' => [
        'name'           => 'OneSMM',
        'iso_lang_code'  => $_GET['lang'] ?? 'en',
        'rtl'            => in_array($_GET['lang'] ?? 'en', ['fa', 'ar']),
        'favicon'        => '/favicon.ico',
        // 'logo'           => 'https://onesmm.com/uploads/logo/onesmm-logo.png',
        'logo'           => 'https://storage.perfectcdn.com/aedutt/bw77pj1h9ga51qfs.svg',
        'seo_key'        => 'smm panel, buy instagram followers, buy telegram members',
        'seo_desc'       => 'OneSMM — #1 SMM Panel with 5,000+ services. Buy Instagram followers, Telegram members, TikTok views & more.',
        'custom_header'  => '',
        'custom_footer'  => '',
        'styles'         => [
            ['href' => '/style.css'],
        ],
        'scripts'        => [],
        // Matches the new sidebar design (text + order). Real menu comes from
        // Perfect Panel admin; the template auto-buckets it into the 3 groups.
        'menu'           => [
            // Quick Links
            ['name' => 'New Order',          'link' => '/neworder',      'active' => ($slug === 'neworder'),     'external' => false],
            ['name' => 'Mass Order',         'link' => '/massorder',     'active' => ($slug === 'massorder'),    'external' => false],
            ['name' => 'Services',           'link' => '/services',      'active' => ($slug === 'services'),     'external' => false],
            ['name' => 'Free Services',      'link' => '/free-services', 'active' => ($slug === 'free-services'),'external' => false],
            ['name' => 'Orders',             'link' => '/orders',        'active' => ($slug === 'orders'),       'external' => false],
            ['name' => 'Subscriptions',      'link' => '/subscriptions', 'active' => ($slug === 'subscriptions'),'external' => false],
            // Payments & Payouts
            ['name' => 'Add Funds',          'link' => '/addfunds',      'active' => ($slug === 'addfunds'),     'external' => false],
            ['name' => 'Refunds',            'link' => '/refunds',       'active' => ($slug === 'refunds'),      'external' => false],
            ['name' => 'Affiliate',          'link' => '/affiliates',    'active' => ($slug === 'affiliates'),   'external' => false],
            ['name' => 'Child Panel',        'link' => '/child_panel',   'active' => ($slug === 'child_panel'),  'external' => false],
            ['name' => 'Levels & Rewards',   'link' => '/levels',        'active' => ($slug === 'levels'),       'external' => false],
            ['name' => 'Giveaway',           'link' => '/giveaway',      'active' => ($slug === 'giveaway'),     'external' => false],
            ['name' => 'API',                'link' => '/api',           'active' => ($slug === 'api'),          'external' => false],
            // Support
            ['name' => 'Support | Contact Us', 'link' => '/tickets',     'active' => ($slug === 'tickets' || $slug === 'viewtickets'), 'external' => false],
            ['name' => 'How to use',         'link' => '/howto',         'active' => ($slug === 'howto'),        'external' => false],
        ],
        'account_menu'   => [
            ['name' => 'Account',  'link' => '/account'],
            ['name' => '$100.00',  'link' => null],
            ['name' => 'Sign Out', 'link' => '/logout'],
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
        'balance_formatted' => '$125.50',
        'username'          => 'Marco Roorkee',
        'avatar'            => 'https://randomuser.me/api/portraits/men/32.jpg',
        'level'             => 2,
        'email'             => 'demo@onesmm.com',
        'lang'              => 'en',
        'timezone'          => 'UTC',
        'is_generated_apikey' => false,
        'apikey'            => 'abcdef1234567890abcdef1234567890',
        'apikey_created'    => '2026-01-15',
        'favorite_services' => false,
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

// ── Mock data: New Order ──
if ($slug === 'neworder') {
    $context['error'] = false;
    $context['errorMessage'] = '';
    $context['success'] = false;
    $context['order'] = ['charge' => '$0.70'];
    // neworder.twig uses JS-driven category/service selects, so no server-side list needed
    // The platform chips + category dropdowns are hardcoded in the template
    $context['serviceCategoryList'] = $context['serviceCategoryList'] ?? [];
    // Preview-only: render demo Link + Quantity fields (Perfect Panel injects these live).
    $context['demo_order_fields'] = true;
}

// ── Mock data: Orders (order history) ──
if ($slug === 'orders') {
    $context['status'] = $_GET['status'] ?? 'all';
    $context['search'] = $_GET['search'] ?? '';
    $context['task'] = true; // show actions column
    $context['orderList'] = [
        ['id' => 10847, 'service_id' => 101, 'service' => 'Instagram Followers - Real & Active', 'link' => 'https://instagram.com/onesmm', 'quantity' => 5000, 'start_count' => 1200, 'remains' => 0, 'charge' => '$12.50', 'original_charge' => '$12.50', 'converted' => false, 'status' => 'Completed', 'date' => '2026-06-01 14:32', 'refill' => true, 'refilling' => false, 'refillAvailableTime' => '', 'cancel' => false, 'hasCancelTask' => false, 'cancelReason' => '', 'orderDetails' => []],
        ['id' => 10843, 'service_id' => 401, 'service' => 'Telegram Channel Members - Real & Active', 'link' => 'https://t.me/onesmm_channel', 'quantity' => 2000, 'start_count' => 500, 'remains' => 350, 'charge' => '$6.00', 'original_charge' => '$6.00', 'converted' => false, 'status' => 'In progress', 'date' => '2026-06-01 10:15', 'refill' => false, 'refilling' => false, 'refillAvailableTime' => '', 'cancel' => true, 'hasCancelTask' => false, 'cancelReason' => '', 'orderDetails' => []],
        ['id' => 10839, 'service_id' => 601, 'service' => 'YouTube Subscribers - Real', 'link' => 'https://youtube.com/@onesmm', 'quantity' => 1000, 'start_count' => 240, 'remains' => 0, 'charge' => '$8.00', 'original_charge' => '$8.00', 'converted' => false, 'status' => 'Completed', 'date' => '2026-05-30 09:00', 'refill' => false, 'refilling' => false, 'refillAvailableTime' => '', 'cancel' => false, 'hasCancelTask' => false, 'cancelReason' => '', 'orderDetails' => []],
        ['id' => 10835, 'service_id' => 802, 'service' => 'Twitter/X Likes', 'link' => 'https://x.com/onesmm/status/123456', 'quantity' => 500, 'start_count' => 12, 'remains' => 0, 'charge' => '$0.50', 'original_charge' => '$0.50', 'converted' => false, 'status' => 'Completed', 'date' => '2026-05-29 16:45', 'refill' => false, 'refilling' => false, 'refillAvailableTime' => '', 'cancel' => false, 'hasCancelTask' => false, 'cancelReason' => '', 'orderDetails' => []],
        ['id' => 10830, 'service_id' => 301, 'service' => 'Instagram Reel Views - Fast', 'link' => 'https://instagram.com/reel/abc123', 'quantity' => 10000, 'start_count' => 200, 'remains' => 2000, 'charge' => '$3.00', 'original_charge' => '$3.00', 'converted' => false, 'status' => 'Partial', 'date' => '2026-05-28 11:20', 'refill' => false, 'refilling' => false, 'refillAvailableTime' => '', 'cancel' => false, 'hasCancelTask' => false, 'cancelReason' => '', 'orderDetails' => []],
        ['id' => 10825, 'service_id' => 902, 'service' => 'TikTok Likes', 'link' => 'https://tiktok.com/@onesmm/video/789', 'quantity' => 3000, 'start_count' => 0, 'remains' => 3000, 'charge' => '$1.50', 'original_charge' => '$1.50', 'converted' => false, 'status' => 'Pending', 'date' => '2026-05-27 08:30', 'refill' => false, 'refilling' => false, 'refillAvailableTime' => '', 'cancel' => true, 'hasCancelTask' => false, 'cancelReason' => '', 'orderDetails' => []],
    ];
    $context['searchList'] = [];
    $context['pagination'] = ['count' => 6, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
}

// ── Mock data: Account ──
if ($slug === 'account') {
    $context['success'] = false;
    $context['successText'] = '';
    $context['error'] = false;
    $context['errorText'] = '';
    $context['successChangeEmail'] = false;
    $context['successChangeEmailMessage'] = '';
    $context['errorChangeEmail'] = false;
    $context['errorChangeEmailMessage'] = '';
    $context['twofactorauth'] = [
        'success' => false,
        'error' => false,
        'activated' => false,
        'active_code' => '',
        'url' => ['generate' => '/account/2fa/generate', 'approve' => '/account/2fa/approve'],
    ];
    $context['user_invoice_module'] = false;
    $context['errorUserInvoice'] = false;
    $context['invoice_details'] = [];
    $context['timezones'] = ['UTC', 'US/Eastern', 'US/Central', 'US/Pacific', 'Europe/London', 'Europe/Berlin', 'Asia/Tokyo', 'Asia/Dubai', 'Asia/Tehran'];
    $context['apiKey'] = ['errorMessage' => ''];
}

// ── Mock data: Add Funds ──
if ($slug === 'addfunds') {
    $context['paymentsList'] = [
        ['id' => 1, 'name' => 'CoinPayments', 'img' => ''],
        ['id' => 2, 'name' => 'Perfect Money', 'img' => ''],
        ['id' => 3, 'name' => 'Payeer', 'img' => ''],
        ['id' => 4, 'name' => 'Stripe', 'img' => ''],
        ['id' => 5, 'name' => 'PayPal', 'img' => ''],
    ];
    $context['currentPayment'] = 1;
    $context['addfunds'] = '';
    $context['paymentList'] = [
        ['id' => 501, 'method' => 'CoinPayments', 'amount' => '$50.00', 'status' => 'Completed', 'date' => '2026-05-28 14:20'],
        ['id' => 500, 'method' => 'Stripe', 'amount' => '$25.00', 'status' => 'Completed', 'date' => '2026-05-20 10:05'],
        ['id' => 498, 'method' => 'Perfect Money', 'amount' => '$100.00', 'status' => 'Completed', 'date' => '2026-05-15 09:30'],
    ];
    $context['pagination'] = ['count' => 3, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
    $context['success'] = false;
    $context['successText'] = '';
    $context['error'] = false;
    $context['errorText'] = '';
    $context['site']['cpf_field'] = false;
}

// ── Mock data: Mass Order ──
if ($slug === 'massorder') {
    $context['error'] = false;
    $context['errorText'] = '';
    $context['success'] = false;
    $context['order'] = [];
}

// ── Mock data: Subscriptions ──
if ($slug === 'subscriptions') {
    $context['status'] = $_GET['status'] ?? 'all';
    $context['search'] = $_GET['search'] ?? '';
    $context['orderList'] = [
        ['id' => 201, 'username' => '@onesmm_channel', 'quantity_min' => 100, 'quantity_max' => 500, 'posts' => 10, 'old_posts' => 0, 'delay' => '30 min', 'service' => 'Instagram Auto Likes - Per Post', 'status' => 'Active', 'date' => '2026-05-20', 'expiry' => '2026-06-20'],
        ['id' => 200, 'username' => '@onesmm', 'quantity_min' => 200, 'quantity_max' => 200, 'posts' => 5, 'old_posts' => 2, 'delay' => '1 hour', 'service' => 'Instagram Auto Comments', 'status' => 'Paused', 'date' => '2026-05-10', 'expiry' => '2026-06-10'],
    ];
    $context['pagination'] = ['count' => 2, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
}

// ── Mock data: Drip Feed ──
if ($slug === 'drip_feed') {
    $context['status'] = $_GET['status'] ?? 'all';
    $context['search'] = $_GET['search'] ?? '';
    $context['dripFeedList'] = [
        ['id' => 301, 'service' => 'Instagram Followers - Drip Feed', 'link' => 'https://instagram.com/onesmm', 'quantity' => 10000, 'runs' => 10, 'interval' => 60, 'status' => 'Active', 'date' => '2026-05-25'],
        ['id' => 300, 'service' => 'Telegram Channel Members', 'link' => 'https://t.me/onesmm_channel', 'quantity' => 5000, 'runs' => 5, 'interval' => 120, 'status' => 'Completed', 'date' => '2026-05-15'],
    ];
    $context['pagination'] = ['count' => 2, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
}

// ── Mock data: Refill ──
if ($slug === 'refill') {
    $context['status'] = $_GET['status'] ?? 'all';
    $context['search'] = $_GET['search'] ?? '';
    $context['refillList'] = [
        ['id' => 401, 'date' => '2026-06-01 09:30', 'order_id' => 10847, 'link' => 'https://instagram.com/onesmm', 'service' => 'Instagram Followers - Real & Active', 'status' => 'Completed'],
        ['id' => 400, 'date' => '2026-05-28 14:00', 'order_id' => 10820, 'link' => 'https://instagram.com/onesmm', 'service' => 'Instagram Followers - Premium', 'status' => 'In progress'],
        ['id' => 399, 'date' => '2026-05-25 11:00', 'order_id' => 10800, 'link' => 'https://t.me/onesmm_channel', 'service' => 'Telegram Channel Subscribers - Premium', 'status' => 'Rejected'],
    ];
    $context['searchList'] = [];
    $context['pagination'] = ['count' => 3, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
}

// ── Mock data: Refunds ──
if ($slug === 'refunds') {
    $context['order_status'] = $_GET['order_status'] ?? 'all';
    $context['search'] = $_GET['search'] ?? '';
    $context['refundList'] = [
        ['id' => 10830, 'service' => 'Instagram Reel Views - Fast', 'link' => 'https://instagram.com/reel/abc123', 'charge' => '$3.00', 'remains' => 2000, 'refund_amount' => '$0.60', 'status' => 'Partial', 'date' => '2026-05-28 11:20'],
    ];
    $context['searchList'] = [];
    $context['pagination'] = ['count' => 1, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
}

// ── Mock data: Tickets ──
if ($slug === 'tickets') {
    $context['additionalFieldsEnabled'] = false;
    $context['additionalFields'] = [];
    $context['ticketList'] = [
        ['id' => 55, 'subject' => 'Order #10830 partial delivery', 'status' => 'answered', 'date' => '2026-06-01 10:00', 'last_reply' => '2026-06-01 10:30'],
        ['id' => 54, 'subject' => 'Payment confirmation delay', 'status' => 'closed', 'date' => '2026-05-25 14:00', 'last_reply' => '2026-05-25 15:00'],
        ['id' => 53, 'subject' => 'API integration help', 'status' => 'closed', 'date' => '2026-05-20 09:00', 'last_reply' => '2026-05-20 11:30'],
    ];
    $context['search'] = $_GET['search'] ?? '';
    $context['ticketsText'] = '';
    $context['pagination'] = ['count' => 3, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
}

// ── Mock data: View Ticket ──
if ($slug === 'viewtickets') {
    $context['ticket'] = [
        'id' => 55,
        'thema' => 'Order #10830 partial delivery',
        'status' => 'answered',
        'date' => '2026-06-01 10:00',
    ];
    $context['messageList'] = [
        ['support' => 0, 'author' => 'demo_user', 'time' => '2026-06-01 10:00', 'message' => '<p>Hi, my order #10830 for Instagram Reel Views only delivered 8,000 out of 10,000. Can I get a refill or partial refund?</p>', 'files' => []],
        ['support' => 1, 'author' => 'Support', 'time' => '2026-06-01 10:30', 'message' => '<p>Hello! We\'ve checked your order and submitted a partial refund for the remaining 2,000 views. The refund of $0.60 has been credited to your balance. Is there anything else we can help with?</p>', 'files' => []],
    ];
    $context['canAddMessage'] = true;
    $context['error'] = false;
    $context['errorMessage'] = '';
}

// ── Mock data: Affiliates ──
if ($slug === 'affiliates') {
    $context['status'] = $_GET['status'] ?? 'all';
    $context['referral_link'] = 'https://onesmm.com/?ref=demo_user';
    $context['commission_rate'] = '5%';
    $context['minimum_payout'] = '$10.00';
    $context['statistics'] = [
        'total_earnings' => '$45.20',
        'unpaid_earnings' => '$12.50',
        'conversion_rate' => '8.3%',
        'total_visits' => 342,
        'total_registrations' => 28,
        'total_deposits' => 14,
        'request_payout' => true,
    ];
    $context['affiliates'] = [
        ['id' => 1, 'username' => 'user_abc', 'date' => '2026-05-15', 'deposits' => '$120.00', 'commission' => '$6.00', 'status' => 'Active'],
        ['id' => 2, 'username' => 'user_def', 'date' => '2026-05-20', 'deposits' => '$80.00', 'commission' => '$4.00', 'status' => 'Active'],
        ['id' => 3, 'username' => 'user_ghi', 'date' => '2026-05-28', 'deposits' => '$0.00', 'commission' => '$0.00', 'status' => 'Registered'],
    ];
    $context['payments'] = [
        ['id' => 1, 'amount' => '$32.70', 'status' => 'Paid', 'date' => '2026-05-01'],
    ];
    $context['pagination'] = ['count' => 3, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
}

// ── Mock data: Child Panel ──
if ($slug === 'child_panel') {
    $context['panelsList'] = [
        ['id' => 1, 'domain' => 'mypanel.example.com', 'status' => 'Active', 'created' => '2026-04-10', 'expires' => '2026-07-10', 'currency' => 'USD'],
    ];
    $context['error'] = false;
    $context['errorMessage'] = '';
    $context['success'] = false;
    $context['successMessage'] = '';
    $context['renew'] = false;
    $context['renewUrl'] = '';
    $context['renewMessage'] = '';
    $context['restore'] = false;
    $context['restoreUrl'] = '';
}

// ── Mock data: Child Panel Order ──
if ($slug === 'child_panel_order') {
    $context['childpanel'] = '<h3>Start Your Own SMM Panel</h3><p>Launch your own branded SMM reseller panel powered by OneSMM. White-label solution with your own domain, logo, and pricing.</p>';
    $context['showForm'] = true;
    $context['errorForm'] = false;
    $context['errorFormMessage'] = '';
    $context['form'] = ['domain' => '', 'currency' => 'USD', 'username' => ''];
    $context['name_servers'] = ['ns1.perfectpanel.com', 'ns2.perfectpanel.com'];
    $context['currenciesList'] = [
        ['code' => 'USD', 'name' => 'US Dollar'],
        ['code' => 'EUR', 'name' => 'Euro'],
        ['code' => 'GBP', 'name' => 'British Pound'],
    ];
    $context['price'] = '$49.99/mo';
}

// ── Mock data: Updates ──
if ($slug === 'updates') {
    $context['types'] = [
        ['key' => 'all', 'label' => 'All Updates'],
        ['key' => 'new_services', 'label' => 'New Services'],
        ['key' => 'improvements', 'label' => 'Improvements'],
        ['key' => 'maintenance', 'label' => 'Maintenance'],
    ];
    $context['type'] = ['key' => 'all', 'label' => 'All Updates'];
    $context['search'] = $_GET['search'] ?? '';
    $context['updatesList'] = [
        ['id' => 10, 'title' => 'New TikTok Services Added', 'type' => 'New Services', 'content' => '<p>We\'ve added 15 new TikTok services including followers, likes, views, and comments. Check them out in the New Order page!</p>', 'date' => '2026-06-01'],
        ['id' => 9, 'title' => 'Payment Gateway Update', 'type' => 'Improvements', 'content' => '<p>CoinPayments now supports 100+ cryptocurrencies. We\'ve also reduced minimum deposit to $1.00.</p>', 'date' => '2026-05-25'],
        ['id' => 8, 'title' => 'Scheduled Maintenance — May 20', 'type' => 'Maintenance', 'content' => '<p>Brief maintenance window 02:00-04:00 UTC on May 20. Order processing may be delayed during this period.</p>', 'date' => '2026-05-18'],
    ];
    $context['pagination'] = ['count' => 3, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
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
