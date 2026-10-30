#!/usr/bin/env node
/**
 * Corner Stone Farm website — automated browser tests.
 *
 * Runs against the LOCAL site built by scripts/local-dev.sh (never a remote site).
 *
 *   bash scripts/local-dev.sh install && bash scripts/local-dev.sh start
 *   node tests/e2e/run.js [--screenshots]
 *
 * Writes .wp-local/test-output/results.json and docs/test-results/phase-2.md.
 * With --screenshots, also refreshes docs/screenshots/.
 */
'use strict';

const { chromium } = require( 'playwright' );
const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const path = require( 'path' );

const ROOT = path.resolve( __dirname, '..', '..' );
const BASE = process.env.CSF_BASE_URL || 'http://localhost:8890';
const OUT = path.join( ROOT, '.wp-local', 'test-output' );
const SHOTS = path.join( ROOT, 'docs', 'screenshots' );
const REPORT = path.join( ROOT, 'docs', 'test-results', 'phase-2.md' );
const AXE = fs.readFileSync( require.resolve( 'axe-core/axe.min.js' ), 'utf8' );
const WANT_SHOTS = process.argv.includes( '--screenshots' );
const ONLY = ( process.argv.find( ( a ) => a.startsWith( '--only=' ) ) || '' ).slice( 7 ); // e.g. --only=editor (no report written)

if ( ! /^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/.test( BASE ) ) {
	console.error( 'Refusing to run against a non-local URL: ' + BASE );
	process.exit( 2 );
}

const VIEWPORTS = {
	mobile: { width: 390, height: 844 },
	tablet: { width: 768, height: 1024 },
	desktop: { width: 1280, height: 900 },
};

const PAGES = [
	'/', '/lessons/', '/lessons/youth-riders/', '/lessons/adult-amateurs/', '/lessons/dressage-foundations/',
	'/lessons/off-site-lessons/', '/lessons/after-school-program/', '/horse-training/',
	'/horse-training/fei-young-horse-development/', '/camps-clinics/', '/horse-sales/',
	'/horse-sales/leasing-mentorship/', '/about/', '/about/meet-beth/', '/about/meet-the-team/',
	'/about/meet-the-horses/', '/about/facilities/', '/resources/', '/resources/faq/', '/contact/', '/booking/',
];

const PRIMARY_MENU = [ 'Home', 'Lessons', 'Horse Training', 'Camps & Clinics', 'Horse Sales', 'About', 'Resources', 'Contact', 'Book a Lesson' ];

// Pages captured for docs/screenshots (mobile + desktop; home also tablet).
const SCREENSHOT_PAGES = [ '/', '/lessons/', '/horse-training/', '/camps-clinics/', '/horse-sales/', '/about/', '/about/meet-beth/', '/resources/', '/resources/faq/', '/contact/', '/booking/' ];

const results = [];
let failures = 0;

function record( group, name, ok, detail = '' ) {
	results.push( { group, name, ok, detail } );
	if ( ! ok ) {
		failures++;
	}
	console.log( `${ ok ? 'PASS' : 'FAIL' }  [${ group }] ${ name }${ detail && ! ok ? ' — ' + detail : '' }` );
}

function wp( ...args ) {
	return execFileSync( 'php', [ path.join( ROOT, '.wp-local', 'wp-cli.phar' ), '--path=' + path.join( ROOT, '.wp-local', 'site' ), '--allow-root', ...args ], { encoding: 'utf8' } ).trim();
}

