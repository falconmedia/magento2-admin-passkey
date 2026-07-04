/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 *
 * Admin passkey setup wizard driver.
 *
 * Drives the four wizard steps (Welcome -> Registration -> Success ->
 * Recommendation) and reuses the existing authenticated registration endpoints:
 * it fetches creation options, runs navigator.credentials.create(), posts the
 * attestation to the verify endpoint, then persists the friendly name via the
 * rename endpoint. All labels and endpoint URLs come from the block config; no
 * inline JS and no hardcoded strings are used (CSP-safe).
 */
define(['jquery'], function ($) {
    'use strict';

    /**
     * Decode a base64url string into a Uint8Array.
     *
     * @param {String} value
     * @returns {Uint8Array}
     */
    function base64UrlToBytes(value) {
        var padded = value.replace(/-/g, '+').replace(/_/g, '/'),
            pad = padded.length % 4;

        if (pad) {
            padded += new Array(5 - pad).join('=');
        }

        var binary = window.atob(padded),
            bytes = new Uint8Array(binary.length),
            i;

        for (i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }

        return bytes;
    }

    /**
     * Encode an ArrayBuffer into a base64url string.
     *
     * @param {ArrayBuffer} buffer
     * @returns {String}
     */
    function bufferToBase64Url(buffer) {
        var bytes = new Uint8Array(buffer),
            binary = '',
            i;

        for (i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }

        return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    /**
     * Convert the server creation options into the shape navigator.credentials.create expects.
     *
     * @param {Object} publicKey
     * @returns {Object}
     */
    function toCreationOptions(publicKey) {
        var options = $.extend({}, publicKey);

        options.challenge = base64UrlToBytes(publicKey.challenge);
        options.user = $.extend({}, publicKey.user, {id: base64UrlToBytes(publicKey.user.id)});

        if (Array.isArray(publicKey.excludeCredentials)) {
            options.excludeCredentials = publicKey.excludeCredentials.map(function (descriptor) {
                return $.extend({}, descriptor, {id: base64UrlToBytes(descriptor.id)});
            });
        }

        return options;
    }

    /**
     * Serialize an attestation PublicKeyCredential for transport to the server.
     *
     * @param {PublicKeyCredential} credential
     * @returns {String}
     */
    function serializeAttestation(credential) {
        var response = credential.response,
            transports = typeof response.getTransports === 'function' ? response.getTransports() : [];

        return JSON.stringify({
            id: credential.id,
            type: credential.type,
            rawId: bufferToBase64Url(credential.rawId),
            response: {
                attestationObject: bufferToBase64Url(response.attestationObject),
                clientDataJSON: bufferToBase64Url(response.clientDataJSON),
                transports: transports
            }
        });
    }

    return function (config, element) {
        var $root = $(element),
            lastCredentialId = null,
            stepIndex = {welcome: 1, registration: 2, success: 3, recommendation: 4},
            createLabel = ($root.find('[data-role="create-label"]').text() || '').trim();

        /**
         * Reflect the current step on the progress rail.
         *
         * @param {String} step
         */
        function updateProgress(step) {
            var current = stepIndex[step] || 1;

            $root.find('[data-progress-step]').each(function () {
                var n = parseInt($(this).attr('data-progress-step'), 10);

                $(this).toggleClass('is-done', n < current).toggleClass('is-active', n === current);
            });
            $root.find('[data-progress-line]').each(function () {
                $(this).toggleClass('is-done', parseInt($(this).attr('data-progress-line'), 10) < current);
            });
        }

        /**
         * Choose between the close (x) control and the Sign out control. When
         * onboarding is mandatory the wizard cannot be dismissed until a passkey
         * exists, so only Sign out is offered.
         *
         * @param {String} step
         */
        function updateDismiss(step) {
            var created = step === 'success' || step === 'recommendation',
                lockOpen = config.mandatory && !created;

            $root.find('[data-role="close-btn"]').attr('hidden', lockOpen ? 'hidden' : null);
            $root.find('[data-role="signout-btn"]').attr('hidden', lockOpen ? null : 'hidden');
        }

        /**
         * Reveal a single wizard step and hide the others.
         *
         * @param {String} step
         */
        function showStep(step) {
            $root.find('[data-step]').attr('hidden', 'hidden');
            $root.find('[data-step="' + step + '"]').removeAttr('hidden');
            updateProgress(step);
            updateDismiss(step);
        }

        /**
         * POST helper that always includes the Admin form key.
         *
         * @param {String} url
         * @param {Object} data
         * @returns {jqXHR}
         */
        function post(url, data) {
            return $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: $.extend({form_key: config.formKey}, data)
            });
        }

        function setStatus(text, isError) {
            $root.find('[data-role="status"]')
                .text(text || '')
                .toggleClass('apk-wiz__status--error', !!isError);
        }

        function setWaiting(waiting) {
            $root.toggleClass('is-waiting', waiting);
            $root.find('[data-role="spinner"]').attr('hidden', waiting ? null : 'hidden');
            $root.find('[data-role="create-label"]').text(waiting ? config.labels.inProgress : createLabel);
            $root.find('[data-action="create"]').prop('disabled', waiting);
        }

        showStep('welcome');

        if (!window.PublicKeyCredential) {
            setStatus(config.labels.unsupported, true);
            $root.find('[data-action="create"]').prop('disabled', true);
        }

        // Close / Sign out are native links (data-role toggled by updateDismiss),
        // so they work even before this component initialises.

        $root.on('click', '[data-action="start"]', function () {
            showStep('registration');
        });

        $root.on('click', '[data-action="create"]', function () {
            setStatus('');
            setWaiting(true);

            post(config.registerOptionsUrl, {}).done(function (optionsResponse) {
                if (!optionsResponse || !optionsResponse.success) {
                    setWaiting(false);
                    setStatus((optionsResponse && optionsResponse.message) || config.labels.registerFailed, true);
                    return;
                }

                navigator.credentials.create({
                    publicKey: toCreationOptions(optionsResponse.publicKey)
                }).then(function (credential) {
                    return post(config.registerVerifyUrl, {credential: serializeAttestation(credential)});
                }).then(function (verifyResponse) {
                    setWaiting(false);

                    if (verifyResponse && verifyResponse.success) {
                        lastCredentialId = verifyResponse.credentialId;
                        showStep('success');
                        $root.find('[data-role="friendly-name"]').trigger('focus');
                        return;
                    }

                    setStatus((verifyResponse && verifyResponse.message) || config.labels.registerFailed, true);
                }).catch(function () {
                    setWaiting(false);
                    setStatus(config.labels.registerFailed, true);
                });
            }).fail(function () {
                setWaiting(false);
                setStatus(config.labels.registerFailed, true);
            });
        });

        $root.on('input', '[data-role="friendly-name"]', function () {
            $(this).removeClass('is-error');
            $root.find('[data-role="name-status"]').text('');
        });

        $root.on('click', '[data-action="save-name"]', function () {
            var $status = $root.find('[data-role="name-status"]'),
                $input = $root.find('[data-role="friendly-name"]'),
                name = ($input.val() || '').trim();

            if (!name) {
                $status.text(config.labels.nameRequired);
                $input.addClass('is-error');
                return;
            }

            post(config.renameUrl, {credential_id: lastCredentialId, friendly_name: name})
                .done(function (response) {
                    if (response && response.success) {
                        showStep('recommendation');
                        return;
                    }

                    $status.text((response && response.message) || config.labels.renameFailed);
                }).fail(function () {
                    $status.text(config.labels.renameFailed);
                });
        });

        $root.on('click', '[data-action="add-another"]', function () {
            lastCredentialId = null;
            setStatus('');
            $root.find('[data-role="name-status"]').text('');
            $root.find('[data-role="friendly-name"]').val('').removeClass('is-error');
            setWaiting(false);
            showStep('registration');
        });

        $root.on('click', '[data-action="finish"]', function () {
            window.location.assign(config.finishUrl);
        });
    };
});
