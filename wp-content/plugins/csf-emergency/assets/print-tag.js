/* Render QR codes for printable horse tags. Error correction level Q (~25%) tolerates
   scuffing and dirt on a halter tag. Margin is in SVG units: 32 = the 4-module quiet zone. */
( function () {
	'use strict';
	document.querySelectorAll( '[data-csf-qr]' ).forEach( function ( el ) {
		var qr = qrcode( 0, 'Q' );
		qr.addData( el.getAttribute( 'data-csf-qr' ) );
		qr.make();
		el.innerHTML = qr.createSvgTag( { cellSize: 8, margin: 32, scalable: true, alt: el.getAttribute( 'data-csf-alt' ) || '' } );
	} );
}() );
