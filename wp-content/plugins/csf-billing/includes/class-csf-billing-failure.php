<?php
/** Billing component.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;

/** Sanitized domain failure; never expose provider/database error bodies. */
class CSF_Billing_Failure extends RuntimeException {}
