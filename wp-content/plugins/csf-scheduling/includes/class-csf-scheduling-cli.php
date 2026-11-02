<?php
/** Explicit operator commands, no automatic provisioning or migrations.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Private scheduling foundation commands. */
class CSF_Scheduling_CLI {
	/** Read-only health, restricted to provisioned staff.
	 */
	public function health() {
		$this->result( CSF_Scheduling_Diagnostics::report() );
	}

	/** Preview by default; apply requires exact target URL and explicit local config constant.
	 *
	 * ## OPTIONS
	 *
	 * [--apply]
	 * : Apply versioned schema after separately reviewed backup and approval.
	 * [--expected-url=<url>]
	 * : Must match home and siteurl for apply.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc Options.
	 */
	public function migrate( $args, $assoc ) {
		if ( ! get_current_user_id() || ! current_user_can( 'manage_options' ) ) {
			WP_CLI::error( 'Select the verified operator with --user; Administrator is not scheduling staff access.' );
		}
		if ( empty( $assoc['apply'] ) ) {
			$this->result(
				array(
					'schema'     => CSF_Scheduling_Schema::inspect(),
					'statements' => CSF_Scheduling_Schema::statements(),
					'writes'     => false,
				)
			);
			return;
		}
		if ( empty( $assoc['expected-url'] ) || get_option( 'home' ) !== $assoc['expected-url'] || get_option( 'siteurl' ) !== $assoc['expected-url'] ) {
			WP_CLI::error( 'Exact home/siteurl required. No migration attempted.' );
		}
		$this->result( CSF_Scheduling_Schema::migrate() );
	}

	/** Owner-verified provisioning (no name or email lookup).
	 *
	 * ## OPTIONS
	 *
	 * --user-ids=<ids>
	 * : Comma-separated existing WordPress IDs.
	 * --owner-verified
	 * : Explicit acknowledgment that identity verification occurred.
	 * --revision=<revision>
	 * : Reviewed singleton revision.
	 * --command-id=<uuid>
	 * : Reuse after an uncertain result.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc Options.
	 */
	public function provision( $args, $assoc ) {
		unset( $args, $assoc );
		WP_CLI::error( 'Blanket provisioning retired. Use Team & Permissions after verified master bootstrap.' );
	}

	/** Bootstrap or recover the single separately verified master identity.
	 *
	 * ## OPTIONS
	 *
	 * --expected-url=<url>
	 * : Must match home and siteurl for the separately approved ceremony.
	 *
	 * --user-id=<id>
	 * : Owner-confirmed existing numeric ID.
	 *
	 * [--owner-verified]
	 * : Required acknowledgment of separate owner identity/MFA authorization.
	 *
	 * --revision=<revision>
	 * : Reviewed singleton revision.
	 *
	 * --command-id=<uuid>
	 * : Idempotent command ID.
	 *
	 * [--transfer]
	 * : Separately authorized transfer/recovery, not ordinary staff assignment.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc CLI options.
	 */
	public function master( $args, $assoc ) {
		if ( empty( $assoc['expected-url'] ) || get_option( 'home' ) !== $assoc['expected-url'] || get_option( 'siteurl' ) !== $assoc['expected-url'] ) {
			WP_CLI::error( 'Exact home/siteurl required for owner identity ceremony.' ); }
		WP_CLI::error( 'CLI ownership mutation retired. Use the independently approved browser ownership ceremony.' );
	}

	/** Replace policy from a private JSON file.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Complete policy JSON document.
	 * --revision=<revision>
	 * : Reviewed singleton revision.
	 * --command-id=<uuid>
	 * : Idempotency UUID.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc Options.
	 */
	public function configure( $args, $assoc ) {
		$this->result( CSF_Scheduling_Store::configure( $this->read( $assoc ), $this->revision( $assoc ), $assoc['command-id'] ?? '' ) );
	}

	/** Explicit resource creation, not appointment entry.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Resource JSON with uuid, kind and private label.
	 * --revision=<revision>
	 * : Reviewed singleton revision.
	 * --command-id=<uuid>
	 * : Idempotency UUID.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc Options.
	 */
	public function resource( $args, $assoc ) {
		$this->result( CSF_Scheduling_Store::resource( $this->read( $assoc ), $this->revision( $assoc ), $assoc['command-id'] ?? '' ) );
	}

	/** Bounded file read; never echo a failed JSON body.
	 *
	 * @param array $assoc Options.
	 * @return array
	 */
	private function read( $assoc ) {
		$file = $assoc['file'] ?? '';
		if ( ! is_file( $file ) || is_link( $file ) || filesize( $file ) > 65536 ) {
			WP_CLI::error( 'Use a regular bounded JSON file.' );
		}
		$value = json_decode( file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $value ) || JSON_ERROR_NONE !== json_last_error() ) {
			WP_CLI::error( 'Invalid JSON; contents not logged.' );
		}
		return $value;
	}

	/** Strict integer revision.
	 *
	 * @param array $assoc Options.
	 * @return int
	 */
	private function revision( $assoc ) {
		$raw = $assoc['revision'] ?? '';
		if ( ! is_string( $raw ) || ! preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $raw ) ) {
			WP_CLI::error( 'Explicit nonnegative revision required.' );
		}
		return (int) $raw;
	}

	/** Safe fixed diagnostics only.
	 *
	 * @param mixed $result Result.
	 */
	private function result( $result ) {
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_code() . ': ' . $result->get_error_message() );
		}
		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
	}
}
