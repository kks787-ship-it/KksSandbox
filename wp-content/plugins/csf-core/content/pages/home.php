<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return CSF_Core_Patterns::section( 'home-hero' )
	. CSF_Core_Patterns::section( 'pathways' )
	. CSF_Core_Patterns::section( 'philosophy' )
	. CSF_Core_Patterns::section( 'meet-beth' )
	. CSF_Core_Patterns::section( 'mentorship' )
	. CSF_Core_Patterns::section( 'featured-programs' )
	. CSF_Core_Patterns::section( 'featured-horses' )
	. CSF_Core_Patterns::section( 'resources-teaser' )
	. CSF_Core_Patterns::section( 'cta-band' );
