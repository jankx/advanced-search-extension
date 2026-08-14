<?php
/**
 * Server-side render for jankx-advanced-search/search.
 *
 * Faithful port of the WordPress core `core/search` block renderer
 * (wp-includes/blocks/search.php) so the markup stays 100% compatible
 * with the `wp-block-search` class names — meaning the Jankx theme
 * styling (theme.json + block styles) applies out of the box.
 *
 * Customization point: adjust the markup / query params below to extend
 * the search experience for Jankx users (e.g. point it at the advanced
 * search results page, add hidden filters, etc.).
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content (empty for dynamic blocks).
 * @var WP_Block $block      Block instance.
 */

namespace Jankx\Extensions\AdvancedSearch\Block\Search;

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists(__NAMESPACE__ . '\\render_block')) {

    /**
     * Builds the correct top-level class names for the block.
     */
    function classnames_for_block($attributes)
    {
        $classnames = [];

        if (!empty($attributes['buttonPosition'])) {
            if ('button-inside' === $attributes['buttonPosition']) {
                $classnames[] = 'wp-block-search__button-inside';
            }

            if ('button-outside' === $attributes['buttonPosition']) {
                $classnames[] = 'wp-block-search__button-outside';
            }

            if ('no-button' === $attributes['buttonPosition']) {
                $classnames[] = 'wp-block-search__no-button';
            }

            // The expandable `button-only` mode relies on the core Interactivity
            // view module (`core/search`). Not ported yet — map it to the
            // classic outside layout so the form keeps working without JS.
            if ('button-only' === $attributes['buttonPosition']) {
                $classnames[] = 'wp-block-search__button-outside';
            }
        }

        if (isset($attributes['buttonUseIcon'])) {
            if (!empty($attributes['buttonPosition']) && 'no-button' !== $attributes['buttonPosition']) {
                if ($attributes['buttonUseIcon']) {
                    $classnames[] = 'wp-block-search__icon-button';
                } else {
                    $classnames[] = 'wp-block-search__text-button';
                }
            }
        }

        return implode(' ', $classnames);
    }

    /**
     * This generates a CSS rule for the given border property and side if provided.
     */
    function apply_border_style($attributes, $property, $side, &$wrapper_styles, &$button_styles, &$input_styles)
    {
        $is_button_inside = isset($attributes['buttonPosition']) && 'button-inside' === $attributes['buttonPosition'];

        $path = ['style', 'border', $property];

        if ($side) {
            array_splice($path, 2, 0, $side);
        }

        $value = \_wp_array_get($attributes, $path, false);

        if (empty($value)) {
            return;
        }

        if ('color' === $property && $side) {
            $has_color_preset = str_contains($value, 'var:preset|color|');
            if ($has_color_preset) {
                $named_color_value = substr($value, strrpos($value, '|') + 1);
                $value             = sprintf('var(--wp--preset--color--%s)', $named_color_value);
            }
        }

        $property_suffix = $side ? sprintf('%s-%s', $side, $property) : $property;

        if ($is_button_inside) {
            $wrapper_styles[] = sprintf('border-%s: %s;', $property_suffix, \esc_attr($value));
        } else {
            $button_styles[] = sprintf('border-%s: %s;', $property_suffix, \esc_attr($value));
            $input_styles[]  = sprintf('border-%s: %s;', $property_suffix, \esc_attr($value));
        }
    }

    /**
     * This adds CSS rules for a given border property e.g. width or color.
     */
    function apply_border_styles($attributes, $property, &$wrapper_styles, &$button_styles, &$input_styles)
    {
        apply_border_style($attributes, $property, null, $wrapper_styles, $button_styles, $input_styles);
        apply_border_style($attributes, $property, 'top', $wrapper_styles, $button_styles, $input_styles);
        apply_border_style($attributes, $property, 'right', $wrapper_styles, $button_styles, $input_styles);
        apply_border_style($attributes, $property, 'bottom', $wrapper_styles, $button_styles, $input_styles);
        apply_border_style($attributes, $property, 'left', $wrapper_styles, $button_styles, $input_styles);
    }

    /**
     * Returns typography class names depending on named font sizes/families.
     */
    function typography_classes($attributes)
    {
        $typography_classes    = [];
        $has_named_font_family = !empty($attributes['fontFamily']);
        $has_named_font_size   = !empty($attributes['fontSize']);

        if ($has_named_font_size) {
            $typography_classes[] = sprintf('has-%s-font-size', \esc_attr($attributes['fontSize']));
        }

        if ($has_named_font_family) {
            $typography_classes[] = sprintf('has-%s-font-family', \esc_attr($attributes['fontFamily']));
        }

        return implode(' ', $typography_classes);
    }

    /**
     * Returns typography styles (excludes text-decoration).
     */
    function typography_styles($attributes)
    {
        $typography_styles = [];

        if (!empty($attributes['style']['typography']['fontSize'])) {
            $typography_styles[] = sprintf(
                'font-size: %s;',
                \wp_get_typography_font_size_value(
                    [
                        'size' => $attributes['style']['typography']['fontSize'],
                    ]
                )
            );
        }

        if (!empty($attributes['style']['typography']['fontFamily'])) {
            $typography_styles[] = sprintf('font-family: %s;', $attributes['style']['typography']['fontFamily']);
        }

        if (!empty($attributes['style']['typography']['letterSpacing'])) {
            $typography_styles[] = sprintf('letter-spacing: %s;', $attributes['style']['typography']['letterSpacing']);
        }

        if (!empty($attributes['style']['typography']['fontWeight'])) {
            $typography_styles[] = sprintf('font-weight: %s;', $attributes['style']['typography']['fontWeight']);
        }

        if (!empty($attributes['style']['typography']['fontStyle'])) {
            $typography_styles[] = sprintf('font-style: %s;', $attributes['style']['typography']['fontStyle']);
        }

        if (!empty($attributes['style']['typography']['lineHeight'])) {
            $typography_styles[] = sprintf('line-height: %s;', $attributes['style']['typography']['lineHeight']);
        }

        if (!empty($attributes['style']['typography']['textTransform'])) {
            $typography_styles[] = sprintf('text-transform: %s;', $attributes['style']['typography']['textTransform']);
        }

        return implode('', $typography_styles);
    }

    /**
     * Returns border color class names.
     */
    function border_color_classes($attributes)
    {
        $border_color_classes    = [];
        $has_custom_border_color = !empty($attributes['style']['border']['color']);
        $has_named_border_color  = !empty($attributes['borderColor']);

        if ($has_custom_border_color || $has_named_border_color) {
            $border_color_classes[] = 'has-border-color';
        }

        if ($has_named_border_color) {
            $border_color_classes[] = sprintf('has-%s-border-color', \esc_attr($attributes['borderColor']));
        }

        return implode(' ', $border_color_classes);
    }

    /**
     * Returns color class names for named/custom text and background colors.
     */
    function color_classes($attributes)
    {
        $classnames = [];

        $has_named_text_color  = !empty($attributes['textColor']);
        $has_custom_text_color = !empty($attributes['style']['color']['text']);
        if ($has_named_text_color) {
            $classnames[] = sprintf('has-text-color has-%s-color', $attributes['textColor']);
        } elseif ($has_custom_text_color) {
            $classnames[] = 'has-text-color';
        }

        $has_named_background_color  = !empty($attributes['backgroundColor']);
        $has_custom_background_color = !empty($attributes['style']['color']['background']);
        $has_named_gradient          = !empty($attributes['gradient']);
        $has_custom_gradient         = !empty($attributes['style']['color']['gradient']);
        if ($has_named_background_color || $has_custom_background_color || $has_named_gradient || $has_custom_gradient) {
            $classnames[] = 'has-background';
        }
        if ($has_named_background_color) {
            $classnames[] = sprintf('has-%s-background-color', $attributes['backgroundColor']);
        }
        if ($has_named_gradient) {
            $classnames[] = sprintf('has-%s-gradient-background', $attributes['gradient']);
        }

        return implode(' ', $classnames);
    }

    /**
     * Builds an array of inline styles for the block.
     */
    function styles_for_block($attributes)
    {
        $wrapper_styles   = [];
        $button_styles    = [];
        $input_styles     = [];
        $label_styles     = [];
        $is_button_inside = !empty($attributes['buttonPosition']) && 'button-inside' === $attributes['buttonPosition'];
        $show_label       = (isset($attributes['showLabel'])) && false !== $attributes['showLabel'];

        // Width styles.
        $has_width = !empty($attributes['width']) && !empty($attributes['widthUnit']);

        if ($has_width) {
            $wrapper_styles[] = sprintf(
                'width: %d%s;',
                \esc_attr($attributes['width']),
                \esc_attr($attributes['widthUnit'])
            );
        }

        // Border styles.
        apply_border_styles($attributes, 'width', $wrapper_styles, $button_styles, $input_styles);
        apply_border_styles($attributes, 'color', $wrapper_styles, $button_styles, $input_styles);
        apply_border_styles($attributes, 'style', $wrapper_styles, $button_styles, $input_styles);

        // Border radius.
        $has_border_radius = !empty($attributes['style']['border']['radius']);

        if ($has_border_radius) {
            $default_padding = '4px';
            $border_radius   = $attributes['style']['border']['radius'];

            if (is_array($border_radius)) {
                foreach ($border_radius as $key => $value) {
                    if (is_string($value) && str_contains($value, 'var:preset|border-radius|')) {
                        $index_to_splice = strrpos($value, '|') + 1;
                        $slug            = \_wp_to_kebab_case(substr($value, $index_to_splice));
                        $value           = "var(--wp--preset--border-radius--$slug)";
                    }

                    if (null !== $value) {
                        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $key));

                        $border_style    = sprintf('border-%s-radius: %s;', \esc_attr($name), \esc_attr($value));
                        $input_styles[]  = $border_style;
                        $button_styles[] = $border_style;

                        if ($is_button_inside && (intval($value) !== 0 || str_contains($value, 'var(--wp--preset--border-radius--'))) {
                            $wrapper_styles[] = sprintf('border-%s-radius: calc(%s + %s);', \esc_attr($name), \esc_attr($value), $default_padding);
                        }
                    }
                }
            } else {
                $border_radius = is_numeric($border_radius) ? $border_radius . 'px' : $border_radius;
                if (is_string($border_radius) && str_contains($border_radius, 'var:preset|border-radius|')) {
                    $index_to_splice = strrpos($border_radius, '|') + 1;
                    $slug            = \_wp_to_kebab_case(substr($border_radius, $index_to_splice));
                    $border_radius   = "var(--wp--preset--border-radius--$slug)";
                }

                $border_style    = sprintf('border-radius: %s;', \esc_attr($border_radius));
                $input_styles[]  = $border_style;
                $button_styles[] = $border_style;

                if ($is_button_inside && intval($border_radius) !== 0) {
                    $wrapper_styles[] = sprintf('border-radius: calc(%s + %s);', \esc_attr($border_radius), $default_padding);
                }
            }
        }

        // Color styles (applied to the button, like core).
        if (!empty($attributes['style']['color']['text'])) {
            $button_styles[] = sprintf('color: %s;', $attributes['style']['color']['text']);
        }
        if (!empty($attributes['style']['color']['background'])) {
            $button_styles[] = sprintf('background-color: %s;', $attributes['style']['color']['background']);
        }
        if (!empty($attributes['style']['color']['gradient'])) {
            $button_styles[] = sprintf('background: %s;', $attributes['style']['color']['gradient']);
        }

        // Typography styles shared across inner elements.
        $typography_styles = \esc_attr(typography_styles($attributes));
        if (!empty($typography_styles)) {
            $label_styles[]  = $typography_styles;
            $button_styles[] = $typography_styles;
            $input_styles[]  = $typography_styles;
        }

        // Typography text-decoration only on the label and button.
        if (!empty($attributes['style']['typography']['textDecoration'])) {
            $text_decoration_value = sprintf('text-decoration: %s;', \esc_attr($attributes['style']['typography']['textDecoration']));
            $button_styles[]       = $text_decoration_value;
            if ($show_label) {
                $label_styles[] = $text_decoration_value;
            }
        }

        return [
            'input'   => !empty($input_styles) ? sprintf(' style="%s"', \esc_attr(\safecss_filter_attr(implode(' ', $input_styles)))) : '',
            'button'  => !empty($button_styles) ? sprintf(' style="%s"', \esc_attr(\safecss_filter_attr(implode(' ', $button_styles)))) : '',
            'wrapper' => !empty($wrapper_styles) ? sprintf(' style="%s"', \esc_attr(\safecss_filter_attr(implode(' ', $wrapper_styles)))) : '',
            'label'   => !empty($label_styles) ? sprintf(' style="%s"', \esc_attr(\safecss_filter_attr(implode(' ', $label_styles)))) : '',
        ];
    }

    /**
     * Dynamically renders the block.
     *
     * @param array $attributes The block attributes.
     * @return string The search block markup.
     */
    function render_block($attributes)
    {
        // Support blocks saved without label/buttonText (default them like core).
        $attributes = \wp_parse_args(
            $attributes,
            [
                'label'      => \__('Tìm kiếm', 'jankx'),
                'buttonText' => \__('Tìm kiếm', 'jankx'),
            ]
        );

        $input_id             = \wp_unique_id('wp-block-search__input-');
        $classnames           = classnames_for_block($attributes);
        $show_label           = !empty($attributes['showLabel']);
        $use_icon_button      = !empty($attributes['buttonUseIcon']);
        $show_button          = (!empty($attributes['buttonPosition']) && 'no-button' === $attributes['buttonPosition']) ? false : true;
        $query_params         = (!empty($attributes['query'])) ? $attributes['query'] : [];
        $button               = '';
        $query_params_markup  = '';
        $inline_styles        = styles_for_block($attributes);
        $color_classes        = color_classes($attributes);
        $typography_classes   = typography_classes($attributes);
        $is_button_inside     = !empty($attributes['buttonPosition']) && 'button-inside' === $attributes['buttonPosition'];
        $border_color_classes = border_color_classes($attributes);

        $label_inner_html = empty($attributes['label']) ? \__('Tìm kiếm', 'jankx') : \wp_kses_post($attributes['label']);
        $label            = new \WP_HTML_Tag_Processor(sprintf('<label %s>%s</label>', $inline_styles['label'], $label_inner_html));
        if ($label->next_tag()) {
            $label->set_attribute('for', $input_id);
            $label->add_class('wp-block-search__label');
            if ($show_label && !empty($attributes['label'])) {
                if (!empty($typography_classes)) {
                    $label->add_class($typography_classes);
                }
            } else {
                $label->add_class('screen-reader-text');
            }
        }

        $input         = new \WP_HTML_Tag_Processor(sprintf('<input type="search" name="s" required %s/>', $inline_styles['input']));
        $input_classes = ['wp-block-search__input'];
        if (!$is_button_inside && !empty($border_color_classes)) {
            $input_classes[] = $border_color_classes;
        }
        if (!empty($typography_classes)) {
            $input_classes[] = $typography_classes;
        }
        if ($input->next_tag()) {
            $input->add_class(implode(' ', $input_classes));
            $input->set_attribute('id', $input_id);
            $input->set_attribute('value', \get_search_query());
            if (isset($attributes['placeholder'])) {
                $input->set_attribute('placeholder', $attributes['placeholder']);
            }
        }

        if (count($query_params) > 0) {
            foreach ($query_params as $param => $value) {
                $query_params_markup .= sprintf(
                    '<input type="hidden" name="%s" value="%s" />',
                    \esc_attr($param),
                    \esc_attr($value)
                );
            }
        }

        if ($show_button) {
            $button_classes         = ['wp-block-search__button'];
            $button_internal_markup = '';
            if (!empty($color_classes)) {
                $button_classes[] = $color_classes;
            }
            if (!empty($typography_classes)) {
                $button_classes[] = $typography_classes;
            }
            if (!$is_button_inside && !empty($border_color_classes)) {
                $button_classes[] = $border_color_classes;
            }

            if (!$use_icon_button) {
                if (!empty($attributes['buttonText'])) {
                    $button_internal_markup = \wp_kses_post($attributes['buttonText']);
                }
            } else {
                $button_classes[] = 'has-icon';
                $button_internal_markup =
                    '<svg class="search-icon" viewBox="0 0 24 24" width="24" height="24">
                        <path d="M13 5c-3.3 0-6 2.7-6 6 0 1.4.5 2.7 1.3 3.7l-3.8 3.8 1.1 1.1 3.8-3.8c1 .8 2.3 1.3 3.7 1.3 3.3 0 6-2.7 6-6S16.3 5 13 5zm0 10.5c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5 4.5 2 4.5 4.5-2 4.5-4.5 4.5z"></path>
                    </svg>';
            }

            $button_classes[] = \wp_theme_get_element_class_name('button');
            $button           = new \WP_HTML_Tag_Processor(sprintf('<button type="submit" %s>%s</button>', $inline_styles['button'], $button_internal_markup));

            if ($button->next_tag()) {
                $button->add_class(implode(' ', $button_classes));
                $button->set_attribute('aria-label', \wp_strip_all_tags($attributes['buttonText']));
            }
        }

        $field_markup_classes = ['wp-block-search__inside-wrapper'];
        if ($is_button_inside && !empty($border_color_classes)) {
            $field_markup_classes[] = $border_color_classes;
        }
        $field_markup = sprintf(
            '<div class="%s" %s>%s</div>',
            \esc_attr(implode(' ', $field_markup_classes)),
            $inline_styles['wrapper'],
            $input . $query_params_markup . $button
        );
        $wrapper_attributes = \get_block_wrapper_attributes(['class' => $classnames]);

        return sprintf(
            '<form role="search" method="get" action="%1s" %2s>%3s</form>',
            \esc_url(\home_url('/')),
            $wrapper_attributes,
            $label . $field_markup
        );
    }
}

echo render_block((array) $attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is fully escaped above
