/** Shared browser-side geometry checks for real Kadence and the protected sandbox. */
'use strict';
module.exports = function layoutMetrics() {
	const failures = [];
	const bounds = element => {
		const r = element.getBoundingClientRect();
		return { left: r.left, right: r.right, width: r.width };
	};
	const visible = element => element.getClientRects().length > 0 && getComputedStyle( element ).visibility !== 'hidden' && element.getBoundingClientRect().width > 0;
	const root = document.querySelector( '.entry-content' ) || document.querySelector( '.editor-styles-wrapper' ) || document.body;
	// Custom properties retain clamp()/rem tokens in getComputedStyle; resolve a real length.
	const probe = document.createElement( 'span' );
	probe.style.cssText = 'position:absolute;display:block;visibility:hidden;width:var(--csf-gutter);height:0;padding:0;border:0';
	root.appendChild( probe );
	const gutter = probe.getBoundingClientRect().width || 16;
	probe.remove();
	const viewport = document.documentElement.clientWidth;
	const headers = Array.from( root.querySelectorAll( '.csf-page-header, .csf-hero' ) ).filter( visible ).map( section => {
		const title = section.querySelector( 'h1' );
		const lead = section.querySelector( '.is-style-csf-lead' );
		if ( ! title || ! lead ) { return null; }
		const data = { title: bounds( title ), lead: bounds( lead ), section: bounds( section ), padding: getComputedStyle( section ).paddingLeft };
		if ( Math.abs( data.title.left - data.lead.left ) > 1 ) { failures.push( 'header/hero lead does not align with title' ); }
		return data;
	} ).filter( Boolean );
	const sections = Array.from( root.querySelectorAll( '.wp-block-group.alignfull[class*="is-style-csf-section-"], .wp-block-cover.alignfull.csf-hero' ) ).filter( visible ).map( section => {
		const rect = bounds( section );
		const padding = parseFloat( getComputedStyle( section ).paddingLeft );
		if ( padding < gutter - 1 ) { failures.push( 'full-width section lost its inner gutter' ); }
		if ( Math.abs( rect.left ) > 1 || Math.abs( rect.right - viewport ) > 1 ) { failures.push( 'section background does not span the viewport' ); }
		return { classes: section.className, ...rect, padding };
	} );
	let textCount = 0;
	const textAtEdge = [];
	for ( const element of root.querySelectorAll( 'p,h1,h2,h3,h4,li,.wp-block-button__link,.csf-book-button' ) ) {
		if ( ! element.closest( '[class*="is-style-csf-section-"], .csf-hero' ) || ! visible( element ) || ! element.textContent.trim() ) { continue; }
		++textCount;
		// RichText uses pre-wrap; hanging spaces extend Range boxes without painting ink.
		// Measure words, including those inside inline links, rather than trailing spaces.
		const walker = document.createTreeWalker( element, NodeFilter.SHOW_TEXT );
		let left = Infinity;
		let right = -Infinity;
		while ( walker.nextNode() ) {
			for ( const word of walker.currentNode.data.matchAll( /\S+/g ) ) {
				const range = document.createRange();
				range.setStart( walker.currentNode, word.index );
				range.setEnd( walker.currentNode, word.index + word[ 0 ].length );
				for ( const r of range.getClientRects() ) {
					if ( r.width > 0 ) { left = Math.min( left, r.left ); right = Math.max( right, r.right ); }
				}
			}
		}
		if ( left < gutter - 1 || right > viewport - gutter + 1 ) {
			textAtEdge.push( { tag: element.tagName, classes: element.className, left, right } );
		}
	}
	if ( textAtEdge.length ) { failures.push( 'section text extends into the viewport gutters' ); }
	const contentAtEdge = Array.from( root.querySelectorAll( '.wp-block-columns,.wp-block-image,.csf-card,.csf-book-button' ) ).filter( element => visible( element ) && element.closest( '[class*="is-style-csf-section-"], .csf-hero' ) ).map( element => ( { classes: element.className, ...bounds( element ) } ) ).filter( r => r.left < gutter - 1 || r.right > viewport - gutter + 1 );
	if ( contentAtEdge.length ) { failures.push( 'columns, cards, images or booking controls extend into viewport gutters' ); }
	const overflow = Math.max( document.documentElement.scrollWidth, document.body.scrollWidth ) - viewport;
	if ( overflow > 1 ) { failures.push( 'horizontal overflow' ); }
	return { viewport, gutter, headers, sections, textCount, textAtEdge, contentAtEdge, overflow, failures: Array.from( new Set( failures ) ) };
};
