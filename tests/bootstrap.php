<?php
/**
 * Advanced Search Extension - PHPUnit bootstrap.
 *
 * Loads:
 *  1. The Composer autoloader (dev deps: phpunit, brain/monkey, mockery).
 *  2. A PSR-4 fallback autoloader for the extension src.
 *  3. A small in-memory WordPress post/meta/terms store (Tests\Support\PostStore).
 *  4. A minimal WP_Query stub that understands the args used by SearchQuery
 *     (post_type arrays, s, paged, fields=ids, orderby date/meta_value_num/
 *     relevance, meta_key) plus WP_Error and WP_REST_* stubs.
 *  5. Brain Monkey aliases for the WP functions used by the extension.
 */

use Brain\Monkey;

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

// WordPress defines this constant; templates use it as a direct-access guard.
if (!defined('ABSPATH')) {
    define('ABSPATH', 'unit-test');
}

// 1. Composer autoloader (dev dependencies + PSR-4 for src and tests).
$composerAutoload = __DIR__ . '/../libs/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// 2. PSR-4 fallback autoloader for this extension (covers the case where the
//    Composer autoloader is not regenerated yet).
spl_autoload_register(function ($class) {
    $prefix = 'Jankx\\Extensions\\AdvancedSearch\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 3. WordPress class stubs.
if (!class_exists('WP_Post')) {
    class WP_Post
    {
        public $ID;
        public $post_type;
        public $post_status;
        public $post_title;
        public $post_excerpt;
        public $post_content;
        public $post_date;
        public $post_name;

        public function __construct($data = [])
        {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}

if (!class_exists('WP_Query')) {
    /**
     * Minimal WP_Query that filters the in-memory PostStore. Understands the
     * args used by SearchQuery: post_type (string or array), post_status, s,
     * posts_per_page, paged, fields (ids), orderby (date, meta_value_num,
     * relevance) with order and meta_key, and exposes found_posts/max_num_pages.
     */
    class WP_Query
    {
        public $posts = [];
        public $found_posts = 0;
        public $post_count = 0;
        public $max_num_pages = 0;

        protected $args = [];

        public function __construct($args = [])
        {
            $this->args = $args;

            $matched = [];
            foreach (\Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::all() as $id => $post) {
                if (!$this->matches($args, $post)) {
                    continue;
                }
                $matched[$id] = $post;
            }

            $orderby = $args['orderby'] ?? 'date';
            $order = strtoupper($args['order'] ?? 'DESC');
            $metaKey = $args['meta_key'] ?? null;
            $search = $args['s'] ?? '';

            uasort($matched, function ($a, $b) use ($orderby, $order, $metaKey, $search) {
                return $this->compare($a, $b, $orderby, $order, $metaKey, $search);
            });

            $this->found_posts = count($matched);

            $perPage = (int) ($args['posts_per_page'] ?? 10);
            $paged = max(1, (int) ($args['paged'] ?? 1));

            if ($perPage === -1) {
                $pagePosts = $matched;
            } else {
                $pagePosts = array_slice($matched, ($paged - 1) * $perPage, $perPage, true);
            }

            $this->max_num_pages = $perPage === -1 ? 1 : (int) ceil($this->found_posts / $perPage);

            if (($args['fields'] ?? '') === 'ids') {
                $this->posts = array_keys($pagePosts);
            } else {
                $this->posts = array_values($pagePosts);
            }
            $this->post_count = count($this->posts);
        }

        protected function matches(array $args, \WP_Post $post): bool
        {
            $postType = $args['post_type'] ?? 'post';
            if ($postType !== 'any') {
                $types = (array) $postType;
                if (!in_array($post->post_type, $types, true)) {
                    return false;
                }
            }

            $status = $args['post_status'] ?? 'publish';
            if (!empty($status) && $status !== 'any' && $post->post_status !== $status) {
                return false;
            }

            if (!empty($args['s'])) {
                $haystack = strtolower($post->post_title . ' ' . $post->post_excerpt . ' ' . $post->post_content);
                if (strpos($haystack, strtolower((string) $args['s'])) === false) {
                    return false;
                }
            }

            return true;
        }

        protected function compare(\WP_Post $a, \WP_Post $b, $orderby, $order, $metaKey, $search)
        {
            switch ($orderby) {
                case 'meta_value_num':
                    $va = $this->metaValue($a, $metaKey);
                    $vb = $this->metaValue($b, $metaKey);
                    break;

                case 'relevance':
                    $sa = $this->relevanceScore($a, (string) $search);
                    $sb = $this->relevanceScore($b, (string) $search);
                    if ($sa !== $sb) {
                        return $sa <=> $sb;
                    }
                    $va = strtotime($a->post_date);
                    $vb = strtotime($b->post_date);
                    return $vb <=> $va;

                case 'date':
                default:
                    $va = strtotime($a->post_date);
                    $vb = strtotime($b->post_date);
            }

            $cmp = $va <=> $vb;
            return $order === 'ASC' ? $cmp : -$cmp;
        }

        protected function metaValue(\WP_Post $post, $key): float
        {
            if (!$key) {
                return 0;
            }
            $value = \Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::meta($post->ID, $key);

            return is_numeric($value) ? (float) $value : 0;
        }

        protected function relevanceScore(\WP_Post $post, string $search): int
        {
            if ($search === '') {
                return 0;
            }
            $title = strtolower($post->post_title);
            $term = strtolower($search);
            if (strpos($title, $term) === 0) {
                return 0;
            }
            if (strpos($title, $term) !== false) {
                return 1;
            }

            return 2;
        }
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        protected $errors = [];
        protected $codes = [];

        public function __construct($code = '', $message = '', $data = '')
        {
            if ($code) {
                $this->errors[$code] = [$message];
                $this->codes[] = $code;
            }
        }

        public function get_error_code()
        {
            return $this->codes[0] ?? '';
        }

        public function get_error_message()
        {
            $code = $this->get_error_code();

            return $code ? ($this->errors[$code][0] ?? '') : '';
        }
    }
}

if (!class_exists('WP_REST_Server')) {
    class WP_REST_Server
    {
        const READABLE = 'GET';
        const CREATABLE = 'POST';
        const EDITABLE = 'PUT, PATCH';
        const DELETABLE = 'DELETE';
        const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
        const METHODS = 'GET, POST, PUT, PATCH, DELETE';
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        protected $params = [];

        public function __construct($method = 'GET', $route = '')
        {
        }

        public function set_param($key, $value)
        {
            $this->params[$key] = $value;
        }

        public function get_param($key)
        {
            return $this->params[$key] ?? null;
        }

        public function get_params()
        {
            return $this->params;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        protected $data;
        protected $status;
        protected $headers;

        public function __construct($data = [], $status = 200, $headers = [])
        {
            $this->data = $data;
            $this->status = $status;
            $this->headers = $headers;
        }

        public function get_data()
        {
            return $this->data;
        }

        public function get_status()
        {
            return $this->status;
        }

        public function get_headers()
        {
            return $this->headers;
        }
    }
}

// 4. Brain Monkey function stubs used by the advanced search tests.
if (!function_exists('advanced_search_test_stub_wp_functions')) {
    function advanced_search_test_stub_wp_functions()
    {
        $GLOBALS['__registered_filters'] = [];
        $GLOBALS['__registered_actions'] = [];
        $GLOBALS['__fired_actions'] = [];
        $GLOBALS['__routes'] = [];
        $GLOBALS['__search_query'] = '';
        $GLOBALS['__paged'] = 0;

        \Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::reset();

        Brain\Monkey\Functions\when('__')->returnArg();
        Brain\Monkey\Functions\when('_x')->returnArg();
        Brain\Monkey\Functions\when('esc_html__')->returnArg();
        Brain\Monkey\Functions\when('esc_html_x')->returnArg();
        Brain\Monkey\Functions\when('esc_html')->returnArg();
        Brain\Monkey\Functions\when('esc_attr')->returnArg();
        Brain\Monkey\Functions\when('esc_url')->returnArg();
        Brain\Monkey\Functions\when('esc_url_raw')->returnArg();
        Brain\Monkey\Functions\when('sanitize_text_field')->alias(function ($value) {
            return trim((string) $value);
        });
        Brain\Monkey\Functions\when('sanitize_key')->alias(function ($key) {
            $key = strtolower((string) $key);

            return preg_replace('/[^a-z0-9_\-]/', '', $key);
        });
        Brain\Monkey\Functions\when('selected')->alias(function ($selected, $current = true, $echo = true) {
            $result = (string) $selected === (string) $current ? " selected='selected'" : '';
            if ($echo) {
                echo $result;
            }

            return $result;
        });
        Brain\Monkey\Functions\when('number_format_i18n')->alias(function ($number, $decimals = 0) {
            return number_format($number, $decimals);
        });

        Brain\Monkey\Functions\when('add_filter')->alias(function ($tag, $callback, $priority = 10, $accepted = 1) {
            $GLOBALS['__registered_filters'][] = [
                'tag' => $tag,
                'callback' => $callback,
                'priority' => $priority,
                'accepted' => $accepted,
            ];

            return true;
        });

        Brain\Monkey\Functions\when('add_action')->alias(function ($tag, $callback, $priority = 10, $accepted = 1) {
            $GLOBALS['__registered_actions'][] = [
                'tag' => $tag,
                'callback' => $callback,
                'priority' => $priority,
                'accepted' => $accepted,
            ];

            return true;
        });

        Brain\Monkey\Functions\when('apply_filters')->alias(function ($tag, $value) {
            return $value;
        });

        Brain\Monkey\Functions\when('do_action')->alias(function ($tag, ...$args) {
            $GLOBALS['__fired_actions'][] = ['tag' => $tag, 'args' => $args];

            return null;
        });

        Brain\Monkey\Functions\when('register_rest_route')->alias(function ($namespace, $route, $args = []) {
            $GLOBALS['__routes'][] = ['namespace' => $namespace, 'route' => $route, 'args' => $args];

            return true;
        });

        Brain\Monkey\Functions\when('rest_ensure_response')->alias(function ($response) {
            if ($response instanceof \WP_REST_Response) {
                return $response;
            }

            return new \WP_REST_Response($response, 200);
        });

        Brain\Monkey\Functions\when('is_wp_error')->alias(function ($thing) {
            return $thing instanceof \WP_Error;
        });

        // Post store accessors.
        Brain\Monkey\Functions\when('get_post')->alias(function ($id = null) {
            $id = $id instanceof \WP_Post ? $id->ID : (int) $id;

            return \Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::get($id);
        });

        Brain\Monkey\Functions\when('get_posts')->alias(function ($args = []) {
            return (new \WP_Query($args))->posts;
        });

        Brain\Monkey\Functions\when('get_post_meta')->alias(function ($id, $key, $single = false) {
            $id = $id instanceof \WP_Post ? $id->ID : (int) $id;
            $value = \Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::meta($id, $key);

            if ($value === null) {
                return $single ? '' : [];
            }

            return $single ? $value : [$value];
        });

        Brain\Monkey\Functions\when('update_post_meta')->alias(function ($id, $key, $value) {
            $id = $id instanceof \WP_Post ? $id->ID : (int) $id;
            \Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::updateMeta($id, $key, $value);

            return true;
        });

        Brain\Monkey\Functions\when('get_the_title')->alias(function ($post = null) {
            if ($post instanceof \WP_Post) {
                return (string) $post->post_title;
            }
            $item = \Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::get((int) $post);

            return $item ? (string) $item->post_title : '';
        });

        Brain\Monkey\Functions\when('get_permalink')->alias(function ($post = null) {
            $id = $post instanceof \WP_Post ? $post->ID : (int) $post;

            return 'https://example.com/?p=' . $id;
        });

        Brain\Monkey\Functions\when('has_post_thumbnail')->alias(function ($post = null) {
            $id = $post instanceof \WP_Post ? $post->ID : (int) $post;

            return (bool) \Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::meta($id, '_thumbnail_id');
        });

        Brain\Monkey\Functions\when('get_the_post_thumbnail_url')->alias(function ($post = null, $size = 'post-thumbnail') {
            $id = $post instanceof \WP_Post ? $post->ID : (int) $post;
            if (!\Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::meta($id, '_thumbnail_id')) {
                return false;
            }

            return 'https://example.com/wp-content/uploads/img-' . $id . '.jpg';
        });

        Brain\Monkey\Functions\when('get_the_terms')->alias(function ($post, $taxonomy) {
            $id = $post instanceof \WP_Post ? $post->ID : (int) $post;
            $names = \Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore::terms($id, $taxonomy);

            if (empty($names)) {
                return false;
            }

            $terms = [];
            foreach ($names as $index => $name) {
                $terms[] = (object) [
                    'term_id' => $index + 1,
                    'name' => $name,
                    'slug' => strtolower(str_replace(' ', '-', $name)),
                ];
            }

            return $terms;
        });

        Brain\Monkey\Functions\when('wp_trim_words')->alias(function ($text, $num_words = 55, $more = null) {
            $text = trim(strip_tags((string) $text));
            $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

            if (count($words) <= $num_words) {
                return implode(' ', $words);
            }

            return implode(' ', array_slice($words, 0, $num_words)) . ($more !== null ? $more : '');
        });

        // URL / request helpers.
        Brain\Monkey\Functions\when('home_url')->alias(function ($path = '') {
            return 'https://example.com' . $path;
        });

        Brain\Monkey\Functions\when('add_query_arg')->alias(function ($args, $url = '') {
            $base = $url ?: 'https://example.com';
            $parsed = parse_url($base);
            $query = [];
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $query);
            }
            $query = array_merge($query, (array) $args);

            $qs = http_build_query($query);
            $path = $parsed['path'] ?? '/';

            return $parsed['scheme'] . '://' . $parsed['host'] . $path . ($qs !== '' ? '?' . $qs : '');
        });

        Brain\Monkey\Functions\when('get_search_query')->alias(function () {
            return (string) ($GLOBALS['__search_query'] ?? '');
        });

        Brain\Monkey\Functions\when('get_query_var')->alias(function ($var) {
            if ($var === 'paged') {
                return (int) ($GLOBALS['__paged'] ?? 0);
            }

            return '';
        });
    }
}
