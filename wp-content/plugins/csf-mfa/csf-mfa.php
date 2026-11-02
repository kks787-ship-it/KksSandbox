<?php
/**
 * Plugin Name: Digital Ranch Verified MFA Sessions
 * Description: Fail-closed Two-Factor session evidence for private Digital Ranch access.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Requires at least: 7.0
 * License: GPL-2.0-or-later
 * Text Domain: csf-mfa
 *
 * @package CSF_MFA
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/includes/class-csf-mfa-session.php';
CSF_MFA_Session::hooks();
