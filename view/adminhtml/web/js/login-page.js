/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
define(['jquery'], function ($) {
    'use strict';

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

    function bufferToBase64Url(buffer) {
        var bytes = new Uint8Array(buffer),
            binary = '',
            i;

        for (i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }

        return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

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

    return function (config, element) {
        var $root = $(element),
            $form = $root.find('#login-form'),
            $status = $root.find('.adminpasskey-login__status'),
            $passkeyTriggers = $root.find('.adminpasskey-login__passkey-trigger'),
            THEME_KEY = 'apkLoginTheme',
            validThemes = ['light', 'system', 'dark'];

        function applyTheme(theme) {
            if (validThemes.indexOf(theme) === -1) {
                theme = 'system';
            }

            document.body.setAttribute('data-apk-theme', theme);
            $root.find('.adminpasskey-theme__btn').each(function () {
                $(this).attr('aria-pressed', $(this).data('theme') === theme ? 'true' : 'false');
            });

            try {
                window.localStorage.setItem(THEME_KEY, theme);
            } catch (e) {
                // Storage unavailable (private mode) — theme still applies for this session.
            }
        }

        (function initTheme() {
            var stored = null;

            try {
                stored = window.localStorage.getItem(THEME_KEY);
            } catch (e) {
                stored = null;
            }

            applyTheme(stored || 'system');
        })();

        $root.on('click', '.adminpasskey-theme__btn', function () {
            applyTheme($(this).data('theme'));
        });

        if (!config.passkeyEnabled || !window.PublicKeyCredential) {
            $passkeyTriggers.prop('disabled', true).addClass('is-disabled');
        }

        function readFormKey() {
            return config.formKey || $root.find('input[name="form_key"]').first().val() || '';
        }

        function setBusy(busy, message) {
            $passkeyTriggers.prop('disabled', busy);
            $status.text(message || '');
        }

        function activateView(view) {
            $root.find('.adminpasskey-login-page__tab').removeClass('is-active');
            $root.find('.adminpasskey-login-page__tab[data-view="' + view + '"]').addClass('is-active');
            $root.find('.adminpasskey-login-page__panel').removeClass('is-active');
            $root.find('.adminpasskey-login-page__panel--' + view).addClass('is-active');
        }

        $root.on('click', '.adminpasskey-login-page__tab', function () {
            activateView($(this).data('view'));
        });

        if (config.defaultView === 'passkey' && config.passkeyEnabled) {
            activateView('passkey');
        } else {
            activateView('password');
        }

        $root.on('click', '.adminpasskey-login__toggle-password', function () {
            var $toggle = $(this),
                $input = $toggle.siblings('input'),
                isPassword = $input.attr('type') === 'password';

            $input.attr('type', isPassword ? 'text' : 'password');
            $toggle.toggleClass('is-revealed', isPassword);
            $toggle.attr('aria-pressed', isPassword ? 'true' : 'false');
        });

        $passkeyTriggers.on('click', function () {
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
                    setBusy(false, config.labels.failed);
                });
            }).fail(function () {
                setBusy(false, config.labels.failed);
            });
        });
    };
});
