<?php
/**
 * Plugin Name: Digital Ranch Workflows — Local Prototype
 * Description: Unreleased domain candidate; no routes, hooks, migrations or delivery transport.
 * Version: 0.0.1-dev
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * @package CSF_Workflows
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/includes/class-csf-workflows.php';
// Definitions only. Never install this prototype on a farm; no production adapters exist.
