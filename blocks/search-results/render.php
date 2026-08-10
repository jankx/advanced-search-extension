<?php
/**
 * Server-side render for jankx-advanced-search/results.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content (empty for dynamic blocks).
 * @var WP_Block $block      Block instance.
 */

if (!defined('ABSPATH')) {
    exit;
}

$renderer = \Jankx\Extensions\AdvancedSearch\Renderer\SearchResultsRenderer::instance();

echo $renderer->render((array) $attributes, (string) $content, $block ?? null);
