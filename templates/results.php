<?php
/**
 * Advanced search results – wrapper.
 *
 * @var SearchProvider   $provider
 * @var SearchResultsRenderer $renderer
 * @var string           $keyword
 * @var string           $tab
 * @var string           $orderby
 * @var string           $sort_label
 * @var array            $tabs
 * @var array            $sort_options
 * @var array            $items
 * @var int              $total
 * @var int              $total_pages
 * @var int              $page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<main class="jankx-advanced-search" id="jankx-advanced-search">
    <div class="jankx-advanced-search__container">
        <?php $renderer->template('header'); ?>
        <?php $renderer->template('tabs'); ?>
        <?php $renderer->template('toolbar'); ?>

        <?php if (!empty($items)) : ?>
            <?php $renderer->template('grid'); ?>
            <?php $renderer->template('pagination'); ?>
        <?php else : ?>
            <?php $renderer->template('no-results'); ?>
        <?php endif; ?>
    </div>
</main>
