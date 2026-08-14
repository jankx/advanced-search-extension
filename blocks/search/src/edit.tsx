import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, TextControl, SelectControl, NumberControl, UnitControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const WIDTH_UNITS = [
	{ value: 'px', label: 'px' },
	{ value: 'em', label: 'em' },
	{ value: 'rem', label: 'rem' },
];

const BUTTON_POSITIONS = [
	{ value: 'button-outside', label: __('Outside', 'jankx') },
	{ value: 'button-inside', label: __('Inside', 'jankx') },
	{ value: 'no-button', label: __('No button', 'jankx') },
];

export default function SearchEdit({ attributes, setAttributes, clientId }) {
	const {
		label = '',
		showLabel = true,
		placeholder = '',
		width,
		widthUnit = 'px',
		buttonText = '',
		buttonPosition = 'button-outside',
		buttonUseIcon = false,
	} = attributes;

	const inputId = `wp-block-search__input-${clientId}`;
	const showButton = buttonPosition !== 'no-button';
	const insideWrapperStyle = width ? { width: `${width}${widthUnit}` } : undefined;

	const blockProps = useBlockProps({
		className: `wp-block-search__${buttonPosition} ${buttonUseIcon ? 'wp-block-search__icon-button' : ''}`.trim(),
	});

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
					<TextControl
						label={__('Button label', 'jankx')}
						value={buttonText}
						onChange={(value) => setAttributes({ buttonText: value })}
					/>
					<SelectControl
						label={__('Button position', 'jankx')}
						value={buttonPosition}
						options={BUTTON_POSITIONS}
						onChange={(value) => setAttributes({ buttonPosition: value })}
					/>
					<ToggleControl
						label={__('Use icon button', 'jankx')}
						checked={buttonUseIcon}
						onChange={(value) => setAttributes({ buttonUseIcon: value })}
					/>
					<div className="wp-block-search__inspector-controls">
						<NumberControl
							label={__('Width', 'jankx')}
							value={width ?? ''}
							min={0}
							onChange={(value) => setAttributes({ width: value !== '' ? Number(value) : undefined })}
						/>
						<UnitControl
							label={__('Width unit', 'jankx')}
							value={widthUnit}
							units={WIDTH_UNITS}
							onChange={(value) => setAttributes({ widthUnit: value })}
						/>
					</div>
				</PanelBody>
			</InspectorControls>

			<form {...blockProps} role="search" method="get" action="#">
				<label
					className={showLabel ? 'wp-block-search__label' : 'screen-reader-text'}
					htmlFor={inputId}
				>
					{label || __('Tìm kiếm', 'jankx')}
				</label>
				<div className="wp-block-search__inside-wrapper" style={insideWrapperStyle}>
					<input
						id={inputId}
						className="wp-block-search__input"
						type="search"
						placeholder={placeholder}
						value=""
						readOnly
					/>
					{showButton && (
						<button className="wp-block-search__button wp-element-button" type="submit">
							{buttonUseIcon ? (
								<svg className="search-icon" viewBox="0 0 24 24" width="24" height="24">
									<path d="M13 5c-3.3 0-6 2.7-6 6 0 1.4.5 2.7 1.3 3.7l-3.8 3.8 1.1 1.1 3.8-3.8c1 .8 2.3 1.3 3.7 1.3 3.3 0 6-2.7 6-6S16.3 5 13 5zm0 10.5c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5 4.5 2 4.5 4.5-2 4.5-4.5 4.5z"></path>
								</svg>
							) : (
								buttonText || __('Tìm kiếm', 'jankx')
							)}
						</button>
					)}
				</div>
			</form>
		</>
	);
}
