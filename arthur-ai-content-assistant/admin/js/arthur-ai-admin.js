/* global arthurAiAdmin, jQuery */
jQuery(document).ready(function ($) {
    var $form    = $('#arthur-ai-request-form');
    var $results = $('#arthur-ai-results');
    var $submit  = $form.find('.arthur-ai-submit');
    var $submitLabel = $submit.find('.arthur-ai-submit-label');
    var defaultLabel = $submitLabel.text();

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
                    var data    = resp.data;
                    var action  = data.action || {};
                    var result  = data.result || {};
                    var postId  = result.post_id || action.target_post_id || null;
                    var actionType = action.action_type || 'unknown';

                    html += '<div class="arthur-ai-result-card">';
                    html += '<div class="arthur-ai-result-header">';
                    html += '<span class="arthur-ai-pill arthur-ai-pill-success">Success</span>';
                    html += '<span class="arthur-ai-result-action">' + arthurAiAdmin.i18nActionType + ': <code>' + actionType + '</code></span>';
                    if (postId) {
                        html += '<span class="arthur-ai-result-target">Post ID: ' + postId + '</span>';
                    } else {
                        html += '<span class="arthur-ai-result-target">' + (arthurAiAdmin.i18nSiteWide || 'Site-wide action') + '</span>';
                    }
                    html += '</div>';

                    if (result.message) {
                        html += '<p class="arthur-ai-result-message">' + result.message + '</p>';
                    }

                    if (postId && arthurAiAdmin.editPostUrlBase) {
                        var editUrl = arthurAiAdmin.editPostUrlBase + postId;
                        html += '<p class="arthur-ai-result-links">';
                        html += '<a class="button button-secondary" href="' + editUrl + '">' + (arthurAiAdmin.i18nEdit || 'Edit content') + '</a>';
                        html += '</p>';
                    }

                    html += '</div>';
                } else {
                    var message = 'Arthur could not complete this request.';
                    if (resp.data && resp.data.message) {
                        message = resp.data.message;
                    } else if (resp.message) {
                        message = resp.message;
                    }
                    html += '<div class="arthur-ai-alert arthur-ai-alert-error">' + message + '</div>';
                }

                $results.html(html);
            })
            .catch(function (err) {
                var msg = 'An unexpected error occurred.';
                if (err && err.json) {
                    err.json().then(function (body) {
                        if (body && body.message) {
                            msg = body.message;
                        }
                        $results.html('<div class="arthur-ai-alert arthur-ai-alert-error">' + msg + '</div>');
                    }).catch(function () {
                        $results.html('<div class="arthur-ai-alert arthur-ai-alert-error">' + msg + '</div>');
                    });
                    return;
                }
                if (err && err.message) {
                    msg += ' Details: ' + err.message;
                }
                $results.html('<div class="arthur-ai-alert arthur-ai-alert-error">' + msg + '</div>');
            })
            .finally(function () {
                $submit.prop('disabled', false);
                $submit.removeClass('is-busy');
                $submitLabel.text(defaultLabel);
            });
    });
});
