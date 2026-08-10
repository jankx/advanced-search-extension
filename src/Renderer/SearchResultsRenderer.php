<?php

namespace Jankx\Extensions\AdvancedSearch\Renderer;

use Jankx\Extensions\AdvancedSearch\Search\SearchProvider;
use Jankx\Extensions\AdvancedSearch\Search\SearchQuery;

/**
 * Renders the `jankx-advanced-search/results` block.
 *
 * Reads the search request (`s`, `type`, `orderby`, `paged`), runs the
 * SearchQuery and outputs the template parts found in the extension's
 * `templates/` directory.
 */
class SearchResultsRenderer
{
    const DEFAULT_PER_PAGE = 12;

    protected static $instance;

    protected $provider;
    protected $context = [];

    public function __construct(?SearchProvider $provider = null)
    {
        $this->provider = $provider ?: SearchProvider::instance();
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function reset_instance(): void
    {
        self::$instance = null;
    }

    public function render(array $attributes = [], string $content = '', $block = null): string
    {
        $keyword = $this->request_keyword();
        $tab = $this->provider->normalize_tab($this->request('type', SearchProvider::TAB_ALL));
        $orderby = $this->provider->normalize_sort($this->request('orderby', SearchProvider::SORT_RECOMMENDED));
        $page = $this->request_page();
        $perPage = (int) ($attributes['perPage'] ?? self::DEFAULT_PER_PAGE);
        if ($perPage < 1) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $result = (new SearchQuery($this->provider))->run($keyword, $tab, $orderby, $page, $perPage);

        $this->context = array_merge($result, [
            'keyword' => $keyword,
            'tab' => $tab,
            'orderby' => $orderby,
            'per_page' => $perPage,
            'tabs' => $this->provider->get_tabs(),
            'sort_options' => $this->provider->get_sort_options(),
            'sort_label' => $this->provider->get_sort_label($orderby),
        ]);

        return $this->render_template('results');
    }

    /**
     * Include a template part with the current render context.
     */
    public function template(string $name): void
    {
        $file = $this->get_templates_dir() . '/' . $name . '.php';
        if (!file_exists($file)) {
            return;
        }

        $vars = $this->context;
        $vars['provider'] = $this->provider;
        $vars['renderer'] = $this;
        extract($vars);

        include $file;
    }

    /**
     * Render a single result card.
     */
    public function card(array $item): void
    {
        $file = $this->get_templates_dir() . '/card.php';
        if (!file_exists($file)) {
            return;
        }

        extract(['item' => $item, 'provider' => $this->provider]);
        include $file;
    }

    /**
     * Build a URL preserving the given query arguments on the home page.
     */
    public function url(array $args = []): string
    {
        return add_query_arg($args, home_url('/'));
    }

    /**
     * Page-number items for the pagination control.
     *
     * @return array[] Each item is `['type' => 'page'|'dots', ...]`.
     */
    public function pagination_items(int $page, int $totalPages): array
    {
        $items = [];
        $from = max(1, $page - 2);
        $to = min($totalPages, $page + 2);

        if ($from > 1) {
            $items[] = ['type' => 'page', 'page' => 1, 'label' => '1', 'current' => false];
        }
        if ($from > 2) {
            $items[] = ['type' => 'dots'];
        }
        for ($i = $from; $i <= $to; $i++) {
            $items[] = ['type' => 'page', 'page' => $i, 'label' => (string) $i, 'current' => $i === $page];
        }
        if ($to < $totalPages - 1) {
            $items[] = ['type' => 'dots'];
        }
        if ($to < $totalPages) {
            $items[] = ['type' => 'page', 'page' => $totalPages, 'label' => (string) $totalPages, 'current' => false];
        }

        return $items;
    }

    // ── Internal helpers ────────────────────────────────────────────────

    protected function render_template(string $name): string
    {
        ob_start();
        $this->template($name);

        return ob_get_clean();
    }

    protected function request(string $key, $default = '')
    {
        if (isset($_GET[$key])) {
            return sanitize_text_field($_GET[$key]);
        }

        return $default;
    }

    protected function request_keyword(): string
    {
        if (isset($_GET['s'])) {
            return sanitize_text_field($_GET['s']);
        }

        return (string) get_search_query();
    }

    protected function request_page(): int
    {
        $paged = (int) get_query_var('paged');
        if ($paged < 1 && isset($_GET['paged'])) {
            $paged = (int) $_GET['paged'];
        }

        return max(1, $paged);
    }

    protected function get_templates_dir(): string
    {
        return dirname(__DIR__, 2) . '/templates';
    }
}