function slugFor( url ) {
	return url === '/' ? 'home' : url.replace( /^\/|\/$/g, '' ).replace( /\//g, '--' );
}

async function runAxe( page ) {
	await page.addScriptTag( { content: AXE } );
	return page.evaluate( async () => {
		const r = await window.axe.run( document, { runOnly: { type: 'tag', values: [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ] } } );
		return r.violations.map( ( v ) => ( { id: v.id, impact: v.impact, nodes: v.nodes.length, sample: v.nodes[ 0 ] && v.nodes[ 0 ].target.join( ' ' ) } ) );
	} );
}

async function shot( page, name ) {
	if ( ! WANT_SHOTS ) {
		return;
	}
	fs.mkdirSync( SHOTS, { recursive: true } );
	await page.screenshot( { path: path.join( SHOTS, name + '.jpg' ), fullPage: true, type: 'jpeg', quality: 62 } );
}

async function newPage( browser, viewport ) {
	const context = await browser.newContext( { viewport, reducedMotion: 'reduce' } );
	const page = await context.newPage();
	page.consoleErrors = [];
	page.on( 'console', ( m ) => m.type() === 'error' && page.consoleErrors.push( m.text() ) );
	page.on( 'pageerror', ( e ) => page.consoleErrors.push( e.message ) );
	return page;
}

// ---------------------------------------------------------------------------

async function testPages( browser ) {
	const bookingUrl = wp( 'eval', 'echo CSF_Core_Booking::get()["url"];' );
	const links = new Map();

	for ( const [ vpName, vp ] of Object.entries( VIEWPORTS ) ) {
		const page = await newPage( browser, vp );
		for ( const url of PAGES ) {
			page.consoleErrors = [];
			const res = await page.goto( BASE + url, { waitUntil: 'networkidle' } );
			const label = `${ url } @${ vpName }`;

			if ( vpName === 'desktop' ) {
				const html = await page.content();
				const h1 = await page.locator( 'h1' ).count();
				const forms = await page.locator( 'main form, footer form' ).count();
				const hints = await page.locator( '.csf-admin-hint' ).count();
				record( 'pages', `${ url } returns 200`, res.status() === 200, 'status ' + res.status() );
				record( 'pages', `${ url } has exactly one H1`, h1 === 1, h1 + ' H1 elements' );
				record( 'pages', `${ url } has no PHP errors`, ! /(Fatal error|Warning:|Notice:|Deprecated:)/.test( html ) );
				record( 'pages', `${ url } exposes no public forms`, forms === 0, forms + ' forms' );
				record( 'pages', `${ url } shows no admin-only hints to visitors`, hints === 0 );

				const booking = await page.$$eval( 'a[data-csf-booking]', ( as ) => as.map( ( a ) => a.href ) );
				record( 'booking', `${ url } booking buttons use the shared Acuity URL (${ booking.length })`, booking.every( ( h ) => h === bookingUrl ), booking.join( ', ' ) );

				const imgs = await page.$$eval( 'main img', ( is ) => is.filter( ( i ) => ! i.hasAttribute( 'alt' ) ).length );
				record( 'a11y', `${ url } every image has alt text`, imgs === 0, imgs + ' without alt' );

				for ( const href of await page.$$eval( 'a[href]', ( as ) => as.map( ( a ) => a.href ) ) ) {
					if ( ! links.has( href ) ) {
						links.set( href, url );
					}
				}
			}

			const overflow = await page.evaluate( () => document.documentElement.scrollWidth - window.innerWidth );
			record( 'responsive', `${ label } has no horizontal scroll`, overflow <= 0, overflow + 'px overflow' );
			record( 'pages', `${ label } has no browser console errors`, page.consoleErrors.length === 0, page.consoleErrors.join( ' | ' ) );

			if ( vpName !== 'tablet' ) {
				const violations = await runAxe( page );
				record( 'a11y', `${ label } axe WCAG 2.2 AA: no violations`, violations.length === 0, JSON.stringify( violations ) );
			}

			if ( SCREENSHOT_PAGES.includes( url ) && ( vpName !== 'tablet' || url === '/' || url === '/lessons/' ) ) {
				await shot( page, `${ slugFor( url ) }-${ vpName }` );
			}
		}
		await page.context().close();
	}
	return { links, bookingUrl };
}

async function testLinks( browser, links ) {
	const page = await newPage( browser, VIEWPORTS.desktop );
	const external = [];
	for ( const [ href, from ] of links ) {
		const u = new URL( href );
		if ( u.protocol === 'mailto:' || u.protocol === 'tel:' ) {
			continue;
		}
		if ( u.origin !== BASE ) {
			external.push( href );
			continue;
		}
		const res = await page.request.get( href, { maxRedirects: 3 } );
		record( 'links', `${ u.pathname }${ u.hash } (linked from ${ from })`, res.status() < 400, 'status ' + res.status() );
	}
	await page.context().close();
	return [ ...new Set( external ) ];
}

async function testNavigation( browser ) {
	for ( const [ vpName, vp ] of [ [ 'desktop', VIEWPORTS.desktop ], [ 'mobile', VIEWPORTS.mobile ] ] ) {
		const page = await newPage( browser, vp );
		await page.goto( BASE + '/' );
		if ( vpName === 'mobile' ) {
			await page.click( '.nav-toggle' );
			record( 'navigation', 'mobile menu toggle sets aria-expanded', ( await page.getAttribute( '.nav-toggle', 'aria-expanded' ) ) === 'true' );
		}
		const top = await page.$$eval( '#primary-nav > ul > li > a', ( as ) => as.map( ( a ) => a.textContent.trim() ) );
		record( 'navigation', `primary menu order (${ vpName })`, JSON.stringify( top ) === JSON.stringify( PRIMARY_MENU ), top.join( ', ' ) );
		const cta = await page.$eval( '#primary-nav .csf-menu-cta > a', ( a ) => a.getAttribute( 'href' ) ).catch( () => '' );
		record( 'navigation', `"Book a Lesson" menu item is highlighted and links to /booking/ (${ vpName })`, cta.endsWith( '/booking/' ), cta );
		if ( vpName === 'desktop' ) {
			const sub = await page.$$eval( '#primary-nav .sub-menu a', ( as ) => as.length );
			record( 'navigation', 'submenus contain the 12 supporting pages', sub === 12, sub + ' submenu links' );
			await page.focus( '#primary-nav > ul > li:nth-child(2) > a' );
			await page.keyboard.press( 'Tab' );
			const focused = await page.evaluate( () => document.activeElement.closest( '.sub-menu' ) !== null );
			record( 'navigation', 'submenu links are reachable by keyboard', focused );
		}
		await page.context().close();
	}
}

async function testEditor( browser ) {
	const page = await newPage( browser, { width: 1440, height: 900 } );
	await page.goto( BASE + '/wp-login.php' );
	await page.fill( '#user_login', 'admin' );
	await page.fill( '#user_pass', 'localtest123' );
	await Promise.all( [ page.waitForNavigation(), page.click( '#wp-submit' ) ] );

	const ids = JSON.parse( wp( 'post', 'list', '--post_type=page,wp_block', '--post_status=any', '--fields=ID,post_name,post_type', '--format=json' ) );
	// Strict: compare every block with the CURRENT save() output. (block.isValid alone also accepts
	// markup that the editor would silently migrate from an older block version.)
	const walk = `(function walk(bs){return bs.flatMap(function(b){var own=[];var v=wp.blocks.validateBlock(b);if(b.isValid===false||(v&&v[0]===false)){own.push({name:b.name,issue:v&&v[1]&&v[1][0]?String(v[1][0].args.slice(0,3).map(function(a){return typeof a==='string'?a:JSON.stringify(a);}).join(' | ')).slice(0,400):''});}return own.concat(walk(b.innerBlocks||[]));});})`;

	for ( const { ID, post_name: name, post_type: type } of ids ) {
		if ( type === 'page' && name === 'privacy-policy' ) {
			continue;
		}
		await page.goto( `${ BASE }/wp-admin/post.php?post=${ ID }&action=edit` );
		await page.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ) && wp.data.select( 'core/block-editor' ).getBlocks().length > 0, null, { timeout: 90000 } );
		const invalid = await page.evaluate( walk + '(wp.data.select("core/block-editor").getBlocks())' );
		record( 'editor', `${ type } "${ name }" opens in the block editor with no invalid blocks`, invalid.length === 0, JSON.stringify( invalid ).slice( 0, 1500 ) );
	}

	// Self-check: the validator must flag deliberately broken markup, or the results above mean nothing.
	const control = await page.evaluate( walk + '(wp.blocks.parse("<!-- wp:paragraph --><div>broken</div><!-- /wp:paragraph -->"))' );
	record( 'editor', 'validity check detects a deliberately broken block (control)', control.length === 1, JSON.stringify( control ) );

	// Every registered Corner Stone Farm pattern parses into valid blocks.
	const patterns = await page.evaluate( `wp.data.resolveSelect('core').getBlockPatterns().then(function(all){var walk=${ walk };return all.filter(function(p){return p.name.indexOf('csf/')===0;}).map(function(p){return {name:p.name,invalid:walk(wp.blocks.parse(p.content))};});})` );
	record( 'editor', `all Corner Stone Farm patterns are registered (${ patterns.length })`, patterns.length >= 33, patterns.length + ' patterns' );
	for ( const p of patterns ) {
		record( 'editor', `pattern ${ p.name } is valid`, p.invalid.length === 0, JSON.stringify( p.invalid ).slice( 0, 800 ) );
	}
	const types = await page.evaluate( () => wp.blocks.getBlockTypes().filter( ( b ) => b.name.indexOf( 'csf/' ) === 0 ).map( ( b ) => b.name ) );
	record( 'editor', `dynamic blocks registered in the editor (${ types.length })`, types.length === 8, types.join( ', ' ) );
	// Blocked outbound requests (Gravatar, wordpress.org) in the sandbox are network failures, not code errors.
	const jsErrors = page.consoleErrors.filter( ( e ) => ! /Failed to load resource: net::ERR_|favicon/.test( e ) );
	record( 'editor', 'no JavaScript errors in the editor (blocked external requests excluded)', jsErrors.length === 0, jsErrors.join( ' | ' ).slice( 0, 800 ) );
	if ( WANT_SHOTS ) {
		await page.goto( `${ BASE }/wp-admin/post.php?post=${ ids.find( ( i ) => i.post_name === 'home' ).ID }&action=edit` );
		await page.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ).getBlocks().length > 0, null, { timeout: 90000 } );
		await page.evaluate( () => wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false ) );
		await page.waitForTimeout( 2500 );
		await page.screenshot( { path: path.join( SHOTS, 'editor-home.jpg' ), type: 'jpeg', quality: 70 } );
	}
	await page.context().close();
}

