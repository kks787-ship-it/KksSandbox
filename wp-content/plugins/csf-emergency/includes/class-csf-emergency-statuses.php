<?php
/**
 * Farm status and horse status vocabularies.
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

/**
 * Status definitions. Severity drives banner colour; colours are chosen for legibility
 * (all at least 7:1 against white text), not brand matching.
 */
class CSF_Emergency_Statuses {

	/**
	 * Farm statuses in workflow order.
	 *
	 * @return array<string,array{label:string,severity:string,headline:string,description:string}>
	 */
	public static function farm() {
		return array(
			'normal'         => array(
				'label'       => __( 'Normal', 'csf-emergency' ),
				'severity'    => 'none',
				'headline'    => '',
				'description' => __( 'No banner is shown. Normal operations.', 'csf-emergency' ),
			),
			'monitoring'     => array(
				'label'       => __( 'Monitoring Conditions', 'csf-emergency' ),
				'severity'    => 'notice',
				'headline'    => __( 'Corner Stone Farm is monitoring local conditions', 'csf-emergency' ),
				'description' => __( 'Nearby hazard being watched. No action needed from the public.', 'csf-emergency' ),
			),
			'preparing'      => array(
				'label'       => __( 'Preparing to Evacuate', 'csf-emergency' ),
				'severity'    => 'warning',
				'headline'    => __( 'Corner Stone Farm is preparing to evacuate', 'csf-emergency' ),
				'description' => __( 'Horses are being readied for possible evacuation.', 'csf-emergency' ),
			),
			'help_requested' => array(
				'label'       => __( 'Evacuation Help Requested', 'csf-emergency' ),
				'severity'    => 'critical',
				'headline'    => __( 'Corner Stone Farm is requesting evacuation assistance', 'csf-emergency' ),
				'description' => __( 'The farm needs transport or handling help. Volunteers must contact the farm and wait for instructions.', 'csf-emergency' ),
			),
			'evacuating'     => array(
				'label'       => __( 'Evacuating Now', 'csf-emergency' ),
				'severity'    => 'critical',
				'headline'    => __( 'Corner Stone Farm is evacuating now', 'csf-emergency' ),
				'description' => __( 'Horses are actively being moved off the farm.', 'csf-emergency' ),
			),
			'evacuated'      => array(
				'label'       => __( 'Evacuated', 'csf-emergency' ),
				'severity'    => 'critical',
				'headline'    => __( 'Corner Stone Farm is currently evacuated', 'csf-emergency' ),
				'description' => __( 'The farm has temporarily relocated due to an emergency.', 'csf-emergency' ),
			),
			'returning'      => array(
				'label'       => __( 'Returning Home', 'csf-emergency' ),
				'severity'    => 'notice',
				'headline'    => __( 'Corner Stone Farm is returning home', 'csf-emergency' ),
				'description' => __( 'Horses are being brought back to the farm.', 'csf-emergency' ),
			),
			'all_clear'      => array(
				'label'       => __( 'All Clear', 'csf-emergency' ),
				'severity'    => 'success',
				'headline'    => __( 'All clear at Corner Stone Farm', 'csf-emergency' ),
				'description' => __( 'The emergency has ended. Set back to Normal to remove the banner.', 'csf-emergency' ),
			),
		);
	}

	/**
	 * Per-horse evacuation status.
	 *
	 * @return array<string,string>
	 */
	public static function horse() {
		return array(
			'at_farm'    => __( 'At farm', 'csf-emergency' ),
			'ready'      => __( 'Ready to load', 'csf-emergency' ),
			'in_transit' => __( 'Loaded / in transit', 'csf-emergency' ),
			'evacuated'  => __( 'Evacuated — safe', 'csf-emergency' ),
			'missing'    => __( 'Missing — please report sightings', 'csf-emergency' ),
			'found'      => __( 'Found — recovered', 'csf-emergency' ),
			'returned'   => __( 'Returned home', 'csf-emergency' ),
			'unknown'    => __( 'Status not yet confirmed', 'csf-emergency' ),
		);
	}

	/**
	 * Label for a farm status key, falling back to Normal.
	 *
	 * @param string $key Status key.
	 * @return array
	 */
	public static function get_farm( $key ) {
		$all = self::farm();
		return $all[ $key ] ?? $all['normal'];
	}
}
