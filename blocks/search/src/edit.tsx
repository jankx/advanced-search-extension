import { useBlockProps, InspectorControls, InnerBlocks, useInnerBlocksProps } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
	Button,
	Modal,
	SearchControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

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

const ICON_SETS = [
	{ value: 'material', label: __('Material Icons', 'jankx') },
	{ value: 'fontawesome', label: __('Font Awesome', 'jankx') },
	{ value: 'dashicons', label: __('Dashicons', 'jankx') },
];

const MATERIAL_ICONS = [
	'search', 'home', 'settings', 'person', 'favorite', 'star', 'menu',
	'close', 'arrow_back', 'arrow_forward', 'check', 'add', 'remove',
	'edit', 'delete', 'visibility', 'visibility_off', 'lock', 'unlock',
	'email', 'phone', 'location_on', 'date_range', 'schedule', 'event',
	'shopping_cart', 'shopping_bag', 'store', 'payment', 'credit_card',
	'account_balance', 'work', 'business', 'school', 'class', 'book',
	'library_books', 'menu_book', 'article', 'description', 'note',
	'calendar_today', 'alarm', 'timer', 'update', 'sync', 'refresh',
	'download', 'upload', 'share', 'link', 'attachment', 'cloud',
	'cloud_upload', 'cloud_download', 'folder', 'folder_open', 'image',
	'photo_library', 'camera_alt', 'videocam', 'mic', 'music_note',
	'play_arrow', 'pause', 'stop', 'skip_next', 'skip_previous',
	'forward', 'replay', 'volume_up', 'volume_down', 'volume_off',
	'wifi', 'bluetooth', 'battery_full', 'battery_alert', 'flashlight_on',
	'flashlight_off', 'brightness_high', 'brightness_low', 'contrast',
	'palette', 'brush', 'format_paint', 'color_lens', 'style',
	'text_fields', 'format_bold', 'format_italic', 'format_underline',
	'format_align_left', 'format_align_center', 'format_align_right',
	'code', 'computer', 'smartphone', 'tablet', 'watch', 'headphones',
	'speaker', 'keyboard', 'mouse', 'printer', 'scanner',
	'forum', 'chat', 'mail', 'send', 'notifications', 'notifications_active',
	'group', 'groups', 'people', 'person_add', 'public', 'language',
	'earth', 'explore', 'travel_explore', 'map', 'place', 'navigation',
	'route', 'directions', 'traffic', 'local_shipping', 'flight',
	'rocket', 'anchor', 'park', 'beach_access', 'pool',
	'fitness_center', 'sports_esports', 'sports_soccer', 'sports_basketball',
	'emoji_events', 'military_tech', 'workspace_premium', 'eco',
	'quickreply', 'support_agent', 'call', 'call_end', 'dialer_sip',
	'ring_volume', 'vibration', 'do_not_disturb', 'volume_up',
	'trending_up', 'trending_down', 'analytics', 'insights', 'monitoring',
	'leaderboard', 'assessment', 'query_stats', 'stacked_line_chart',
];

const FONTAWESOME_ICONS = [
	'search', 'home', 'cog', 'user', 'heart', 'star', 'bell',
	'envelope', 'phone', 'map-marker-alt', 'calendar', 'clock',
	'shopping-cart', 'credit-card', 'lock', 'unlock', 'eye', 'eye-slash',
	'edit', 'trash', 'plus', 'minus', 'check', 'times',
	'arrow-left', 'arrow-right', 'arrow-up', 'arrow-down',
	'chevron-left', 'chevron-right', 'chevron-up', 'chevron-down',
	'bars', 'times', 'search', 'filter', 'sort', 'sync',
	'download', 'upload', 'share', 'link', 'paperclip', 'cloud',
	'folder', 'file', 'image', 'video', 'music', 'microphone',
	'play', 'pause', 'stop', 'forward', 'backward',
	'wifi', 'bluetooth', 'battery-full', 'battery-quarter',
	'sun', 'moon', 'cloud-sun', 'cloud-rain', 'snowflake',
	'paint-brush', 'palette', 'pen', 'pencil-alt', 'eraser',
	'code', 'laptop', 'mobile-alt', 'tablet-alt', 'headphones',
	'speaker', 'keyboard', 'printer', 'server', 'database',
	'comments', 'comment', 'users', 'user-plus', 'globe',
	'map', 'compass', 'route', 'ship', 'plane', 'car',
	'leaf', 'tree', 'seedling', 'fire', 'bolt', 'trophy',
	'gamepad', 'dumbbell', 'football-ball', 'basketball-ball',
	'chart-line', 'chart-bar', 'chart-pie', 'chart-area',
	'life-ring', 'headset', 'phone-alt', 'fax',
];

