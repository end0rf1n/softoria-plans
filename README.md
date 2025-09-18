Plans - WordPress Plugin (Softoria Test Task)

A lightweight WordPress plugin that adds a **Plan** custom post type and a **[plans]** shortcode to display pricing plans in **Monthly | Annual** tabs. Front-end has **no jQuery dependency**.

---

Download
- **Latest:** [Download plugin ZIP](https://github.com/end0rf1n/softoria-plans/releases/latest/download/plans.zip)
- **v1.0.0:** [Download plugin ZIP](https://github.com/end0rf1n/softoria-plans/releases/download/v1.0.0/plans.zip)

---

Requirements
- WordPress 6.8.2
- PHP 7.4+

---

Features
- CPT: `plan` (no public single or archive pages)
- Meta fields (post_meta):
  - `price` (number)
  - `custom_price_label` (string; replaces price with a label like “Contact Sales”)
  - `is_annual` (bool)
  - `button_text` (string)
  - `button_link` (url)
  - `features` (array of strings)
  - `is_starred` (bool) — shows a “Recommended” badge
  - `is_enabled` (bool) — toggles visibility in shortcode
- Shortcode `[plans]`:
  - Tab switcher **Monthly | Annual**
  - Responsive grid (3 columns on desktop; 4 when there are 4 plans; gracefully down to 1–2 on small screens)
  - Card shows: title, price or custom label, features list, CTA button, and optional “Recommended” badge
- Admin UX:
  - Clean meta box with validation/sanitization
  - Easy features repeater
  - Inline toggles for `is_starred` and `is_enabled` on the list table (AJAX)
  - Validation notice if `button_text` is set but `button_link` is empty/invalid
- Performance:
  - Shortcode HTML cached via transient
  - Cache automatically invalidated on save/delete/untrash/toggles
- Front-end:
  - Vanilla JS tabs (no jQuery)
  - Minimal, accessible styles with subtle hovers

---

Installation
1. Copy the plugin folder to:
   wp-content/plugins/plans
2. Activate Plans in WP Admin → Plugins.
3. Create your Plan entries in WP Admin → Plans.
4. Add the shortcode to any page:
   [plans]

---

Shortcode Options
[plans currency="$" decimals="2" price_suffix_month="/mo" price_suffix_year="/yr" starred_badge="Recommended" show_switch="1" columns=""]

Attributes
- currency — currency symbol (default: $)
- decimals — price decimals (default: 2)
- price_suffix_month — suffix for monthly price (default: /mo)
- price_suffix_year — suffix for annual price (default: /yr)
- starred_badge — text for the “starred” badge (default: Recommended)
- show_switch — show tabs switcher (1 or 0, default: 1)
- columns — integer to force a fixed number of columns (optional)

Examples
[plans]
[plans currency="€" decimals="0" starred_badge="Top pick"]
[plans columns="4"]

---

Admin
- Meta box with sanitized inputs:
  - price (number), custom_price_label, is_annual, button_text, button_link (url), features (repeatable), is_starred, is_enabled
- Validation: when button_text is provided but button_link is missing/invalid, an admin notice is displayed on save
- List table: quick AJAX toggles for is_starred and is_enabled

---

Caching
- Shortcode output is cached in a transient (plans_shortcode_v*) for 12 hours.
- Cache is cleared when:
  - a Plan is saved/updated
  - a Plan is deleted/trashed/untrashed
  - is_starred or is_enabled is toggled in the list table

---

Compatibility
- PHP 7.4+
- WordPress 6.8.2
- No jQuery on the front-end (tiny jQuery snippet is used only in the admin list screen)

---

Architecture Notes
- Plans_CPT — registers the custom post type (public off; no single/archive)
- Plans_Meta — meta registration, meta box UI, sanitization/validation, cache bust on save
- Plans_Admin — list table columns, AJAX toggles, admin assets
- Plans_Shortcode — frontend render, grouping by monthly/annual, caching
- Plans_Cache — centralized cache invalidation hooks

---

Security
- Nonces on meta save and AJAX actions
- Strict capability checks (edit_posts)
- Escaping: esc_html, esc_url, number_format_i18n, wp_kses_post
- URL sanitization via esc_url_raw

---

Development
- Front-end: vanilla JS, CSS variables; accessible styles and keyboard focus
- Code comments and UI strings in English
- Easy to extend (e.g., REST, sorting, additional shortcode options)

---

License
GPL-2.0-or-later

---
