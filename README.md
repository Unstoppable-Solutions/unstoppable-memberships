# Unstoppable Memberships

A fast, lightweight WordPress membership plugin: create free membership levels and
restrict any content by membership, login status, or specific levels — with no
external services and no account required.

Built by [Unstoppable Solutions](https://unstoppablesolutions.app).

> This is the **free** plugin, distributed on WordPress.org. Paid memberships and
> payment processing are provided by a separate premium add-on
> (`unstoppable-memberships-pro`), which requires this plugin.

## Features

- **Free membership levels** — unlimited, with optional badges.
- **Content restriction** — posts, pages, and any custom post type, by public /
  logged-in / any member / specific levels.
- **Set access in the editor** or inline from the post list via Quick Edit and an
  **Access** column.
- **Redirect mode** — send non-members to any page *before* it renders, reliable
  with Elementor and other page builders (global or per item).
- **Member account & dashboard** with membership status.
- **Add members manually** with a fast AJAX user search that scales to large sites.
- **Member reports** — active members, new signups, cancellations, churn.
- **One-click page creation** for pricing, register, login, account, and dashboard.
- **Shortcodes** — `[umem_register]`, `[umem_login]`, `[umem_pricing]`,
  `[umem_checkout]`, `[umem_account]`, `[umem_member_dashboard]`,
  `[umem_restrict]`, `[umem_logout_link]`.

## Built right

- OOP architecture following the WordPress Plugin Handbook.
- Security-first: nonces, sanitization, escaping, prepared SQL, capability checks.
- No inline script or style; all assets enqueued.
- No external services — everything runs on your own site.
- Translation-ready, multisite-aware, WooCommerce HPOS compatible.

## Requirements

- WordPress 5.8+
- PHP 7.4+

## Installation

1. Install from the WordPress.org directory, or upload the ZIP via
   **Plugins → Add New → Upload**.
2. Activate, then go to **Memberships → Dashboard** and follow the setup checklist.
3. Create a membership level, then use **Memberships → Tools** to create the
   member-facing pages in one click.
4. Open any post or page and set **Membership Access** in the editor to restrict it.

## Development

- CI runs the official WordPress **Plugin Check** on every push and PR
  (`.github/workflows/plugin-check.yml`).
- Releases deploy to WordPress.org SVN automatically from a published GitHub
  Release (`.github/workflows/deploy-to-wporg.yml`).
- See [`CLAUDE.md`](CLAUDE.md) for the release workflow and org conventions, and
  [`ROADMAP.md`](ROADMAP.md) for what's planned.

## License

GPL-2.0-or-later. See the main plugin file header.
