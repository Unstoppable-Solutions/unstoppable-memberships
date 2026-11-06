# Unstoppable Memberships — Roadmap

A fast, lightweight WordPress membership plugin by Unstoppable Solutions, shipped
as two products:

- **Unstoppable Memberships** (free) — `unstoppable-memberships`, distributed on WordPress.org.
- **Unstoppable Memberships Pro** (paid add-on) — `unstoppable-memberships-pro`, self-hosted, licensed via Base44.

The free plugin is a complete product on its own (no locked features). Pro is a
separate plugin that requires the free one and adds payments and advanced tools
through hooks. This roadmap is a plan, not a commitment of dates.

---

## Status at a glance

| Area | Free | Pro |
|------|------|-----|
| Membership levels (free) | ✅ Shipped (2.0.0) | — |
| Content restriction (level / login / members) | ✅ Shipped | — |
| Quick Edit access column | ✅ Shipped | — |
| Redirect mode (Elementor-safe) | ✅ Shipped | — |
| Member account & dashboard | ✅ Shipped | — |
| Manual Add Member + user search | ✅ Shipped | — |
| Member reports (counts) | ✅ Shipped | — |
| One-click page creation | ✅ Shipped | — |
| Shortcodes | ✅ Shipped | — |
| Drip content | ⏳ Moving to Pro | 🔜 Planned |
| Paid levels + Stripe checkout | — | 🔜 Built in 1.5.0 code, to port |
| Recurring billing + webhooks | — | 🔜 Port from 1.5.0 |
| License + updater | — | 🔜 Port from 1.5.0 |
| Payments & Subscriptions admin | — | 🔜 Planned |
| Coupons | — | 🔜 Planned |
| Emails + templates | — | 🔜 Planned |
| CSV import/export | — | 🔜 Planned |
| PayPal | — | 🔜 Planned |

---

## FREE PLUGIN (WordPress.org)

### v2.0.0 — Shipped
Free re-architecture: split Pro out, stripped all Pro machinery, made it
WordPress.org-compliant (no license/updater/notices, no inline script/style, no
locked features, no external services). All PHP passes `php -l`.

### v2.1.0 — Submission readiness (next)
The goal is a clean WordPress.org submission.
- Run **Plugin Check** and resolve all confirmed findings.
- Run **PHPCS** (WordPress-Extra) and fix violations; document any justified ignores.
- Live-install test of the actual ZIP: activate, every admin page, settings save,
  restrict a post and a page, add a member, deactivate/reactivate, uninstall.
- Remove drip from the free plugin and expose a `umem_can_access` filter so Pro
  can add the drip gate (free = instant access once eligible).
- Add real screenshots and finalize readme tags (max 5), FAQ, and descriptions.
- Submit to WordPress.org; address reviewer feedback.

### v2.2.0 — Member experience polish
- Front-end profile edit on the account page.
- Login/registration redirect settings.
- Better empty states and inline help across admin.
- Accessibility pass (labels, focus, contrast) on all front-end templates.

### v2.3.0 — Restriction depth (free)
- Restrict by WordPress role, not only membership level.
- Partial-content restriction via `[umem_restrict]` improvements (teaser + more tag).
- Restrict menu items for non-members.
- Bulk Edit access (alongside the existing Quick Edit).

### v2.4.0 — Developer & i18n
- REST read endpoints for levels and a member's own status.
- Expanded hooks/filters reference in the readme and inline docs.
- Complete translations workflow; ship an updated POT and a sample translation.

### Ongoing (free)
- Keep `Tested up to` current with each WordPress release.
- Security maintenance and Plugin Check re-runs on every release.
- Block editor: register the shortcodes as blocks (pricing, login/register,
  restricted content, account) — free-tier blocks only.

---

## PRO ADD-ON (self-hosted)

Pro requires the free plugin and hooks into it. The 1.5.0 codebase (Stripe,
license, updater) is preserved as the starting point.

### v1.0.0 — Foundation + payments
- Scaffold `unstoppable-memberships-pro` as a separate plugin that requires the
  free plugin and registers through its hooks.
- Port the **license** class, **updater**, Check-for-Updates, and license-reminder
  notice (Base44-backed).
- Port **Stripe Checkout**: paid level fields, one-time + recurring, verified
  webhooks, Payments recording.
- **Drip content**: add the metabox drip fields and the `umem_can_access` gate
  (days-after-signup, fixed date).

### v1.1.0 — Subscription management
- Member-facing cancel, and admin cancel/pause/resume.
- Upgrade / downgrade between levels, with proration where the gateway supports it.
- Subscriptions admin screen (filter, search, act).

### v1.2.0 — Coupons & discounts
- Coupon CRUD (percentage / fixed, expiry, usage limits, per-level).
- Applied at checkout; recorded against payments.

### v1.3.0 — Email engine
- Automated emails: signup, renewal, failed payment, cancellation, expiration,
  trial ending.
- Customizable templates with smart tags (member name, level, dates).
- Per-email enable/disable and send log.

### v1.4.0 — Data & reporting
- CSV import (members) and export (members, subscriptions, payments).
- Advanced reports: revenue, MRR, churn, failed-payment recovery, LTV.
- Dashboard widgets.

### v1.5.0 — More gateways
- **PayPal** (Subscriptions + Orders), same gateway-agnostic pattern as Stripe.
- Optional: Square / Authorize.Net.
- "Offline / manual" gateway for bank transfer and comped members.

### Later / exploratory
- Group, family, and corporate memberships (seats).
- Gift memberships.
- Abandoned-checkout and failed-payment recovery sequences.
- Membership health score and churn-risk report.
- Content access simulator (preview what a given member can see).
- Audit log of admin changes.
- Elementor widgets; WooCommerce bridge (sell memberships as Woo products to
  inherit its full gateway ecosystem).
- WP-CLI commands.

---

## Guiding principles

- **Free stays genuinely free** — no locked features; Pro adds, never unlocks.
- **Security first** — nonces, capability checks, prepared SQL, escaped output,
  Plugin Check + PHPCS before every release.
- **Lightweight** — no bloat, assets enqueued, no inline script/style.
- **Elementor-friendly** — restriction that holds on page builders.
- **Semantic versioning** — bump on every change; keep header, constant, readme,
  and changelog in sync.
