<?php

namespace Jankx\Extensions\AdvancedSearch;

use Jankx\Extensions\AbstractExtension;
use Jankx\Extensions\AdvancedSearch\Rest\AdvancedSearchController;

/**
 * Advanced Search Extension
 *
 * Replaces the default WordPress search results page with a rich layout:
 * a search header, category tabs (Trải nghiệm / Cẩm nang du lịch /
 * Ăn gì ở đâu / Tour & Dịch vụ), a result toolbar with sorting, and a
 * 4-column card grid with thumbnails, rating, price and duration.
 *
 * Renders through the `jankx-advanced-search/results` block, which the
 * child theme's `templates/search.html` embeds on the search template.
 *
 * @package Jankx\Extensions\AdvancedSearch
 */
class AdvancedSearchExtension extends AbstractExtension
{
    protected static $instance;

    public function __construct()
    {
        $this->register_autoloader();
        parent::__construct();
    }

    protected function register_autoloader(): void
    {
        spl_autoload_register(function ($class) {
            $prefix = 'Jankx\\Extensions\\AdvancedSearch\\';
            $base_dir = __DIR__ . '/src/';
            $len = strlen($prefix);

            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }

    public function init(): void
    {
        self::$instance = $this;
    }

    public static function get_instance(): ?self
    {
        return self::$instance;
    }

    public function register_hooks(): void
    {
        // Register blocks on every request so ServerSideRender works in the
        // editor and the search template can use the block on the frontend.
        add_action('init', [$this, 'register_blocks']);

        // REST API for the search results (same query logic as the page).
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Frontend assets only on the search results page.
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    public function register_blocks(): void
    {
        $blocksDir = $this->get_extension_path() . '/blocks';
        if (!is_dir($blocksDir)) {
            return;
        }

        foreach (glob($blocksDir . '/*', GLOB_ONLYDIR) as $blockDir) {
            if (!file_exists($blockDir . '/block.json')) {
                continue;
            }

            $blockJson = json_decode(file_get_contents($blockDir . '/block.json'), true);
            $blockName = $blockJson['name'] ?? '';

            if (!$blockName || \WP_Block_Type_Registry::get_instance()->is_registered($blockName)) {
                continue;
            }

            register_block_type_from_metadata($blockDir);
        }
    }

    public function register_rest_routes(): void
    {
        (new AdvancedSearchController())->register_routes();
    }

    public function enqueue_frontend_assets(): void
    {
        if (!is_search()) {
            return;
        }

        $ext_url = $this->get_extension_url();
        $ext_path = $this->get_extension_path();

        wp_enqueue_style(
            'jankx-advanced-search',
            $ext_url . '/assets/advanced-search.css',
            [],
            filemtime($ext_path . '/assets/advanced-search.css')
        );

        wp_enqueue_script(
            'jankx-advanced-search',
            $ext_url . '/assets/advanced-search.js',
            [],
            filemtime($ext_path . '/assets/advanced-search.js'),
            true
        );
    }
}
