<?php

namespace Jankx\Extensions\AdvancedSearch\Rest;

use WP_REST_Request;
use WP_REST_Response;
use Jankx\Extensions\AdvancedSearch\Search\SearchProvider;
use Jankx\Extensions\AdvancedSearch\Search\SearchQuery;

/**
 * REST endpoints for the advanced search.
 *
 * Endpoints:
 *   GET /jankx/advanced-search/v1/results     – search results (tabbed, paged, filterable)
 *   GET /jankx/advanced-search/v1/suggestions – federated auto-suggest (multi-post-type)
 *   GET /jankx/advanced-search/v1/filter-data – available post types + taxonomy terms
 */
class AdvancedSearchController
{
    const NAMESPACE = 'jankx/advanced-search/v1';

    public function register_routes(): void
    {
        register_rest_route(self::NAMESPACE , '/results', [
            'methods' => 'GET',
            'callback' => [$this, 'get_results'],
            'permission_callback' => '__return_true',
            'args' => [
                's' => ['type' => 'string', 'default' => ''],
                'type' => ['type' => 'string', 'default' => SearchProvider::TAB_ALL],
                'orderby' => ['type' => 'string', 'default' => SearchProvider::SORT_RECOMMENDED],
                'page' => ['type' => 'integer', 'default' => 1],
                'per_page' => ['type' => 'integer', 'default' => 12],
                // filter-box params (compatible with dynamic-data-layout queryId context)
                'post_type' => ['type' => 'string', 'default' => ''],
                'tax_query' => ['type' => 'string', 'default' => ''], // JSON-encoded array
            ],
        ]);

        register_rest_route(self::NAMESPACE , '/suggestions', [
            'methods' => 'GET',
            'callback' => [$this, 'get_suggestions'],
            'permission_callback' => '__return_true',
            'args' => [
                's' => ['type' => 'string', 'default' => '', 'required' => true],
                'per_group' => ['type' => 'integer', 'default' => 5],
                'post_type' => ['type' => 'string', 'default' => ''], // limit to one post type
            ],
        ]);

        register_rest_route(self::NAMESPACE , '/filter-data', [
            'methods' => 'GET',
            'callback' => [$this, 'get_filter_data'],
            'permission_callback' => '__return_true',
            'args' => [
                'post_type' => ['type' => 'string', 'default' => ''],
                'taxonomy' => ['type' => 'string', 'default' => ''],
            ],
        ]);
    }

    // ── /results ─────────────────────────────────────────────────────────

    public function get_results(WP_REST_Request $request): WP_REST_Response
    {
        $provider = SearchProvider::instance();
        $tab = $provider->normalize_tab((string) $request->get_param('type'));
        $orderby = $provider->normalize_sort((string) $request->get_param('orderby'));
        $page = max(1, (int) $request->get_param('page'));
        $perPage = min(max(1, (int) $request->get_param('per_page')), 24);
        $keyword = trim((string) $request->get_param('s'));

        $postTypeFilter = sanitize_key((string) $request->get_param('post_type'));
        $taxQueryRaw = (string) $request->get_param('tax_query');
        $taxQuery = [];
        if ($taxQueryRaw !== '') {
            $decoded = json_decode($taxQueryRaw, true);
            if (is_array($decoded)) {
                $taxQuery = $decoded;
            }
        }

        $result = (new SearchQuery($provider))->run(
            $keyword,
            $tab,
            $orderby,
            $page,
            $perPage,
            $postTypeFilter,
            $taxQuery
        );

        return new WP_REST_Response(array_merge($result, [
            'keyword' => $keyword,
            'tab' => $tab,
            'orderby' => $orderby,
            'tabs' => $provider->get_tabs(),
            'sort_options' => $provider->get_sort_options(),
        ]), 200);
    }

    // ── /suggestions ─────────────────────────────────────────────────────

