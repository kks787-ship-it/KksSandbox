/* Horse profile editor: choose additional photographs from the media library. */
( function ( $ ) {
	'use strict';

	var frame;
	var $input = $( '#csf-photos' );
	var $preview = $( '#csf-photo-preview' );

	$( '#csf-photos-select' ).on( 'click', function ( event ) {
		event.preventDefault();
		if ( ! frame ) {
			frame = wp.media( {
				title: 'Choose horse photographs',
				library: { type: 'image' },
				multiple: true
			} );
			frame.on( 'select', function () {
				var ids = [];
				$preview.empty();
				frame.state().get( 'selection' ).each( function ( attachment ) {
					var data = attachment.toJSON();
					var src = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
					ids.push( data.id );
					$preview.append( $( '<img>', { src: src, alt: '' } ) );
				} );
				$input.val( ids.join( ',' ) );
			} );
		}
		frame.open();
	} );

	$( '#csf-photos-clear' ).on( 'click', function ( event ) {
		event.preventDefault();
		$input.val( '' );
		$preview.empty();
	} );
}( jQuery ) );
