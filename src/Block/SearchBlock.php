<?php

namespace Jankx\Extensions\AdvancedSearch\Block;

/**
 * Renderer for the jankx-advanced-search/search block.
 *
 * All render logic lives here as a plain PHP class so it is easy to
 * extend, unit-test and maintain.  The class is registered as the
 * block's render_callback in AdvancedSearchExtension::register_blocks().
 */
class SearchBlock
{
    // ── Singleton used by register_callback ─────────────────────────────

    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // ── Public render callback ───────────────────────────────────────────

    /**
     * Render callback registered via register_block_type().
     *
     * @param array    $attributes Block attributes.
     * @param string   $content    Inner content (unused – dynamic block).
     * @param \WP_Block $block     Block instance.
     */
    public function render(array $attributes, string $content, \WP_Block $block): string
    {
        $attributes = \wp_parse_args($attributes, [
            'label' => \__('Tìm kiếm', 'jankx'),
            'buttonText' => \__('Tìm kiếm', 'jankx'),
            'buttonPosition' => 'button-outside',
            'buttonUseIcon' => false,
            'showLabel' => true,
            'enableSuggestions' => true,
            'suggestionMinChars' => 2,
            'suggestionDebounce' => 300,
            'suggestionPerGroup' => 5,
            'showPostTypeFilter' => false,
            'showTaxonomyFilter' => false,
            'defaultPostType' => '',
            'filterTaxonomy' => '',
            'queryId' => '',
            'searchResultsUrl' => '',
            'iconName' => '',
            'iconSet' => 'material',
            'iconSize' => '24px',
            'iconColor' => '',
            'iconBackgroundColor' => '',
            'iconBackgroundValue' => '',
            'iconPadding' => '8px',
            'iconBorderRadius' => '4px',
        ]);

        $input_id = \wp_unique_id('jankx-search-input-');
        $classnames = $this->classnames($attributes);
        $show_label = !empty($attributes['showLabel']);
        $use_icon = !empty($attributes['buttonUseIcon']);
        $show_button = ($attributes['buttonPosition'] !== 'no-button');
        $is_button_inside = ($attributes['buttonPosition'] === 'button-inside');
        $query_params = is_array($attributes['query'] ?? null) ? $attributes['query'] : [];
        $inline = $this->inline_styles($attributes);
        $color_cls = $this->color_classes($attributes);
        $typo_cls = $this->typography_classes($attributes);
        $border_color_cls = $this->border_color_classes($attributes);

        $label_html = $this->label_markup($attributes, $input_id, $inline, $show_label, $typo_cls);
        $input_html = $this->input_markup($attributes, $input_id, $inline, $is_button_inside, $typo_cls, $border_color_cls);
        $hidden_html = $this->hidden_params_markup($query_params);
        $button_html = $show_button
            ? $this->button_markup($attributes, $inline, $use_icon, $is_button_inside, $color_cls, $typo_cls, $border_color_cls)
            : '';

        // Render inner blocks (icon-picker, svg-icon, advanced-image-box) if present
        $inner_blocks_html = '';
        if (!empty($block->inner_blocks) && count($block->inner_blocks) > 0) {
            foreach ($block->inner_blocks as $inner_block) {
                $inner_blocks_html .= $inner_block->render();
            }
        }

        $field_markup = $this->field_wrapper($inline, $is_button_inside, $border_color_cls, $input_html . $hidden_html . $button_html);

        // Append inner blocks after the field wrapper (e.g. custom icon blocks)
        if (!empty($inner_blocks_html)) {
            $field_markup .= '<div class="jankx-search-form__inner-blocks">' . $inner_blocks_html . '</div>';
        }

        $filter_markup = $this->filter_boxes_markup($attributes, $input_id);
        $suggestion_markup = $this->suggestion_container_markup($attributes);

        $action = !empty($attributes['searchResultsUrl'])
            ? \esc_url($attributes['searchResultsUrl'])
            : \esc_url(\home_url('/'));

        $wrapper_attrs = \get_block_wrapper_attributes(['class' => $classnames]);
        $data_attrs = $this->data_attributes($attributes, $input_id);

        return sprintf(
            '<div class="jankx-search-form__container" %s>'
            . '<form role="search" method="get" action="%s" %s>'
            . '%s%s%s%s'
            . '</form>'
            . '</div>',
            $data_attrs,
            $action,
            $wrapper_attrs,
            $label_html,
            $filter_markup,
            $field_markup,
            $suggestion_markup
        );
    }

    // ── Label ────────────────────────────────────────────────────────────

