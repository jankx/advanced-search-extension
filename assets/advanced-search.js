/**
 * Advanced Search – frontend interactions.
 * 
 * Includes: 
 * 1) Toolbar sorting auto-submit.
 * 2) Search block features: Auto-suggest (federated), post_type and taxonomy filters via REST API.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // 1. Toolbar sort
        document.querySelectorAll('.jankx-advanced-search__sort select').forEach(function (select) {
            select.addEventListener('change', function () {
                if (select.form) {
                    select.form.submit();
                }
            });
        });

        // 2. Advanced Search Blocks
        document.querySelectorAll('.jankx-search-form__container').forEach(initSearchBlock);
    });

    function initSearchBlock(container) {
        const data = container.dataset;
        if (!data.jankxSearch) return;

        const restUrl = data.restUrl;
        const nonce = data.nonce;
        const enableSuggestions = data.enableSuggestions === '1';
        const debounceMs = parseInt(data.suggestionDebounce || '300', 10);
        const minChars = parseInt(data.suggestionMinChars || '2', 10);
        const perGroup = data.suggestionPerGroup || '5';
        const defaultPt = data.defaultPostType;
        const filterTax = data.filterTaxonomy;

        const form = container.querySelector('form');
        const input = document.getElementById(data.searchInputId);
        const ptSelect = container.querySelector('[data-jankx-post-type-filter]');
        const taxSelect = container.querySelector('[data-jankx-taxonomy-filter]');
        const suggestionsBox = container.querySelector('.jankx-search-form__suggestions');

        if (!input) return;

        // --- Fetch filter data ---
        if (ptSelect || taxSelect) {
            let filterDataUrl = restUrl + '/filter-data';
            let urlParams = new URLSearchParams();
            if (defaultPt) urlParams.set('post_type', defaultPt);
            if (filterTax) urlParams.set('taxonomy', filterTax);

            if (urlParams.toString()) {
                filterDataUrl += '?' + urlParams.toString();
            }

            fetch(filterDataUrl)
                .then(r => r.json())
                .then(res => {
                    if (ptSelect && res.post_types) {
                        res.post_types.forEach(pt => {
                            const opt = document.createElement('option');
                            opt.value = pt.value;
                            opt.textContent = pt.label;
                            ptSelect.appendChild(opt);
                        });
                        if (defaultPt) ptSelect.value = defaultPt;
                    }
                    if (taxSelect && res.terms) {
                        res.terms.forEach(term => {
                            const opt = document.createElement('option');
                            opt.value = term.value;
                            opt.textContent = term.label + ' (' + term.count + ')';
                            taxSelect.appendChild(opt);
                        });
                    }
                })
                .catch(console.error);
        }

        // --- Suggestion UI ---
        if (enableSuggestions && suggestionsBox) {
            let debounceTimer;
            let targetPostType = defaultPt; // Will limit suggest to selected post type if changed

            if (ptSelect) {
                ptSelect.addEventListener('change', (e) => {
                    targetPostType = e.target.value;
                });
            }

            input.addEventListener('input', function () {
                const keyword = input.value.trim();

                clearTimeout(debounceTimer);

                if (keyword.length < minChars) {
                    closeSuggestions();
                    return;
                }

                suggestionsBox.innerHTML = '<div class="jankx-search-suggest-loading">Đang tìm kiếm...</div>';
                suggestionsBox.hidden = false;

                debounceTimer = setTimeout(() => {
                    let suggestUrl = restUrl + '/suggestions?s=' + encodeURIComponent(keyword) + '&per_group=' + perGroup;
                    if (targetPostType) suggestUrl += '&post_type=' + encodeURIComponent(targetPostType);

                    fetch(suggestUrl)
                        .then(r => r.json())
                        .then(res => renderSuggestions(res))
                        .catch(err => closeSuggestions());
                }, debounceMs);
            });

            // Close on click outside
            document.addEventListener('click', function (e) {
                if (!container.contains(e.target)) {
                    closeSuggestions();
                }
            });

            function renderSuggestions(res) {
                if (!res || !res.groups || res.groups.length === 0) {
                    suggestionsBox.innerHTML = '<div class="jankx-search-suggest-empty">Không tìm thấy kết quả phù hợp.</div>';
                    return;
                }

                let html = '';
                res.groups.forEach(group => {
                    html += '<div class="jankx-search-suggest-group">';
                    html += '<div class="jankx-search-suggest-group-header">' + group.post_type_label + '</div>';
                    group.items.forEach(item => {
                        html += '<a href="' + item.permalink + '" class="jankx-search-suggest-item">';
                        if (item.thumbnail) {
                            html += '<div class="jankx-search-suggest-thumb"><img src="' + item.thumbnail + '" alt="" loading="lazy"></div>';
                        }
                        html += '<div class="jankx-search-suggest-info">';
                        html += '<div class="jankx-search-suggest-title">' + item.title + '</div>';
                        if (item.tag) {
                            html += '<div class="jankx-search-suggest-tag">' + item.tag + '</div>';
                        }
                        html += '</div>';
                        html += '</a>';
                    });
                    html += '</div>';
                });

                if (res.search_url) {
                    html += '<a href="' + res.search_url + '" class="jankx-search-suggest-all">Xem tất cả ' + res.total + ' kết quả</a>';
                }

                suggestionsBox.innerHTML = html;
            }

            function closeSuggestions() {
                suggestionsBox.hidden = true;
                suggestionsBox.innerHTML = '';
            }
        }
    }
})();
