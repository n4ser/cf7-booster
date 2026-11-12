/* CF7 Booster – Admin JS v2 */
(function ($) {
    'use strict';

    /* ===================================================================== */
    /*  UTILITIES                                                             */
    /* ===================================================================== */

    function tr(text) {
        return (typeof cf7on !== 'undefined' && cf7on.strings && cf7on.strings[text]) || text;
    }

    function showNotice(msg, type) {
        var cls = (type === 'error') ? 'notice-error' : 'notice-success';
        $('#cf7on-notice').empty().append($('<div>').addClass('notice ' + cls + ' is-dismissible').append($('<p>').text(msg)));
        setTimeout(function () { $('#cf7on-notice .notice').fadeOut(400); }, 4000);
    }

    function escHtml(str) {
        return String(str || '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function sel(opts, current) {
        var h = '';
        $.each(opts, function(_, o) {
            h += '<option value="' + escHtml(o.val) + '"' + (current === o.val ? ' selected' : '') + '>' + escHtml(o.label) + '</option>';
        });
        return h;
    }

    /* ===================================================================== */
    /*  PLACEHOLDER CHIPS — click to insert into closest textarea            */
    /* ===================================================================== */

    var lastTemplate = null;
    $(document).on('focusin', '.cf7on-tpl-textarea', function () {
        lastTemplate = this;
    });

    $(document).on('click', '.cf7on-chip', function () {
        var ph = $(this).data('ph');
        // The placeholder legend sits outside the channel cards.
        // Insert into the last focused message template or the first template.
        var $ta = $(this).closest('.cf7on-card').find('.cf7on-tpl-textarea').first();
        if (!$ta.length && lastTemplate && $.contains(document, lastTemplate)) {
            $ta = $(lastTemplate);
        }
        if (!$ta.length) {
            $ta = $('.cf7on-tpl-textarea').first();
        }
        if (!$ta.length) return;
        var el = $ta[0], s = el.selectionStart, e = el.selectionEnd;
        el.value = el.value.substring(0, s) + ph + el.value.substring(e);
        el.selectionStart = el.selectionEnd = s + ph.length;
        el.focus();
        var $c = $(this);
        $c.addClass('cf7on-chip--inserted');
        setTimeout(function(){ $c.removeClass('cf7on-chip--inserted'); }, 700);
    });

    /* ===================================================================== */
    /*  GENERIC SAVE BUTTON (Telegram / Bale / Settings pages)               */
    /* ===================================================================== */

    $(document).on('click', '.cf7on-save', function () {
        var $btn     = $(this);
        var action   = $btn.data('action');
        var fieldIds = ($btn.data('fields') || '').split(',').map(function(s){ return s.trim(); });
        var map      = $btn.data('map') || {};
        if (typeof map === 'string') { try { map = JSON.parse(map); } catch(e){ map = {}; } }

        var post = { action: action, nonce: cf7on.nonce };

        $.each(fieldIds, function(_, id) {
            var $el = $('#' + id);
            var key = map[id] || id;
            if ($el.attr('type') === 'checkbox') {
                post[key] = $el.is(':checked') ? '1' : '';
            } else if ($el.is('textarea') && key === 'destinations') {
                var lines = $el.val().split('\n').map(function(l){ return l.trim(); }).filter(Boolean);
                $.each(lines, function(i, v){ post['destinations[' + i + ']'] = v; });
            } else {
                post[key] = $el.val();
            }
        });

        var orig = $btn.text();
        $btn.prop('disabled', true).text(tr('Saving…'));
        $.post(cf7on.ajaxurl, post, function(r) {
            $btn.prop('disabled', false).text(orig);
            r.success ? showNotice(r.data, 'success') : showNotice(r.data || 'Error', 'error');
        }).fail(function(){ $btn.prop('disabled', false).text(orig); showNotice(tr('Request failed.'), 'error'); });
    });

    /* ===================================================================== */
    /*  TEST CONNECTION                                                       */
    /* ===================================================================== */

    $(document).on('click', '[data-action="cf7on_test_telegram"]', function () {
        testConn('cf7on_test_telegram', $('#tg_token').val(), '#cf7on-test-telegram-result');
    });
    $(document).on('click', '[data-action="cf7on_test_bale"]', function () {
        testConn('cf7on_test_bale', $('#ba_token').val(), '#cf7on-test-bale-result');
    });
    function testConn(action, token, sel) {
        $(sel).text(' Testing…');
        $.post(cf7on.ajaxurl, { action: action, nonce: cf7on.nonce, bot_token: token }, function(r) {
            if (r.success) {
                var n = r.data && r.data.bot ? '@' + r.data.bot.username : tr('Connected');
                $(sel).html(' <span style="color:#28a745">✓ ' + escHtml(n) + '</span>');
            } else {
                $(sel).html(' <span style="color:#dc3545">✗ ' + escHtml(r.data || tr('Failed')) + '</span>');
            }
        });
    }

    /* ===================================================================== */
    /*  RESEND                                                                */
    /* ===================================================================== */

    $(document).on('click', '.cf7on-resend', function () {
        var $b = $(this), id = $b.data('id');
        $b.prop('disabled', true).text(tr('Sending…'));
        $.post(cf7on.ajaxurl, { action: 'cf7on_resend', nonce: cf7on.nonce, submission_id: id }, function(r) {
            $b.prop('disabled', false).text(tr('Resend'));
            alert(r.success ? r.data : (r.data || tr('Failed')));
        });
    });

    /* ===================================================================== */
    /*  AUTOMATION RULE BUILDER                                               */
    /* ===================================================================== */

    if (typeof cf7onRules === 'undefined') return;

    // Migrate any old single-condition rules to conditions array
    $.each(cf7onRules, function(_, r) {
        if (!r.conditions && r.condition) {
            r.conditions = [r.condition];
            delete r.condition;
        }
        r.conditions = r.conditions || [];
        r.actions    = r.actions    || [];
        r.logic      = r.logic      || 'AND';
    });

    renderRules();

    /* -- Top-level rule actions ------------------------------------------ */
    $(document).on('click', '#cf7on-add-rule', function () {
        collectRules();
        cf7onRules.push({
            name: tr('New Rule'), enabled: true, logic: 'AND',
            conditions: [{ type: 'field_name', name_operator: 'contains', name_value: '', operator: 'contains', value: '' }],
            actions: [{ channel: 'telegram', destination: '', template: '' }]
        });
        renderRules();
    });

    $(document).on('click', '#cf7on-save-rules', function () {
        collectRules();
        var $b = $(this);
        $b.prop('disabled', true).text(tr('Saving…'));
        $.post(cf7on.ajaxurl, { action: 'cf7on_save_rules', nonce: cf7on.nonce, rules: JSON.stringify(cf7onRules) }, function(r) {
            $b.prop('disabled', false).text(tr('Save All Rules'));
            r.success ? showNotice(r.data || 'Saved!', 'success') : showNotice(r.data || 'Error', 'error');
        });
    });

    $(document).on('click', '.cf7on-rule-remove', function () {
        if (!confirm(tr('Delete this rule?'))) return;
        collectRules();
        cf7onRules.splice(+$(this).data('idx'), 1);
        renderRules();
    });

    /* -- Condition actions ----------------------------------------------- */
    $(document).on('click', '.cf7on-add-condition', function () {
        collectRules();
        var idx = +$(this).data('idx');
        cf7onRules[idx].conditions.push({ type: 'field_name', name_operator: 'contains', name_value: '', operator: 'contains', value: '' });
        renderRules();
    });

    $(document).on('click', '.cf7on-cond-remove', function () {
        collectRules();
        var idx = +$(this).data('idx'), ci = +$(this).data('cidx');
        cf7onRules[idx].conditions.splice(ci, 1);
        renderRules();
    });

    /* -- Action row actions ---------------------------------------------- */
    $(document).on('click', '.cf7on-add-action', function () {
        collectRules();
        cf7onRules[+$(this).data('idx')].actions.push({ channel: 'telegram', destination: '', template: '' });
        renderRules();
    });

    $(document).on('click', '.cf7on-action-remove', function () {
        collectRules();
        var idx = +$(this).data('idx'), ai = +$(this).data('aidx');
        cf7onRules[idx].actions.splice(ai, 1);
        renderRules();
    });

    /* -- KEY FIX: Type dropdown change → collect state then re-render ----- */
    $(document).on('change', '.rule-cond-type', function () {
        collectRules();
        // Update just the type value in the data model for the changed row
        var idx  = +$(this).data('idx');
        var cidx = +$(this).data('cidx');
        if (cf7onRules[idx] && cf7onRules[idx].conditions[cidx]) {
            cf7onRules[idx].conditions[cidx].type = $(this).val();
        }
        renderRules();
    });

    /* -- Hide value field for empty/not_empty operators ------------------- */
    $(document).on('change', '.rule-cond-op', function () {
        var op   = $(this).val();
        var $row = $(this).closest('.cf7on-condition-row');
        if (op === 'empty' || op === 'not_empty') {
            $row.find('.rule-cond-val').val('').hide();
        } else {
            $row.find('.rule-cond-val').show();
        }
    });

    /* -- Toggle action template ------------------------------------------ */
    $(document).on('click', '.cf7on-toggle-tpl', function () {
        $(this).closest('.cf7on-action-row').find('.cf7on-action-tpl').toggle();
        var open = $(this).closest('.cf7on-action-row').find('.cf7on-action-tpl').is(':visible');
        $(this).text(open ? '▲ Custom template' : '▼ Custom template');
    });

    /* ===================================================================== */
    /*  renderRules                                                           */
    /* ===================================================================== */

    function translateRuleMarkup() {
        var strings = (typeof cf7on !== 'undefined' && cf7on.strings) || {};
        var $root = $('#cf7on-rules-list');
        // Translate text labels, select options, and placeholders, not user-entered values.
        $root.find('*').addBack().contents().filter(function () {
            return this.nodeType === 3;
        }).each(function () {
            var original = this.nodeValue;
            var key = original.trim();
            if (Object.prototype.hasOwnProperty.call(strings, key)) {
                this.nodeValue = original.replace(key, strings[key]);
            }
        });
        $root.find('[placeholder], [title]').each(function () {
            var $el = $(this);
            $.each(['placeholder', 'title'], function (_, attr) {
                var value = $el.attr(attr);
                if (value && strings[value]) {
                    $el.attr(attr, strings[value]);
                }
            });
        });
    }

    function renderRules() {
        if (!cf7onRules.length) {
            $('#cf7on-rules-list').html('<div class="cf7on-card"><p class="cf7on-empty">No rules yet. Click "+ Add Rule" to get started.</p></div>');
            translateRuleMarkup();
            return;
        }
        var html = '';
        $.each(cf7onRules, function(i, rule) {
            html += renderRuleCard(i, rule);
        });
        $('#cf7on-rules-list').html(html);
        translateRuleMarkup();
    }

    function renderRuleCard(i, rule) {
        var h = '<div class="cf7on-rule-card">';

        /* Header */
        h += '<div class="cf7on-rule-head">';
        h += '<label class="cf7on-toggle-wrap"><input type="checkbox" class="rule-enabled" data-idx="' + i + '"' + (rule.enabled ? ' checked' : '') + '><span class="cf7on-slider"></span></label>';
        h += '<input type="text" class="regular-text rule-name" data-idx="' + i + '" value="' + escHtml(rule.name) + '" placeholder="Rule name">';
        h += '<button class="cf7on-rule-remove button-link cf7on-danger-link" data-idx="' + i + '">✕ Delete</button>';
        h += '</div>';

        /* Logic selector */
        if (rule.conditions.length > 1) {
            h += '<p class="cf7on-logic-row">Match ';
            h += '<select class="rule-logic" data-idx="' + i + '">';
            h += '<option value="AND"' + (rule.logic !== 'OR' ? ' selected':'') + '>ALL conditions (AND)</option>';
            h += '<option value="OR"'  + (rule.logic === 'OR'  ? ' selected':'') + '>ANY condition (OR)</option>';
            h += '</select></p>';
        }

        /* Conditions */
        h += '<div class="cf7on-conditions-wrap">';
        $.each(rule.conditions, function(ci, cond) {
            h += renderConditionRow(i, ci, cond, rule.conditions.length, rule.logic);
        });
        h += '</div>';
        h += '<p class="cf7on-add-row"><button class="button button-small cf7on-add-condition" data-idx="' + i + '">+ Add condition</button></p>';

        /* Actions — THEN section */
        h += '<div class="cf7on-then-wrap">';
        h += '<div class="cf7on-then-label">THEN — send to:</div>';
        $.each(rule.actions, function(ai, action) {
            h += renderActionRow(i, ai, action, rule.actions.length);
        });
        h += '<button class="button button-small cf7on-add-action" data-idx="' + i + '">+ Add channel / destination</button>';
        h += '</div>';

        h += '</div>'; // end rule-card
        return h;
    }

    /* ===================================================================== */
    /*  renderConditionRow                                                    */
    /* ===================================================================== */

    var OPERATORS = [
        { val: '=',           label: '= equals' },
        { val: '!=',          label: '≠ not equals' },
        { val: 'contains',    label: '~ contains' },
        { val: 'not_contains',label: '!~ not contains' },
        { val: 'starts',      label: 'starts with' },
        { val: 'ends',        label: 'ends with' },
        { val: '>',           label: '> greater than' },
        { val: '<',           label: '< less than' },
        { val: '>=',          label: '≥ ≥' },
        { val: '<=',          label: '≤ ≤' },
        { val: 'regex',       label: '⊛ regex' },
        { val: 'empty',       label: 'is empty' },
        { val: 'not_empty',   label: 'is not empty' },
    ];

    var NAME_OPERATORS = [
        { val: 'contains',    label: 'name contains' },
        { val: '=',           label: 'name equals' },
        { val: 'starts',      label: 'name starts with' },
        { val: 'ends',        label: 'name ends with' },
        { val: 'regex',       label: 'name matches regex' },
    ];

    function renderConditionRow(i, ci, cond, total, logic) {
        var badge = ci === 0 ? 'IF' : (logic === 'OR' ? 'OR' : 'AND');
        var h = '<div class="cf7on-condition-row">';
        h += '<span class="cf7on-cond-badge cf7on-badge-' + badge.toLowerCase() + '">' + badge + '</span>';

        /* Type selector */
        var types = [
            { val: 'field_name',  label: '📋 Field name pattern + value' },
            { val: 'field_exact', label: '🔤 Exact field name + value' },
            { val: 'form_id',     label: '📄 Form (select from list)' },
            { val: 'form_name',   label: '🏷 Form title' },
        ];
        h += '<select class="rule-cond-type" data-idx="' + i + '" data-cidx="' + ci + '">';
        h += sel(types, cond.type || 'field_name');
        h += '</select>';

        /* Type-specific inputs */
        var type = cond.type || 'field_name';

        if (type === 'form_id') {
            /* ── Form dropdown ─────────────────────────────────────── */
            h += '<select class="rule-cond-val" data-idx="' + i + '" data-cidx="' + ci + '">';
            h += '<option value="">— select form —</option>';
            $.each(cf7onForms, function(_, f) {
                h += '<option value="' + f.id + '"' + (String(cond.value) === String(f.id) ? ' selected' : '') + '>' + escHtml(f.title) + '</option>';
            });
            h += '</select>';

        } else if (type === 'form_name') {
            /* ── Form title text match ─────────────────────────────── */
            var fnOps = [ {val:'contains',label:'contains'},{val:'=',label:'equals'},{val:'starts',label:'starts with'},{val:'regex',label:'regex'} ];
            h += '<select class="rule-cond-op" data-idx="' + i + '" data-cidx="' + ci + '">';
            h += sel(fnOps, cond.operator || 'contains');
            h += '</select>';
            h += '<input type="text" class="regular-text rule-cond-val" data-idx="' + i + '" data-cidx="' + ci + '"'
               + ' value="' + escHtml(cond.value || '') + '" placeholder="form title…">';

        } else if (type === 'field_exact') {
            /* ── Exact field name ──────────────────────────────────── */
            h += '<input list="dl-exact-' + i + '-' + ci + '" type="text" class="rule-cond-field" data-idx="' + i + '" data-cidx="' + ci + '"'
               + ' value="' + escHtml(cond.field || '') + '" placeholder="exact-field-name" style="width:140px;">';
            h += '<datalist id="dl-exact-' + i + '-' + ci + '">';
            $.each(cf7onFields, function(_, f) {
                h += '<option value="' + escHtml(f.name) + '">' + escHtml(f.name) + ' (' + f.type + ' — ' + f.form + ')</option>';
            });
            h += '</datalist>';
            h += renderOpAndValue(i, ci, cond);

        } else {
            /* ── field_name: pattern match ─────────────────────────── */
            /*    Row 1: field name pattern                              */
            h += '<div class="cf7on-sub-row">';
            h += '<span class="cf7on-sub-label">Field name:</span>';
            h += '<select class="rule-cond-name-op" data-idx="' + i + '" data-cidx="' + ci + '">';
            h += sel(NAME_OPERATORS, cond.name_operator || 'contains');
            h += '</select>';
            // Input with datalist of base types AND actual field names
            h += '<input list="dl-name-' + i + '-' + ci + '" type="text" class="rule-cond-name-val" data-idx="' + i + '" data-cidx="' + ci + '"'
               + ' value="' + escHtml(cond.name_value || '') + '" placeholder="radio, checkbox, …" style="width:140px;">';
            h += '<datalist id="dl-name-' + i + '-' + ci + '">';
            // Add base types first (most useful)
            $.each(cf7onBasetypes, function(_, bt) {
                h += '<option value="' + escHtml(bt) + '">' + escHtml(bt) + ' (base type)</option>';
            });
            // Then actual field names
            $.each(cf7onFields, function(_, f) {
                h += '<option value="' + escHtml(f.name) + '">' + escHtml(f.name) + ' (' + f.type + ')</option>';
            });
            h += '</datalist>';
            h += '</div>';
            /*    Row 2: value condition                                 */
            h += '<div class="cf7on-sub-row">';
            h += '<span class="cf7on-sub-label">Value:</span>';
            h += renderOpAndValue(i, ci, cond);
            h += '</div>';
        }

        if (total > 1) {
            h += '<button class="cf7on-cond-remove button-link cf7on-danger-link" data-idx="' + i + '" data-cidx="' + ci + '" title="Remove">✕</button>';
        }
        h += '</div>';
        return h;
    }

    function renderOpAndValue(i, ci, cond) {
        var hideVal = (cond.operator === 'empty' || cond.operator === 'not_empty');
        var h = '<select class="rule-cond-op" data-idx="' + i + '" data-cidx="' + ci + '">';
        h += sel(OPERATORS, cond.operator || '=');
        h += '</select>';
        h += '<input type="text" class="rule-cond-val" data-idx="' + i + '" data-cidx="' + ci + '"'
           + ' value="' + escHtml(cond.value || '') + '" placeholder="value"'
           + (hideVal ? ' style="display:none;"' : '') + '>';
        return h;
    }

    /* ===================================================================== */
    /*  renderActionRow                                                       */
    /* ===================================================================== */

    function renderActionRow(i, ai, action, total) {
        var h = '<div class="cf7on-action-row">';

        h += '<select class="rule-action-ch" data-idx="' + i + '" data-aidx="' + ai + '">';
        h += '<option value="telegram"' + (action.channel === 'telegram' ? ' selected':'') + '>📨 Telegram</option>';
        h += '<option value="bale"'     + (action.channel === 'bale'     ? ' selected':'') + '>💬 Bale</option>';
        h += '</select>';

        h += '<input type="text" class="regular-text rule-action-dest" data-idx="' + i + '" data-aidx="' + ai + '"'
           + ' value="' + escHtml(action.destination) + '" placeholder="Chat ID or @channel">';

        /* Custom template toggle */
        var hasTpl = !!(action.template && action.template.trim());
        h += '<button type="button" class="button button-small cf7on-toggle-tpl">' + (hasTpl ? '▲' : '▼') + ' Custom template</button>';

        if (total > 1) {
            h += '<button class="cf7on-action-remove button-link cf7on-danger-link" data-idx="' + i + '" data-aidx="' + ai + '" title="Remove">✕</button>';
        }

        h += '<div class="cf7on-action-tpl"' + (hasTpl ? '' : ' style="display:none;"') + '>';
        h += '<label style="font-size:11px;color:#646970;">Override message template for this destination (leave blank to use global template):</label>';
        h += '<textarea class="large-text code rule-action-template" rows="5" data-idx="' + i + '" data-aidx="' + ai + '"'
           + ' style="font-family:monospace;font-size:12px;">' + escHtml(action.template || '') + '</textarea>';
        h += '</div>';

        h += '</div>'; // action-row
        return h;
    }

    /* ===================================================================== */
    /*  collectRules — DOM → cf7onRules                                      */
    /* ===================================================================== */

    function collectRules() {
        $('.cf7on-rule-card').each(function(i) {
            var r = cf7onRules[i];
            if (!r) return;
            r.name    = $(this).find('.rule-name').first().val();
            r.enabled = $(this).find('.rule-enabled').first().is(':checked');
            r.logic   = $(this).find('.rule-logic').first().val() || 'AND';

            r.conditions = [];
            $(this).find('.cf7on-condition-row').each(function() {
                var type = $(this).find('.rule-cond-type').val();
                var c = { type: type };

                if (type === 'form_id') {
                    c.value = $(this).find('.rule-cond-val').val();

                } else if (type === 'form_name') {
                    c.operator = $(this).find('.rule-cond-op').val() || 'contains';
                    c.value    = $(this).find('.rule-cond-val').val();

                } else if (type === 'field_exact') {
                    c.field    = $(this).find('.rule-cond-field').val();
                    c.operator = $(this).find('.rule-cond-op').val() || '=';
                    c.value    = $(this).find('.rule-cond-val').val();

                } else { /* field_name */
                    c.name_operator = $(this).find('.rule-cond-name-op').val() || 'contains';
                    c.name_value    = $(this).find('.rule-cond-name-val').val();
                    c.operator      = $(this).find('.rule-cond-op').val() || 'contains';
                    c.value         = $(this).find('.rule-cond-val').val();
                }
                r.conditions.push(c);
            });

            r.actions = [];
            $(this).find('.cf7on-action-row').each(function() {
                r.actions.push({
                    channel:     $(this).find('.rule-action-ch').val(),
                    destination: $(this).find('.rule-action-dest').val(),
                    template:    $(this).find('.rule-action-template').val() || '',
                });
            });
        });
    }

})(jQuery);

/* ===================================================================== */
/*  CF7 FORM TAB — Save button (AJAX, stays on CF7 editor page)         */
/* ===================================================================== */
(function ($) {
'use strict';
function tr(text) {
    return (typeof cf7on !== 'undefined' && cf7on.strings && cf7on.strings[text]) || text;
}
$(document).on('click', '#cf7on-save-form-tab', function () {
    var $btn    = $(this);
    var formId  = $btn.data('form-id');
    var $result = $('#cf7on-tab-save-result');

    // Collect all named inputs/textareas inside .cf7on-form-tab-wrap
    var $wrap = $btn.closest('.cf7on-form-tab-wrap');
    var post  = { action: 'cf7on_save_form_settings', nonce: cf7on.nonce, form_id: formId };

    $wrap.find('input[name], textarea[name], select[name]').each(function () {
        var name = $(this).attr('name');
        if ( ! name || name === 'cf7on_form_tab_nonce' ) return;
        if ( $(this).attr('type') === 'checkbox' ) {
            post[ name ] = $(this).is(':checked') ? '1' : '';
        } else {
            post[ name ] = $(this).val();
        }
    });

    var orig = $btn.text();
    $btn.prop('disabled', true).text(tr('Saving…'));
    $result.text('');

    $.post(cf7on.ajaxurl, post, function (r) {
        $btn.prop('disabled', false).text(orig);
        if (r.success) {
            $result.empty().append($('<span>').css('color','#28a745').text('✓ ' + r.data));
        } else {
            $result.empty().append($('<span>').css('color','#dc3545').text('✗ ' + (r.data || 'Error')));
        }
        setTimeout(function(){ $result.text(''); }, 4000);
    }).fail(function () {
        $btn.prop('disabled', false).text(orig);
        $result.empty().append($('<span>').css('color','#dc3545').text('✗ Request failed'));
    });
});

})(jQuery);
