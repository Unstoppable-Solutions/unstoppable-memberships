# Changelog

All notable user-facing changes are documented here. For WordPress.org users,
the same changelog lives in `readme.txt` and renders on the plugin's directory
page. This file follows the format described at https://keepachangelog.com.

## [Unreleased]

- Submission readiness: Plugin Check + PHPCS pass, live-install test, real
  screenshots. (See `ROADMAP.md`.)

## [2.0.0]

Initial free WordPress.org release.

### Added
- Free membership levels with optional badges.
- Content restriction for posts, pages, and custom post types by public /
  logged-in / any member / specific levels.
- Access controls in the editor, plus an **Access** column and inline Quick Edit
  on the post and page list screens.
- Redirect mode for restricted content (global and per-item), reliable with
  Elementor and other page builders.
- Member account and dashboard, with shortcodes for register, login, pricing,
  checkout, account, dashboard, restrict, and logout.
- Manual **Add Member** screen with an AJAX user search that scales to large sites.
- Member reports: active members, new signups, cancellations, and churn.
- One-click creation of the member-facing pages.

### Notes
- This free plugin contains no payment processing, no license system, and no
  external services. Paid memberships are provided by the separate
  `unstoppable-memberships-pro` add-on.
