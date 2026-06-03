# OneSMM Frontend — Desktop Redesign Handoff

## What This Is

This package contains the local preview server and the template/CSS files you need to redesign **5 pages** for desktop. The site is a Perfect Panel theme (Twig templates + single CSS file).

## Pages To Redesign (Desktop Only)

| Page | Template File | Preview URL | Notes |
|------|--------------|-------------|-------|
| Blog listing | `css-files/blog.twig` | http://localhost:8000/blog | Card grid with 6 mock posts |
| Blog post | `css-files/blog-post.twig` | http://localhost:8000/blog/some-slug | Full article with mock content |
| Services | `css-files/services.twig` | http://localhost:8000/services | 10 categories, 30+ services with mock data |
| API docs | `css-files/api.twig` | http://localhost:8000/api | 7 API methods with example responses |
| Signup | `css-files/signup.twig` | http://localhost:8000/signup | Combined sign-in / sign-up auth page |

**Do NOT modify:**
- `singin.twig` — already redesigned, included for reference only
- `layout.twig` — master layout shell, already done. Only touch if your page changes need layout-level CSS

**You WILL modify:**
- `css-files/style.css` — all styles live here. Add your new styles at the end of the relevant section
- The 5 `.twig` template files listed above

## Setup (2 minutes)

### Requirements
- PHP 8.0+ (check: `php -v`)
- Composer (check: `composer -V`)

### Install & Run

```bash
cd handoff-package
composer install          # installs Twig (only dependency)
php -S localhost:8000 preview.php
```

Then open http://localhost:8000/ — you'll see the homepage (singin.twig) as reference for the design direction.

### Preview Any Page

The URL path maps to the template filename:
- `http://localhost:8000/blog` → renders `css-files/blog.twig` (with 6 mock posts)
- `http://localhost:8000/blog/any-slug` → renders `css-files/blog-post.twig` (mock article)
- `http://localhost:8000/services` → renders `css-files/services.twig` (full mock catalog)
- `http://localhost:8000/api` → renders `css-files/api.twig` (all 7 API methods)
- `http://localhost:8000/signup` → renders `css-files/signup.twig` (sign-in/sign-up tabs)

### Simulating Auth State

Add `?auth=1` to any URL to simulate a logged-in user:
- `http://localhost:8000/api?auth=1`

## How Templates Work

### Twig Basics

Templates use [Twig](https://twig.symfony.com/doc/3.x/) syntax:
- `{{ variable }}` — output a value
- `{% if condition %}...{% endif %}` — conditional
- `{% for item in list %}...{% endfor %}` — loop
- `{{ lang('key') }}` — translation function (returns the key itself in preview)
- `{{ page_url('route') }}` — generates URL for a named route

### Available Variables (Mock Data)

The preview server provides mock data. The key variables available in templates:

```
site.name          → "OneSMM"
site.rtl           → false (set ?lang=fa or ?lang=ar for RTL)
site.logo          → logo URL
user.auth          → false (or true with ?auth=1)
page.title         → derived from URL
page.url           → derived from URL
csrftoken          → mock CSRF token
```

**All page-specific variables are now fully mocked.** Every page renders with realistic data:

| Page | Mock data provided |
|------|--------------------|
| Blog | 6 posts with titles, images, excerpts, pagination |
| Blog post | Full article with headings, tables, FAQ boxes, highlight boxes |
| Services | 10 categories (Instagram, Telegram, YouTube, Twitter, TikTok, Facebook), 30+ services with IDs, prices, min/max, average times, descriptions |
| API | 7 methods (add, status, multiStatus, services, balance, refill, cancel) with parameters and example JSON responses |
| Signup | 7 form fields (username, email, password, etc.), terms checkbox, Google sign-in |

### CSS Architecture

Everything is in `css-files/style.css`. It's organized in sections:

1. **CSS custom properties** (`:root` at the top) — design tokens for colors, spacing, etc.
2. **Theme tokens** — `--ink-1` (text), `--bg-1` (background), `--brand` (accent), `--line` (borders)
3. **Landing page components** — prefixed with `lp-` (hero, sections, etc.)
4. **Panel page components** — `.page-header`, `.card`, `.blog-grid`, `.api-*`, etc.
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

Look at the homepage (`http://localhost:8000/`) as your reference. The redesigned hero + sign-in bar establishes the visual direction:

- **Clean, modern, not template-looking** — intentional hierarchy, not uniform
- **Gradient accents** — orange-red gradient `#ff6255 → #ff8d64` for primary CTAs
- **Typography** — Poppins for headings, Inter for body
- **Subtle depth** — light shadows, layered curves, not flat
- **Stats/metrics displayed prominently** — numbers catch the eye
- Both light and dark themes must work (use CSS custom properties)

## What To Deliver Back

1. The modified `.twig` files for the 5 pages
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
    ├── layout.twig        ← master layout shell (reference, avoid editing)
    ├── singin.twig        ← homepage (reference — DO NOT edit)
    ├── signup.twig        ← REDESIGN THIS
    ├── blog.twig          ← REDESIGN THIS
    ├── blog-post.twig     ← REDESIGN THIS
    ├── blogpost.twig      ← legacy redirect, ignore
    ├── services.twig      ← REDESIGN THIS
    └── api.twig           ← REDESIGN THIS
```

## Tips

- Run `php -S localhost:8000 preview.php` and keep it running while you work — changes to `.twig` and `.css` files are picked up on refresh
- The pages currently have basic styling from Bootstrap 3.3.7 (loaded via layout.twig). Build on top of it, don't fight it
- Use the `lp-` prefix for new landing-page components. Use no prefix or `pp-` for panel page components
- Test at 1440px viewport width minimum
- The `?lang=fa` or `?lang=ar` query param flips the page to RTL — but RTL is not required for this round
