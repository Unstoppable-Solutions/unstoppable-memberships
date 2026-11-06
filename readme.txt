=== Unstoppable Memberships ===
Contributors: paramedic192
Donate link: https://unstoppablesolutions.app/donate
Tags: membership, restrict content, members only, drip content, content protection
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create free membership levels and restrict any content by membership, login status, or specific levels — lightweight, with no account required.

== Description ==

Unstoppable Memberships is a fast, lightweight way to run free memberships and protect content on WordPress. Create membership levels, restrict any post, page, or custom post type, drip content out over time, and manage your members — all from a clean admin, with security built in from the ground up.

It is built with an object-oriented architecture that follows the WordPress Plugin Handbook, with prepared SQL, nonces, capability checks, and full output escaping throughout. It runs entirely on your own site and connects to no external services.

**Content restriction**

* Restrict posts, pages, and any custom post type
* Access modes: public, logged-in users, any active member, or specific levels
* Set access right in the editor, or inline from the Posts/Pages list via Quick Edit and an Access column
* Drip content by days-after-signup or a fixed date
* Custom restricted message per item or site-wide
* Redirect non-members to any page before the page renders — reliable with Elementor and other page builders, set globally or per item

**Memberships & members**

* Unlimited free membership levels with optional badges
* Member account and dashboard with membership status
* Add members manually, with a fast user search that scales to large sites
* Member reports: active members, new signups, cancellations, and churn
* One-click creation of your pricing, register, login, account, and dashboard pages

**Shortcodes**

* `[umem_register]`, `[umem_login]`, `[umem_pricing]`, `[umem_checkout]`
* `[umem_account]`, `[umem_member_dashboard]`, `[umem_logout_link]`
* `[umem_restrict levels=""]…[/umem_restrict]`

**Built right**

* Clean OOP architecture following the WordPress Plugin Handbook
* Security-first: nonces, sanitization, escaping, prepared SQL, capability checks
* Developer hooks and filters throughout
* Translation-ready and multisite-aware
* WooCommerce HPOS compatible

A separate premium add-on (distributed outside WordPress.org) adds paid memberships and payment processing. This free plugin is fully functional on its own and contains no locked features.

== External Services ==

This plugin does not connect to any external services. All functionality runs on your own WordPress site. No data is sent anywhere.

== Installation ==

1. Upload the `unstoppable-memberships` folder to `/wp-content/plugins/`, or install the ZIP via Plugins → Add New → Upload.
2. Activate the plugin through the Plugins menu.
3. Go to **Memberships → Dashboard** and follow the setup checklist.
4. Create your membership levels, then use **Memberships → Tools** to create the member-facing pages in one click.
5. Open any post or page and set **Membership Access** in the editor to restrict it.

== Frequently Asked Questions ==

= Does it require an account or external service? =

No. The plugin runs entirely on your own site and connects to nothing.

= How do I restrict a page? =

Edit the page and set the **Membership Access** box in the sidebar to "Any active member" or "Specific membership levels". For page builders like Elementor, set the global or per-page redirect mode under Settings so blocked visitors are redirected before the page renders.

= Can I take payments? =

Paid memberships and payment processing are provided by a separate premium add-on distributed outside WordPress.org. This free plugin handles free memberships and content restriction.

= Is it translation-ready and multisite-compatible? =

Yes to both.

== Changelog ==

= 2.0.0 =
* Free WordPress.org release: free membership levels, content restriction (by level, login status, or members only), drip content, redirect mode for page builders, Quick Edit access controls, member account and dashboard, manual member management with user search, member reports, one-click page creation, and shortcodes. No external services; no locked features.

== Upgrade Notice ==

= 2.0.0 =
First WordPress.org release.