    private function label_markup(array $a, string $input_id, array $inline, bool $show_label, string $typo_cls): string
    {
        $inner = empty($a['label']) ? \__('Tìm kiếm', 'jankx') : \wp_kses_post($a['label']);
        $tag = new \WP_HTML_Tag_Processor(sprintf('<label %s>%s</label>', $inline['label'], $inner));
        if ($tag->next_tag()) {
            $tag->set_attribute('for', $input_id);
            $tag->add_class('wp-block-search__label');
            if ($show_label && !empty($a['label'])) {
                if ($typo_cls) {
                    $tag->add_class($typo_cls);
                }
            } else {
                $tag->add_class('screen-reader-text');
            }
        }

        return (string) $tag;
    }

    // ── Input ────────────────────────────────────────────────────────────

    private function input_markup(array $a, string $input_id, array $inline, bool $is_inside, string $typo_cls, string $border_cls): string
    {
        $tag = new \WP_HTML_Tag_Processor(sprintf('<input type="search" name="s" required %s/>', $inline['input']));
        $classes = ['wp-block-search__input'];
        if (!$is_inside && $border_cls) {
            $classes[] = $border_cls;
        }
        if ($typo_cls) {
            $classes[] = $typo_cls;
        }

        if ($tag->next_tag()) {
            $tag->add_class(implode(' ', $classes));
            $tag->set_attribute('id', $input_id);
            $tag->set_attribute('value', \get_search_query());
            if (isset($a['placeholder'])) {
                $tag->set_attribute('placeholder', $a['placeholder']);
            }
            if (!empty($a['enableSuggestions'])) {
                $tag->set_attribute('autocomplete', 'off');
                $tag->set_attribute('aria-autocomplete', 'list');
                $tag->set_attribute('aria-haspopup', 'listbox');
                $tag->set_attribute('aria-expanded', 'false');
            }
        }

        return (string) $tag;
    }

    // ── Hidden query params ──────────────────────────────────────────────

    private function hidden_params_markup(array $params): string
    {
        $html = '';
        foreach ($params as $name => $value) {
            $html .= sprintf(
                '<input type="hidden" name="%s" value="%s" />',
                \esc_attr($name),
                \esc_attr($value)
            );
        }

        return $html;
    }

    // ── Button ───────────────────────────────────────────────────────────

    private function button_markup(array $a, array $inline, bool $use_icon, bool $is_inside, string $color_cls, string $typo_cls, string $border_cls): string
    {
        $classes = ['wp-block-search__button'];
        $inner = '';

        if ($color_cls) {
            $classes[] = $color_cls;
        }
        if ($typo_cls) {
            $classes[] = $typo_cls;
        }
        if (!$is_inside && $border_cls) {
            $classes[] = $border_cls;
        }

        if (!$use_icon) {
            $inner = \wp_kses_post($a['buttonText'] ?? '');
        } else {
            $classes[] = 'has-icon';

            $icon_name = $a['iconName'] ?? '';
            $icon_set = $a['iconSet'] ?? 'material';
            $icon_size = $a['iconSize'] ?? '24px';
            $icon_color = $a['iconColor'] ?? '';
            $icon_bg_value = $a['iconBackgroundValue'] ?? '';
            $icon_padding = $a['iconPadding'] ?? '8px';
            $icon_border_radius = $a['iconBorderRadius'] ?? '4px';

            if (!empty($icon_name)) {
                $inner = $this->render_custom_icon($icon_name, $icon_set, $icon_size, $icon_color, $icon_bg_value, $icon_padding, $icon_border_radius);
            } else {
                $inner = '<svg class="search-icon" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">'
                    . '<path d="M13 5c-3.3 0-6 2.7-6 6 0 1.4.5 2.7 1.3 3.7l-3.8 3.8 1.1 1.1 3.8-3.8c1 .8 2.3 1.3 3.7 1.3 3.3 0 6-2.7 6-6S16.3 5 13 5zm0 10.5c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5 4.5 2 4.5 4.5-2 4.5-4.5 4.5z"></path>'
                    . '</svg>';
            }
        }

        $el_class = \wp_theme_get_element_class_name('button');
        if ($el_class) {
            $classes[] = $el_class;
        }

        $icon_style = '';
        if ($use_icon && !empty($icon_bg_value)) {
            $styles = [];
            $styles[] = sprintf('background-color: %s;', \esc_attr($icon_bg_value));
            if (!empty($icon_padding)) {
                $styles[] = sprintf('padding: %s;', \esc_attr($icon_padding));
            }
            if (!empty($icon_border_radius)) {
                $styles[] = sprintf('border-radius: %s;', \esc_attr($icon_border_radius));
            }
            if (!empty($styles)) {
                $icon_style = sprintf(' style="%s"', \esc_attr(\safecss_filter_attr(implode(' ', $styles))));
            }
        }

        $tag = new \WP_HTML_Tag_Processor(sprintf('<button type="submit" %s>%s</button>', $inline['button'] . $icon_style, $inner));
        if ($tag->next_tag()) {
            $tag->add_class(implode(' ', $classes));
            $tag->set_attribute('aria-label', \wp_strip_all_tags($a['buttonText'] ?? ''));
        }

        return (string) $tag;
    }

