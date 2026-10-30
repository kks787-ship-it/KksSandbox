/**
 * Editor UI for Corner Stone Farm dynamic blocks. No build step: block titles, icons and
 * attributes come from the PHP registration; this file only adds settings controls and a
 * live server-rendered preview. Blocks save nothing to post content but their attributes.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var ServerSideRender = wp.serverSideRender;
	var defs = window.csfBlockControls || {};

	function control( def, props ) {
		var attr = def[ 0 ];
		var type = def[ 1 ];
		var label = def[ 2 ];
		var value = props.attributes[ attr ];
		var set = function ( v ) {
			var next = {};
			next[ attr ] = v;
			props.setAttributes( next );
		};
		if ( 'toggle' === type ) {
			return el( ToggleControl, { key: attr, label: label, checked: !! value, onChange: set, __nextHasNoMarginBottom: true } );
		}
		if ( 'select' === type ) {
			var options = Object.keys( def[ 3 ] ).map( function ( k ) {
				return { value: k, label: def[ 3 ][ k ] };
			} );
			return el( SelectControl, { key: attr, label: label, value: value, options: options, onChange: set, __nextHasNoMarginBottom: true, __next40pxDefaultSize: true } );
		}
		if ( 'number' === type ) {
			return el( TextControl, { key: attr, type: 'number', min: 1, max: 24, label: label, value: value, onChange: function ( v ) { set( parseInt( v, 10 ) || 1 ); }, __nextHasNoMarginBottom: true, __next40pxDefaultSize: true } );
		}
		return el( TextControl, { key: attr, label: label, value: value || '', onChange: set, __nextHasNoMarginBottom: true, __next40pxDefaultSize: true } );
	}

	Object.keys( defs ).forEach( function ( name ) {
		wp.blocks.registerBlockType( name, {
			edit: function ( props ) {
				var inspector = defs[ name ].length
					? el( InspectorControls, null, el( PanelBody, { title: wp.i18n.__( 'Settings', 'csf-core' ) }, defs[ name ].map( function ( d ) { return control( d, props ); } ) ) )
					: null;
				return el(
					'div',
					useBlockProps(),
					inspector,
					el( ServerSideRender, { block: name, attributes: props.attributes, skipBlockSupportAttributes: true } )
				);
			},
			save: function () {
				return null;
			}
		} );
	} );
}( window.wp ) );
