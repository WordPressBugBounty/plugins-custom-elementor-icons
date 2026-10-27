/* Editor side of the dynamic "Custom Icon" block: the icon is rendered by PHP and shown through ServerSideRender.
   The uploaded icon fonts arrive in window.ceiCustomIconData (see Block::enqueue_editor_data). No build step. */
( function ( blocks, element, blockEditor, components, i18n, serverSideRender ) {
	var el = element.createElement;
	var useState = element.useState;
	var __ = i18n.__;
	var data = window.ceiCustomIconData || { fonts: [], adminUrl: '' };
	var MAX_SHOWN = 300;

	function noFontsHint() {
		var link = data.adminUrl
			? el( 'a', { href: data.adminUrl }, __( 'Upload an icon font', 'custom-elementor-icons' ) )
			: null;
		return el(
			'p',
			{ className: 'cei-block-hint' },
			__( 'No icon fonts uploaded yet.', 'custom-elementor-icons' ),
			' ',
			link
		);
	}

	function IconPicker( props ) {
		var search = useState( '' );
		var fontIndex = useState( 0 );
		var q = search[ 0 ].toLowerCase();
		var font = data.fonts[ Math.min( fontIndex[ 0 ], data.fonts.length - 1 ) ];
		var matches = font.icons.filter( function ( icon ) {
			return ! q || ( icon.class + ' ' + icon.tags ).toLowerCase().indexOf( q ) !== -1;
		} );
		var shown = matches.slice( 0, MAX_SHOWN );

		return el(
			'div',
			null,
			data.fonts.length > 1
				? el( components.SelectControl, {
						label: __( 'Icon set', 'custom-elementor-icons' ),
						value: String( fontIndex[ 0 ] ),
						options: data.fonts.map( function ( f, i ) {
							return { label: f.name, value: String( i ) };
						} ),
						onChange: function ( v ) {
							fontIndex[ 1 ]( parseInt( v, 10 ) );
						},
				  } )
				: null,
			el( components.SearchControl || components.TextControl, {
				label: __( 'Search icons', 'custom-elementor-icons' ),
				value: search[ 0 ],
				onChange: function ( v ) {
					search[ 1 ]( v );
				},
			} ),
			el(
				'div',
				{ className: 'cei-icon-grid', style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(44px, 1fr))', gap: '4px', maxHeight: '260px', overflowY: 'auto', marginTop: '8px' } },
				shown.map( function ( icon ) {
					var selected = props.font === font.name && props.icon === icon.class;
					return el(
						'button',
						{
							key: icon.class,
							type: 'button',
							title: icon.class,
							'aria-label': icon.class,
							'aria-pressed': selected,
							className: 'components-button' + ( selected ? ' is-pressed' : '' ),
							style: { height: '44px', justifyContent: 'center', fontSize: '22px', border: selected ? '2px solid #007cba' : '1px solid #ddd' },
							onClick: function () {
								props.onPick( font.name, icon.class );
							},
						},
						el( 'i', { className: font.prefix + icon.class, 'aria-hidden': 'true' } )
					);
				} )
			),
			matches.length > MAX_SHOWN
				? el( 'p', null, __( 'Showing the first 300 icons, refine the search to see more.', 'custom-elementor-icons' ) )
				: null,
			! matches.length ? el( 'p', null, __( 'No icons found.', 'custom-elementor-icons' ) ) : null
		);
	}

	blocks.registerBlockType( 'custom-elementor-icons/custom-icon', {
		edit: function ( props ) {
			var a = props.attributes;
			var hasFonts = data.fonts && data.fonts.length;
			var blockProps = blockEditor.useBlockProps();
			var Align = blockEditor.AlignmentControl || blockEditor.AlignmentToolbar;

			function set( obj ) {
				props.setAttributes( obj );
			}

			var preview;
			if ( ! hasFonts ) {
				preview = noFontsHint();
			} else if ( ! a.icon ) {
				preview = el( 'p', { className: 'cei-block-hint' }, __( 'Choose an icon in the block sidebar.', 'custom-elementor-icons' ) );
			} else {
				preview = el( serverSideRender, { block: 'custom-elementor-icons/custom-icon', attributes: a } );
			}

			return el(
				'div',
				blockProps,
				el(
					blockEditor.BlockControls,
					null,
					el( Align, {
						value: a.alignment,
						onChange: function ( v ) {
							set( { alignment: v || 'left' } );
						},
					} )
				),
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Icon', 'custom-elementor-icons' ) },
						hasFonts
							? el( IconPicker, {
									font: a.font,
									icon: a.icon,
									onPick: function ( font, icon ) {
										set( { font: font, icon: icon } );
									},
							  } )
							: noFontsHint()
					),
					el(
						components.PanelBody,
						{ title: __( 'Settings', 'custom-elementor-icons' ), initialOpen: true },
						el( components.RangeControl, {
							label: __( 'Size (px)', 'custom-elementor-icons' ),
							value: a.size,
							min: 8,
							max: 200,
							onChange: function ( v ) {
								set( { size: v || 32 } );
							},
						} ),
						el( components.TextControl, {
							label: __( 'Accessible label', 'custom-elementor-icons' ),
							help: __( 'Leave empty if the icon is decorative.', 'custom-elementor-icons' ),
							value: a.label,
							onChange: function ( v ) {
								set( { label: v } );
							},
						} )
					)
				),
				preview
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n, window.wp.serverSideRender );