    /**
     * Federated auto-suggest: query each configured post type independently
     * and return grouped results — one group per post type that has hits.
     *
     * Designed to replace the smart-search block's suggestion dropdown.
     * Compatible with the dynamic-data-layout queryId context so the results
     * page can be refreshed after the user picks a suggestion.
     */
    public function get_suggestions(WP_REST_Request $request): WP_REST_Response
    {
        $keyword = trim((string) $request->get_param('s'));
        $perGroup = min(max(1, (int) $request->get_param('per_group')), 10);
        $postTypeParam = sanitize_key((string) $request->get_param('post_type'));

        if ($keyword === '') {
            return new WP_REST_Response(['groups' => [], 'total' => 0, 'keyword' => ''], 200);
        }

        $provider = SearchProvider::instance();
        $allTypes = $provider->get_tab_post_types(SearchProvider::TAB_ALL);
        $postTypes = ($postTypeParam !== '' && in_array($postTypeParam, $allTypes, true))
            ? [$postTypeParam]
            : $allTypes;

        $groups = [];
        $total = 0;

        foreach ($postTypes as $postType) {
            $config = $provider->get_post_type_config($postType);
            $posts = get_posts([
                'post_type' => $postType,
                'post_status' => 'publish',
                's' => $keyword,
                'posts_per_page' => $perGroup,
                'suppress_filters' => false,
            ]);

            if (empty($posts)) {
                continue;
            }

            $items = array_map(function ($post) use ($provider) {
                return [
                    'id' => (int) $post->ID,
                    'title' => get_the_title($post),
                    'permalink' => get_permalink($post),
                    'thumbnail' => $provider->get_thumbnail($post),
                    'tag' => $provider->get_tag($post),
                    'excerpt' => $provider->get_excerpt($post),
                ];
            }, $posts);

            $groups[] = [
                'post_type' => $postType,
                'post_type_label' => $config['label'],
                'items' => $items,
            ];

            $total += count($items);
        }

        // All-results search link appended so the dropdown can show "See all X results"
        $search_url = add_query_arg(['s' => $keyword], home_url('/'));

        return new WP_REST_Response([
            'groups' => $groups,
            'total' => $total,
            'keyword' => $keyword,
            'search_url' => $search_url,
        ], 200);
    }

    // ── /filter-data ─────────────────────────────────────────────────────

    /**
     * Returns the configured post types and taxonomy terms for the active post type.
     *
     * Used by the search-form block's post-type selector and taxonomy filter boxes.
     * Compatible with advanced-filter (jankx/advanced-filter + jankx/advanced-filters)
     * through the shared queryId context.
     */
    public function get_filter_data(WP_REST_Request $request): WP_REST_Response
    {
        $provider = SearchProvider::instance();
        $postTypeParam = sanitize_key((string) $request->get_param('post_type'));
        $taxParam = sanitize_key((string) $request->get_param('taxonomy'));

        // ── Post types ───────────────────────────────────────────────────
        $postTypeOptions = [];
        foreach ($provider->get_tab_post_types(SearchProvider::TAB_ALL) as $pt) {
            $config = $provider->get_post_type_config($pt);
            $obj = get_post_type_object($pt);
            $postTypeOptions[] = [
                'value' => $pt,
                'label' => $obj ? (string) $obj->labels->singular_name : $config['label'],
                'taxonomies' => $config['tag_taxonomies'],
            ];
        }

        // ── Taxonomies for the selected post type ────────────────────────
        $taxonomyOptions = [];
        $termOptions = [];

        if ($postTypeParam !== '') {
            $config = $provider->get_post_type_config($postTypeParam);
            $taxonomies = $config['tag_taxonomies'] ?? [];

            foreach ($taxonomies as $tax) {
                $taxObj = get_taxonomy($tax);
                if (!$taxObj) {
                    continue;
                }
                $taxonomyOptions[] = [
                    'value' => $tax,
                    'label' => $taxObj->label,
                ];
            }

            // Terms for the requested taxonomy (or the first available one)
            $activeTax = ($taxParam !== '' && in_array($taxParam, $taxonomies, true))
                ? $taxParam
                : ($taxonomies[0] ?? '');

            if ($activeTax !== '') {
                $terms = get_terms([
                    'taxonomy' => $activeTax,
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'number' => 200,
                ]);

                if (!is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $termOptions[] = [
                            'value' => $term->slug,
                            'label' => $term->name,
                            'count' => (int) $term->count,
                            'parent' => (int) $term->parent,
                        ];
                    }
                }
            }
        }

        return new WP_REST_Response([
            'post_types' => $postTypeOptions,
            'taxonomies' => $taxonomyOptions,
            'terms' => $termOptions,
        ], 200);
    }
}
