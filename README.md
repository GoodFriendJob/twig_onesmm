# OneSMM Frontend — Dashboard Redesign Handoff

## What This Is

This package contains the local preview server and the template/CSS files for redesigning the **dashboard (logged-in) pages**. The site is a Perfect Panel theme (Twig templates + single CSS file).

## Setup (2 minutes)

### Requirements
- PHP 8.0+ (check: `php -v`)
- Composer (check: `composer -V`)

### Install & Run

```bash
cd handoff-package
composer install          # installs Twig (only dependency)
php -S 0.0.0.0:8000 preview.php
```

Then open http://localhost:8000/ — you'll land on the **New Order** page inside the authenticated dashboard shell (sidebar + topbar).

## Dashboard Pages

The server starts in authenticated mode by default. All dashboard pages render with realistic mock data.

| Page | Template File | Preview URL | Description |
|------|--------------|-------------|-------------|
| **New Order** | `neworder.twig` | http://localhost:8000/neworder | Platform chips + category/service selects |
| **Mass Order** | `massorder.twig` | http://localhost:8000/massorder | Bulk order form |
| **Services** | `services.twig` | http://localhost:8000/services | 10 categories, 30+ services |
| **Orders** | `orders.twig` | http://localhost:8000/orders | Order history with status filters |
| **Add Funds** | `addfunds.twig` | http://localhost:8000/addfunds | Payment methods + deposit form |
| **Subscriptions** | `subscriptions.twig` | http://localhost:8000/subscriptions | Auto-order subscription list |
| **Drip Feed** | `drip_feed.twig` | http://localhost:8000/drip_feed | Drip feed order list |
| **Refill** | `refill.twig` | http://localhost:8000/refill | Refill request list |
| **Refunds** | `refunds.twig` | http://localhost:8000/refunds | Refund request list |
| **API Docs** | `api.twig` | http://localhost:8000/api | 7 API methods with examples |
| **Affiliates** | `affiliates.twig` | http://localhost:8000/affiliates | Referral stats + funnel |
| **Child Panel** | `child_panel.twig` | http://localhost:8000/child_panel | Child panel list |
| **Child Panel Order** | `child_panel_order.twig` | http://localhost:8000/child_panel_order | Order a child panel |
| **Support** | `tickets.twig` | http://localhost:8000/tickets | New ticket form + ticket list |
| **View Ticket** | `viewtickets.twig` | http://localhost:8000/viewtickets | Ticket thread view |
| **Account** | `account.twig` | http://localhost:8000/account | Profile, password, 2FA, API key |
| **Updates** | `updates.twig` | http://localhost:8000/updates | Platform updates/changelog |

### Reference Pages (already redesigned — DO NOT edit)

| Page | URL | Notes |
|------|-----|-------|
| Homepage/Sign-in | http://localhost:8000/singin | Design direction reference |
| Layout shell | `layout.twig` | Sidebar, topbar, footer — already done |

## What To Modify

- **`css-files/style.css`** — all styles live here. Add new styles at the end of the relevant section
- **Any `.twig` template file** listed in the dashboard pages table above
- **`layout.twig`** — only if your changes need layout-level adjustments (sidebar, topbar)

## Simulating States

- **Public (logged-out) view**: Add `?auth=0` to any URL — e.g. `http://localhost:8000/services?auth=0`
- **RTL mode**: Add `?lang=fa` or `?lang=ar` to any URL
- The active sidebar item highlights automatically based on the current page

## Mock Data

Every page renders with realistic dummy data:

| Page | Mock data provided |
|------|--------------------|
| New Order | Platform chips (Telegram active, others "coming soon") |
| Orders | 6 orders across Instagram, Telegram, YouTube, Twitter, TikTok with mixed statuses |
| Services | 10 categories, 30+ services with IDs, prices, min/max, descriptions |
| Add Funds | 5 payment methods (CoinPayments, Perfect Money, Payeer, Stripe, PayPal), 3 transaction history entries |
| Account | User profile (demo_user), API key, timezone list, 2FA section |
| Tickets | 3 mock tickets with statuses (answered, closed) |
| View Ticket | Ticket thread with 2 messages (user + support reply) |
| Subscriptions | 2 mock subscriptions (active, paused) |
| Drip Feed | 2 mock drip feed orders (active, completed) |
| Refill | 3 mock refill requests with mixed statuses |
| Refunds | 1 mock partial refund |
| Affiliates | Referral stats, 3 referrals, conversion funnel, 1 payout |
| Child Panel | 1 active panel |
| Child Panel Order | Order form with pricing and nameservers |
| Updates | 3 platform updates (new services, improvements, maintenance) |
| Mass Order | Empty form ready for bulk orders |
| API | 7 API methods with parameters and JSON response examples |

