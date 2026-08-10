/**
 * Advanced Search – frontend interactions.
 *
 * Auto-submits the toolbar sort dropdown when its value changes.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.jankx-advanced-search__sort select').forEach(function (select) {
            select.addEventListener('change', function () {
                if (select.form) {
                    select.form.submit();
                }
            });
        });
    });
})();