async function testEmergency( browser ) {
	const set = ( status, msg ) => wp( 'eval', `wp_set_current_user(1); delete_option('csf_emergency_state'); $r = CSF_Emergency_Farm_State::change('${ status }', '${ msg }'); echo is_wp_error($r) ? $r->get_error_message() : 'ok';` );
	try {
		set( 'evacuated', 'Automated test: horses moved to a safe location.' );
		for ( const [ vpName, vp ] of [ [ 'desktop', VIEWPORTS.desktop ], [ 'mobile', VIEWPORTS.mobile ] ] ) {
			const page = await newPage( browser, vp );
			for ( const url of [ '/', '/lessons/', '/contact/' ] ) {
				await page.goto( BASE + url );
				const banner = page.locator( '.csf-banner' ).first();
				const visible = await banner.isVisible().catch( () => false );
				const first = await page.evaluate( () => {
					const b = document.querySelector( '.csf-banner' );
					const h = document.querySelector( '.site-header' );
					return !! ( b && h && ( b.compareDocumentPosition( h ) & Node.DOCUMENT_POSITION_FOLLOWING ) );
				} );
				record( 'emergency', `banner visible on ${ url } @${ vpName }`, visible );
				record( 'emergency', `banner appears above the site header on ${ url } @${ vpName }`, first );
				const overflow = await page.evaluate( () => document.documentElement.scrollWidth - window.innerWidth );
				record( 'emergency', `banner causes no horizontal scroll on ${ url } @${ vpName }`, overflow <= 0, overflow + 'px' );
				if ( url === '/' ) {
					const v = await runAxe( page );
					record( 'emergency', `axe WCAG 2.2 AA with banner @${ vpName }`, v.length === 0, JSON.stringify( v ) );
					if ( WANT_SHOTS ) {
						await page.screenshot( { path: path.join( SHOTS, `emergency-banner-${ vpName }.jpg` ), type: 'jpeg', quality: 70 } );
					}
				}
			}
			await page.context().close();
		}

		set( 'help_requested', 'Automated test: transport help requested.' );
		const page = await newPage( browser, VIEWPORTS.mobile );
		await page.goto( BASE + '/emergency/' );
		const text = await page.textContent( 'body' );
		record( 'emergency', 'help-requested mode says online volunteer sign-up is not available', /Online volunteer sign-up is not available yet/.test( text ) );
		record( 'emergency', 'help-requested mode shows the do-not-self-deploy statement', /Do not self-deploy/.test( text ) );
		record( 'emergency', 'no notification or dispatch claims on public pages', ! /notification(s)? (has|have) been sent|volunteers (have been )?notified/i.test( text ) );
		const res = await page.request.get( BASE + '/emergency/' );
		record( 'emergency', '/emergency is still noindex', /noindex/.test( res.headers()[ 'x-robots-tag' ] || '' ) );
		await page.context().close();
	} finally {
		set( 'normal', '' );
	}
	const page = await newPage( browser, VIEWPORTS.desktop );
	await page.goto( BASE + '/' );
	record( 'emergency', 'banner removed after returning to Normal', ( await page.locator( '.csf-banner' ).count() ) === 0 );
	await page.context().close();
}

