<?php
/** Selected-resource conflict context.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Bounded private context for a selected conflicting resource. */
class CSF_Scheduling_Conflict extends RuntimeException {
	/** Selected resource label; never another appointment or household.
	 *
	 * @var string
	 */
	public $resource_label;
	/** Constructor.
	 *
	 * @param string $reason Fixed reason.
	 * @param string $label Selected label.
	 */
	public function __construct( $reason, $label ) {
		parent::__construct( $reason );
		$this->resource_label = wp_specialchars_decode( $label, ENT_QUOTES ); }
}
