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
* No external dependencies

== Installation ==
1. Upload the plugin folder to /wp-content/plugins/
2. Activate via Plugins menu
3. Go to CF7 Booster → Telegram and enter your bot token
4. Open any CF7 form, click the 🚀 CF7 Booster tab, enable & configure

== Support ==
https://inaser.ir/plugin/cf7-booster
https://inaser.ir/contact

== Changelog ==
= 1.1.0 =
* Per-form settings tab integrated into CF7 form editor
* Removed global template — templates now defined per-form
* Telegram/Bale global pages show per-form status overview
* Added support footer on Settings page
* Renamed plugin to CF7 Booster

= 1.0.0 =
* Initial release
