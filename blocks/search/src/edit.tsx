import {
	useBlockProps,
	InspectorControls,
	useInnerBlocksProps,
	__experimentalUseBorderProps,
	getTypographyClassesAndStyles,
	useSettings,
} from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const WIDTH_UNITS = [
	{ value: 'px', label: 'px' },
	{ value: 'em', label: 'em' },
	{ value: 'rem', label: 'rem' },
];

const BUTTON_POSITIONS = [
	{ value: 'button-outside', label: __('Outside', 'jankx') },
	{ value: 'button-inside', label: __('Inside', 'jankx') },
];

const SEARCH_BUTTON_TEMPLATE: any[] = [
	['jankx/advanced-button', {
		triggerType: 'button',
		buttonType: 'submit',
		text: 'Tìm kiếm',
		showLabel: true,
	}],
];

const ALLOWED_BLOCKS = ['jankx/advanced-button'];
const DEFAULT_INNER_PADDING = '4px';

export default function SearchEdit({ attributes, setAttributes, clientId }) {
	const {
		label = '',
		showLabel = true,
		placeholder = '',
		width,
		widthUnit = 'px',
		buttonPosition = 'button-outside',
		textColor,
		style: blockStyle = {},
	} = attributes;

	const inputId = `jankx-search-form__input-${clientId}`;
	const insideWrapperWidth = width ? { width: `${width}${widthUnit}` } : undefined;
	const isButtonPositionInside = buttonPosition === 'button-inside';

	const resolvedTextColor: string | undefined =
		(blockStyle as any)?.color?.text ||
		(textColor ? `var(--wp--preset--color--${textColor})` : undefined);

	const textStyle: { color?: string } = resolvedTextColor
		? { color: resolvedTextColor }
		: {};

	// Background colour/gradient from block settings (mirrors the frontend
	// SearchBlock::inline_styles output on the capsule wrapper).
	const colorStyle = (blockStyle as any)?.color;
	const resolveColor = (value?: string) =>
		value?.includes('var:preset|color|')
			? `var(--wp--preset--color--${value.split('|').pop()})`
			: value;
	const backgroundStyle =
		colorStyle?.gradient
			? { background: colorStyle.gradient }
			: colorStyle?.background
				? { backgroundColor: resolveColor(colorStyle.background) }
				: attributes?.gradient
					? { background: `var(--wp--preset--gradient--${attributes.gradient})` }
					: attributes?.backgroundColor
						? { backgroundColor: `var(--wp--preset--color--${attributes.backgroundColor})` }
						: {};

	// Border props matching WordPress core/search
	const borderRadius = (blockStyle as any)?.border?.radius;
	let borderProps: any = typeof __experimentalUseBorderProps === 'function'
		? __experimentalUseBorderProps(attributes)
		: { className: '', style: {} };

	if (typeof borderRadius === 'number') {
		borderProps = {
			...borderProps,
			style: {
				...borderProps?.style,
				borderRadius: `${borderRadius}px`,
			},
		};
	}

	// Typography props matching WordPress core/search
	const [fluidTypographySettings, layout] = typeof useSettings === 'function'
		? useSettings('typography.fluid', 'layout')
		: [undefined, undefined];

	const typographyProps: any = typeof getTypographyClassesAndStyles === 'function'
		? getTypographyClassesAndStyles(attributes, {
				typography: {
					fluid: fluidTypographySettings,
				},
				layout: {
					wideSize: (layout as any)?.wideSize,
				},
		  })
		: { className: '', style: {} };

	// Helper for expanding border-radius on inside-wrapper when button is inside
	const isNonZeroBorderRadius = (radius: any) =>
		radius !== undefined && parseInt(radius, 10) !== 0;
	const padBorderRadius = (radius: any) =>
		isNonZeroBorderRadius(radius)
			? `calc(${radius} + ${DEFAULT_INNER_PADDING})`
			: undefined;

	const getWrapperStyles = () => {
		const styles: any = isButtonPositionInside
			? { ...borderProps?.style }
			: {
					borderRadius: borderProps?.style?.borderRadius,
					borderTopLeftRadius: borderProps?.style?.borderTopLeftRadius,
					borderTopRightRadius: borderProps?.style?.borderTopRightRadius,
					borderBottomLeftRadius: borderProps?.style?.borderBottomLeftRadius,
					borderBottomRightRadius: borderProps?.style?.borderBottomRightRadius,
			  };

		if (isButtonPositionInside) {
			if (typeof borderRadius === 'object' && borderRadius !== null) {
				const {
					borderTopLeftRadius,
					borderTopRightRadius,
					borderBottomLeftRadius,
					borderBottomRightRadius,
				} = borderProps?.style || {};
				return {
					...styles,
					borderTopLeftRadius: padBorderRadius(borderTopLeftRadius),
					borderTopRightRadius: padBorderRadius(borderTopRightRadius),
					borderBottomLeftRadius: padBorderRadius(borderBottomLeftRadius),
					borderBottomRightRadius: padBorderRadius(borderBottomRightRadius),
				};
			}
			const radius = Number.isInteger(borderRadius)
				? `${borderRadius}px`
				: borderRadius;
			styles.borderRadius = padBorderRadius(radius);
		}
		return styles;
	};

	const blockProps = useBlockProps({
		className: `jankx-search-form jankx-search-form__container jankx-search-form__${buttonPosition}`,
	});

	// Input styles and classes:
	// If button is inside, input has no border/outline (the wrapper has it), but gets typography.
	// If button is outside, input gets borderProps (border, radius) and typographyProps.
	const textFieldClasses = [
		'jankx-search-form__input',
		isButtonPositionInside ? undefined : borderProps?.className,
		typographyProps?.className,
	]
		.filter(Boolean)
		.join(' ');

	const textFieldStyles: React.CSSProperties = {
		...(isButtonPositionInside
			? {
					borderRadius: borderProps?.style?.borderRadius,
					borderTopLeftRadius: borderProps?.style?.borderTopLeftRadius,
					borderTopRightRadius: borderProps?.style?.borderTopRightRadius,
					borderBottomLeftRadius: borderProps?.style?.borderBottomLeftRadius,
					borderBottomRightRadius: borderProps?.style?.borderBottomRightRadius,
			  }
			: borderProps?.style),
		...typographyProps?.style,
		...textStyle,
		textDecoration: undefined,
	};

	const wrapperClasses = [
		'jankx-search-form__inside-wrapper',
		isButtonPositionInside ? borderProps?.className : undefined,
	]
		.filter(Boolean)
		.join(' ');

	const wrapperStyles: React.CSSProperties = {
		...insideWrapperWidth,
		...getWrapperStyles(),
		...(isButtonPositionInside ? backgroundStyle : {}),
		...(resolvedTextColor
			? { ['--jankx-search-placeholder-color' as any]: resolvedTextColor }
			: {}),
	};

	const innerBlocksProps = useInnerBlocksProps(
		{},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: SEARCH_BUTTON_TEMPLATE,
			templateLock: 'all',
			renderAppender: false,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Search settings', 'jankx')} initialOpen={true}>
					<ToggleControl
						label={__('Show label', 'jankx')}
						checked={showLabel}
						onChange={(value) => setAttributes({ showLabel: value })}
					/>
					<TextControl
						label={__('Label', 'jankx')}
						value={label}
						onChange={(value) => setAttributes({ label: value })}
					/>
					<TextControl
						label={__('Placeholder', 'jankx')}
						value={placeholder}
						onChange={(value) => setAttributes({ placeholder: value })}
					/>
					<SelectControl
						label={__('Button position', 'jankx')}
						value={buttonPosition}
						options={BUTTON_POSITIONS}
						onChange={(value) => setAttributes({ buttonPosition: value })}
						help={__('Show the button inside or outside the search field', 'jankx')}
					/>
					<div className="jankx-search-form__inspector-controls">
						<SelectControl
							label={__('Width unit', 'jankx')}
							value={widthUnit}
							options={WIDTH_UNITS}
							onChange={(value) => setAttributes({ widthUnit: value })}
						/>
					</div>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<label
					className={showLabel ? 'jankx-search-form__label' : 'screen-reader-text'}
					htmlFor={inputId}
					style={textStyle}
				>
					{label || __('Tìm kiếm', 'jankx')}
				</label>
				<div
					className={wrapperClasses}
					style={wrapperStyles}
				>
					<input
						id={inputId}
						className={textFieldClasses}
						type="search"
						placeholder={placeholder}
						value=""
						readOnly
						style={textFieldStyles}
					/>
					{/* Outer button div is a plain flex item — no Gutenberg wrappers on it.
					    Inner blocks container lives one level deeper so editor internals
					    don't interfere with the flex row layout. */}
					<div className="jankx-search-form__button">
						<div {...innerBlocksProps} />
					</div>
				</div>
			</div>
		</>
	);
}
