<?php
/**
 * Router for PHP's built-in server so pretty permalinks, robots.txt, and sitemaps reach
 * WordPress. Local testing only.
 *
 * @package CSF
 */

$csf_root = getenv( 'CSF_WP_ROOT' );
$csf_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ); // phpcs:ignore
$csf_file = $csf_root . $csf_path;

if ( '/' !== $csf_path && is_file( $csf_file ) && ! str_ends_with( $csf_path, '.php' ) ) {
	return false;
}
if ( str_ends_with( $csf_path, '.php' ) && is_file( $csf_file ) ) {
	$_SERVER['SCRIPT_NAME'] = $csf_path;
	chdir( dirname( $csf_file ) );
	require $csf_file;
	return;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
chdir( $csf_root );
require $csf_root . '/index.php';
