/* global arthurAiAdmin, jQuery */
jQuery(document).ready(function ($) {
    var $form        = $('#arthur-ai-request-form');
    var $results     = $('#arthur-ai-results');
    var $submit      = $form.find('.arthur-ai-submit');
    var $submitLabel = $submit.find('.arthur-ai-submit-label');
    var defaultLabel = $submitLabel.text();

    var allowedActions = Array.isArray(arthurAiAdmin.allowedActions) ? arthurAiAdmin.allowedActions : [];
    var riskyActions   = ['set_login_custom_css', 'set_login_custom_js']; // extend as needed

    // Tab switching
    $('.arthur-ai-tab-button').on('click', function () {
        var $btn = $(this);
        var tab  = $btn.data('tab');

        $('.arthur-ai-tab-button').removeClass('is-active').attr('aria-selected', 'false');
        $btn.addClass('is-active').attr('aria-selected', 'true');

        $('.arthur-ai-tab-panel').removeClass('is-active');
        $('#arthur-ai-tab-' + tab).addClass('is-active');
    });

    if (!$form.length) {
        return;
    }

    function escapeHtml(str) {
        return $('<div>').text(str || '').html();
    }

    function formatActionLabel(actionType) {
        if (!actionType) {
            return 'Unknown action';
        }
        return actionType.replace(/_/g, ' ').replace(/\b\w/g, function (c) {
            return c.toUpperCase();
        });
    }

    function renderStatusRow(opts) {
        var statusClass = 'is-unknown';
        var icon        = '!';
        var label       = 'Untested';

        if (opts.status === 'safe') {
            statusClass = 'is-safe';
            icon        = '✓';
            label       = 'Safe';
        } else if (opts.status === 'warning') {
            statusClass = 'is-warning';
            icon        = '!';
            label       = 'Untested';
        } else if (opts.status === 'error') {
            statusClass = 'is-error';
            icon        = '✕';
            label       = 'Unable';
        }

        return '' +
            '<li class="arthur-ai-report-item ' + statusClass + '">' +
            '  <span class="arthur-ai-report-icon" aria-hidden="true">' + icon + '</span>' +
            '  <div class="arthur-ai-report-copy">' +
            '    <div class="arthur-ai-report-title">' + escapeHtml(opts.title) + '</div>' +
            '    <div class="arthur-ai-report-detail">' + escapeHtml(opts.detail) + '</div>' +
            '  </div>' +
            '  <span class="arthur-ai-report-tag">' + label + '</span>' +
            '</li>';
    }

    function normalizeActions(data) {
        var actions = [];

        if (data && Array.isArray(data.actions) && data.actions.length) {
            actions = data.actions.slice();
        } else if (data && data.result && Array.isArray(data.result.actions) && data.result.actions.length) {
            actions = data.result.actions.slice();
        }

        // Fallback to the single action structure the API returns.
        if (!actions.length && data && data.action) {
            actions.push({
                action_type: data.action.action_type,
                target_post_id: data.result ? (data.result.post_id || data.action.target_post_id || null) : (data.action.target_post_id || null),
                success: data.result ? data.result.success : undefined,
                message: data.result ? data.result.message : undefined
            });
        }

        return actions;
    }

    function runArthurOverrideAction(overrideActionJson, userRequest) {
        var formData = new FormData();
        formData.append('module_id', arthurAiAdmin.moduleId || 'content');
        formData.append('user_request', userRequest || '');
        formData.append('confirm', '1');
        formData.append('override_action', overrideActionJson);

        $submit.prop('disabled', true);
        $submit.addClass('is-busy');
        $submitLabel.text(arthurAiAdmin.i18nWorking || 'Working...');
        $results.append('<div class="arthur-ai-alert arthur-ai-alert-info">Applying the change...</div>');

        fetch(arthurAiAdmin.restUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': arthurAiAdmin.restNonce
            },
            body: formData
        })
            .then(function (response) {
                if (!response.ok) {
                    throw response;
                }
                return response.json();
            })
            .then(function (resp) {
                if (resp.success && resp.data && resp.data.result) {
                    var result      = resp.data.result;
                    var msg         = result.message || 'Custom change applied.';
                    var statusClass = result.success ? 'arthur-ai-alert-success' : 'arthur-ai-alert-error';
                    $results.html('<div class="arthur-ai-alert ' + statusClass + '">' + escapeHtml(msg) + '</div>');
                } else {
                    $results.html('<div class="arthur-ai-alert arthur-ai-alert-error">The override action did not run successfully.</div>');
                }
            })
            .catch(function (err) {
                var msg = 'There was an error applying the change.';
                if (err && err.message) {
                    msg += ' Details: ' + err.message;
                }
                $results.html('<div class="arthur-ai-alert arthur-ai-alert-error">' + escapeHtml(msg) + '</div>');
            })
            .finally(function () {
                $submit.prop('disabled', false);
                $submit.removeClass('is-busy');
                $submitLabel.text(defaultLabel);
            });
    }

    $form.on('submit', function (e) {
        e.preventDefault();

        var userRequest = $.trim($form.find('textarea[name="user_request"]').val());
        if (!userRequest) {
            $results.html('<div class="arthur-ai-alert arthur-ai-alert-error">Please enter a request.</div>');
            return;
        }

        var formData = new FormData();
        formData.append('user_request', userRequest);
        formData.append('_wpnonce', arthurAiAdmin.nonce);

        var fileInput = document.getElementById('arthur-ai-file');
        if (fileInput && fileInput.files[0]) {
            formData.append('attachment', fileInput.files[0]);
        }

        // UI: set loading state
        $submit.prop('disabled', true);
        $submit.addClass('is-busy');
        $submitLabel.text(arthurAiAdmin.i18nWorking || 'Working...');
        $results.html('<div class="arthur-ai-alert arthur-ai-alert-info">' + (arthurAiAdmin.i18nSending || 'Sending request to Arthur...') + '</div>');

        fetch(arthurAiAdmin.restUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': arthurAiAdmin.restNonce
            },
            body: formData
        })
            .then(function (response) {
                if (!response.ok) {
                    throw response;
                }
                return response.json();
            })
            .then(function (resp) {
                var html = '';

                if (resp.success && resp.data) {
                    var data        = resp.data;
                    var action      = data.action || {};
                    var result      = data.result || {};
                    var actionsList = normalizeActions(data);

                    var primaryActionType =
                        action.action_type ||
                        (actionsList[0] && (actionsList[0].action_type || actionsList[0].type)) ||
                        'unknown';

                    var postId =
                        result.post_id ||
                        action.target_post_id ||
                        (actionsList[0] && actionsList[0].target_post_id) ||
                        null;

                    var supportedAction = allowedActions.indexOf(primaryActionType) !== -1;
                    var isRiskyAction   = riskyActions.indexOf(primaryActionType) !== -1;

                    // Top-level status
                    var actionStatus = 'warning';
                    var detailText;

                    if (!supportedAction) {
                        detailText = 'Not in the current action database. Proceed with caution.';
                    } else if (isRiskyAction) {
                        detailText = 'This action applies custom code (CSS/JS). Review the change before running.';
                    } else {
                        actionStatus = 'safe';
                        detailText   = 'This matches a known Arthur action.';
                    }

                    if (result && result.success === false && result.risky) {
                        // Risky plan that has not yet run.
                        actionStatus = 'warning';
                        detailText   = result.message || detailText;
                    } else if (result && result.success === false) {
                        actionStatus = 'error';
                        detailText   = result.message || 'Arthur could not complete this action.';
                    }

                    html += '<div class="arthur-ai-result-card">';
                    html += '<div class="arthur-ai-result-header">';
                    html += '<div class="arthur-ai-result-meta">';

                    var pillClass = (result.success === false && !result.risky) ? 'arthur-ai-pill-error' : 'arthur-ai-pill-success';
                    var pillText  = (result.success === false && !result.risky) ? 'Issue' : 'Success';

                    // If we have not run yet but it is risky, treat it as an "Issue" plan.
                    if (result.risky && result.success === false) {
                        pillClass = 'arthur-ai-pill-warning';
                        pillText  = 'Review required';
                    }

                    html += '<span class="arthur-ai-pill ' + pillClass + '">' + pillText + '</span>';
                    html += '<span class="arthur-ai-result-action">' + (arthurAiAdmin.i18nActionType || 'Action type') + ': <code>' + escapeHtml(primaryActionType) + '</code></span>';

                    if (postId) {
                        html += '<span class="arthur-ai-result-target">Post ID: ' + postId + '</span>';
                    } else {
                        html += '<span class="arthur-ai-result-target">' + (arthurAiAdmin.i18nSiteWide || 'Site-wide action') + '</span>';
                    }

                    html += '</div>';
                    html += '</div>'; // .arthur-ai-result-header

                    html += '<div class="arthur-ai-action-report">';
                    html += '<div class="arthur-ai-report-header">';
                    html += '<h3>Action report</h3>';
                    html += '<p>See how Arthur handled each step of your request.</p>';
                    html += '</div>';
                    html += '<ul class="arthur-ai-report-list">';

                    // Per-step rows, if we have multiple actions.
                    if (actionsList.length) {
                        actionsList.forEach(function (item) {
                            var actionType = item.action_type || item.type || 'unknown';
                            var stepStatus = 'warning';
                            var stepDetail = '';

                            var stepSupported = allowedActions.indexOf(actionType) !== -1;
                            var stepRisky     = riskyActions.indexOf(actionType) !== -1;

                            if (item.success === false) {
                                stepStatus = 'error';
                            } else if (item.success === true && stepSupported && !stepRisky) {
                                stepStatus = 'safe';
                            }

                            if (item.message) {
                                stepDetail = item.message;
                            } else if (item.detail) {
                                stepDetail = item.detail;
                            } else if (!stepSupported) {
                                stepDetail = 'Not in the current action database. Proceed with caution.';
                            } else if (stepRisky) {
                                stepDetail = 'This step applies custom code (CSS/JS).';
                            } else {
                                stepDetail = 'Arthur processed this step.';
                            }

                            html += renderStatusRow({
                                title: formatActionLabel(actionType),
                                detail: stepDetail,
                                status: stepStatus
                            });
                        });
                    }

                    // Summary / fallback row
                    if (result && result.message && result.success !== false && !result.risky) {
                        html += renderStatusRow({
                            title: 'Result',
                            detail: result.message,
                            status: 'safe'
                        });
                    } else if (result && result.message && (result.success === false || result.risky)) {
                        html += renderStatusRow({
                            title: 'Result detail',
                            detail: result.message,
                            status: result.risky ? 'warning' : 'error'
                        });
                    } else if (!actionsList.length) {
                        html += renderStatusRow({
                            title: formatActionLabel(action.action_type || 'unknown'),
                            detail: detailText,
                            status: actionStatus
                        });
                    }

                    html += '</ul>';
                    html += '</div>'; // .arthur-ai-action-report

                    // Edit link if a post was involved.
                    if (postId && arthurAiAdmin.editPostUrlBase) {
                        var editUrl = arthurAiAdmin.editPostUrlBase + postId;
                        html += '<p class="arthur-ai-result-links">';
                        html += '<a class="button button-secondary" href="' + editUrl + '">' + (arthurAiAdmin.i18nEdit || 'Edit content') + '</a>';
                        html += '</p>';
                    }

                    // If this is a risky plan (e.g. custom CSS), append "Run anyway" UI.
                    if (result && result.risky) {
                        html += '' +
                            '<div class="arthur-ai-report-actions">' +
                            '  <button type="button" class="button button-primary arthur-ai-run-anyway">Run anyway</button>' +
                            '  <p class="arthur-ai-report-disclaimer">' +
                            '    This will apply custom code changes (CSS/JS) generated by Arthur. ' +
                            '    Review the effect on your site and be prepared to revert if needed.' +
                            '  </p>' +
                            '</div>';
                    }

                    html += '</div>'; // .arthur-ai-result-card
                } else {
                    var message = 'Arthur could not complete this request.';
                    if (resp.data && resp.data.message) {
                        message = resp.data.message;
                    } else if (resp.message) {
                        message = resp.message;
                    }
                    html += '<div class="arthur-ai-alert arthur-ai-alert-error">' + escapeHtml(message) + '</div>';
                }

                $results.html(html);

                // Wire up "Run anyway" if needed.
                if (resp.success && resp.data && resp.data.result && resp.data.result.risky) {
                    var action   = resp.data.action || {};
                    var override = JSON.stringify(action || {});
                    var request  = $.trim($form.find('textarea[name="user_request"]').val()) || '';

                    $results.find('.arthur-ai-run-anyway').on('click', function () {
                        runArthurOverrideAction(override, request);
                    });
                }
            })
            .catch(function (err) {
                var msg = 'An unexpected error occurred.';
                if (err && err.json) {
                    err.json().then(function (body) {
                        if (body && body.message) {
                            msg = body.message;
                        }
                        $results.html('<div class="arthur-ai-alert arthur-ai-alert-error">' + escapeHtml(msg) + '</div>');
                    }).catch(function () {
                        $results.html('<div class="arthur-ai-alert arthur-ai-alert-error">' + escapeHtml(msg) + '</div>');
                    });
                    return;
                }
                if (err && err.message) {
                    msg += ' Details: ' + err.message;
                }
                $results.html('<div class="arthur-ai-alert arthur-ai-alert-error">' + escapeHtml(msg) + '</div>');
            })
            .finally(function () {
                $submit.prop('disabled', false);
                $submit.removeClass('is-busy');
                $submitLabel.text(defaultLabel);
            });
    });
});
