<?php
/**
 * Advanced search results – card grid.
 *
 * @var array  $items
 * @var SearchResultsRenderer $renderer
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="jankx-advanced-search__grid">
    <?php foreach ($items as $item) : ?>
        <?php $renderer->card($item); ?>
    <?php endforeach; ?>
</div>
