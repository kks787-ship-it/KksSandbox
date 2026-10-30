<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'About', 'csf-core' ),
	__( 'Facilities', 'csf-core' ),
	esc_html__( 'Where lessons, training, and camps happen.', 'csf-core' )
)
. $csf::section(
	$csf::note( esc_html__( 'a description of the actual facilities (arena type and footing, barn and stabling, turnout, amenities for riders and parents) and authentic photographs. Nothing about the facilities is described here until the owners supply it.', 'csf-core' ) )
	. $csf::columns(
		array(
			$csf::placeholder( 'facility-arena' ),
			$csf::placeholder( 'facility-barn' ),
			$csf::placeholder( 'facility-grounds' ),
		)
	),
	'white'
)
. CSF_Core_Patterns::section( 'cta-band' );
