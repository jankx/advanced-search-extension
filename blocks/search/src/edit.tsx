import { useBlockProps, InspectorControls, InnerBlocks, useInnerBlocksProps } from '@wordpress/block-editor';
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

	const inputId = `wp-block-search__input-${clientId}`;
	const insideWrapperStyle = width ? { width: `${width}${widthUnit}` } : undefined;

	const resolvedTextColor: string | undefined =
		(blockStyle as any)?.color?.text ||
		(textColor ? `var(--wp--preset--color--${textColor})` : undefined);

	const blockProps = useBlockProps({
		className: `wp-block-search__${buttonPosition}`,
	});

	const textStyle: { color?: string } = resolvedTextColor
		? { color: resolvedTextColor }
		: {};

	const buttonWrapperProps = useInnerBlocksProps(
		{ className: 'wp-block-search__button' },
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
					<div className="wp-block-search__inspector-controls">
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
					className={showLabel ? 'wp-block-search__label' : 'screen-reader-text'}
					htmlFor={inputId}
					style={textStyle}
				>
					{label || __('Tìm kiếm', 'jankx')}
				</label>
				<div
					className="wp-block-search__inside-wrapper"
					style={{
						...insideWrapperStyle,
						...(resolvedTextColor ? { ['--jankx-search-placeholder-color' as string]: resolvedTextColor } : {}),
					}}
				>
					<input
						id={inputId}
						className="wp-block-search__input"
						type="search"
						placeholder={placeholder}
						value=""
						readOnly
						style={textStyle}
					/>
					<div {...buttonWrapperProps} />
				</div>
			</div>
		</>
	);
}
