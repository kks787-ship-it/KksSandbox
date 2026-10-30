<?php
/**
 * Capabilities and the Emergency Coordinator role.
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

/**
 * Emergency capabilities are never implied by other roles. They must be granted explicitly.
 */
class CSF_Emergency_Capabilities {

	/** Change farm emergency status and view the event log. */
	const MANAGE_EMERGENCY = 'csf_manage_emergency';

	/** Create and edit horse emergency identification profiles. */
	const MANAGE_HORSES = 'csf_manage_horse_profiles';

	/**
	 * Add the coordinator role and give administrators both capabilities.
	 */
	public static function install() {
		add_role(
			'csf_emergency_coordinator',
			__( 'Emergency Coordinator', 'csf-emergency' ),
			array(
				'read'                 => true,
				'upload_files'         => true,
				self::MANAGE_EMERGENCY => true,
				self::MANAGE_HORSES    => true,
			)
		);

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( self::MANAGE_EMERGENCY );
			$admin->add_cap( self::MANAGE_HORSES );
		}
	}

	/**
	 * Capability map for the horse profile post type. Every primitive maps to MANAGE_HORSES.
	 *
	 * @return array<string,string>
	 */
	public static function horse_caps() {
		$cap = self::MANAGE_HORSES;
		return array(
			'edit_post'              => $cap,
			'read_post'              => $cap,
			'delete_post'            => $cap,
			'edit_posts'             => $cap,
			'edit_others_posts'      => $cap,
			'delete_posts'           => $cap,
			'publish_posts'          => $cap,
			'read_private_posts'     => $cap,
			'delete_private_posts'   => $cap,
			'delete_published_posts' => $cap,
			'delete_others_posts'    => $cap,
			'edit_private_posts'     => $cap,
			'edit_published_posts'   => $cap,
			'create_posts'           => $cap,
		);
	}

	/**
	 * Whether the user has an active second factor, when a supported MFA plugin is present.
	 *
	 * @param int $user_id User ID.
	 * @return bool|null True/false when detectable, null when no supported MFA plugin is active.
	 */
	public static function user_has_mfa( $user_id ) {
		if ( class_exists( 'Two_Factor_Core' ) && method_exists( 'Two_Factor_Core', 'is_user_using_two_factor' ) ) {
			return (bool) Two_Factor_Core::is_user_using_two_factor( $user_id );
		}
		return null;
	}
}
