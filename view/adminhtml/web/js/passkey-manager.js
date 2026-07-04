/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 *
 * Admin passkey profile management actions.
 *
 * Wires the rename and revoke buttons on the "My Passkeys" page to the
 * authenticated self-service endpoints. Each action targets the credential row it
 * belongs to (via its data-entity-id), always includes the Admin form key, and
 * confirms destructive actions before posting. All labels and endpoint URLs come
 * from the block config; no inline JS and no hardcoded strings are used (CSP-safe).
 */
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element);

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

        $root.on('click', '[data-action="rename"]', function () {
            var $row = $(this).closest('[data-entity-id]'),
                entityId = $row.attr('data-entity-id'),
                current = $row.find('[data-role="friendly-name"]').text().trim(),
                name = window.prompt(config.labels.renamePrompt, current);

            if (name === null) {
                return;
            }

            name = name.trim();
            if (!name) {
                return;
            }

            post(config.renameUrl, {entity_id: entityId, friendly_name: name})
                .done(function (response) {
                    if (response && response.success) {
                        $row.find('[data-role="friendly-name"]').text(response.friendlyName || name);
                        return;
                    }

                    window.alert((response && response.message) || config.labels.failed);
                }).fail(function () {
                    window.alert(config.labels.failed);
                });
        });

        $root.on('click', '[data-action="revoke"]', function () {
            var $row = $(this).closest('[data-entity-id]'),
                entityId = $row.attr('data-entity-id');

            if (!window.confirm(config.labels.revokeConfirm)) {
                return;
            }

            post(config.revokeUrl, {entity_id: entityId})
                .done(function (response) {
                    if (response && response.success) {
                        window.location.reload();
                        return;
                    }

                    window.alert((response && response.message) || config.labels.failed);
                }).fail(function () {
                    window.alert(config.labels.failed);
                });
        });
    };
});
