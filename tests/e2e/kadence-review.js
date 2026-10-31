#!/usr/bin/env node
/**
 * Authenticated page review only: no login, form submission, setup, or content edits.
 * Credentials come from a private JSON file outside Git, never command-line values.
 * Outputs are private under .wp-local/kadence-review/ (ignored by Git).
 */
'use strict';
const fs = require( 'fs' );
const path = require( 'path' );
const { chromium } = require( 'playwright' );
const layoutMetrics = require( './layout-metrics' );
const BASE = process.env.CSF_REVIEW_URL || 'https://sandbox.cornerstonefarmomak.com';
const ROOT = path.resolve( __dirname, '../..' );
const OUT = path.join( ROOT, '.wp-local/kadence-review' );
const PAGES = [
	'/', '/lessons/', '/about/meet-beth/', '/horse-training/', '/camps-clinics/', '/contact/',
	'/lessons/youth-riders/', '/lessons/adult-amateurs/', '/lessons/dressage-foundations/',
	'/lessons/off-site-lessons/', '/lessons/after-school-program/',
	'/horse-training/fei-young-horse-development/', '/horse-sales/',
	'/horse-sales/leasing-mentorship/', '/about/', '/about/meet-the-team/',
	'/about/meet-the-horses/', '/about/facilities/', '/resources/', '/resources/faq/', '/booking/',
];
const WIDTHS = [ 390, 768, 1280 ];

async function main() {
	if ( BASE !== 'https://sandbox.cornerstonefarmomak.com' && ! /^http:\/\/(localhost|127\.0\.0\.1):\d+$/.test( BASE ) ) {
		throw new Error( 'Only the exact sandbox origin or a disposable localhost site is allowed.' );
	}
	let httpCredentials;
	if ( process.env.CSF_HTTP_AUTH_FILE ) {
		const file = process.env.CSF_HTTP_AUTH_FILE;
		if ( fs.statSync( file ).mode & 0o077 ) {
			throw new Error( 'Basic Authentication file must have private permissions (0600).' );
		}
		let data;
		try { data = JSON.parse( fs.readFileSync( file, 'utf8' ) ); }
		catch { throw new Error( 'Cannot read a valid Basic Authentication JSON file; contents were not logged.' ); }
		if ( typeof data.username !== 'string' || ! data.username || typeof data.password !== 'string' || ! data.password ) {
			throw new Error( 'Basic Authentication file needs nonempty username and password fields.' );
		}
		httpCredentials = { username: data.username, password: data.password, origin: BASE };
	} else if ( BASE.startsWith( 'https:' ) ) {
		throw new Error( 'Authenticated review is blocked: provide CSF_HTTP_AUTH_FILE through secure workspace access, never chat.' );
	}
	fs.mkdirSync( OUT, { recursive: true, mode: 0o700 } );
	const browser = await chromium.launch( process.env.CSF_CHROMIUM_EXECUTABLE ? { executablePath: process.env.CSF_CHROMIUM_EXECUTABLE } : {} );
	const report = { origin: BASE, theme: 'Kadence required; version checked separately in WordPress', results: [], manual: [ 'Issue #1: shared intro/content gutters on ALL 21 pages; preserve full-bleed backgrounds', 'Visual header, palette, typography and authentic assets', 'Desktop submenu keyboard use and focus visibility', 'Kadence page metadata and Gutenberg editing/saving', 'Footer pattern 27 placement; booking destination acceptance' ] };
	try {
		for ( const width of WIDTHS ) {
			const context = await browser.newContext( { viewport: { width, height: width === 390 ? 844 : 1024 }, httpCredentials, reducedMotion: 'reduce', serviceWorkers: 'block' } );
			// Refuse browser writes. WordPress GETs may still trigger normal cache/cron work.
			await context.route( '**/*', route => [ 'GET', 'HEAD' ].includes( route.request().method() ) ? route.continue() : route.abort() );
			const page = await context.newPage();
			for ( const url of PAGES ) {
				const response = await page.goto( BASE + url, { waitUntil: 'networkidle' } );
				if ( response.status() === 401 ) {
					throw new Error( 'HTTP 401: authentication is unavailable or rejected. Page routes and Kadence rendering remain UNTESTED.' );
				}
				if ( new URL( page.url() ).origin !== BASE ) {
					throw new Error( 'Unexpected navigation outside the approved review origin.' );
				}
				const theme = await page.evaluate( () => document.body.classList.contains( 'wp-theme-kadence' ) || Array.from( document.querySelectorAll( 'link[href],script[src]' ) ).some( el => /\/themes\/kadence\//.test( el.href || el.src ) ) );
				if ( ! theme ) {
					throw new Error( 'Real Kadence was not detected; a substitute theme cannot establish compatibility.' );
				}
				const layout = await page.evaluate( () => ( {
					h1: document.querySelectorAll( 'h1' ).length,
					overflow: Math.max( document.documentElement.scrollWidth, document.body.scrollWidth ) - innerWidth,
					footerPatternPresent: !! document.querySelector( '.csf-footer' ),
				} ) );
				const result = { path: url, width, status: response.status(), ...layout };
				result.geometry = await page.evaluate( layoutMetrics );
				if ( url === '/' && width === 390 ) {
					const toggle = page.locator( '#mobile-toggle' );
					if ( await toggle.isVisible() ) {
						await toggle.focus();
						await page.keyboard.press( 'Enter' );
						// Kadence adds .active after a short drawer initialization timer.
						await page.locator( '#mobile-drawer.active' ).waitFor( { state: 'visible', timeout: 3000 } );
						result.mobileMenuOpened = await toggle.getAttribute( 'aria-expanded' ) === 'true';
						await page.keyboard.press( 'Escape' );
						await page.waitForFunction( () => ! document.querySelector( '#mobile-drawer' ).classList.contains( 'show-drawer' ), null, { timeout: 3000 } ).catch( () => null );
						result.mobileMenuClosedOnEscape = await toggle.getAttribute( 'aria-expanded' ) === 'false';
					} else {
						result.mobileMenuNeedsManualCheck = true;
					}
				}
				await page.addScriptTag( { path: require.resolve( 'axe-core/axe.min.js' ) } );
				result.accessibility = await page.evaluate( async () => ( await window.axe.run( document, { runOnly: { type: 'tag', values: [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ] } } ) ).violations.map( v => ( { id: v.id, impact: v.impact, targets: v.nodes.map( n => n.target ) } ) ) );
				result.passed = result.status === 200 && result.h1 === 1 && result.overflow <= 1 && result.geometry.headers.length > 0 && result.geometry.sections.length > 0 && result.geometry.textCount > 0 && result.geometry.failures.length === 0 && result.accessibility.length === 0 && result.mobileMenuOpened !== false && result.mobileMenuClosedOnEscape !== false;
				await page.screenshot( { path: path.join( OUT, ( url === '/' ? 'home' : url.replace( /^\/|\/$/g, '' ).replace( /\//g, '-' ) ) + '-' + width + '.png' ), fullPage: true } );
				report.results.push( result );
				console.log( `${ result.passed ? 'PASS' : 'REVIEW' } ${ url } at ${ width }px` );
			}
			await context.close();
		}
	} catch ( error ) {
		report.blocked = error.message;
		throw error;
	} finally {
		fs.writeFileSync( path.join( OUT, 'results.json' ), JSON.stringify( report, null, 2 ), { mode: 0o600 } );
		await browser.close();
	}
	if ( report.results.some( r => ! r.passed ) ) { process.exitCode = 1; }
}
main().catch( error => { console.error( error.message ); process.exitCode = 2; } );
