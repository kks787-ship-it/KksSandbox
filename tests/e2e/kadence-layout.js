#!/usr/bin/env node
/** Local real-theme regression; baseline CSS is read from the reviewed 0778176 commit. */
'use strict';
const fs = require( 'fs' );
const path = require( 'path' );
const assert = require( 'assert/strict' );
const { execFileSync } = require( 'child_process' );
const { chromium } = require( 'playwright' );
const metrics = require( './layout-metrics' );
const ROOT = path.resolve( __dirname, '../..' );
const BASE = process.env.CSF_LAYOUT_TEST_BASE || 'http://127.0.0.1:8892';
const OUT = path.join( ROOT, '.wp-local/kadence-layout' );
const PAGES = [ '/', '/lessons/', '/lessons/youth-riders/', '/lessons/adult-amateurs/', '/lessons/dressage-foundations/', '/lessons/off-site-lessons/', '/lessons/after-school-program/', '/horse-training/', '/horse-training/fei-young-horse-development/', '/camps-clinics/', '/horse-sales/', '/horse-sales/leasing-mentorship/', '/about/', '/about/meet-beth/', '/about/meet-the-team/', '/about/meet-the-horses/', '/about/facilities/', '/resources/', '/resources/faq/', '/contact/', '/booking/' ];
const CONFIRMED = [ '/about/meet-beth/', '/camps-clinics/' ];
if ( ! /^http:\/\/127\.0\.0\.1:\d+$/.test( BASE ) ) { throw new Error( 'Disposable localhost site required; never use the sandbox.' ); }

async function main() {
	fs.mkdirSync( OUT, { recursive: true } );
	const baseline = execFileSync( 'git', [ 'show', '0778176:wp-content/plugins/csf-core/assets/css/brand.css' ], { cwd: ROOT, encoding: 'utf8' } );
	const browser = await chromium.launch( process.env.CSF_CHROMIUM_EXECUTABLE ? { executablePath: process.env.CSF_CHROMIUM_EXECUTABLE } : {} );
	const results = [];
	try {
		for ( const phase of [ 'before', 'after' ] ) {
			for ( const width of [ 390, 768, 1280 ] ) {
				const context = await browser.newContext( { viewport: { width, height: 900 }, reducedMotion: 'reduce' } );
				if ( phase === 'before' ) {
					await context.route( /\/wp-content\/plugins\/csf-core\/assets\/css\/brand\.css(?:\?|$)/, route => route.fulfill( { body: baseline, contentType: 'text/css' } ) );
				}
				const page = await context.newPage();
				for ( const url of PAGES ) {
					const response = await page.goto( BASE + url, { waitUntil: 'networkidle' } );
					assert.equal( response.status(), 200, url );
					assert.ok( await page.locator( 'body.wp-theme-kadence' ).count(), 'Real Kadence is required' );
					await page.evaluate( () => document.fonts.ready );
					const layout = await page.evaluate( metrics );
					const result = { phase, path: url, width, ...layout };
					results.push( result );
					const slug = url === '/' ? 'home' : url.split( '/' ).filter( Boolean ).join( '-' );
					if ( phase === 'after' || CONFIRMED.includes( url ) || url === '/' ) {
						await page.screenshot( { path: path.join( OUT, phase + '-' + slug + '-' + width + '.png' ), fullPage: true } );
					}
					if ( CONFIRMED.includes( url ) ) {
						await page.locator( '.csf-page-header' ).screenshot( { path: path.join( OUT, 'header-' + phase + '-' + slug + '-' + width + '.png' ) } );
					}
					console.log( `${ phase } ${ url } ${ width }px: ${ layout.failures.length ? layout.failures.join( '; ' ) : 'geometry passed' }` );
				}
				await context.close();
			}
		}
	} finally {
		fs.writeFileSync( path.join( OUT, 'geometry.json' ), JSON.stringify( { theme: 'Kadence 1.5.2', baseline: '0778176', results }, null, 2 ) );
		await browser.close();
	}
	for ( const url of CONFIRMED ) {
		assert.ok( results.some( r => r.phase === 'before' && r.path === url && r.width === 1280 && r.failures.length ), 'Baseline must reproduce ' + url );
	}
	const failed = results.filter( r => r.phase === 'after' && ( r.failures.length || ! r.textCount || ! r.sections.length ) );
	assert.deepEqual( failed.map( r => ( { path: r.path, width: r.width, failures: r.failures, textAtEdge: r.textAtEdge } ) ), [], 'All 21 pages must preserve gutters, alignment and full-bleed backgrounds' );
	console.log( 'PASS: 63 real-Kadence page/viewport geometry checks; both confirmed baseline failures reproduced.' );
}
main().catch( error => { console.error( error.message ); process.exitCode = 1; } );
