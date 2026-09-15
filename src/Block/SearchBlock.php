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

        $border_color_classes = $this->border_color_classes($attributes);
        $typography_classes   = $this->typography_classes($attributes);
        $color_classes        = $this->color_classes($attributes);

        // Scoped <style> cho placeholder color (::placeholder không thể dùng inline style)
        $placeholder_style = $this->placeholder_style($attributes, $input_id);

        $label_html = $this->label_markup($attributes, $input_id, $inline, $show_label, $typography_classes);
        $input_html = $this->input_markup($attributes, $input_id, $inline, $border_color_classes, $typography_classes, $is_button_inside);
        $hidden_html = $this->hidden_params_markup($query_params);

        // Render inner blocks (jankx/advanced-button or jankx/svg-icon placed inside the button)
        $inner_blocks_html = '';
        $has_advanced_button = false;
        if (!empty($block->inner_blocks) && count($block->inner_blocks) > 0) {
            foreach ($block->inner_blocks as $inner_block) {
                if ($inner_block->name === 'jankx/advanced-button') {
                    $has_advanced_button = true;
                }
                $inner_blocks_html .= $inner_block->render();
            }
        }

        // When using jankx/advanced-button as inner block, render it directly
        // instead of building our own button markup
        if ($has_advanced_button && !empty($inner_blocks_html)) {
            $field_markup = $this->field_wrapper($inline, $input_html . $hidden_html . $inner_blocks_html, $border_color_classes, $is_button_inside);
        } else {
            $button_html = $show_button
                ? $this->button_markup($attributes, $inline, $use_icon, $inner_blocks_html, $border_color_classes, $typography_classes, $color_classes, $is_button_inside)
                : '';

            $field_markup = $this->field_wrapper($inline, $input_html . $hidden_html . $button_html, $border_color_classes, $is_button_inside);
        }

        $filter_markup = $this->filter_boxes_markup($attributes, $input_id);
        $suggestion_markup = $this->suggestion_container_markup($attributes);

        $action = !empty($attributes['searchResultsUrl'])
            ? \esc_url($attributes['searchResultsUrl'])
            : \esc_url(\home_url('/'));

        $block_wrapper_attrs = \get_block_wrapper_attributes([
            'class' => 'jankx-search-form__container',
            'style' => $inline['container'],
        ]);
        $data_attrs = $this->data_attributes($attributes, $input_id);

        return sprintf(
            '%s'
            . '<div %s %s>'
            . '<form role="search" method="get" action="%s" class="%s">'
            . '%s%s%s%s'
            . '</form>'
            . '</div>',
            $placeholder_style,
            $block_wrapper_attrs,
            $data_attrs,
            $action,
            \esc_attr($classnames),
            $label_html,
            $filter_markup,
            $field_markup,
            $suggestion_markup
        );
    }

    // ── Label ────────────────────────────────────────────────────────────

    private function label_markup(array $a, string $input_id, array $inline, bool $show_label, string $typography_classes = ''): string
    {
        $inner = empty($a['label']) ? \__('Tìm kiếm', 'jankx') : \wp_kses_post($a['label']);
        $tag = new \WP_HTML_Tag_Processor(sprintf('<label %s>%s</label>', $inline['label'], $inner));
        if ($tag->next_tag()) {
            $tag->set_attribute('for', $input_id);
            $tag->add_class('jankx-search-form__label');
            if ($show_label && !empty($a['label'])) {
                if (!empty($typography_classes)) {
                    $tag->add_class($typography_classes);
                }
            } else {
                $tag->add_class('screen-reader-text');
            }
        }

        return (string) $tag;
    }

    // ── Input ────────────────────────────────────────────────────────────

    private function input_markup(array $a, string $input_id, array $inline, string $border_color_classes = '', string $typography_classes = '', bool $is_button_inside = false): string
    {
        $tag = new \WP_HTML_Tag_Processor(sprintf('<input type="search" name="s" required %s/>', $inline['input']));
        $classes = ['jankx-search-form__input'];

        if (!$is_button_inside && !empty($border_color_classes)) {
            $classes[] = $border_color_classes;
        }
        if (!empty($typography_classes)) {
            $classes[] = $typography_classes;
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

    private function button_markup(
        array $a,
        array $inline,
        bool $use_icon,
        string $inner_blocks_html = '',
        string $border_color_classes = '',
        string $typography_classes = '',
        string $color_classes = '',
        bool $is_button_inside = false
    ): string {
        $classes = ['jankx-search-form__button'];

        if (!$is_button_inside && !empty($border_color_classes)) {
            $classes[] = $border_color_classes;
        }
        if (!empty($typography_classes)) {
            $classes[] = $typography_classes;
        }
        if (!empty($color_classes)) {
            $classes[] = $color_classes;
        }

        $inner = '';

        if (!$use_icon) {
            $inner = \wp_kses_post($a['buttonText'] ?? '');
        } else {
            $classes[] = 'has-icon';

            // Priority 1: Use jankx/svg-icon inner block if present
            if (!empty($inner_blocks_html)) {
                $inner = '<div class="jankx-search-form__button-icon">' . $inner_blocks_html . '</div>';
            } else {
                // Fallback to legacy icon picker attributes
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
        }

        $el_class = \wp_theme_get_element_class_name('button');
        if ($el_class) {
            $classes[] = $el_class;
        }

        $icon_style = '';
        // Only apply background styles when NOT using svg-icon inner block
        if ($use_icon && empty($inner_blocks_html)) {
            $icon_bg_value = $a['iconBackgroundValue'] ?? '';
            $icon_padding = $a['iconPadding'] ?? '8px';
            $icon_border_radius = $a['iconBorderRadius'] ?? '4px';
            if (!empty($icon_bg_value)) {
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

    private function field_wrapper(array $inline, string $inner_html, string $border_color_classes = '', bool $is_button_inside = false): string
    {
        $classes = ['jankx-search-form__inside-wrapper'];
        if ($is_button_inside && !empty($border_color_classes)) {
            $classes[] = $border_color_classes;
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

    // ── CSS helpers ─────────────────────────────────────────────────────

    private function classnames(array $a): string
    {
        $cls = ['jankx-search-form'];
        $pos = $a['buttonPosition'] ?? 'button-outside';

        if ($pos === 'button-inside') {
            $cls[] = 'jankx-search-form__button-inside';
        }
        if ($pos === 'button-outside') {
            $cls[] = 'jankx-search-form__button-outside';
        }
        if ($pos === 'no-button') {
            $cls[] = 'jankx-search-form__no-button';
        }
        if ($pos === 'button-only') {
            $cls[] = 'jankx-search-form__button-outside';
        } // fallback

        if (!empty($a['buttonUseIcon']) && $pos !== 'no-button') {
            $cls[] = 'jankx-search-form__icon-button';
        } elseif (empty($a['buttonUseIcon']) && $pos !== 'no-button') {
            $cls[] = 'jankx-search-form__text-button';
        }

        return implode(' ', $cls);
    }

    // ── CSS & Style helpers matching core WordPress search block ────────

    private function border_color_classes(array $a): string
    {
        $border_color_classes = [];
        $has_custom_border_color = !empty($a['style']['border']['color']);
        $has_named_border_color  = !empty($a['borderColor']);

        if ($has_custom_border_color || $has_named_border_color) {
            $border_color_classes[] = 'has-border-color';
        }

        if ($has_named_border_color) {
            $border_color_classes[] = sprintf('has-%s-border-color', \esc_attr($a['borderColor']));
        }

        return implode(' ', $border_color_classes);
    }

    private function typography_classes(array $a): string
    {
        $typography_classes = [];
        if (!empty($a['fontSize'])) {
            $typography_classes[] = sprintf('has-%s-font-size', \esc_attr($a['fontSize']));
        }
        if (!empty($a['fontFamily'])) {
            $typography_classes[] = sprintf('has-%s-font-family', \esc_attr($a['fontFamily']));
        }

        return implode(' ', $typography_classes);
    }

    private function color_classes(array $a): string
    {
        $classnames = [];

        // Text color
        if (!empty($a['textColor'])) {
            $classnames[] = sprintf('has-text-color has-%s-color', \esc_attr($a['textColor']));
        } elseif (!empty($a['style']['color']['text'])) {
            $classnames[] = 'has-text-color';
        }

        // Background color / gradient
        $has_named_background_color  = !empty($a['backgroundColor']);
        $has_custom_background_color = !empty($a['style']['color']['background']);
        $has_named_gradient          = !empty($a['gradient']);
        $has_custom_gradient         = !empty($a['style']['color']['gradient']);

        if ($has_named_background_color || $has_custom_background_color || $has_named_gradient || $has_custom_gradient) {
            $classnames[] = 'has-background';
        }
        if ($has_named_background_color) {
            $classnames[] = sprintf('has-%s-background-color', \esc_attr($a['backgroundColor']));
        }
        if ($has_named_gradient) {
            $classnames[] = sprintf('has-%s-gradient-background', \esc_attr($a['gradient']));
        }

        return implode(' ', $classnames);
    }

    private function apply_border_style(array $a, string $property, ?string $side, array &$wrapper_styles, array &$button_styles, array &$input_styles): void
    {
        $is_button_inside = ($a['buttonPosition'] ?? 'button-outside') === 'button-inside';
        $path = ['style', 'border', $property];

        if ($side !== null) {
            array_splice($path, 2, 0, $side);
        }

        $value = \_wp_array_get($a, $path, false);
        if (empty($value) && $side === null && $property === 'color' && !empty($a['borderColor'])) {
            $value = sprintf('var(--wp--preset--color--%s)', \esc_attr($a['borderColor']));
        }

        if (empty($value)) {
            return;
        }

        if ($property === 'color' && str_contains((string) $value, 'var:preset|color|')) {
            $value = sprintf('var(--wp--preset--color--%s)', substr($value, strrpos($value, '|') + 1));
        }

        $property_suffix = $side !== null ? sprintf('%s-%s', $side, $property) : $property;

        if ($is_button_inside) {
            $wrapper_styles[] = sprintf('border-%s: %s;', $property_suffix, \esc_attr($value));
        } else {
            $button_styles[] = sprintf('border-%s: %s;', $property_suffix, \esc_attr($value));
            $input_styles[]  = sprintf('border-%s: %s;', $property_suffix, \esc_attr($value));
        }
    }

    private function apply_border_styles(array $a, string $property, array &$wrapper_styles, array &$button_styles, array &$input_styles): void
    {
        $this->apply_border_style($a, $property, null, $wrapper_styles, $button_styles, $input_styles);
        $this->apply_border_style($a, $property, 'top', $wrapper_styles, $button_styles, $input_styles);
        $this->apply_border_style($a, $property, 'right', $wrapper_styles, $button_styles, $input_styles);
        $this->apply_border_style($a, $property, 'bottom', $wrapper_styles, $button_styles, $input_styles);
        $this->apply_border_style($a, $property, 'left', $wrapper_styles, $button_styles, $input_styles);
    }

    private function inline_styles(array $a): array
    {
        $wrapper_styles   = [];
        $button_styles    = [];
        $input_styles     = [];
        $label_styles     = [];
        $container_styles = [];
        $is_button_inside = ($a['buttonPosition'] ?? 'button-outside') === 'button-inside';
        $show_label       = !empty($a['showLabel']);

        // Width — on the inside wrapper
        if (!empty($a['width']) && !empty($a['widthUnit'])) {
            $wrapper_styles[] = sprintf('width: %d%s;', (int) $a['width'], \esc_attr($a['widthUnit']));
        }

        // Border width, color, style — applied to wrapper if inside, or input+button if outside
        $this->apply_border_styles($a, 'width', $wrapper_styles, $button_styles, $input_styles);
        $this->apply_border_styles($a, 'color', $wrapper_styles, $button_styles, $input_styles);
        $this->apply_border_styles($a, 'style', $wrapper_styles, $button_styles, $input_styles);

        // Border radius styles
        $has_border_radius = !empty($a['style']['border']['radius']);
        if ($has_border_radius) {
            $default_padding = '4px';
            $border_radius   = $a['style']['border']['radius'];

            if (is_array($border_radius)) {
                foreach ($border_radius as $key => $value) {
                    if (is_string($value) && str_contains($value, 'var:preset|border-radius|')) {
                        $index_to_splice = strrpos($value, '|') + 1;
                        $slug            = \_wp_to_kebab_case(substr($value, $index_to_splice));
                        $value           = "var(--wp--preset--border-radius--{$slug})";
                    }

                    if ($value !== null && $value !== '') {
                        $name = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $key));
                        $border_style    = sprintf('border-%s-radius: %s;', \esc_attr($name), \esc_attr($value));
                        $input_styles[]  = $border_style;
                        $button_styles[] = $border_style;

                        if ($is_button_inside && ((int) $value !== 0 || str_contains((string) $value, 'var(--wp--preset--border-radius--'))) {
                            $wrapper_styles[] = sprintf(
                                'border-%s-radius: calc(%s + %s);',
                                \esc_attr($name),
                                \esc_attr($value),
                                $default_padding
                            );
                        }
                    }
                }
            } else {
                $border_radius_str = is_numeric($border_radius) ? $border_radius . 'px' : $border_radius;
                if (is_string($border_radius_str) && str_contains($border_radius_str, 'var:preset|border-radius|')) {
                    $index_to_splice   = strrpos($border_radius_str, '|') + 1;
                    $slug              = \_wp_to_kebab_case(substr($border_radius_str, $index_to_splice));
                    $border_radius_str = "var(--wp--preset--border-radius--{$slug})";
                }

                $border_style    = sprintf('border-radius: %s;', \esc_attr($border_radius_str));
                $input_styles[]  = $border_style;
                $button_styles[] = $border_style;

                if ($is_button_inside && (int) $border_radius !== 0) {
                    $wrapper_styles[] = sprintf(
                        'border-radius: calc(%s + %s);',
                        \esc_attr($border_radius_str),
                        $default_padding
                    );
                }
            }
        }

        // Text colour
        if (!empty($a['style']['color']['text'])) {
            $button_styles[] = sprintf('color: %s;', \esc_attr($a['style']['color']['text']));
            $input_styles[]  = sprintf('color: %s;', \esc_attr($a['style']['color']['text']));
        } elseif (!empty($a['textColor'])) {
            $button_styles[] = sprintf('color: var(--wp--preset--color--%s);', \esc_attr($a['textColor']));
            $input_styles[]  = sprintf('color: var(--wp--preset--color--%s);', \esc_attr($a['textColor']));
        }

        // Button background & gradient
        if (!empty($a['style']['color']['background'])) {
            $bg = $a['style']['color']['background'];
            if (str_contains((string) $bg, 'var:preset|color|')) {
                $bg = sprintf('var(--wp--preset--color--%s)', substr($bg, strrpos($bg, '|') + 1));
            }
            $button_styles[] = sprintf('background-color: %s;', \esc_attr($bg));
        } elseif (!empty($a['backgroundColor'])) {
            $button_styles[] = sprintf('background-color: var(--wp--preset--color--%s);', \esc_attr($a['backgroundColor']));
        }

        if (!empty($a['style']['color']['gradient'])) {
            $button_styles[] = sprintf('background: %s;', \esc_attr($a['style']['color']['gradient']));
        } elseif (!empty($a['gradient'])) {
            $button_styles[] = sprintf('background: var(--wp--preset--gradient--%s);', \esc_attr($a['gradient']));
        }

        // Typography styles shared across inner elements
        $typography_styles = $this->typography_style_string($a);
        if (!empty($typography_styles)) {
            $label_styles[]  = $typography_styles;
            $button_styles[] = $typography_styles;
            $input_styles[]  = $typography_styles;
        }

        // Typography text-decoration is only applied to label and button (input opts out)
        if (!empty($a['style']['typography']['textDecoration'])) {
            $text_decoration_value = sprintf('text-decoration: %s;', \esc_attr($a['style']['typography']['textDecoration']));
            $button_styles[]       = $text_decoration_value;
            if ($show_label) {
                $label_styles[] = $text_decoration_value;
            }
        }

        $fmt = fn($parts) => !empty($parts)
            ? sprintf(' style="%s"', \esc_attr(\safecss_filter_attr(implode(' ', $parts))))
            : '';

        return [
            'input'     => $fmt($input_styles),
            'button'    => $fmt($button_styles),
            'wrapper'   => $fmt($wrapper_styles),
            'label'     => $fmt($label_styles),
            'container' => implode(' ', $container_styles),
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

    /**
     * Generate a scoped <style> tag to apply placeholder color.
     *
     * The ::placeholder pseudo-element cannot be styled with inline styles,
     * so we output a tiny <style> block scoped to the input id.
     *
     * @param array  $a        Block attributes.
     * @param string $input_id Unique ID for the input element.
     * @return string HTML <style> tag, or empty string.
     */
    private function placeholder_style(array $a, string $input_id): string
    {
        // Determine the placeholder color from block supports color settings.
        $color = '';

        if (!empty($a['style']['color']['text'])) {
            // Custom color value
            $color = $a['style']['color']['text'];
        } elseif (!empty($a['textColor'])) {
            // Preset color slug → CSS variable
            $color = sprintf('var(--wp--preset--color--%s)', \esc_attr($a['textColor']));
        }

        if (!$color) {
            return '';
        }

        // Build a small, scoped CSS rule for the placeholder.
        // opacity 0.7 follows the convention used by core/search.
        return sprintf(
            '<style>#%1$s::placeholder { color: %2$s; opacity: 0.7; }</style>',
            \esc_attr($input_id),
            $color  // already escaped / is a CSS var
        );
    }
}
