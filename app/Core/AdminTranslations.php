<?php
namespace CF7ON\Core;

defined( 'ABSPATH' ) || exit;

/** Labels used by the dynamic JavaScript rule editor and AJAX feedback. */
class AdminTranslations {
    public static function all(): array {
        $labels = [
            "Saving…",
            "Sending…",
            "Request failed.",
            "Resend",
            "Error",
            "Testing…",
            "Failed",
            "Connected",
            "Save All Rules",
            "New Rule",
            "Delete this rule?",
            "No rules yet. Click \"+ Add Rule\" to get started.",
            "Rule name",
            "✕ Delete",
            "Match",
            "ALL conditions (AND)",
            "ANY condition (OR)",
            "+ Add condition",
            "+ Add channel / destination",
            "THEN — send to:",
            "= equals",
            "≠ not equals",
            "~ contains",
            "!~ not contains",
            "starts with",
            "ends with",
            "> greater than",
            "< less than",
            "≥ ≥",
            "≤ ≤",
            "⊛ regex",
            "is empty",
            "is not empty",
            "name contains",
            "name equals",
            "name starts with",
            "name ends with",
            "name matches regex",
            "📋 Field name pattern + value",
            "🔤 Exact field name + value",
            "📄 Form (select from list)",
            "🏷 Form title",
            "— select form —",
            "contains",
            "equals",
            "regex",
            "form title…",
            "exact-field-name",
            "Field name:",
            "radio, checkbox, …",
            "Value:",
            "value",
            "📨 Telegram",
            "💬 Bale",
            "Chat ID or @channel",
            "▲ Custom template",
            "▼ Custom template",
            "Override message template for this destination (leave blank to use global template):",
            "Remove",
            "+ Add Rule",
            "Name (your-name or similar)",
            "Email",
            "Phone",
            "Message",
            "Subject",
            "Form title",
            "Submission date",
            "Form ID",
            "Visitor IP address",
            "Page URL",
            "All fields",
            "New form submission",
            "FormRelay CF7",
        ];

        $translations = [];
        foreach ( $labels as $label ) {
            $translations[ $label ] = __( $label, 'cf7-booster' );
        }
        return $translations;
    }
}
