/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 *
 * Admin login-page passkey enhancement.
 *
 * Progressively enhances the native Admin login form: when the browser supports
 * WebAuthn it injects a "Sign in with a passkey" button that runs a discoverable
 * credential assertion and, on success, redirects to the URL returned by the
 * server. The native username/password (+ Magento 2FA) form is never altered, so
 * it remains the fallback at all times. No inline JS is used (CSP-safe).
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
     * Convert the server publicKey options into the shape navigator.credentials.get expects.
     *
     * @param {Object} publicKey
     * @returns {Object}
     */
    function toRequestOptions(publicKey) {
        var options = $.extend({}, publicKey);

        options.challenge = base64UrlToBytes(publicKey.challenge);

        if (Array.isArray(publicKey.allowCredentials)) {
            options.allowCredentials = publicKey.allowCredentials.map(function (descriptor) {
                return $.extend({}, descriptor, {id: base64UrlToBytes(descriptor.id)});
            });
        }

        return options;
    }

    /**
     * Serialize an assertion PublicKeyCredential for transport to the server.
     *
     * @param {PublicKeyCredential} credential
     * @returns {String}
     */
    function serializeAssertion(credential) {
        var response = credential.response;

        return JSON.stringify({
            id: credential.id,
            type: credential.type,
            rawId: bufferToBase64Url(credential.rawId),
            response: {
                authenticatorData: bufferToBase64Url(response.authenticatorData),
                clientDataJSON: bufferToBase64Url(response.clientDataJSON),
                signature: bufferToBase64Url(response.signature),
                userHandle: response.userHandle ? bufferToBase64Url(response.userHandle) : null
            }
        });
    }

    /**
     * Read the current Admin form key from the login form so the POST matches the
     * session's expected key.
     *
     * @returns {String}
     */
    function readFormKey() {
        return $('input[name="form_key"]').first().val() || '';
    }

    return function (config, element) {
        var $root = $(element),
            $form = $('#login-form');

        // Require both WebAuthn support and a login form to enhance.
        if (!window.PublicKeyCredential || !$form.length) {
            return;
        }

        var $button = $('<button></button>', {
            type: 'button',
            'class': 'action-secondary adminpasskey-login__button',
            text: config.labels.button
        });
        var $status = $('<p></p>', {'class': 'adminpasskey-login__status', 'aria-live': 'polite'});

        $root.append($button).append($status);

        function setBusy(busy, message) {
            $button.prop('disabled', busy);
            $status.text(message || '');
        }

        $button.on('click', function () {
            setBusy(true, config.labels.inProgress);

            $.ajax({
                url: config.optionsUrl,
                type: 'POST',
                dataType: 'json',
                data: {form_key: readFormKey()}
            }).done(function (optionsResponse) {
                if (!optionsResponse || !optionsResponse.success) {
                    setBusy(false, (optionsResponse && optionsResponse.message) || config.labels.failed);
                    return;
                }

                navigator.credentials.get({
                    publicKey: toRequestOptions(optionsResponse.publicKey)
                }).then(function (credential) {
                    return $.ajax({
                        url: config.verifyUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            form_key: readFormKey(),
                            assertion: serializeAssertion(credential),
                            redirect_url: config.redirectUrl || ''
                        }
                    });
                }).then(function (verifyResponse) {
                    if (verifyResponse && verifyResponse.success && verifyResponse.redirectUrl) {
                        window.location.assign(verifyResponse.redirectUrl);
                        return;
                    }

                    setBusy(false, (verifyResponse && verifyResponse.message) || config.labels.failed);
                }).catch(function () {
                    // Includes user cancellation / no credential: fall back silently to password.
                    setBusy(false, config.labels.failed);
                });
            }).fail(function () {
                setBusy(false, config.labels.failed);
            });
        });
    };
});
