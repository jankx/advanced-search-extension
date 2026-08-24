import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
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

	return (
		<Modal
			title={__('Choose an icon', 'jankx')}
			isOpen={isOpen}
			onClose={onClose}
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
	} = attributes;

	const [isIconPickerOpen, setIsIconPickerOpen] = useState(false);

	const inputId = `wp-block-search__input-${clientId}`;
	const showButton = buttonPosition !== 'no-button';
	const insideWrapperStyle = width ? { width: `${width}${widthUnit}` } : undefined;

	const blockProps = useBlockProps({
		className: [
			`wp-block-search__${buttonPosition}`,
			buttonUseIcon ? 'wp-block-search__icon-button' : '',
			iconName ? 'has-custom-icon' : '',
		].filter(Boolean).join(' ').trim(),
	});

	const hasIconBackground = !!(iconBackgroundColor || iconBackgroundValue);

	const iconButtonStyle = hasIconBackground ? {
		backgroundColor: iconBackgroundValue || undefined,
		padding: iconPadding || undefined,
		borderRadius: iconBorderRadius || undefined,
	} : undefined;

	const renderedIcon = iconName
		? renderIcon(iconName, iconSet, iconSize, iconColor)
		: null;

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
					<div style={{ marginBottom: '12px' }}>
						<label className="components-base-control__label" style={{ display: 'block', marginBottom: '6px' }}>
							{__('Select icon', 'jankx')}
						</label>
						<Button
							isSecondary
							onClick={() => setIsIconPickerOpen(true)}
							style={{ width: '100%', justifyContent: 'flex-start', gap: '8px' }}
						>
							{renderedIcon || <span style={{ opacity: 0.5 }}>{__('Choose an icon', 'jankx')}</span>}
						</Button>
						{iconName && (
							<Button
								isDestructive
								isLink
								onClick={() => setAttributes({ iconName: '', iconSet: 'material' })}
								style={{ marginTop: '4px' }}
							>
								{__('Remove icon', 'jankx')}
							</Button>
						)}
						<IconPickerModal
							isOpen={isIconPickerOpen}
							onClose={() => setIsIconPickerOpen(false)}
							onSelect={(icon) => setAttributes({
								iconName: icon.name,
								iconSet: icon.iconSet,
							})}
							currentIcon={iconName}
							currentSet={iconSet}
						/>
					</div>

					{iconName && (
						<>
							<div className="components-base-control">
								<label className="components-base-control__label">
									{__('Icon size', 'jankx')}
								</label>
								<div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
									<input
										type="range"
										min={12}
										max={64}
										value={parseInt(iconSize) || 24}
										onChange={(e) => setAttributes({ iconSize: `${e.target.value}px` })}
										style={{ flex: 1 }}
									/>
									<span style={{ minWidth: '40px', textAlign: 'right' }}>{iconSize}</span>
								</div>
							</div>

							<div className="components-base-control">
								<label className="components-base-control__label">
									{__('Icon color', 'jankx')}
								</label>
								<div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
									<input
										type="color"
										value={iconColor || '#333333'}
										onChange={(e) => setAttributes({ iconColor: e.target.value })}
										style={{ width: '40px', height: '32px', padding: '2px', border: '1px solid #ccc', borderRadius: '4px', cursor: 'pointer' }}
									/>
									<input
										type="text"
										value={iconColor}
										onChange={(e) => setAttributes({ iconColor: e.target.value })}
										placeholder="#333333"
										style={{ flex: 1 }}
									/>
								</div>
							</div>

							<div className="components-base-control">
								<label className="components-base-control__label">
									{__('Icon background color', 'jankx')}
								</label>
								<div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
									<input
										type="color"
										value={iconBackgroundValue || '#f0f0f0'}
										onChange={(e) => setAttributes({ iconBackgroundValue: e.target.value, iconBackgroundColor: 'custom' })}
										style={{ width: '40px', height: '32px', padding: '2px', border: '1px solid #ccc', borderRadius: '4px', cursor: 'pointer' }}
									/>
									<input
										type="text"
										value={iconBackgroundValue}
										onChange={(e) => setAttributes({ iconBackgroundValue: e.target.value, iconBackgroundColor: e.target.value ? 'custom' : '' })}
										placeholder="transparent"
										style={{ flex: 1 }}
									/>
								</div>
							</div>

							<div className="components-base-control">
								<label className="components-base-control__label">
									{__('Icon padding', 'jankx')}
								</label>
								<input
									type="text"
									value={iconPadding}
									onChange={(e) => setAttributes({ iconPadding: e.target.value })}
									placeholder="8px"
									style={{ width: '100%' }}
								/>
							</div>

							<div className="components-base-control">
								<label className="components-base-control__label">
									{__('Icon border radius', 'jankx')}
								</label>
								<input
									type="text"
									value={iconBorderRadius}
									onChange={(e) => setAttributes({ iconBorderRadius: e.target.value })}
									placeholder="4px"
									style={{ width: '100%' }}
								/>
							</div>
						</>
					)}
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
						<button
							className="wp-block-search__button wp-element-button"
							type="submit"
							style={iconButtonStyle}
						>
							{buttonUseIcon ? (
								renderedIcon || (
									<svg className="search-icon" viewBox="0 0 24 24" width="24" height="24">
										<path d="M13 5c-3.3 0-6 2.7-6 6 0 1.4.5 2.7 1.3 3.7l-3.8 3.8 1.1 1.1 3.8-3.8c1 .8 2.3 1.3 3.7 1.3 3.3 0 6-2.7 6-6S16.3 5 13 5zm0 10.5c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5 4.5 2 4.5 4.5-2 4.5-4.5 4.5z"></path>
									</svg>
								)
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
