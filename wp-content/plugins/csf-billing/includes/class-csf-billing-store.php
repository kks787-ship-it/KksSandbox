<?php
/** Dedicated atomic ledger; no scheduling-table mutations.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;
// Financial commands require authoritative reads within a database transaction.
// phpcs:disable WordPress.DB.DirectDatabaseQuery
/** Serializes billing commands and their audits under one InnoDB lock. */
final class CSF_Billing_Store {
	/** Nested transaction guard.
	 *
	 * @var bool
	 */
	private $active = false;
	/** Resolve only compiled table names.
	 *
	 * @param string $name Allowlisted identifier.
	 */
	public function table( $name ) {
		global $wpdb;
		CSF_Billing_Domain::need( in_array( $name, array( 'guard', 'orders', 'certificates', 'commands', 'audit', 'throttle' ), true ) );
		return $wpdb->prefix . 'csf_billing_' . $name;
	}
	/** Fail closed when any required table is absent or nontransactional.
	 */
	public function ready() {
		global $wpdb;
		foreach ( array( 'guard', 'orders', 'certificates', 'commands', 'audit', 'throttle', 'users', 'usermeta' ) as $name ) {
			$table  = in_array( $name, array( 'users', 'usermeta' ), true ) ? $wpdb->$name : $this->table( $name );
			$engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table ) );
			CSF_Billing_Domain::need( 'InnoDB' === $engine, 'billing_schema_unavailable' );
		}
		$required = array(
			'guard'        => array( 'PRIMARY' => 'id' ),
			'orders'       => array(
				'PRIMARY'        => 'id',
				'provider_order' => 'provider_order_id',
				'capture'        => 'capture_id',
			),
			'certificates' => array(
				'PRIMARY'            => 'id',
				'one_per_order'      => 'order_id',
				'certificate_number' => 'number_hash',
			),
			'commands'     => array( 'PRIMARY' => 'id' ),
			'audit'        => array( 'PRIMARY' => 'id' ),
			'throttle'     => array( 'PRIMARY' => 'id' ),
		);
		foreach ( $required as $name => $keys ) {
			$indexes = $wpdb->get_results( $wpdb->prepare( 'SHOW INDEX FROM %i', $this->table( $name ) ), ARRAY_A );
			CSF_Billing_Domain::need( is_array( $indexes ) && '' === $wpdb->last_error, 'billing_schema_unavailable' );
			foreach ( $keys as $key => $column ) {
				$matches = array_values(
					array_filter(
						$indexes,
						static function ( $entry ) use ( $key ) {
							return $entry['Key_name'] === $key;
						}
					)
				);
				CSF_Billing_Domain::need( 1 === count( $matches ) && '0' === (string) $matches[0]['Non_unique'] && $matches[0]['Column_name'] === $column && null === $matches[0]['Sub_part'], 'billing_schema_unavailable' );
			}
		}
	}
	/** Exact actor/kind/payload replay; commit receipt and audit with the mutation.
	 *
	 * @param string        $id Exact object identifier.
	 * @param array         $payload Immutable command bindings.
	 * @param callable      $callback Transactional mutation.
	 * @param callable|null $guard Authorization recheck before mutation or replay.
	 * @throws Throwable After rolling back a failed command.
	 */
	public function command( $id, $payload, $callback, $guard = null ) {
		global $wpdb;
		CSF_Billing_Domain::need( ! $this->active, 'nested_transaction' );
		$hash     = hash( 'sha256', CSF_Billing_Domain::json( $payload ) );
		$previous = $wpdb->suppress_errors( true );
		try {
			$this->ready();
			$this->check( $wpdb->query( 'START TRANSACTION' ) );
			$this->active = true;
			$version      = $wpdb->get_var( $wpdb->prepare( 'SELECT schema_version FROM %i WHERE id = 1 FOR UPDATE', $this->table( 'guard' ) ) );
			CSF_Billing_Domain::need( '2' === (string) $version, 'billing_schema_unavailable' );
			if ( null !== $guard ) {
				$guard();
			}
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %s', $this->table( 'commands' ), $id ), ARRAY_A );
			CSF_Billing_Domain::need( '' === $wpdb->last_error, 'ledger_unavailable' );
			if ( $row ) {
				CSF_Billing_Domain::need( hash_equals( $row['payload_hash'], $hash ), 'command_conflict' );
				$result = json_decode( $row['result'], true, 32, JSON_THROW_ON_ERROR );
			} else {
				$result = $callback( $this );
				$this->insert(
					'commands',
					array(
						'id'           => $id,
						'payload_hash' => $hash,
						'result'       => CSF_Billing_Domain::json( $result ),
						'created_at'   => gmdate( 'Y-m-d H:i:s' ),
					)
				);
			}
			$this->check( $wpdb->query( 'COMMIT' ) );
			return $result;
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			throw $error;
		} finally {
			$this->active = false;
			$wpdb->suppress_errors( $previous );
		}
	}
	/** Read a consistent authorized snapshot without accumulating replay receipts.
	 *
	 * @param callable      $callback Bounded read callback.
	 * @param callable|null $guard Current authorization callback.
	 * @return mixed
	 * @throws Throwable When a read or authorization fails.
	 */
	public function snapshot( $callback, $guard = null ) {
		global $wpdb;
		CSF_Billing_Domain::need( ! $this->active, 'nested_transaction' );
		$previous = $wpdb->suppress_errors( true );
		try {
			$this->ready();
			$this->check( $wpdb->query( 'START TRANSACTION' ) );
			$this->active = true;
			$version      = $wpdb->get_var( $wpdb->prepare( 'SELECT schema_version FROM %i WHERE id=1 FOR UPDATE', $this->table( 'guard' ) ) );
			CSF_Billing_Domain::need( '2' === (string) $version, 'billing_schema_unavailable' );
			if ( $guard ) {
				$guard(); }
			$result = $callback( $this );
			$this->check( $wpdb->query( 'COMMIT' ) );
			return $result;
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			throw $error; } finally {
			$this->active = false;
			$wpdb->suppress_errors( $previous ); }
	}

	/** Transactional exact-ID lookup.
	 *
	 * @param string $table Allowlisted ledger table.
	 * @param string $id Exact object identifier.
	 */
	public function row( $table, $id ) {
		global $wpdb;
		CSF_Billing_Domain::need( $this->active, 'transaction_required' );
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %s', $this->table( $table ), $id ), ARRAY_A );
		CSF_Billing_Domain::need( '' === $wpdb->last_error, 'ledger_unavailable' );
		return $row;
	}
	/** Find one uniquely bound financial record inside the transaction.
	 *
	 * @param string $table Compiled ledger table.
	 * @param string $field Compiled unique binding column.
	 * @param string $value Exact provider or local ID.
	 * @return array|null
	 */
	public function find( $table, $field, $value ) {
		global $wpdb;
		CSF_Billing_Domain::need( $this->active && ( ( 'orders' === $table && 'provider_order_id' === $field ) || ( 'certificates' === $table && 'order_id' === $field ) ), 'transaction_required' );
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE %i = %s', $this->table( $table ), $field, $value ), ARRAY_A );
		CSF_Billing_Domain::need( '' === $wpdb->last_error, 'ledger_unavailable' );
		return $row;
	}

	/** Bounded indexed anti-abuse counter; store only a keyed address digest.
	 *
	 * @param string $scope Compiled route group.
	 * @param int    $limit Requests per minute.
	 */
	public function throttle( $scope, $limit ) {
		global $wpdb;
		$this->ready();
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : false;
		CSF_Billing_Domain::need( false !== $ip, 'request_forbidden' );
		$window = intdiv( time(), 60 );
		$key    = hash_hmac( 'sha256', $scope . '|' . $ip . '|' . $window, CSF_Billing_Config::key() );
		$this->check( $wpdb->query( $wpdb->prepare( 'INSERT INTO %i (id,hits,expires) VALUES (%s,1,%d) ON DUPLICATE KEY UPDATE hits=LEAST(hits+1,1000000)', $this->table( 'throttle' ), $key, ( $window + 2 ) * 60 ) ) );
		$count = $wpdb->get_var( $wpdb->prepare( 'SELECT hits FROM %i WHERE id=%s', $this->table( 'throttle' ), $key ) );
		CSF_Billing_Domain::need( null !== $count && (int) $count <= $limit, 'rate_limited' );
		$this->check( $wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE expires < %d LIMIT 100', $this->table( 'throttle' ), time() ) ) );
	}
	/** Uncached receipt read, bound to the current anonymous session or an authorized operator.
	 *
	 * @param string      $id Purchase UUID.
	 * @param string|null $session_hash Customer binding; null requires financial audit access.
	 * @return array
	 */
	public function receipt( $id, $session_hash ) {
		global $wpdb;
		CSF_Billing_Domain::uuid( $id );
		return $this->snapshot(
			function ( $store ) use ( $id, $session_hash ) {
				$row = $store->row( 'orders', $id );
				CSF_Billing_Domain::need( $row && ( null === $session_hash || hash_equals( $row['session_hash'], $session_hash ) ), 'purchase_unavailable' );
				$doc  = json_decode( $row['document'], true, 32, JSON_THROW_ON_ERROR );
				$cert = $store->find( 'certificates', 'order_id', $id );
				return array(
					'order_id'       => $id,
					'status'         => $row['status'],
					'order_revision' => (int) $row['revision'],
					'package'        => $doc['product']['label'],
					'amount_minor'   => $doc['amount_minor'],
					'currency'       => $doc['currency'],
					'terms'          => $doc['terms'],
					'accepted_at'    => $doc['accepted_at'],
					'certificate_id' => $cert['id'] ?? null,
					'number_sealed'  => $cert['number_sealed'] ?? null,
					'purchased'      => isset( $cert['purchased'] ) ? (int) $cert['purchased'] : 0,
					'redeemed'       => isset( $cert['redeemed'] ) ? (int) $cert['redeemed'] : 0,
					'revision'       => isset( $cert['revision'] ) ? (int) $cert['revision'] : 0,
				);
			},
			function () use ( $session_hash ) {
				if ( null === $session_hash ) {
					CSF_Billing_Access::require_cap( 'csf_billing_audit' );
				} }
		);
	}
	/** Bounded staff-only purchase index with no plaintext certificate number.
	 *
	 * @return array
	 */
	public function listing() {
		global $wpdb;
		CSF_Billing_Access::require_cap( 'csf_billing_audit' );
		$this->ready();
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id,status,created_at FROM %i ORDER BY created_at DESC,id DESC LIMIT 50', $this->table( 'orders' ) ), ARRAY_A );
		CSF_Billing_Domain::need( is_array( $rows ) && '' === $wpdb->last_error, 'ledger_unavailable' );
		return $rows;
	}

	/** Minimal unresolved dispute queue; no raw provider/customer payloads.
	 *
	 * @return array
	 */
	public function reviews() {
		global $wpdb;
		CSF_Billing_Access::require_cap( 'csf_billing_audit' );
		$this->ready();
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT created_at,result FROM %i WHERE result LIKE %s ORDER BY created_at DESC LIMIT 50', $this->table( 'commands' ), '%"review_required":true%' ), ARRAY_A );
		CSF_Billing_Domain::need( is_array( $rows ) && '' === $wpdb->last_error, 'ledger_unavailable' );
		return $rows;
	}

	/** Minimal private event history, never provider bodies or session credentials.
	 *
	 * @param string      $order Purchase UUID.
	 * @param string|null $certificate Certificate UUID.
	 * @return array
	 */
	public function history( $order, $certificate ) {
		global $wpdb;
		CSF_Billing_Access::require_cap( 'csf_billing_audit' );
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT kind,actor,details,created_at FROM %i WHERE object_id=%s OR object_id=%s ORDER BY id DESC LIMIT 100', $this->table( 'audit' ), $order, $certificate ?? '' ), ARRAY_A );
		CSF_Billing_Domain::need( is_array( $rows ) && '' === $wpdb->last_error, 'ledger_unavailable' );
		return $rows;
	}

	/** Checked insert; duplicate external IDs cannot issue twice.
	 *
	 * @param string $table Allowlisted ledger table.
	 * @param array  $data Validated database columns.
	 */
	public function insert( $table, $data ) {
		global $wpdb;
		CSF_Billing_Domain::need( $this->active, 'transaction_required' );
		$this->check( $wpdb->insert( $this->table( $table ), $data, $this->formats( $data ) ) );
	}
	/** Checked update while singleton lock is held.
	 *
	 * @param string $table Allowlisted ledger table.
	 * @param string $id Exact object identifier.
	 * @param array  $data Validated database columns.
	 */
	public function update( $table, $id, $data ) {
		global $wpdb;
		CSF_Billing_Domain::need( $this->active, 'transaction_required' );
		$this->check( $wpdb->update( $this->table( $table ), $data, array( 'id' => $id ), $this->formats( $data ), array( '%s' ) ) );
	}
	/** Audited details must contain no contact, terms or certificate secrets.
	 *
	 * @param string $kind Compiled audit event name.
	 * @param int    $actor Authorized actor ID or zero for provider events.
	 * @param string $object_id Audited object UUID.
	 * @param array  $details Minimal non-sensitive audit details.
	 */
	public function audit( $kind, $actor, $object_id, $details ) {
		$this->insert(
			'audit',
			array(
				'kind'       => $kind,
				'actor'      => $actor,
				'object_id'  => $object_id,
				'details'    => CSF_Billing_Domain::json( $details ),
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}
	/** Never inherit WordPress core's integer object_id coercion for financial UUIDs.
	 *
	 * @param array $data Compiled ledger columns.
	 * @return array
	 */
	private function formats( $data ) {
		return array_map(
			static function ( $column ) {
				return in_array( $column, array( 'actor', 'revision', 'purchased', 'redeemed', 'schema_version', 'hits', 'expires' ), true ) ? '%d' : '%s';
			},
			array_keys( $data )
		);
	}

	/** Database errors never escape in public responses.
	 *
	 * @param mixed $result Database operation result.
	 */
	private function check( $result ) {
		CSF_Billing_Domain::need( false !== $result, 'ledger_unavailable' );
	}
}