async function testStructuredContent( browser ) {
	// Local sample data only — removed again afterwards so the site returns to its shipping state.
	const created = JSON.parse( wp( 'eval-file', path.join( __dirname, 'fixtures.php' ), 'create' ) );
	try {
		const page = await newPage( browser, VIEWPORTS.desktop );

		await page.goto( created.article );
		const meta = await page.textContent( '.csf-article-meta' ).catch( () => '' );
		record( 'library', 'article shows topic, reading time, and review date', /min read/.test( meta ) && /Reviewed/.test( meta ) && /Horse Care/.test( meta ), meta );
		record( 'library', 'article shows related articles', ( await page.locator( '.csf-related .csf-card' ).count() ) === 2 );
		record( 'library', 'article page passes axe', ( await runAxe( page ) ).length === 0 );
		await shot( page, 'sample-article-desktop' );

		await page.goto( BASE + '/resources/' );
		record( 'library', 'Resources lists published articles', ( await page.locator( '.csf-listing .csf-card' ).count() ) === 3 );
		record( 'library', 'Resources lists topics with articles', ( await page.locator( '.csf-topics a' ).count() ) === 2 );
		record( 'library', 'draft articles are not listed', ! ( await page.textContent( 'main' ) ).includes( 'Draft article' ) );

		await page.goto( created.library_archive );
		record( 'library', '/library/ archive works', ( await page.textContent( 'main' ) ).includes( 'Grooming basics' ) );

		await page.goto( BASE + '/horse-sales/' );
		const sales = await page.textContent( 'main' );
		record( 'horses', 'sale horse is listed', sales.includes( 'Sample Sale Horse' ) );
		record( 'horses', 'sold horse is not listed', ! sales.includes( 'Sample Sold Horse' ) );
		await page.goto( created.horse );
		const horse = await page.textContent( 'main' );
		record( 'horses', 'unapproved price is not published', ! horse.includes( '$12,345' ) );
		record( 'horses', 'horse page shows details and an inquiry button', horse.includes( 'Breed' ) && ( await page.locator( '.csf-horse-inquiry a' ).count() ) === 1 );
		record( 'horses', 'horse page passes axe', ( await runAxe( page ) ).length === 0 );
		await shot( page, 'sample-horse-desktop' );

		await page.goto( BASE + '/' );
		record( 'horses', 'home "Featured horses" appears when a sale horse exists', ( await page.locator( '.csf-listing-section .csf-card' ).count() ) >= 1 );

		await page.goto( BASE + '/camps-clinics/' );
		const camps = await page.textContent( 'main' );
		record( 'events', 'upcoming event is listed', camps.includes( 'Sample Summer Camp' ) );
		record( 'events', 'past event is hidden', ! camps.includes( 'Sample Past Clinic' ) );
		await page.goto( created.event );
		record( 'events', 'open event shows a Register button', ( await page.locator( '.csf-event-register a' ).count() ) === 1 );
		record( 'events', 'event page passes axe', ( await runAxe( page ) ).length === 0 );
		await shot( page, 'sample-event-desktop' );

		const mobile = await newPage( browser, VIEWPORTS.mobile );
		await mobile.goto( BASE + '/' );
		await shot( mobile, 'home-with-sample-content-mobile' );
		await mobile.context().close();
		await page.context().close();
	} finally {
		wp( 'eval-file', path.join( __dirname, 'fixtures.php' ), 'delete' );
	}
	const page = await newPage( browser, VIEWPORTS.desktop );
	await page.goto( BASE + '/' );
	record( 'horses', 'home "Featured horses" section disappears when none are listed', ( await page.locator( '.csf-listing-section' ).count() ) === 0 );
	await page.context().close();
}