    /**
     * Render a custom icon from the icon picker.
     *
     * @param string $name     Icon name
     * @param string $set      Icon set (material|fontawesome|dashicons)
     * @param string $size     Icon size (CSS value)
     * @param string $color    Icon color (CSS value)
     * @param string $bg_value Background color value
     * @param string $padding  Padding around icon
     * @param string $radius   Border radius
     * @return string HTML markup
     */
    private function render_custom_icon(string $name, string $set, string $size, string $color, string $bg_value, string $padding, string $radius): string
    {
        $style_parts = [];
        $style_parts[] = sprintf('font-size: %s;', \esc_attr($size));
        $style_parts[] = 'line-height: 1;';
        $style_parts[] = 'display: inline-flex;';
        $style_parts[] = 'align-items: center;';
        $style_parts[] = 'justify-content: center;';

        if (!empty($color)) {
            $style_parts[] = sprintf('color: %s;', \esc_attr($color));
        }

        $style = \safecss_filter_attr(implode(' ', $style_parts));

        switch ($set) {
            case 'fontawesome':
                return sprintf('<i class="fas fa-%s" style="%s" aria-hidden="true"></i>', \esc_attr($name), \esc_attr($style));

            case 'dashicons':
                return sprintf('<span class="dashicons dashicons-%s" style="%s" aria-hidden="true"></span>', \esc_attr($name), \esc_attr($style));

            case 'material':
            default:
                return sprintf('<span class="material-icons" style="%s" aria-hidden="true">%s</span>', \esc_attr($style), \esc_html($name));
        }
    }

    // ── Field wrapper ────────────────────────────────────────────────────

    private function field_wrapper(array $inline, bool $is_inside, string $border_cls, string $inner_html): string
    {
        $classes = ['wp-block-search__inside-wrapper'];
        if ($is_inside && $border_cls) {
            $classes[] = $border_cls;
        }

        return sprintf(
            '<div class="%s" %s>%s</div>',
            \esc_attr(implode(' ', $classes)),
            $inline['wrapper'],
            $inner_html
        );
    }

    // ── Post type + Taxonomy filter boxes ───────────────────────────────

    private function filter_boxes_markup(array $a, string $input_id): string
    {
        $html = '';

        if (!empty($a['showPostTypeFilter'])) {
            $html .= sprintf(
                '<div class="jankx-search-form__post-type-filter">'
                . '<label class="screen-reader-text" for="%1$s-post-type">%2$s</label>'
                . '<select id="%1$s-post-type" class="jankx-search-form__post-type-select" name="post_type" data-jankx-post-type-filter="1">'
                . '<option value="">%3$s</option>'
                . '</select>'
                . '</div>',
                \esc_attr($input_id),
                \esc_html__('Loại nội dung', 'jankx'),
                \esc_html__('— Loại nội dung —', 'jankx')
            );
        }

        if (!empty($a['showTaxonomyFilter'])) {
            $filter_tax = \sanitize_key($a['filterTaxonomy'] ?? '');
            $html .= sprintf(
                '<div class="jankx-search-form__taxonomy-filter">'
                . '<label class="screen-reader-text" for="%1$s-taxonomy">%2$s</label>'
                . '<select id="%1$s-taxonomy" class="jankx-search-form__taxonomy-select" name="taxonomy_filter" data-jankx-taxonomy-filter="%4$s">'
                . '<option value="">%3$s</option>'
                . '</select>'
                . '</div>',
                \esc_attr($input_id),
                \esc_html__('Danh mục', 'jankx'),
                \esc_html__('— Danh mục —', 'jankx'),
                \esc_attr($filter_tax)
            );
        }

        return $html;
    }

    // ── Suggestion dropdown (populated by JS) ────────────────────────────