const DASHICONS_ICONS = [
	'search', 'admin-home', 'admin-settings', 'admin-users', 'heart', 'star',
	'menu', 'dismiss', 'arrow-left', 'arrow-right', 'arrow-up', 'arrow-down',
	'check', 'plus', 'minus', 'edit', 'trash', 'visibility', 'visibility-hidden',
	'lock', 'unlock', 'email', 'phone', 'location', 'calendar',
	'clock', 'cart', 'credit-card', 'id', 'welcome-write-blog',
	'welcome-add-page', 'welcome-view-site', 'welcome-widgets-menus',
	'format-image', 'format-gallery', 'format-video', 'format-audio',
	'format-aside', 'format-link', 'format-quote', 'format-standard',
	'code', 'laptop', 'smartphone', 'tablet', 'desktop',
	'admin-comments', 'admin-multisite', 'admin-network', 'admin-site',
	'chart-bar', 'chart-line', 'chart-pie', 'chart-area',
	'nametag', 'businessman', 'id-alt', 'businesswoman',
	'car', 'car-alt', 'flight', 'shipping', 'offline',
	'wordpress', 'wordpress-alt', 'pressthis', 'satellite',
	'portfolio', 'book', 'book-alt', 'lightbulb',
	'masks', 'art', 'location-alt', 'public',
	'backup', 'warning', 'update', 'update-alt',
	'sync', 'share', 'share-alt', 'share-alt2',
];

function renderIcon(name, iconSet, size, color) {
	if (!name) return null;

	const style = {
		fontSize: size || '24px',
		color: color || undefined,
		lineHeight: 1,
		display: 'inline-flex',
		alignItems: 'center',
		justifyContent: 'center',
	};

	if (iconSet === 'material') {
		return <span className="material-icons" style={style}>{name}</span>;
	}
	if (iconSet === 'fontawesome') {
		return <i className={`fas fa-${name}`} style={style}></i>;
	}
	if (iconSet === 'dashicons') {
		return <span className={`dashicons dashicons-${name}`} style={style}></span>;
	}
	return <span className="material-icons" style={style}>{name}</span>;
}

function getIconList(iconSet) {
	switch (iconSet) {
		case 'fontawesome': return FONTAWESOME_ICONS;
		case 'dashicons': return DASHICONS_ICONS;
		default: return MATERIAL_ICONS;
	}
}

