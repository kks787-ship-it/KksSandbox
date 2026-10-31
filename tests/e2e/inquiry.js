#!/usr/bin/env node
/** Disposable fixture only. Its MU plugin intercepts all outgoing mail. */
'use strict';
const assert = require( 'assert/strict' );
const fs = require( 'fs' );
const { chromium } = require( 'playwright' );
const BASE = process.env.CSF_INQUIRY_TEST_BASE;
const LOG = process.env.CSF_INQUIRY_TEST_MAIL_LOG;
if ( ! /^http:\/\/127\.0\.0\.1:\d+$/.test( BASE || '' ) || ! /^\/tmp\/csf-setup-regression\.[^/]+\/mail\.log$/.test( LOG || '' ) ) {
	throw new Error( 'Use CSF_INQUIRY_BROWSER=1 bash tests/integration/run-setup-reporting.sh.' );
}
let checks = 0;
function check( value, message ) { assert.ok( value, message ); ++checks; console.log( 'PASS: ' + message ); }
async function main() {
	const browser = await chromium.launch( process.env.CSF_CHROMIUM_EXECUTABLE ? { executablePath: process.env.CSF_CHROMIUM_EXECUTABLE } : {} );
	try {
		for ( const width of [ 390, 768, 1280 ] ) {
			const page = await browser.newPage( { viewport: { width, height: 900 } } );
			const response = await page.goto( BASE + '/contact/' );
			check( response.status() === 200 && await page.locator( '.csf-inquiry form' ).isVisible(), 'form renders over HTTP at ' + width + 'px (substitute theme)' );
			check( await page.evaluate( () => document.documentElement.scrollWidth <= innerWidth + 1 ), 'no horizontal overflow at ' + width + 'px' );
			await page.addScriptTag( { path: require.resolve( 'axe-core/axe.min.js' ) } );
			const violations = await page.evaluate( async () => ( await axe.run( '.csf-inquiry', { runOnly: { type: 'tag', values: [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ] } } ) ).violations.map( v => v.id ) );
			check( ! violations.length, 'inquiry axe checks at ' + width + 'px: ' + JSON.stringify( violations ) );
			await page.close();
		}
		const page = await browser.newPage();
		await page.goto( BASE + '/contact/' );
		const form = page.locator( '.csf-inquiry form' );
		await form.locator( 'button' ).click();
		check( await form.locator( ':invalid' ).count() > 0 && page.url() === BASE + '/contact/', 'native required fields prevent empty submission' );
		await form.locator( '[name="name"]' ).fill( 'Synthetic Visitor' );
		await form.locator( '[name="name"]' ).focus();
		await page.keyboard.press( 'Tab' );
		check( await page.evaluate( () => document.activeElement.name === 'email' ), 'keyboard moves directly from name to email, skipping honeypot' );
		await form.locator( '[name="email"]' ).fill( 'visitor@example.invalid' );
		await form.locator( '[name="message"]' ).fill( 'Synthetic browser inquiry; no personal information.' );
		await form.locator( '[name="consent"]' ).check();
		const payload = await form.evaluate( el => Object.fromEntries( new FormData( el ) ) );
		await page.waitForTimeout( 3100 );
		const sent = page.waitForResponse( res => res.request().method() === 'POST' && res.url().includes( '/wp-admin/admin-post.php' ) );
		await form.locator( 'button' ).click();
		check( ( await sent ).status() === 303, 'valid HTTP POST redirects with 303' );
		await page.waitForURL( '**/contact/?csf_inquiry=submitted#csf-inquiry' );
		check( await page.getByRole( 'status' ).isVisible() && ! page.url().includes( 'visitor' ), 'confirmation is visible without PII in URL' );
		const replay = await page.request.post( BASE + '/wp-admin/admin-post.php', { form: payload } );
		check( replay.status() === 429, 'HTTP replay is rejected without another mail call' );
		const invalid = await page.request.post( BASE + '/wp-admin/admin-post.php', { form: { ...payload, _csf_nonce: 'invalid' } } );
		check( invalid.status() === 400, 'invalid nonce HTTP request is rejected' );
		const get = await page.request.get( BASE + '/wp-admin/admin-post.php?action=csf_inquiry' );
		check( get.status() === 400, 'GET cannot submit inquiries' );
		check( fs.readFileSync( LOG, 'utf8' ).trim() === 'mock accepted', 'exactly one transport call, intercepted by fixture MU plugin' );
		console.log( checks + ' local inquiry browser checks passed; no real mail sent.' );
	} finally { await browser.close(); }
}
main().catch( error => { console.error( error.message ); process.exitCode = 1; } );
