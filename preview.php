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
// Perfect Panel returns the translated string; in preview we map the keys we use
// to human labels (so pages match the design) and humanize anything unmapped.
$LANG = [
    'general.search.placeholder'      => 'Search',
    // Affiliates
    'affiliates.total_earnings'       => 'Total Earnings',
    'affiliates.available_earnings'   => 'Available Earnings',
    'affiliates.visits'               => 'Visits',
    'affiliates.registrations'        => 'Registrations',
    'affiliates.referrals'            => 'Referrals',
    'affiliates.conversion_rate'      => 'Conversion rate',
    'affiliates.referral_link'        => 'Referral Link',
    'affiliates.commission_rate'      => 'Commission rate',
    'affiliates.minimum_payout'       => 'Minimum Payout',
    'affiliates.user'                 => 'User',
    'affiliates.join_date'            => 'Join Date',
    'affiliates.spent'                => 'Spent',
    'affiliates.your_commission'      => 'Your Commission',
    'affiliates.payout_date'          => 'Date',
    'affiliates.payout_amount'        => 'Amount',
    'affiliates.remained'             => 'Remained',
    // Add funds
    'addfunds.amount'                 => 'Amount',
    'addfunds.date'                   => 'Date',
    'addfunds.method'                 => 'Method',
    'addfunds.button.pay'             => 'Submit',
    'addfunds.cpf'                    => 'CPF',
    // Child panel
    'child_panel.form.domain'         => 'Domain',
    'child_panel.form.ns.info'        => 'Please Change name servers to the following:',
    'child_panel.form.username'       => 'Admin Username',
    'child_panel.form.password'       => 'Admin Password',
    'child_panel.form.submit'         => 'Submit Order',
    // Orders
    'orders.id'                       => 'ID',
    'orders.date'                     => 'Date',
    'orders.link'                     => 'Link',
    'orders.charge'                   => 'Charge',
    'orders.startcount'               => 'Start Count',
    'orders.quantity'                 => 'Quantity',
    'orders.service'                  => 'Service',
    'orders.status'                   => 'Status',
    'orders.remains'                  => 'Remains',
    'orders.all'                      => 'All',
    'orders.status.pending'           => 'Pending',
    'orders.status.inprogress'        => 'In progress',
    'orders.status.completed'         => 'Completed',
    'orders.status.partial'           => 'Partial',
    'orders.status.processing'        => 'Processing',
    'orders.status.canceled'          => 'Canceled',
    'orders.button.reorder'           => 'Reorder',
    'orders.button.resume'            => 'Resume',
    'orders.button.action'            => 'Action',
    'orders.button.cancel'            => 'Cancel',
    'orders.refilling'                => 'Refilling',
    // Services
    'services.id'                     => 'ID',
    'services.name'                   => 'Service',
    'services.description'            => 'Description',
    'services.all'                    => 'All',
    'services.favorite'               => 'Favorite',
    'services.button.order'           => 'Order',
    'services.min'                    => 'Min',
    'services.max'                    => 'Max',
    // Tickets
    'tickets.id'                      => 'Ticket ID',
    'tickets.subject'                 => 'Subject',
    'tickets.category'                => 'Category',
    'tickets.status'                  => 'Status',
    'tickets.updated'                 => 'Last Update',
    'tickets.message'                 => 'Message',
    'tickets.button'                  => 'Submit Ticket',
    // Giveaway
    'giveaway.learn_more'             => 'Learn More',
    // Subscriptions
    'subscriptions.id'                => 'ID',
    'subscriptions.username'          => 'Username',
    'subscriptions.quantity'          => 'Quantity',
    'subscriptions.new_posts'         => 'Posts',
    'subscriptions.old_posts'         => 'Old Posts',
    'subscriptions.delay'             => 'Delay',
    'subscriptions.service'           => 'Service',
    'subscriptions.status'            => 'Status',
    'subscriptions.created'           => 'Created',
    'subscriptions.expiry'            => 'Expiry',
    'subscriptions.status.all'        => 'All',
    'subscriptions.button.unpause'    => 'Resume',
    'subscriptions.button.reorder'    => 'Reorder',
    'subscriptions.button.cancel'     => 'Cancel',
    // Refunds
    'orders.refunds.id'               => 'Order ID',
    'orders.refunds.amount'           => 'Refund',
    'orders.refunds.status'           => 'Status',
    'orders.refunds.date'             => 'Date',
    'orders.refunds.all'              => 'All',
    'orders.refunds.partial'          => 'Partial',
    'orders.refunds.canceled'         => 'Canceled',
];
$twig->addFunction(new \Twig\TwigFunction('lang', function ($key) use ($LANG) {
    if (isset($LANG[$key])) return $LANG[$key];
    // Humanize fallback: last dotted segment, words capitalized
    $last = strrchr($key, '.');
    $last = $last === false ? $key : substr($last, 1);
    return ucwords(str_replace(['_', '-'], ' ', $last));
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
        'viewticket'    => '/viewtickets',
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
    'giveaway', 'levels',
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

// Handle ticket detail sub-URLs: /viewticket(s)/{id} → viewtickets.twig
if (preg_match('#^viewtickets?(?:/.*)?$#', $slug)) {
    $slug = 'viewtickets';
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
            // ['name' => 'Free Services',      'link' => '/free-services', 'active' => ($slug === 'free-services'),'external' => false],
            ['name' => 'Free Services',      'link' => '/services', 'active' => ($slug === 'free-services'),'external' => false],
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
            // ['name' => 'How to use',         'link' => '/howto',         'active' => ($slug === 'howto'),        'external' => false],
            ['name' => 'How to use',         'link' => '/tickets',         'active' => ($slug === 'howto'),        'external' => false],
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

// ── Notifications (top-bar bell slide-in panel) — shown on every page ──
$context['notifications'] = [
    ['title' => 'Service Speed Update', 'new' => true, 'date' => '2026-05-27', 'time' => '05:12:01',
     'text' => "We've improved processing speed across several Instagram, TikTok, and Telegram services. Most new orders now start faster than before, with better overall stability during peak hours. We're also continuing to optimize delivery times to provide a smoother experience for all users. Thank you for your patience and continued support."],
    ['title' => 'New Services Added', 'new' => false, 'date' => '2026-05-27', 'time' => '05:12:01',
     'text' => 'New services have been added to the panel, including updated options for Instagram followers, TikTok views, YouTube likes, and Telegram members. These services were selected based on user demand and current market performance. Feel free to test them and share your feedback so we can continue improving the panel.', 'read_more' => true],
    ['title' => 'System Maintenance Notice', 'new' => false, 'date_label' => 'Sunday, May 6th',
     'text' => 'Scheduled maintenance will take place to improve platform stability. Order processing may be briefly delayed during this window.', 'read_more' => true],
];
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
                'default'                 => 'Default',
                'package'                 => 'Package',
                'custom_comments'         => 'Custom Comments',
                'mentions_hashtag'        => 'Mentions Hashtag',
                'custom_comments_package' => 'Custom Comments Package',
                'poll'                    => 'Poll',
                'subscription'            => 'Subscriptions',
                'web_traffic'             => 'Web Traffic',
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
                'mentions_hashtag' => [
                    'key'      => 'Your API key',
                    'action'   => '"add"',
                    'service'  => 'Service ID',
                    'link'     => 'Link to page',
                    'quantity' => 'Needed quantity',
                    'hashtag'  => 'Hashtag to scrape users from',
                ],
                'custom_comments_package' => [
                    'key'      => 'Your API key',
                    'action'   => '"add"',
                    'service'  => 'Service ID',
                    'link'     => 'Link to page',
                    'comments' => 'Comments list separated by \\n',
                ],
                'poll' => [
                    'key'           => 'Your API key',
                    'action'        => '"add"',
                    'service'       => 'Service ID',
                    'link'          => 'Link to page',
                    'quantity'      => 'Needed quantity',
                    'answer_number' => 'Poll answer number',
                ],
                'subscription' => [
                    'key'      => 'Your API key',
                    'action'   => '"add"',
                    'service'  => 'Service ID',
                    'username' => 'Username',
                    'min'      => 'Quantity min',
                    'max'      => 'Quantity max',
                    'posts'    => 'Future posts',
                    'delay'    => 'Delay in minutes',
                ],
                'web_traffic' => [
                    'key'      => 'Your API key',
                    'action'   => '"add"',
                    'service'  => 'Service ID',
                    'link'     => 'Link to page',
                    'quantity' => 'Needed quantity',
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
    $svc = '🚀🌐 Telegram Members – Normal Drop (30Days Refill)';
    $context['orderList'] = [
        ['id' => 2745, 'service_id' => 18, 'service' => $svc, 'link' => 'https://t.me/profile', 'quantity' => 100,  'start_count' => 84, 'remains' => 42,    'charge' => '$0.041', 'status' => 'In progress', 'date' => '2026-05-28', 'time' => '01:32:47', 'refilling' => false, 'orderDetails' => []],
        ['id' => 2745, 'service_id' => 18, 'service' => $svc, 'link' => 'https://t.me/profile', 'quantity' => 100,  'start_count' => 84, 'remains' => 42,    'charge' => '$0.041', 'status' => 'Partial',     'date' => '2026-05-28', 'time' => '01:32:47', 'refilling' => false, 'orderDetails' => []],
        ['id' => 2745, 'service_id' => 18, 'service' => $svc, 'link' => 'https://t.me/profile', 'quantity' => 100,  'start_count' => 84, 'remains' => 42,    'charge' => '$0.041', 'status' => 'Completed',   'date' => '2026-05-28', 'time' => '01:32:47', 'refilling' => false, 'orderDetails' => []],
        ['id' => 2745, 'service_id' => 18, 'service' => $svc, 'link' => 'https://t.me/profile', 'quantity' => 2000, 'start_count' => 84, 'remains' => 1300,  'charge' => '$0.82',  'status' => 'In progress', 'date' => '2026-05-28', 'time' => '01:32:47', 'refilling' => false, 'orderDetails' => []],
        ['id' => 2745, 'service_id' => 18, 'service' => $svc, 'link' => 'https://t.me/profile', 'quantity' => 100,  'start_count' => 84, 'remains' => 42,    'charge' => '$0.041', 'status' => 'Paused',      'date' => '2026-05-28', 'time' => '01:32:47', 'refilling' => false, 'orderDetails' => []],
        ['id' => 2745, 'service_id' => 18, 'service' => $svc, 'link' => 'https://t.me/profile', 'quantity' => 100,  'start_count' => 84, 'remains' => '-',   'charge' => '$0.041', 'status' => 'Canceled',    'date' => '2026-05-28', 'time' => '01:32:47', 'refilling' => false, 'orderDetails' => []],
        ['id' => 2745, 'service_id' => 18, 'service' => $svc, 'link' => 'https://t.me/profile', 'quantity' => 100,  'start_count' => 84, 'remains' => '-',   'charge' => '$0.041', 'status' => 'Pending',     'date' => '2026-05-28', 'time' => '01:32:47', 'refilling' => false, 'orderDetails' => []],
    ];
    $context['searchList'] = [];
    $context['pagination'] = ['count' => 7, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
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
    $cryptomusInstr = 'Extra descriptions can be displayed here for the users to read. It can be as long as you desire, but better to keep it short and appealing to the users. You DON\'T need a <strong>Cryptomus</strong> account for depositing your funds. You can deposit easily by sending the address generated by the gateway.';
    $context['paymentsList'] = [
        ['id' => 1, 'name' => 'Cryptomus',    'category' => 'crypto', 'icon_key' => 'cryptomus',     'instruction' => $cryptomusInstr],
        ['id' => 2, 'name' => 'Heleket',      'category' => 'crypto', 'icon_key' => 'heleket',       'instruction' => 'Send the exact amount to the generated Heleket address. Funds are credited after network confirmation.'],
        ['id' => 3, 'name' => 'Cryptogate',   'category' => 'crypto', 'icon_key' => 'cryptogate',    'instruction' => 'Cryptogate supports 100+ coins. Choose your coin on the next screen.'],
        ['id' => 4, 'name' => '1xgate',       'category' => 'other',  'icon_key' => '1xgate',        'instruction' => 'Pay through the 1xgate checkout. You will be redirected to complete the payment.'],
        ['id' => 5, 'name' => 'Paypal',       'category' => 'card',   'icon_key' => 'paypal',        'instruction' => 'Pay securely with your PayPal balance or linked card.'],
        ['id' => 6, 'name' => 'Wise',         'category' => 'bank',   'icon_key' => 'wise',          'instruction' => 'Bank transfer via Wise. Use the reference shown after submitting.'],
        ['id' => 7, 'name' => 'USDT Direct',  'category' => 'crypto', 'icon_key' => 'usdt',          'instruction' => 'Send USDT (TRC20/ERC20) directly to the generated wallet address.'],
        ['id' => 8, 'name' => 'Crypto Direct','category' => 'crypto', 'icon_key' => 'crypto_direct', 'instruction' => 'Send your crypto directly to the address generated by the gateway.'],
    ];
    $context['currentPayment'] = 1;
    $context['addfunds'] = $cryptomusInstr;
    $context['paymentList'] = [
        ['id' => 501, 'method' => 'Cryptomus',    'amount' => '$7.00',   'date' => '2026-05-28', 'time' => '10:17:53', 'affiliation' => false],
        ['id' => 500, 'method' => 'Crypto Direct','amount' => '$5.50',   'date' => '2026-05-25', 'time' => '18:24:23', 'affiliation' => false],
        ['id' => 499, 'method' => 'Affiliation',  'amount' => '$1.00',   'date' => '2026-05-25', 'time' => '18:24:23', 'affiliation' => true],
        ['id' => 498, 'method' => 'Paypal',       'amount' => '$138.00', 'date' => '2026-05-19', 'time' => '20:01:24', 'affiliation' => false],
    ];
    $context['pagination'] = ['count' => 4, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
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
        ['id' => 201, 'service_id' => 203, 'username' => '@onesmm_channel', 'quantity_min' => 100, 'quantity_max' => 500, 'posts' => 10, 'old_posts' => 0, 'delay' => '30 min', 'service' => 'Instagram Auto Likes - Per Post', 'status' => 'Active',    'date' => '2026-05-20', 'expiry' => '2026-06-20'],
        ['id' => 200, 'service_id' => 210, 'username' => '@onesmm',         'quantity_min' => 200, 'quantity_max' => 200, 'posts' => 5,  'old_posts' => 2, 'delay' => '1 hour', 'service' => 'Instagram Auto Comments',     'status' => 'Paused',    'date' => '2026-05-10', 'expiry' => '2026-06-10'],
        ['id' => 199, 'service_id' => 203, 'username' => '@brandhub',       'quantity_min' => 150, 'quantity_max' => 150, 'posts' => 8,  'old_posts' => 8, 'delay' => '45 min', 'service' => 'Instagram Auto Likes - Per Post', 'status' => 'Completed', 'date' => '2026-04-28', 'expiry' => '2026-05-28'],
        ['id' => 198, 'service_id' => 401, 'username' => '@tg_growth',      'quantity_min' => 300, 'quantity_max' => 800, 'posts' => 12, 'old_posts' => 4, 'delay' => '2 hours','service' => 'Telegram Auto Post Views',      'status' => 'Expired',   'date' => '2026-04-10', 'expiry' => '2026-05-10'],
        ['id' => 197, 'service_id' => 210, 'username' => '@old_acct',       'quantity_min' => 100, 'quantity_max' => 100, 'posts' => 3,  'old_posts' => 0, 'delay' => '1 hour', 'service' => 'Instagram Auto Comments',     'status' => 'Canceled',  'date' => '2026-03-30', 'expiry' => '2026-04-30'],
    ];
    $context['pagination'] = ['count' => 5, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
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
        ['id' => 10830, 'service' => 'Instagram Reel Views - Fast',          'link' => 'https://instagram.com/reel/abc123', 'charge' => '$3.00', 'remains' => 2000, 'refund_amount' => '$0.60', 'status' => 'Partial',   'date' => '2026-05-28 11:20'],
        ['id' => 10791, 'service' => 'TikTok Likes',                          'link' => 'https://tiktok.com/@onesmm/video/789','charge' => '$1.50', 'remains' => 3000, 'refund_amount' => '$1.50', 'status' => 'Canceled',  'date' => '2026-05-22 08:30'],
        ['id' => 10744, 'service' => 'Telegram Channel Members - Real',       'link' => 'https://t.me/onesmm_channel',         'charge' => '$6.00', 'remains' => 0,    'refund_amount' => '$2.10', 'status' => 'Completed', 'date' => '2026-05-15 16:05'],
    ];
    $context['searchList'] = [];
    $context['pagination'] = ['count' => 3, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
}

// ── Mock data: Tickets ──
if ($slug === 'tickets') {
    $context['additionalFieldsEnabled'] = false;
    $context['additionalFields'] = [];
    $context['ticketCategories'] = ['General', 'Orders', 'Payment', 'API', 'Child Panel'];
    $context['ticketList'] = [
        ['id' => 248985, 'subject' => 'A Sample Subject here that can be as long as the width of this...', 'category' => 'General', 'status' => 'New',      'date' => '2026-05-27', 'time' => '05:12:01'],
        ['id' => 248877, 'subject' => 'A Sample Subject here',           'category' => 'Orders',  'status' => 'Open',     'date' => '2026-05-23', 'time' => '03:04:15'],
        ['id' => 248609, 'subject' => 'Another Sample for the title',    'category' => 'Payment', 'status' => 'Answered', 'date' => '2026-05-11', 'time' => '21:44:18'],
        ['id' => 248475, 'subject' => 'Thank you for the fast delivery', 'category' => 'General', 'status' => 'Closed',   'date' => '2026-05-02', 'time' => '15:20:10'],
    ];
    $context['support'] = [
        'email'        => 'hello@onesmm.com',
        'whatsapp'     => '+61 234 5678 90',
        'telegram'     => '@channel1 username',
        'telegram2'    => '@channel1 username',
        'telegram_url' => '#',
        'whatsapp_url' => '#',
    ];
    $context['search'] = $_GET['search'] ?? '';
    $context['ticketsText'] = '';
    $context['pagination'] = ['count' => 4, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
}

// ── Mock data: View Ticket ──
if ($slug === 'viewtickets') {
    $context['ticket'] = [
        'id' => 55,
        'thema' => 'A Sample Subject Here',
        'status' => 'Open',
        'date' => '2026-05-27',
    ];
    $context['messageList'] = [
        ['support' => 0, 'author' => 'Marco Roorkee', 'avatar' => $context['user']['avatar'], 'date' => '2026-05-27', 'time' => '05:12:01',
         'message' => '<p>Hi, I placed an order for a promotional package yesterday, but I still haven\'t received anything on my account. It\'s been over 24 hours, and I was expecting it to be delivered much sooner based on the timeframe mentioned. Could you please check what\'s going on with my order?</p>',
         'files' => [
            ['name' => 'Filename.PNG',   'url' => '#', 'size' => '134 Kb'],
            ['name' => 'Screenshot.JPG', 'url' => '#', 'size' => '88 Kb'],
            ['name' => 'Readme.PDF',     'url' => '#', 'size' => '371 Kb'],
         ]],
        ['support' => 1, 'author' => 'One Support', 'avatar' => '', 'date' => '2026-05-27', 'time' => '05:12:01',
         'message' => '<p>Thanks for reaching out, and I\'m really sorry for the delay. I\'ve checked your order, and it seems it got stuck in the processing queue due to a temporary system slowdown. The good news is that I\'ve manually pushed it through, and it should start delivering within the next 30–60 minutes. If you don\'t see any progress after that, just let me know and I\'ll escalate it immediately.</p>', 'files' => []],
        ['support' => 0, 'author' => 'Marco Roorkee', 'avatar' => $context['user']['avatar'], 'date' => '2026-05-27', 'time' => '05:12:01',
         'message' => '<p>Great, thanks for the quick update. I\'ll keep an eye on it over the next hour. If it still doesn\'t show any movement, I\'ll follow up, but hopefully the manual push solves it. Appreciate your help!</p>', 'files' => []],
    ];
    $context['canAddMessage'] = true;
    $context['error'] = false;
    $context['errorMessage'] = '';
}

// ── Mock data: Affiliates ──
if ($slug === 'affiliates') {
    $context['status'] = $_GET['status'] ?? 'all';
    $context['referral_link'] = 'https://onesmm.com/ref/zhd29';
    $context['commission_rate'] = '3%';
    $context['minimum_payout'] = '$1.00';
    $context['statistics'] = [
        'total_earnings'     => '$3.01',
        'available_earnings' => '$0.15',
        'visits'             => 117,
        'registrations'      => 3,
        'referrals'          => 3,
        'conversion_rate'    => '2.88%',
        'request_payout'     => true,
    ];
    $context['affiliates'] = [
        ['id' => 1, 'avatar' => 'https://randomuser.me/api/portraits/men/32.jpg',   'username' => 'Marco.R',  'date' => '2026-05-28', 'time' => '01:32:47', 'spent' => '$14.70', 'commission' => '$2.9'],
        ['id' => 2, 'avatar' => 'https://randomuser.me/api/portraits/women/44.jpg', 'username' => 'Jullia.M', 'date' => '2026-05-28', 'time' => '01:32:47', 'spent' => '$3.00',  'commission' => '$0.1'],
        ['id' => 3, 'avatar' => '',                                                  'username' => 'Ravi.P',   'date' => '2026-05-28', 'time' => '01:32:47', 'spent' => '$0.30',  'commission' => '$0.01'],
    ];
    $context['payments'] = [
        ['id' => 1, 'amount' => '$1.00', 'remained' => '$0.15', 'date' => '2026-05-28', 'time' => '01:32:47'],
        ['id' => 2, 'amount' => '$1.00', 'remained' => '$0.00', 'date' => '2026-05-26', 'time' => '05:24:05'],
        ['id' => 3, 'amount' => '$1.00', 'remained' => '$0.04', 'date' => '2026-05-23', 'time' => '10:17:01'],
    ];
    $context['pagination'] = ['count' => 3, 'current' => 1, 'pages' => 1, 'next' => null, 'last' => null];
}

// ── Mock data: Child Panel (reseller order page) ──
if ($slug === 'child_panel') {
    $context['form'] = ['domain' => '', 'currency' => 'USD', 'username' => ''];
    $context['name_servers'] = ['dns1.cloudns.net', 'dns2.cloudns.net'];
    $context['currenciesList'] = [
        ['code' => 'USD', 'name' => 'United States Dollar'],
        ['code' => 'EUR', 'name' => 'Euro'],
        ['code' => 'GBP', 'name' => 'British Pound'],
    ];
    $context['price'] = '$24.90';
    $context['features'] = [
        'Custom branded domain',
        'Setup takes a few minutes after DNS propagates.',
        'Custom branded domain',
        'Set your own service prices',
        'Free SSL certificate',
        'Auto order forwarding to Pulse',
        'Payment gateway integration',
        'Tickets, blog, FAQ included',
    ];
    $context['setup_note'] = 'Setup takes a few minutes after DNS propagates.';
    $context['errorForm'] = false;
    $context['errorFormMessage'] = '';
}

// ── Mock data: Giveaway & Rewards ──
if ($slug === 'giveaway') {
    $context['giveaways'] = [
        ['icon' => '👽', 'title' => 'Make a post on Reddit',     'reward' => '$1 Balance',      'url' => '#'],
        ['icon' => '🎬', 'title' => 'Earn by Sharing Our Video', 'reward' => 'up to $5 Balance', 'earn_label' => 'And for every 1,000 views earn', 'url' => '#'],
        ['icon' => '🔵', 'title' => 'Write a Google review',      'reward' => '$0.5 Balance',    'url' => '#'],
        ['icon' => '🟥', 'title' => 'Make a post on Quora',       'reward' => '$0.5 Balance',    'url' => '#'],
        ['icon' => '🌐', 'title' => 'Post on BlackHatWorld',      'reward' => '$1 Balance',      'url' => '#'],
        ['icon' => '💼', 'title' => 'Make a post on Linkedin',    'reward' => '$1 Balance',      'url' => '#'],
        ['icon' => '▶️', 'title' => 'Create a video on Youtube',  'reward' => 'up to $5 Balance', 'url' => '#'],
        ['icon' => '⭐', 'title' => 'Write a Trustpilot review',  'reward' => '$1 Balance',      'url' => '#'],
    ];
    $context['giveaway_steps'] = [
        ['num' => '01', 'icon' => '✓', 'title' => 'Complete the Task',  'desc' => 'Read the description of each task'],
        ['num' => '02', 'icon' => '⬆', 'title' => 'Submit Your Proof',  'desc' => 'Via Learn More button'],
        ['num' => '03', 'icon' => '🎁', 'title' => 'Receive Your Rewards', 'desc' => 'After 5 to 72 hours'],
    ];
    $context['giveaway_rules'] = [
        'You must be a registered user of ' . $context['site']['name'] . '.',
        'Minimum Activity Requirement: To participate in any giveaway, you must have completed at least one paid transaction on onesmm.com.',
        'Each reward can be claimed once per user unless explicitly stated otherwise.',
        'One Account Only: Creating multiple accounts to claim giveaways is strictly prohibited. Attempts to deceive the system are automatically detected through IP/device fingerprinting, order and payment history, cookie/cache correlation, and other fraud signals. If multiple accounts are linked to the same user, all related accounts will be disqualified, banned, and any pending or approved rewards will be forfeited.',
        'Submitted content must be original, public, and remain online for at least 1 year.',
        'Verification may take 6–72 hours. Manual review can take longer — we\'ll notify you if additional checks are needed.',
        'Rewards are credited only after manual verification by our moderation team.',
        'Any fake, duplicated, deleted, or private post will result in disqualification and reward rejection.',
        'We\'re botters too — and yes, we also hate fake engagement. If we detect botting, view farming, or manipulative tactics (like fake accounts or automated comments), we\'ll blacklist the submission, suspend the user, and permanently exclude them from future campaigns. Don\'t waste your time — fake engagement helps no one and can even harm your own account\'s reputation.',
    ];
}

// ── Mock data: Levels & Rewards ──
if ($slug === 'levels') {
    $context['level'] = [
        'name'        => $context['user']['username'],
        'avatar'      => $context['user']['avatar'],
        'current'     => 2,
        'stars'       => 2,
        'total_spent' => '$138',
        'next_level'  => 3,
        'next_reach'  => '$1,000',
        'remaining'   => '$862 Remaining',
        'percent'     => 13,
    ];
    $context['level_steps'] = [
        ['num' => '01', 'icon_key' => 'cart',   'title' => 'Place Orders',               'desc' => 'And grow your social media'],
        ['num' => '02', 'icon_key' => 'chart',  'title' => 'Reach the minimum threshold', 'desc' => 'After you\'ve spent the minimum amount'],
        ['num' => '03', 'icon_key' => 'smiley', 'title' => 'Enjoy the Discount',          'desc' => 'Each level gives you additional discount'],
    ];
    $context['levels_list'] = [
        ['level' => 1, 'req' => 'Entry level',                       'discount' => null,  'benefit' => 'No additional benefit'],
        ['level' => 2, 'req' => 'Users who have spent $100 or more',   'discount' => '1%', 'benefit' => '1% Discount'],
        ['level' => 3, 'req' => 'Users who have spent $1,000 or more', 'discount' => '2%', 'benefit' => '2% Discount'],
        ['level' => 4, 'req' => 'Users who have spent $10,000 or more','discount' => '3%', 'benefit' => '3% Discount'],
        ['level' => 5, 'req' => 'Users who have spent $50,000 or more','discount' => '5%', 'benefit' => '5% Discount'],
    ];
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