    private function suggestion_container_markup(array $a): string
    {
        if (empty($a['enableSuggestions'])) {
            return '';
        }

        return '<div class="jankx-search-form__suggestions" role="listbox" '
            . 'aria-label="' . \esc_attr__('Gợi ý tìm kiếm', 'jankx') . '" '
            . 'aria-live="polite" hidden></div>';
    }

    // ── data-* attributes for the JS module ─────────────────────────────

    private function data_attributes(array $a, string $input_id): string
    {
        return implode(' ', [
            'data-jankx-search="1"',
            sprintf('data-rest-url="%s"', \esc_attr(\rest_url('jankx/advanced-search/v1'))),
            sprintf('data-nonce="%s"', \esc_attr(\wp_create_nonce('wp_rest'))),
            sprintf('data-enable-suggestions="%s"', empty($a['enableSuggestions']) ? '0' : '1'),
            sprintf('data-suggestion-min-chars="%d"', (int) ($a['suggestionMinChars'] ?? 2)),
            sprintf('data-suggestion-debounce="%d"', (int) ($a['suggestionDebounce'] ?? 300)),
            sprintf('data-suggestion-per-group="%d"', (int) ($a['suggestionPerGroup'] ?? 5)),
            sprintf('data-default-post-type="%s"', \esc_attr($a['defaultPostType'] ?? '')),
            sprintf('data-filter-taxonomy="%s"', \esc_attr($a['filterTaxonomy'] ?? '')),
            sprintf('data-query-id="%s"', \esc_attr($a['queryId'] ?? '')),
            sprintf('data-search-input-id="%s"', \esc_attr($input_id)),
        ]);
    }

    // ── CSS helpers (ported from core/search) ───────────────────────────

    private function classnames(array $a): string
    {
        $cls = [];
        $pos = $a['buttonPosition'] ?? 'button-outside';

        if ($pos === 'button-inside') {
            $cls[] = 'wp-block-search__button-inside';
        }
        if ($pos === 'button-outside') {
            $cls[] = 'wp-block-search__button-outside';
        }
        if ($pos === 'no-button') {
            $cls[] = 'wp-block-search__no-button';
        }
        if ($pos === 'button-only') {
            $cls[] = 'wp-block-search__button-outside';
        } // fallback

        if (!empty($a['buttonUseIcon']) && $pos !== 'no-button') {
            $cls[] = 'wp-block-search__icon-button';
        } elseif (empty($a['buttonUseIcon']) && $pos !== 'no-button') {
            $cls[] = 'wp-block-search__text-button';
        }

        return implode(' ', $cls);
    }

    private function typography_classes(array $a): string
    {
        $cls = [];
        if (!empty($a['fontSize'])) {
            $cls[] = sprintf('has-%s-font-size', \esc_attr($a['fontSize']));
        }
        if (!empty($a['fontFamily'])) {
            $cls[] = sprintf('has-%s-font-family', \esc_attr($a['fontFamily']));
        }

        return implode(' ', $cls);
    }

    private function color_classes(array $a): string
    {
        $cls = [];
        if (!empty($a['textColor'])) {
            $cls[] = 'has-text-color';
            $cls[] = sprintf('has-%s-color', $a['textColor']);
        } elseif (!empty($a['style']['color']['text'])) {
            $cls[] = 'has-text-color';
        }
        if (
            !empty($a['backgroundColor']) || !empty($a['style']['color']['background'])
            || !empty($a['gradient']) || !empty($a['style']['color']['gradient'])
        ) {
            $cls[] = 'has-background';
        }
        if (!empty($a['backgroundColor'])) {
            $cls[] = sprintf('has-%s-background-color', $a['backgroundColor']);
        }
        if (!empty($a['gradient'])) {
            $cls[] = sprintf('has-%s-gradient-background', $a['gradient']);
        }

        return implode(' ', $cls);
    }

    private function border_color_classes(array $a): string
    {
        $cls = [];
        if (!empty($a['style']['border']['color']) || !empty($a['borderColor'])) {
            $cls[] = 'has-border-color';
        }
        if (!empty($a['borderColor'])) {
            $cls[] = sprintf('has-%s-border-color', \esc_attr($a['borderColor']));
        }

        return implode(' ', $cls);
    }