function IconPickerModal({ isOpen, onClose, onSelect, currentIcon, currentSet }) {
	const [search, setSearch] = useState('');
	const [activeSet, setActiveSet] = useState(currentSet || 'material');

	const icons = useMemo(() => {
		const list = getIconList(activeSet);
		if (!search) return list;
		const lower = search.toLowerCase();
		return list.filter((name) => name.toLowerCase().includes(lower));
	}, [activeSet, search]);

	if (!isOpen) return null;

	return (
		<Modal
			title={__('Choose an icon', 'jankx')}
			onRequestClose={onClose}
			className="jankx-icon-picker-modal"
			style={{ maxWidth: '500px' }}
		>
			<div style={{ marginBottom: '12px' }}>
				<SelectControl
					value={activeSet}
					options={ICON_SETS}
					onChange={(val) => setActiveSet(val)}
				/>
			</div>
			<SearchControl
				value={search}
				onChange={setSearch}
				placeholder={__('Search icons...', 'jankx')}
			/>
			<div style={{
				display: 'grid',
				gridTemplateColumns: 'repeat(auto-fill, minmax(44px, 1fr))',
				gap: '4px',
				maxHeight: '300px',
				overflowY: 'auto',
				padding: '8px 0',
			}}>
				{icons.map((name) => (
					<Button
						key={name}
						onClick={() => {
							onSelect({ name, iconSet: activeSet });
							onClose();
						}}
						title={name}
						style={{
							width: '40px',
							height: '40px',
							minWidth: '40px',
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							padding: '4px',
							border: currentIcon === name ? '2px solid #007cba' : '1px solid #ddd',
							borderRadius: '4px',
							cursor: 'pointer',
							fontSize: '18px',
						}}
					>
						{renderIcon(name, activeSet, '20px')}
					</Button>
				))}
			</div>
			{icons.length === 0 && (
				<p style={{ textAlign: 'center', padding: '20px', color: '#666' }}>
					__('No icons found', 'jankx')
				</p>
			)}
		</Modal>
	);
}

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
		iconName = '',
		iconSet = 'material',
		iconSize = '24px',
		iconColor = '',
		iconBackgroundColor = '',
		iconBackgroundValue = '',
		iconPadding = '8px',
		iconBorderRadius = '4px',
		// Block Supports — color
		textColor,
		style: blockStyle = {},
	} = attributes;

	const [isIconPickerOpen, setIsIconPickerOpen] = useState(false);

	const inputId = `wp-block-search__input-${clientId}`;
	const showButton = buttonPosition !== 'no-button';
	const insideWrapperStyle = width ? { width: `${width}${widthUnit}` } : undefined;

	// Resolve text color: custom value takes priority, then preset slug via CSS var.
	const resolvedTextColor: string | undefined =
		(blockStyle as any)?.color?.text ||
		(textColor ? `var(--wp--preset--color--${textColor})` : undefined);

	const blockProps = useBlockProps({
		className: [
			`wp-block-search__${buttonPosition}`,
			buttonUseIcon ? 'wp-block-search__icon-button' : '',
		].filter(Boolean).join(' ').trim(),
	});

	// Check if there are inner blocks (svg-icon placed inside button)
	const hasInnerBlocks = useSelect(
		(select: any) => {
			const { getBlockCount } = select('core/block-editor');
			return getBlockCount(clientId) > 0;
		},
		[clientId]
	);

	const hasIconBackground = !!(iconBackgroundColor || iconBackgroundValue);

	const iconButtonStyle: { [key: string]: string | undefined } = {
		...(hasIconBackground ? {
			backgroundColor: iconBackgroundValue || undefined,
			padding: iconPadding || undefined,
			borderRadius: iconBorderRadius || undefined,
		} : {}),
		...(resolvedTextColor ? { color: resolvedTextColor } : {}),
	};

	// Inline style for input/label elements in editor preview
	const textStyle: { color?: string } = resolvedTextColor
		? { color: resolvedTextColor }
		: {};

	// InnerBlocks props for the icon slot inside button
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'wp-block-search__button-icon' },
		{
			allowedBlocks: ['jankx/svg-icon'],
			template: [['jankx/svg-icon', {}]],
			templateLock: false,
			renderAppender: hasInnerBlocks ? false : InnerBlocks.ButtonBlockAppender,
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
						<SelectControl
							label={__('Width unit', 'jankx')}
							value={widthUnit}
							options={WIDTH_UNITS}
							onChange={(value) => setAttributes({ widthUnit: value })}
						/>
					</div>
				</PanelBody>

				<PanelBody title={__('Icon settings', 'jankx')} initialOpen={false}>
					<p style={{ fontSize: '12px', color: '#555', margin: 0 }}>
						{__('Enable "Use icon button" then click the button below to add a jankx/svg-icon block inside the search button. Use the svg-icon block\'s own settings panel to configure size, color, and appearance.', 'jankx')}
					</p>
				</PanelBody>
			</InspectorControls>

			<form {...blockProps} role="search" method="get" action="#">
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
						// Pass placeholder color as CSS variable for ::placeholder targeting in editor
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
					{showButton && (
						<button
							className="wp-block-search__button wp-element-button"
							type="submit"
							style={Object.keys(iconButtonStyle).length ? iconButtonStyle : undefined}
						>
							{buttonUseIcon ? (
								// When using icon mode: show InnerBlocks (jankx/svg-icon) or fallback SVG
								<div {...innerBlocksProps} />
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