## How Templates Work

### Twig Basics

Templates use [Twig](https://twig.symfony.com/doc/3.x/) syntax:
- `{{ variable }}` — output a value
- `{% if condition %}...{% endif %}` — conditional
- `{% for item in list %}...{% endfor %}` — loop
- `{{ lang('key') }}` — translation function (returns the key itself in preview)
- `{{ page_url('route') }}` — generates URL for a named route

### CSS Architecture

Everything is in `css-files/style.css`. It's organized in sections:

1. **CSS custom properties** (`:root` at the top) — design tokens for colors, spacing, etc.
2. **Theme tokens** — `--ink-1` (text), `--bg-1` (background), `--brand` (accent), `--line` (borders)
3. **Landing page components** — prefixed with `lp-` (hero, sections, etc.)
4. **Panel page components** — `.page-header`, `.card`, data tables, etc.
5. **Responsive breakpoints** — `@media` blocks near the end
6. **Dark theme overrides** — `[data-theme="dark"]` selectors

**Key design tokens:**
```css
--brand: #5b7fff;        /* primary blue */
--ink-1: #1a1a2e;        /* primary text */
--ink-2: #444;           /* secondary text */
--ink-3: #888;           /* muted text */
--bg-1: #fff;            /* page background */
--bg-2: #f5f5f5;         /* card/input background */
--line: #e5e7eb;         /* borders */
```

## Design Direction

Look at the homepage (`http://localhost:8000/singin`) as your reference. The redesigned hero + sign-in bar establishes the visual direction:

- **Clean, modern, not template-looking** — intentional hierarchy, not uniform
- **Gradient accents** — orange-red gradient `#ff6255 → #ff8d64` for primary CTAs
- **Typography** — Poppins for headings, Inter for body
- **Subtle depth** — light shadows, layered curves, not flat
- Both light and dark themes must work (use CSS custom properties)

## What To Deliver Back

1. The modified `.twig` files for the dashboard pages
2. The updated `style.css` with your additions
3. Screenshots of each page at 1440px width (light theme)

## File Structure

```
handoff-package/
├── README.md              ← you're reading this
├── preview.php            ← local preview server (don't modify)
├── composer.json          ← PHP dependency (Twig)
└── css-files/
    ├── style.css          ← ALL styles — edit this
    ├── layout.twig        ← master layout shell (sidebar + topbar)
    ├── singin.twig        ← homepage (reference — DO NOT edit)
    ├── neworder.twig      ← dashboard: new order form
    ├── massorder.twig     ← dashboard: bulk order
    ├── services.twig      ← dashboard: service catalog
    ├── orders.twig        ← dashboard: order history
    ├── addfunds.twig      ← dashboard: deposit/payment
    ├── subscriptions.twig ← dashboard: auto-order subscriptions
    ├── drip_feed.twig     ← dashboard: drip feed orders
    ├── refill.twig        ← dashboard: refill requests
    ├── refunds.twig       ← dashboard: refund requests
    ├── api.twig           ← dashboard: API documentation
    ├── affiliates.twig    ← dashboard: referral program
    ├── child_panel.twig   ← dashboard: child panel list
    ├── child_panel_order.twig ← dashboard: order child panel
    ├── tickets.twig       ← dashboard: support tickets
    ├── viewtickets.twig   ← dashboard: ticket thread
    ├── account.twig       ← dashboard: user account/settings
    └── updates.twig       ← dashboard: platform updates
```

## Tips

- Run `php -S 0.0.0.0:8000 preview.php` and keep it running while you work — changes to `.twig` and `.css` files are picked up on refresh
- The pages use Bootstrap 3.3.7 (loaded via layout.twig). Build on top of it, don't fight it
- Use the `lp-` prefix for landing-page components. Use no prefix or `pp-` for panel components
- Test at 1440px viewport width minimum
- The `?lang=fa` or `?lang=ar` query param flips the page to RTL — but RTL is not required for this round
