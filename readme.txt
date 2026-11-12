=== CF7 Booster ===
Plugin URI:         https://inaser.ir/plugin/cf7-booster
Requires at least:  5.8
Tested up to:       6.5
Requires PHP:       7.4
License:            GPLv2 or later

Supercharge Contact Form 7 with per-form Telegram & Bale notifications, automation rules, submission management, and delivery logs.

== Description ==

CF7 Booster adds a native tab to every CF7 form editor page where you can configure:
- Telegram bot destination and custom message template for that specific form
- Bale bot destination and custom message template for that specific form

Global bot tokens are set once under CF7 Booster → Telegram / Bale.

**Features:**
* Per-form message templates (set from the CF7 form editor)
* Send to Telegram (bots, channels, groups, multiple destinations)
* Send to Bale Messenger (bots, channels, groups)
* Automation rules — route by form, field value pattern, AND/OR logic
* Smart placeholders: {name} {email} {radio} {all_fields} and every CF7 field
* Async queue with configurable retry
* Submission storage with CSV export
* Full delivery logs with sensitive data masking
* Requires an active installation of Contact Form 7; no separate PHP library dependencies

== Installation ==
1. Install and activate Contact Form 7.
2. Upload this plugin folder to /wp-content/plugins/ and activate it.
3. Go to CF7 Booster → Telegram or Bale and enter a bot token and recipient destination.
4. Open any CF7 form, click the 🚀 CF7 Booster tab, then enable and configure the channels.
5. Submit a test form; check delivery logs and received messages before using the plugin in production.

== Support ==
https://inaser.ir/plugin/cf7-booster
https://inaser.ir/contact

== Staging Notes ==
Version 1.2.0-beta2 is for testing on staging sites. Review TESTING.md for PHP/JavaScript checks, the queue smoke tests, and manual acceptance criteria. Do not deploy to production without review.

== Changelog ==
= 1.2.0-beta2 =
* Redesigned bilingual operations dashboard with responsive activity chart and channel status.
* Reorganized validated settings, adding pause controls, retention days and privacy switches.
* Added real Telegram/Bale test-message actions and editable global templates.
* Added automation and form configuration checks, retention tests, and a CI-built staging ZIP.

= 1.2.0-beta1 =
* Improved retry tracking and avoidance of duplicate deliveries.
* Improved per-form routing precedence and manual retries.
* Fixed CF7 editor settings JavaScript and placeholder insertion.
* Added Persian translations and RTL stylesheet.
* Hardened HTTP requests and CSV export.
* Added CI and smoke tests. Removed unused modules and bundled ZIP.

= 1.1.0 =
* Per-form settings tab integrated into CF7 form editor
* Removed global template — templates now defined per-form
* Telegram/Bale global pages show per-form status overview
* Added support footer on Settings page
* Renamed plugin to CF7 Booster

= 1.0.0 =
* Initial release