function testSetup() {
	const again = wp( 'csf', 'setup' );
	record( 'setup', 're-running setup changes nothing', ! /create|update|rebuild/.test( again.replace( /Success.*/, '' ) ), again );

	const id = wp( 'post', 'list', '--post_type=page', '--name=facilities', '--field=ID' );
	const original = wp( 'post', 'get', id, '--field=post_content' );
	try {
		wp( 'post', 'update', id, '--post_content=' + original + '\n<!-- wp:paragraph --><p>Edited by owner.</p><!-- /wp:paragraph -->' );
		const out = wp( 'csf', 'setup' );
		record( 'setup', 'setup never overwrites a page edited in WordPress', /kept\s+\/about\/facilities\//.test( out ), out );
	} finally {
		wp( 'csf', 'setup', '--force' );
	}
	record( 'setup', 'setup --force restores the original layout', ! wp( 'post', 'get', id, '--field=post_content' ).includes( 'Edited by owner' ) );
	const notes = JSON.parse( wp( 'csf', 'status', '--format=json' ) );
	return notes;
}

function writeReport( external, notes, started ) {
	const groups = [ ...new Set( results.map( ( r ) => r.group ) ) ];
	const pass = results.filter( ( r ) => r.ok ).length;
	let md = `# Phase 2 — Automated test results\n\n`;
	md += `Generated by \`tests/e2e/run.js\` on ${ new Date( started ).toISOString().replace( 'T', ' ' ).slice( 0, 16 ) } UTC against the local site (${ BASE }).\n\n`;
	md += `**${ pass } passed, ${ failures } failed** (${ results.length } checks).\n\n`;
	md += `Environment: WordPress ${ wp( 'core', 'version' ) }, PHP ${ process.versions && execFileSync( 'php', [ '-r', 'echo PHP_VERSION;' ], { encoding: 'utf8' } ) }, theme "${ wp( 'theme', 'list', '--status=active', '--field=title' ) }", Chromium (Playwright), axe-core 4.10 (WCAG 2.0/2.1/2.2 A & AA rules).\n\n`;
	md += `> The active theme is the TEST-ONLY Kadence stand-in unless a Kadence zip was supplied. Header/footer rendering must be re-verified on the development server with Kadence.\n\n`;
	md += `| Group | Passed | Failed |\n|---|---|---|\n`;
	for ( const g of groups ) {
		const rs = results.filter( ( r ) => r.group === g );
		md += `| ${ g } | ${ rs.filter( ( r ) => r.ok ).length } | ${ rs.filter( ( r ) => ! r.ok ).length } |\n`;
	}
	const failed = results.filter( ( r ) => ! r.ok );
	md += `\n## Failures\n\n` + ( failed.length ? failed.map( ( r ) => `- [${ r.group }] ${ r.name }: ${ r.detail }` ).join( '\n' ) : 'None.' ) + '\n';
	md += `\n## External links found (not fetched — outbound access is blocked in the test environment)\n\n` + external.map( ( e ) => `- ${ e }` ).join( '\n' ) + '\n';
	md += `\n## Outstanding owner notes by page\n\n| Page | Owner notes |\n|---|---|\n` + notes.map( ( n ) => `| \`${ n.path }\` | ${ n.owner_notes } |` ).join( '\n' ) + `\n\n**Total: ${ notes.reduce( ( a, n ) => a + Number( n.owner_notes ), 0 ) }** (must be 0 before launch).\n`;
	md += `\n## All checks\n\n<details><summary>${ results.length } checks</summary>\n\n` + results.map( ( r ) => `- ${ r.ok ? '✅' : '❌' } [${ r.group }] ${ r.name }` ).join( '\n' ) + '\n\n</details>\n';
	fs.mkdirSync( path.dirname( REPORT ), { recursive: true } );
	fs.writeFileSync( REPORT, md );
	fs.mkdirSync( OUT, { recursive: true } );
	fs.writeFileSync( path.join( OUT, 'results.json' ), JSON.stringify( { results, external, notes }, null, 1 ) );
}

( async () => {
	const started = Date.now();
	const browser = await chromium.launch();
	try {
		const suites = { editor: testEditor, navigation: testNavigation, emergency: testEmergency, content: testStructuredContent };
		if ( ONLY ) {
			await suites[ ONLY ]( browser );
		} else {
			const { links } = await testPages( browser );
			const external = await testLinks( browser, links );
			await testNavigation( browser );
			await testEditor( browser );
			await testEmergency( browser );
			await testStructuredContent( browser );
			const notes = testSetup();
			writeReport( external, notes, started );
		}
	} finally {
		await browser.close();
	}
	console.log( `\n${ results.length - failures } passed, ${ failures } failed. Report: ${ path.relative( ROOT, REPORT ) }` );
	process.exit( failures ? 1 : 0 );
} )();
