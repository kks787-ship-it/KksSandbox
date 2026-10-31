#!/usr/bin/env node
/** Disposable fixture only. Its MU plugin intercepts all outgoing mail. */
'use strict';
const assert = require( 'assert/strict' );
const fs = require( 'fs' );
const path = require( 'path' );
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
		await page.goto( BASE + '/contact/?csf_inquiry=submitted#csf-inquiry' );
		check( await page.getByRole( 'status' ).count() === 0, 'forged submitted URL cannot display an acknowledgment' );
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
		const publicChallenge = await page.request.get( BASE + '/contact/?csf_inquiry=' + encodeURIComponent( payload.challenge ) );
		check( ! ( await publicChallenge.text() ).includes( 'Your inquiry was accepted for sending.' ), 'a public form challenge is not a confirmation receipt' );
		await page.waitForTimeout( 3100 );
		const sent = page.waitForResponse( res => res.request().method() === 'POST' && res.url().includes( '/wp-admin/admin-post.php' ) );
		const arrival = page.waitForResponse( res => res.request().method() === 'GET' && new URL( res.url() ).pathname === '/contact/' && new URL( res.url() ).searchParams.has( 'csf_inquiry' ) );
		await form.locator( 'button' ).click();
		const posted = await sent;
		check( posted.status() === 303, 'valid HTTP POST redirects with 303' );
		const destination = new URL( await posted.headerValue( 'location' ), BASE );
		const receipt = destination.searchParams.get( 'csf_inquiry' );
		check( receipt && receipt !== 'submitted' && receipt.split( ':' ).length === 3 && Array.from( destination.searchParams.keys() ).join() === 'csf_inquiry' && ! destination.href.includes( 'visitor' ), 'redirect carries only a signed receipt, with no sender/message details' );
		await page.waitForURL( url => url.searchParams.get( 'csf_inquiry' ) === receipt && url.hash === '#csf-inquiry' );
		check( await page.getByRole( 'status' ).isVisible() && ( await page.getByRole( 'status' ).textContent() ).includes( 'accepted for sending' ), 'successful POST acknowledgment means transport acceptance, not inbox delivery' );
		const arrived = await arrival;
		check( ( ( await arrived.headerValue( 'cache-control' ) ) || '' ).includes( 'no-store' ) && await arrived.headerValue( 'referrer-policy' ) === 'no-referrer', 'receipt page prevents shared caching and outgoing receipt referrers' );
		await page.reload();
		check( await page.getByRole( 'status' ).count() === 0, 'refresh cannot replay the acknowledgment' );
		const other = await browser.newContext();
		const reused = await other.request.get( destination.href );
		check( ! ( await reused.text() ).includes( 'Your inquiry was accepted for sending.' ), 'used receipt cannot acknowledge again in another browser context' );
		await other.close();
		const replay = await page.request.post( BASE + '/wp-admin/admin-post.php', { form: payload } );
		check( replay.status() === 429, 'HTTP replay is rejected without another mail call' );
		const invalid = await page.request.post( BASE + '/wp-admin/admin-post.php', { form: { ...payload, _csf_nonce: 'invalid' } } );
		check( invalid.status() === 400, 'invalid nonce HTTP request is rejected' );
		const get = await page.request.get( BASE + '/wp-admin/admin-post.php?action=csf_inquiry' );
		check( get.status() === 400, 'GET cannot submit inquiries' );
		// A second fresh form reaches the mocked transport failure path, never real email.
		await page.goto( BASE + '/contact/' );
		const failedPayload = await page.locator( '.csf-inquiry form' ).evaluate( el => Object.fromEntries( new FormData( el ) ) );
		Object.assign( failedPayload, { name: 'Synthetic Failure', email: 'failure@example.invalid', message: 'Synthetic failure case.', consent: '1' } );
		fs.writeFileSync( path.join( path.dirname( LOG ), 'mail-fail' ), '1' );
		await page.waitForTimeout( 3100 );
		const rejected = await page.request.post( BASE + '/wp-admin/admin-post.php', { form: failedPayload, maxRedirects: 0 } );
		check( rejected.status() === 503 && ! rejected.headers().location && ! ( await rejected.text() ).includes( 'Your inquiry was accepted for sending.' ), 'mail failure returns an error without a receipt redirect or acknowledgment' );
		await page.goto( BASE + '/contact/?csf_inquiry=submitted' );
		check( await page.getByRole( 'status' ).count() === 0, 'forged flag after mail failure still cannot display acknowledgment' );
		check( fs.readFileSync( LOG, 'utf8' ).trim() === 'mock accepted', 'exactly one transport call, intercepted by fixture MU plugin' );
		console.log( checks + ' local inquiry browser checks passed; no real mail sent.' );
	} finally { await browser.close(); }
}
main().catch( error => { console.error( error.message ); process.exitCode = 1; } );
