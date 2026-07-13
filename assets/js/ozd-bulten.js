/**
 * Frontend AJAX handling for OZD E-Bülten.
 *
 * @package OZD_WP_EBulten
 */

(function () {
    'use strict';

    function showMessage(box, type, message) {
        if (!box) {
            return;
        }

        box.hidden = false;
        box.className = 'ozd-alert ' + type;
        box.textContent = message;
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-ozd-form]');

        if (!form || !window.OZDEBulten || OZDEBulten.ajaxEnabled !== '1') {
            return;
        }

        event.preventDefault();

        var wrap = form.closest('[data-ozd-ebulten]');
        var messageBox = wrap ? wrap.querySelector('[data-ozd-message]') : null;
        var button = form.querySelector('[data-ozd-submit]');
        var stepInput = form.querySelector('[data-ozd-step]');
        var consentRow = form.querySelector('[data-ozd-consent-row]');
        var emailRow = form.querySelector('[data-ozd-email-row]');
        var data = new FormData(form);

        data.set('action', 'ozd_ebulten_subscribe');

        if (button) {
            button.disabled = true;
        }

        fetch(OZDEBulten.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (json) {
                var payload = json.data || {};

                if (!json.success) {
                    showMessage(messageBox, 'error', payload.message || OZDEBulten.defaultError);
                    return;
                }

                showMessage(messageBox, 'success', payload.message || OZDEBulten.defaultDone);

                if (payload.step === 'consent') {
                    if (stepInput) {
                        stepInput.value = 'consent';
                    }
                    if (consentRow) {
                        consentRow.hidden = false;
                    }
                    if (button && payload.buttonText) {
                        button.textContent = payload.buttonText;
                    }
                    if (emailRow) {
                        var email = emailRow.querySelector('input[type="email"]');
                        if (email) {
                            email.readOnly = true;
                        }
                    }
                    var nameRow = form.querySelector('[data-ozd-name-row]');
                    if (nameRow) {
                        var name = nameRow.querySelector('input[type="text"]');
                        if (name) {
                            name.readOnly = true;
                        }
                    }
                }

                if (payload.step === 'done') {
                    form.reset();
                    form.hidden = true;
                }
            })
            .catch(function () {
                showMessage(messageBox, 'error', OZDEBulten.networkError);
            })
            .finally(function () {
                if (button) {
                    button.disabled = false;
                }
            });
    });
}());
