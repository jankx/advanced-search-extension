<?php

namespace Jankx\Extensions\AdvancedSearch\Rest;

use WP_REST_Request;
use WP_REST_Response;
use Jankx\Extensions\AdvancedSearch\Search\SearchProvider;
use Jankx\Extensions\AdvancedSearch\Search\SearchQuery;

/**
 * REST endpoints for the advanced search results.
 *
 * Endpoints:
 *   GET /jankx/advanced-search/v1/results – search results for a keyword,
 *       scoped to a tab (`type`) and ordered with `orderby`, paginated.
 */
class AdvancedSearchController
{
    const NAMESPACE = 'jankx/advanced-search/v1';

    public function register_routes(): void
    {
        register_rest_route(self::NAMESPACE, '/results', [
            'methods' => 'GET',
            'callback' => [$this, 'get_results'],
            'permission_callback' => '__return_true',
            'args' => [
                's' => ['type' => 'string', 'default' => ''],
                'type' => ['type' => 'string', 'default' => SearchProvider::TAB_ALL],
                'orderby' => ['type' => 'string', 'default' => SearchProvider::SORT_RECOMMENDED],
                'page' => ['type' => 'integer', 'default' => 1],
                'per_page' => ['type' => 'integer', 'default' => 12],
            ],
        ]);
    }

    public function get_results(WP_REST_Request $request): WP_REST_Response
    {
        $provider = SearchProvider::instance();
        $tab = $provider->normalize_tab((string) $request->get_param('type'));
        $orderby = $provider->normalize_sort((string) $request->get_param('orderby'));
        $page = max(1, (int) $request->get_param('page'));
        $perPage = min(max(1, (int) $request->get_param('per_page')), 24);
        $keyword = trim((string) $request->get_param('s'));

        $result = (new SearchQuery($provider))->run($keyword, $tab, $orderby, $page, $perPage);

        return new WP_REST_Response(array_merge($result, [
            'keyword' => $keyword,
            'tab' => $tab,
            'orderby' => $orderby,
            'tabs' => $provider->get_tabs(),
            'sort_options' => $provider->get_sort_options(),
        ]), 200);
    }
}
