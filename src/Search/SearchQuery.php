<?php

namespace Jankx\Extensions\AdvancedSearch\Search;

/**
 * Runs the search query behind the advanced search results page.
 *
 * Query modes:
 *  - `wp`  – a normal WP_Query. Used for relevance/date ordering and for
 *            meta_value_num ordering when the tab's post types share a single
 *            price or rating meta key.
 *  - `php` – fetch every matching post id, then sort in PHP. Used when the
 *            tab mixes post types with different meta keys (e.g. the
 *            "Tất cả" tab sorting by price), because SQL cannot order by
 *            several meta keys at once.
 */
class SearchQuery
{
    protected $provider;

    public function __construct(SearchProvider $provider)
    {
        $this->provider = $provider;
    }

    public function run(string $keyword, string $tab, string $orderby, int $page, int $perPage): array
    {
        $postTypes = $this->provider->get_tab_post_types($tab);
        if (empty($postTypes)) {
            $postTypes = ['post'];
        }

        $args = [
            'post_type' => $postTypes,
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
        ];

        if ($keyword !== '') {
            $args['s'] = $keyword;
        }

        $sort = $this->resolve_sort($orderby, $postTypes, $keyword !== '');

        if ($sort['mode'] === 'php') {
            return $this->run_php_sort($args, $sort['compare'], $page, $perPage);
        }

        $query = new \WP_Query(array_merge($args, $sort['args']));
        $items = array_map([$this->provider, 'format_post'], $query->posts);

        return [
            'items' => $items,
            'total' => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'page' => min($page, max(1, (int) $query->max_num_pages)),
        ];
    }

    /**
     * Resolve the orderby request into query args or a PHP comparator.
     */
    protected function resolve_sort(string $orderby, array $postTypes, bool $hasKeyword): array
    {
        switch ($orderby) {
            case SearchProvider::SORT_PRICE_ASC:
            case SearchProvider::SORT_PRICE_DESC:
                $keys = $this->provider->price_meta_keys($postTypes);
                if (count($keys) === 1) {
                    return [
                        'mode' => 'wp',
                        'args' => [
                            'meta_key' => $keys[0],
                            'orderby' => 'meta_value_num',
                            'order' => $orderby === SearchProvider::SORT_PRICE_ASC ? 'ASC' : 'DESC',
                        ],
                    ];
                }

                $ascending = $orderby === SearchProvider::SORT_PRICE_ASC;
                return [
                    'mode' => 'php',
                    'compare' => function (\WP_Post $a, \WP_Post $b) use ($ascending) {
                        $pa = $this->provider->get_price($a);
                        $pb = $this->provider->get_price($b);

                        return $ascending ? $pa <=> $pb : $pb <=> $pa;
                    },
                ];

            case SearchProvider::SORT_RATING:
                $keys = $this->provider->rating_meta_keys($postTypes);
                if (count($keys) === 1) {
                    return [
                        'mode' => 'wp',
                        'args' => [
                            'meta_key' => $keys[0],
                            'orderby' => 'meta_value_num',
                            'order' => 'DESC',
                        ],
                    ];
                }

                return [
                    'mode' => 'php',
                    'compare' => function (\WP_Post $a, \WP_Post $b) {
                        return $this->provider->get_rating($b) <=> $this->provider->get_rating($a);
                    },
                ];

            case SearchProvider::SORT_DATE:
                return [
                    'mode' => 'wp',
                    'args' => ['orderby' => 'date', 'order' => 'DESC'],
                ];

            case SearchProvider::SORT_RECOMMENDED:
            default:
                if ($hasKeyword) {
                    return [
                        'mode' => 'wp',
                        'args' => ['orderby' => 'relevance'],
                    ];
                }

                return [
                    'mode' => 'wp',
                    'args' => ['orderby' => 'date', 'order' => 'DESC'],
                ];
        }
    }

    /**
     * Fetch all matching ids and sort/paginate in PHP. Used when the sort
     * needs a value that lives under different meta keys across post types.
     */
    protected function run_php_sort(array $args, callable $compare, int $page, int $perPage): array
    {
        $allArgs = $args;
        $allArgs['posts_per_page'] = -1;
        $allArgs['fields'] = 'ids';
        unset($allArgs['paged']);

        $ids = get_posts($allArgs);
        $posts = array_map('get_post', array_map('intval', (array) $ids));
        $posts = array_values(array_filter($posts, function ($post) {
            return $post instanceof \WP_Post;
        }));

        $total = count($posts);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        usort($posts, $compare);

        $pagePosts = array_slice($posts, ($page - 1) * $perPage, $perPage);
        $items = array_map([$this->provider, 'format_post'], $pagePosts);

        return [
            'items' => $items,
            'total' => $total,
            'total_pages' => $totalPages,
            'page' => $page,
        ];
    }
}