    private function inline_styles(array $a): array
    {
        $wrapper = [];
        $button = [];
        $input = [];
        $label = [];

        // Width
        if (!empty($a['width']) && !empty($a['widthUnit'])) {
            $wrapper[] = sprintf('width: %d%s;', (int) $a['width'], \esc_attr($a['widthUnit']));
        }

        // Border (colour, width, style) — delegated to helper
        foreach (['width', 'color', 'style'] as $prop) {
            $this->apply_border_styles($a, $prop, $wrapper, $button, $input);
        }

        // Border radius
        if (!empty($a['style']['border']['radius'])) {
            $radius = $a['style']['border']['radius'];
            $padding = '4px';
            $is_inside = ($a['buttonPosition'] ?? '') === 'button-inside';

            if (is_array($radius)) {
                foreach ($radius as $corner => $val) {
                    if (!$val) {
                        continue;
                    }
                    $name = strtolower((string) preg_replace('/((?<=[a-z])[A-Z])/', '-$1', $corner));
                    $style = sprintf('border-%s-radius: %s;', \esc_attr($name), \esc_attr($val));
                    $input[] = $style;
                    $button[] = $style;
                    if ($is_inside) {
                        $wrapper[] = sprintf('border-%s-radius: calc(%s + %s);', \esc_attr($name), \esc_attr($val), $padding);
                    }
                }
            } else {
                $r = is_numeric($radius) ? $radius . 'px' : $radius;
                $style = sprintf('border-radius: %s;', \esc_attr($r));
                $input[] = $style;
                $button[] = $style;
                if ($is_inside && (int) $r !== 0) {
                    $wrapper[] = sprintf('border-radius: calc(%s + %s);', \esc_attr($r), $padding);
                }
            }
        }

        // Colours on button
        if (!empty($a['style']['color']['text'])) {
            $button[] = sprintf('color: %s;', $a['style']['color']['text']);
        }
        if (!empty($a['style']['color']['background'])) {
            $button[] = sprintf('background-color: %s;', $a['style']['color']['background']);
        }
        if (!empty($a['style']['color']['gradient'])) {
            $button[] = sprintf('background: %s;', $a['style']['color']['gradient']);
        }

        // Typography on all elements
        $typo = $this->typography_style_string($a);
        if ($typo) {
            $label[] = $typo;
            $button[] = $typo;
            $input[] = $typo;
        }
        if (!empty($a['style']['typography']['textDecoration'])) {
            $td = sprintf('text-decoration: %s;', \esc_attr($a['style']['typography']['textDecoration']));
            $button[] = $td;
            if (!empty($a['showLabel'])) {
                $label[] = $td;
            }
        }

        $fmt = fn($parts) => $parts
            ? sprintf(' style="%s"', \esc_attr(\safecss_filter_attr(implode(' ', $parts))))
            : '';

        return [
            'input' => $fmt($input),
            'button' => $fmt($button),
            'wrapper' => $fmt($wrapper),
            'label' => $fmt($label),
        ];
    }

    private function typography_style_string(array $a): string
    {
        $parts = [];
        $typo = $a['style']['typography'] ?? [];

        if (!empty($typo['fontSize'])) {
            $size = \wp_get_typography_font_size_value(['size' => $typo['fontSize']]);
            $parts[] = sprintf('font-size: %s;', $size);
        }
        foreach ([
            'fontFamily' => 'font-family',
            'letterSpacing' => 'letter-spacing',
            'fontWeight' => 'font-weight',
            'fontStyle' => 'font-style',
            'lineHeight' => 'line-height',
            'textTransform' => 'text-transform',
        ] as $key => $prop) {
            if (!empty($typo[$key])) {
                $parts[] = sprintf('%s: %s;', $prop, \esc_attr($typo[$key]));
            }
        }

        return implode('', $parts);
    }

    private function apply_border_style(array $a, string $property, ?string $side, array &$wrapper, array &$button, array &$input): void
    {
        $is_inside = ($a['buttonPosition'] ?? '') === 'button-inside';
        $path = ['style', 'border', $property];
        if ($side !== null) {
            array_splice($path, 2, 0, $side);
        }

        $value = \_wp_array_get($a, $path, false);
        if (!$value) {
            return;
        }

        if ($property === 'color' && $side !== null && str_contains((string) $value, 'var:preset|color|')) {
            $value = sprintf('var(--wp--preset--color--%s)', substr($value, strrpos($value, '|') + 1));
        }

        $suffix = $side !== null ? sprintf('%s-%s', $side, $property) : $property;
        $decl = sprintf('border-%s: %s;', $suffix, \esc_attr($value));

        if ($is_inside) {
            $wrapper[] = $decl;
        } else {
            $button[] = $decl;
            $input[] = $decl;
        }
    }

    private function apply_border_styles(array $a, string $property, array &$wrapper, array &$button, array &$input): void
    {
        foreach ([null, 'top', 'right', 'bottom', 'left'] as $side) {
            $this->apply_border_style($a, $property, $side, $wrapper, $button, $input);
        }
    }
}
