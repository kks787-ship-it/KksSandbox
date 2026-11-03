<?php
/**
 * Local-only transactional relationship/outbox research candidate.
 *
 * @package CSF_Workflows
 */

// This domain library has no WordPress entry points or default production authorization.
// phpcs:disable WordPress.DB,WordPress.WP.AlternativeFunctions,WordPress.PHP.DevelopmentFunctions,WordPress.NamingConventions.ValidVariableName,WordPress.Security.EscapeOutput

require_once __DIR__ . '/interface-csf-workflows-access.php';
require_once __DIR__ . '/interface-csf-workflows-appointments.php';
require_once __DIR__ . '/interface-csf-workflows-sink.php';

/** Separate experimental tables. No activation or migration applies these definitions. */
final class CSF_Workflows {
	const BOARDER_FIELDS   = array( 'barn_name', 'owner_observation', 'tack_notes' );
	const EDUCATION_FIELDS = array( 'display_label', 'goals', 'experience', 'focus' );
	/**
	 * Internal db state.
	 *
	 * @var mysqli
	 */
	private mysqli $db;
	/**
	 * Internal prefix state.
	 *
	 * @var string
	 */
	private string $prefix;
	/**
	 * Internal access state.
	 *
	 * @var CSF_Workflows_Access
	 */
	private CSF_Workflows_Access $access;
	/**
	 * Internal appointments state.
	 *
	 * @var CSF_Workflows_Appointments|null
	 */
	private ?CSF_Workflows_Appointments $appointments;
	/**
	 * Internal inside state.
	 *
	 * @var bool
	 */
	private bool $inside = false;
	/**
	 * No default/fake authorization or real notification driver is provided.
	 *
	 * @param mysqli                          $db Caller-owned transaction connection.
	 * @param string                          $prefix Prefix.
	 * @param CSF_Workflows_Access            $access Authoritative access adapter.
	 * @param CSF_Workflows_Appointments|null $appointments Trusted scheduler adapter, if implemented.
	 */
	public function __construct( mysqli $db, string $prefix, CSF_Workflows_Access $access, ?CSF_Workflows_Appointments $appointments = null ) {
		self::check( (bool) preg_match( '/^[a-z][a-z0-9_]{0,30}$/D', $prefix ) );
		$this->db           = $db;
		$this->prefix       = $prefix;
		$this->access       = $access;
		$this->appointments = $appointments;
	}
	/**
	 * Sanitized domain failure.
	 *
	 * @param bool   $ok Ok.
	 * @param string $code Code.
	 * @return void
	 * @throws RuntimeException When validation, authorization or storage fails.
	 */
	private static function check( bool $ok, string $code = 'invalid' ): void {
		if ( ! $ok ) {
			throw new RuntimeException( $code ); }
	}
	/**
	 * Pure definitions for disposable tests only; no installer exists in this package.
	 *
	 * @return array
	 */
	public static function definitions(): array {
		return array(
			'facility' => 'id VARCHAR(64) PRIMARY KEY, revision BIGINT NOT NULL',
			'entity'   => 'facility VARCHAR(64) NOT NULL, kind VARCHAR(32) NOT NULL, id VARCHAR(64) NOT NULL, revision BIGINT NOT NULL, data LONGTEXT NOT NULL, PRIMARY KEY(facility,kind,id)',
			'command'  => 'facility VARCHAR(64) NOT NULL, id VARCHAR(64) NOT NULL, actor BIGINT NOT NULL, digest CHAR(64) NOT NULL, result LONGTEXT NOT NULL, PRIMARY KEY(facility,id)',
			'audit'    => 'id BIGINT AUTO_INCREMENT PRIMARY KEY, facility VARCHAR(64) NOT NULL, command_id VARCHAR(64) NOT NULL, actor BIGINT NOT NULL, event VARCHAR(48) NOT NULL, subject VARCHAR(64) NOT NULL, old_revision BIGINT NOT NULL, new_revision BIGINT NOT NULL',
			'outbox'   => 'id CHAR(64) PRIMARY KEY, facility VARCHAR(64) NOT NULL, command_id VARCHAR(64) NOT NULL, event VARCHAR(48) NOT NULL, subject VARCHAR(64) NOT NULL, recipient BIGINT NOT NULL, state VARCHAR(16) NOT NULL, attempts INT NOT NULL, next_after BIGINT NOT NULL, lease CHAR(32) NULL, lease_until BIGINT NULL',
			'attempt'  => 'id BIGINT AUTO_INCREMENT PRIMARY KEY, outbox_id CHAR(64) NOT NULL, state VARCHAR(16) NOT NULL, at_time BIGINT NOT NULL',
		);
	}
	/**
	 * Validated identifiers, never raw request/table strings.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	private function table( string $name ): string {
		self::check( isset( self::definitions()[ $name ] ) );
		return '`' . $this->prefix . $name . '`'; }
	/**
	 * Prepared query on the caller-owned connection; errors roll the transaction back.
	 *
	 * @param string $sql Sql.
	 * @param array  $values Values.
	 * @return mysqli_stmt
	 */
	private function sql( string $sql, array $values = array() ): mysqli_stmt {
		$stmt = $this->db->prepare( $sql );
		if ( $values ) {
			$types = implode( '', array_map( static fn( $v ) => is_int( $v ) ? 'i' : 's', $values ) );
			$stmt->bind_param( $types, ...$values );
		}
		$stmt->execute();
		return $stmt;
	}
	/**
	 * Reject an externally active transaction instead of implicitly committing it.
	 * SET TRANSACTION affects only the next owned transaction on this connection.
	 *
	 * @return void
	 * @throws Throwable If a transaction is active or storage fails.
	 */
	private function begin(): void {
		$this->db->query( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED' );
		$this->db->begin_transaction();
	}

	/**
	 * Stable payload hashing includes actor, facility and full mutation body.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function canonical( mixed $value ): string {
		$sort = static function ( $v ) use ( &$sort ) {
			if ( is_array( $v ) ) {
				if ( ! array_is_list( $v ) ) {
					ksort( $v );
				} foreach ( $v as &$item ) {
					$item = $sort( $item );
				} unset( $item );
			} return $v;
		};
		return json_encode( $sort( $value ), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE );
	}
	/**
	 * No role, account or facility is inferred from a display name.
	 *
	 * @param string      $facility Facility scope.
	 * @param int         $id Scoped stable identity.
	 * @param string|null $permission Permission.
	 * @return array
	 */
	private function actor( string $facility, int $id, ?string $permission = null ): array {
		self::check( $id > 0 );
		$a = $this->access->actor( $facility, $id );
		self::check( ( $a['id'] ?? null ) === $id && ( $a['facility'] ?? null ) === $facility && true === ( $a['active'] ?? false ) && true === ( $a['verified'] ?? false ) && true === ( $a['fresh_proof'] ?? false ), 'denied' );
		if ( null !== $permission ) {
			self::check( in_array( $permission, $a['permissions'] ?? array(), true ), 'denied' );
		} return $a;
	}
	/**
	 * One facility lock serializes revisions, replay, audit and outbox.
	 *
	 * @param string   $facility Facility scope.
	 * @param int      $actor Verified acting account.
	 * @param string   $command Actor-bound command identity.
	 * @param string   $action Action.
	 * @param array    $body Body.
	 * @param callable $apply Trusted mutation participant.
	 * @return array
	 * @throws Throwable When validation, authorization or storage fails.
	 */
	private function command( string $facility, int $actor, string $command, string $action, array $body, callable $apply ): array {
		foreach ( array( $facility, $command ) as $id ) {
			self::check( (bool) preg_match( '/^[A-Za-z0-9-]{1,64}$/D', $id ) ); }
		self::check( ! $this->inside );
		$this->begin();
		$this->inside = true;
		try {
			$row = $this->sql( 'SELECT revision FROM ' . $this->table( 'facility' ) . ' WHERE id=? FOR UPDATE', array( $facility ) )->get_result()->fetch_assoc();
			self::check( null !== $row, 'facility_missing' );
			$before = $this->actor( $facility, $actor );
			$this->authorize_command( $facility, $actor, $action, $body );
			$digest = hash( 'sha256', self::canonical( array( $facility, $actor, $action, $body ) ) );
			$old    = $this->sql( 'SELECT * FROM ' . $this->table( 'command' ) . ' WHERE facility=? AND id=?', array( $facility, $command ) )->get_result()->fetch_assoc();
			if ( $old ) {
				self::check( (int) $old['actor'] === $actor && hash_equals( $old['digest'], $digest ), 'replay_mismatch' );
				$result = json_decode( $old['result'], true, 512, JSON_THROW_ON_ERROR ); } else {
				$result = $apply();
				$this->sql( 'INSERT INTO ' . $this->table( 'command' ) . '(facility,id,actor,digest,result) VALUES(?,?,?,?,?)', array( $facility, $command, $actor, $digest, self::canonical( $result ) ) );
				$this->sql( 'UPDATE ' . $this->table( 'facility' ) . ' SET revision=revision+1 WHERE id=?', array( $facility ) );
				}
				self::check( self::canonical( $before ) === self::canonical( $this->actor( $facility, $actor ) ), 'authorization_changed' );
				$this->db->commit();
				return $result;
		} catch ( Throwable $error ) {
			$this->db->rollback();
			throw $error; } finally {
			$this->inside = false; }
	}
	/**
	 * Replays require current action-specific rights, not merely an old successful command.
	 *
	 * @param string $f Facility scope.
	 * @param int    $actor Verified acting account.
	 * @param string $action Action.
	 * @param array  $body Body.
	 * @return void
	 */
	private function authorize_command( string $f, int $actor, string $action, array $body ): void {
		$map = array(
			'horse.links'       => 'horse_links',
			'primary.changed'   => 'primary_self_assign',
			'primary.review'    => 'primary_review',
			'note.committed'    => 'education_write',
			'appointment.event' => 'appointment_notify',
		);
		if ( isset( $map[ $action ] ) ) {
			$this->actor( $f, $actor, $map[ $action ] ); }
		if ( in_array( $action, array( 'primary.changed', 'note.committed' ), true ) ) {
			$this->student( $f, $actor, $body[0] ); }
		if ( 'horse.edit' === $action ) {
			$row    = $this->get( $f, 'horse', $body[0] );
			$fields = $row['data']['links'][ (string) $actor ] ?? array();
			self::check( (bool) $fields && ! array_diff( array_keys( $body[2] ), $fields ), 'denied' );
		}
	}

	/**
	 * Experimental storage is facility-scoped and returns data only through projections.
	 *
	 * @param string $facility Facility scope.
	 * @param string $kind Kind.
	 * @param string $id Scoped stable identity.
	 * @return array|null
	 */
	private function get( string $facility, string $kind, string $id ): ?array {
		$row = $this->sql( 'SELECT revision,data FROM ' . $this->table( 'entity' ) . ' WHERE facility=? AND kind=? AND id=?', array( $facility, $kind, $id ) )->get_result()->fetch_assoc();
		return $row ? array(
			'revision' => (int) $row['revision'],
			'data'     => json_decode( $row['data'], true, 512, JSON_THROW_ON_ERROR ),
		) : null;
	}
	/**
	 * Transaction-only write.
	 *
	 * @param string $f Facility scope.
	 * @param string $kind Kind.
	 * @param string $id Scoped stable identity.
	 * @param int    $revision Expected current revision.
	 * @param array  $data Data.
	 * @return void
	 */
	private function put( string $f, string $kind, string $id, int $revision, array $data ): void {
		self::check( $this->inside );
		$this->sql( 'INSERT INTO ' . $this->table( 'entity' ) . '(facility,kind,id,revision,data) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE revision=VALUES(revision),data=VALUES(data)', array( $f, $kind, $id, $revision, self::canonical( $data ) ) );
	}
	/**
	 * Metadata-only append; no educational text or contact values.
	 *
	 * @param string $f Facility scope.
	 * @param string $cmd Actor-bound command identity.
	 * @param int    $actor Verified acting account.
	 * @param string $event Event.
	 * @param string $id Scoped stable identity.
	 * @param int    $old Old.
	 * @param int    $new_revision New.
	 * @return void
	 */
	private function audit( string $f, string $cmd, int $actor, string $event, string $id, int $old, int $new_revision ): void {
		$this->sql( 'INSERT INTO ' . $this->table( 'audit' ) . '(facility,command_id,actor,event,subject,old_revision,new_revision) VALUES(?,?,?,?,?,?,?)', array( $f, $cmd, $actor, $event, $id, $old, $new_revision ) );
	}
	/**
	 * Unique event/recipient key shares the mutation's transaction.
	 *
	 * @param string $f Facility scope.
	 * @param string $cmd Actor-bound command identity.
	 * @param string $event Event.
	 * @param string $subject Subject.
	 * @param array  $recipients Recipients.
	 * @return void
	 */
	private function emit( string $f, string $cmd, string $event, string $subject, array $recipients ): void {
		self::check( $this->inside );
		foreach ( array_unique( $recipients ) as $recipient ) {
			self::check( is_int( $recipient ) && $recipient > 0 );
			$id = hash( 'sha256', self::canonical( array( $f, $cmd, $event, $subject, $recipient ) ) );
			$this->sql( 'INSERT INTO ' . $this->table( 'outbox' ) . '(id,facility,command_id,event,subject,recipient,state,attempts,next_after) VALUES(?,?,?,?,?,?,?,0,0)', array( $id, $f, $cmd, $event, $subject, $recipient, 'pending' ) );
		}
	}
	/**
	 * Farm-controlled links; zero/one/multiple accounts, never legal ownership transfer.
	 *
	 * @param string $f Facility scope.
	 * @param int    $actor Verified acting account.
	 * @param string $cmd Actor-bound command identity.
	 * @param string $horse Horse.
	 * @param int    $revision Expected current revision.
	 * @param array  $links Farm-approved account/field assignments.
	 * @return array
	 */
	public function horse_links( string $f, int $actor, string $cmd, string $horse, int $revision, array $links ): array {
		return $this->command(
			$f,
			$actor,
			$cmd,
			'horse.links',
			array( $horse, $revision, $links ),
			function () use ( $f, $actor, $cmd, $horse, $revision, $links ) {
				$this->actor( $f, $actor, 'horse_links' );
				$row = $this->get( $f, 'horse', $horse );
				self::check( $row && $row['revision'] === $revision, 'revision_conflict' );
				foreach ( $links as $id => $fields ) {
					self::check( ctype_digit( (string) $id ) && (int) $id > 0 && is_array( $fields ) && array_is_list( $fields ) && ! array_diff( $fields, self::BOARDER_FIELDS ) ); }
				foreach ( array_keys( $links ) as $target ) {
					$member = $this->access->recipient( $f, (int) $target, 'horse.access', $horse );
					self::check( true === ( $member['active'] ?? false ) && true === ( $member['verified'] ?? false ) && true === ( $member['allowed'] ?? false ), 'link_target_denied' );
				}
				$data          = $row['data'];
				$data['links'] = $links;
				$this->put( $f, 'horse', $horse, $revision + 1, $data );
				$this->audit( $f, $cmd, $actor, 'horse.links', $horse, $revision, $revision + 1 );
				return array( 'revision' => $revision + 1 );
			}
		);
	}
	/**
	 * Narrow field projection excludes all farm-only/clinical/financial/relationship fields.
	 *
	 * @param string $f Facility scope.
	 * @param int    $actor Verified acting account.
	 * @param string $horse Horse.
	 * @return array
	 */
	public function horse( string $f, int $actor, string $horse ): array {
		$a   = $this->actor( $f, $actor );
		$row = $this->get( $f, 'horse', $horse );
		self::check( null !== $row, 'not_found' );
		$fields = in_array( 'horse_view_all', $a['permissions'], true ) ? self::BOARDER_FIELDS : ( $row['data']['links'][ (string) $actor ] ?? array() );
		self::check( (bool) $fields, 'denied' );
		return array_intersect_key( $row['data']['profile'], array_flip( $fields ) );
	}
	/**
	 * Boarders cannot self-link, change other horses, or edit protected fields.
	 *
	 * @param string $f Facility scope.
	 * @param int    $actor Verified acting account.
	 * @param string $cmd Actor-bound command identity.
	 * @param string $horse Horse.
	 * @param int    $revision Expected current revision.
	 * @param array  $fields Explicit allowed profile fields.
	 * @return array
	 */
	public function horse_edit( string $f, int $actor, string $cmd, string $horse, int $revision, array $fields ): array {
		return $this->command(
			$f,
			$actor,
			$cmd,
			'horse.edit',
			array( $horse, $revision, $fields ),
			function () use ( $f, $actor, $cmd, $horse, $revision, $fields ) {
				$this->actor( $f, $actor );
				$row = $this->get( $f, 'horse', $horse );
				self::check( $row && $row['revision'] === $revision, 'revision_conflict' );
				$allowed = $row['data']['links'][ (string) $actor ] ?? array();
				self::check( $fields && ! array_diff( array_keys( $fields ), $allowed ), 'denied' );
				foreach ( $fields as $value ) {
					self::check( is_string( $value ) && strlen( $value ) <= 4000 ); }
				$data            = $row['data'];
				$data['profile'] = array_replace( $data['profile'], $fields );
				$this->put( $f, 'horse', $horse, $revision + 1, $data );
				$this->audit( $f, $cmd, $actor, 'horse.edit', $horse, $revision, $revision + 1 );
				return array( 'revision' => $revision + 1 );
			}
		);
	}
	/**
	 * All authorized educators see educational projections, regardless of primary assignment.
	 *
	 * @param string $f Facility scope.
	 * @param int    $actor Verified acting account.
	 * @param string $student Student.
	 * @return array
	 */
	public function student( string $f, int $actor, string $student ): array {
		$this->actor( $f, $actor, 'education_view' );
		$row = $this->get( $f, 'student', $student );
		self::check( null !== $row, 'not_found' );
		return array_intersect_key( $row['data']['education'], array_flip( self::EDUCATION_FIELDS ) );
	}
	/**
	 * An immediate self-reassignment is pending management review, never pending effectiveness.
	 *
	 * @param string $f Facility scope.
	 * @param int    $actor Verified acting account.
	 * @param string $cmd Actor-bound command identity.
	 * @param string $student Student.
	 * @param int    $revision Expected current revision.
	 * @return array
	 */
	public function assign( string $f, int $actor, string $cmd, string $student, int $revision ): array {
		return $this->command(
			$f,
			$actor,
			$cmd,
			'primary.changed',
			array( $student, $revision ),
			function () use ( $f, $actor, $cmd, $student, $revision ) {
				$this->actor( $f, $actor, 'primary_self_assign' );
				$this->student( $f, $actor, $student );
				$row = $this->get( $f, 'primary', $student ) ?? array(
					'revision' => 0,
					'data'     => array(
						'primary' => null,
						'pending' => null,
					),
				);
				self::check( $row['revision'] === $revision, 'revision_conflict' );
				$previous = $row['data']['primary'];
				$this->put(
					$f,
					'primary',
					$student,
					$revision + 1,
					array(
						'primary' => $actor,
						'pending' => $cmd,
					)
				);
				$this->put(
					$f,
					'change',
					$cmd,
					1,
					array(
						'student'             => $student,
						'from'                => $previous,
						'to'                  => $actor,
						'assignment_revision' => $revision + 1,
						'state'               => 'pending_review',
					)
				);
				$this->audit( $f, $cmd, $actor, 'primary.changed', $student, $revision, $revision + 1 );
				$this->emit( $f, $cmd, 'primary.changed', $student, $this->access->managers( $f ) );
				return array(
					'primary'  => $actor,
					'revision' => $revision + 1,
					'state'    => 'pending_review',
				);
			}
		);
	}
	/**
	 * Stale rejection is a conflict review and cannot overwrite a later assignment.
	 *
	 * @param string $f Facility scope.
	 * @param int    $actor Verified acting account.
	 * @param string $cmd Actor-bound command identity.
	 * @param string $change Change.
	 * @param string $decision Decision.
	 * @return array
	 */
	public function review( string $f, int $actor, string $cmd, string $change, string $decision ): array {
		return $this->command(
			$f,
			$actor,
			$cmd,
			'primary.review',
			array( $change, $decision ),
			function () use ( $f, $actor, $cmd, $change, $decision ) {
				$this->actor( $f, $actor, 'primary_review' );
				self::check( in_array( $decision, array( 'approve', 'reject' ), true ) );
				$row = $this->get( $f, 'change', $change );
				self::check( $row && 'pending_review' === $row['data']['state'], 'already_reviewed' );
				$data    = $row['data'];
				$current = $this->get( $f, 'primary', $data['student'] );
				$same    = $current && $current['revision'] === $data['assignment_revision'] && $current['data']['pending'] === $change;
				if ( 'reject' === $decision && null !== $data['from'] ) {
					try {
						$previous = $this->access->recipient( $f, $data['from'], 'primary.restore', $data['student'] );
						$same     = $same && true === ( $previous['active'] ?? false ) && true === ( $previous['verified'] ?? false ) && true === ( $previous['allowed'] ?? false );
					} catch ( Throwable $e ) {
								$same = false; }
				}
				$state = $same ? ( 'approve' === $decision ? 'approved' : 'rejected' ) : 'conflict_review';
				if ( $same ) {
					$this->put(
						$f,
						'primary',
						$data['student'],
						$current['revision'] + 1,
						array(
							'primary' => 'approve' === $decision ? $data['to'] : $data['from'],
							'pending' => null,
						)
					); }
				$data['state'] = $state;
				$this->put( $f, 'change', $change, $row['revision'] + 1, $data );
				$this->audit( $f, $cmd, $actor, 'primary.' . $state, $data['student'], $current['revision'], $same ? $current['revision'] + 1 : $current['revision'] );
				$this->emit( $f, $cmd, 'primary.' . $state, $data['student'], array_values( array_filter( array( $data['from'], $data['to'] ), static fn( $id ) => is_int( $id ) ) ) );
				return array(
					'state'   => $state,
					'primary' => $same && 'reject' === $decision ? $data['from'] : $current['data']['primary'],
				);
			}
		);
	}
	/**
	 * Authorship is distinct from the effective primary at commit.
	 *
	 * @param string $f Facility scope.
	 * @param int    $actor Verified acting account.
	 * @param string $cmd Actor-bound command identity.
	 * @param string $student Student.
	 * @param string $text Educational note text.
	 * @return array
	 */
	public function note( string $f, int $actor, string $cmd, string $student, string $text ): array {
		return $this->command(
			$f,
			$actor,
			$cmd,
			'note.committed',
			array( $student, $text ),
			function () use ( $f, $actor, $cmd, $student, $text ) {
				$this->actor( $f, $actor, 'education_write' );
				$this->student( $f, $actor, $student );
				self::check( strlen( $text ) > 0 && strlen( $text ) <= 8000 );
				$this->put(
					$f,
					'note',
					$cmd,
					1,
					array(
						'student' => $student,
						'author'  => $actor,
						'text'    => $text,
					)
				);
				$this->audit( $f, $cmd, $actor, 'note.committed', $student, 0, 1 );
				$primary   = $this->get( $f, 'primary', $student );
				$recipient = $primary['data']['primary'] ?? null;
				if ( is_int( $recipient ) ) {
					$this->emit( $f, $cmd, 'note.committed', $student, array( $recipient ) ); }
				return array(
					'note'              => $cmd,
					'author'            => $actor,
					'primary_at_commit' => $recipient,
				);
			}
		);
	}
	/**
	 * New committed notes are readable to all currently authorized educators, not only primary.
	 *
	 * @param string $f Facility scope.
	 * @param int    $actor Verified acting account.
	 * @param string $student Student.
	 * @return array
	 */
	public function notes( string $f, int $actor, string $student ): array {
		$this->student( $f, $actor, $student );
		$rows = $this->sql( 'SELECT id,data FROM ' . $this->table( 'entity' ) . ' WHERE facility=? AND kind=? ORDER BY id', array( $f, 'note' ) )->get_result()->fetch_all( MYSQLI_ASSOC );
		$out  = array();
		foreach ( $rows as $row ) {
			$data = json_decode( $row['data'], true, 512, JSON_THROW_ON_ERROR );
			if ( $data['student'] === $student ) {
				$out[] = array(
					'id'     => $row['id'],
					'author' => $data['author'],
					'text'   => $data['text'],
				);
			}
		} return $out;
	}
	/**
	 * Requires a trusted same-connection adapter; no replacement scheduling/conflict engine.
	 *
	 * @param string $f Facility scope.
	 * @param int    $actor Verified acting account.
	 * @param string $cmd Actor-bound command identity.
	 * @return array
	 */
	public function appointment( string $f, int $actor, string $cmd ): array {
		return $this->command(
			$f,
			$actor,
			$cmd,
			'appointment.event',
			array(),
			function () use ( $f, $actor, $cmd ) {
				$this->actor( $f, $actor, 'appointment_notify' );
				self::check( null !== $this->appointments, 'scheduler_adapter_missing' );
				$change = $this->appointments->change( $this->db, $f, $cmd );
				self::check( is_array( $change ), 'uncommitted_appointment' );
				self::check( in_array( $change['event'], array( 'new', 'rescheduled', 'confirmed', 'canceled' ), true ) );
				if ( 'draft' === $change['status'] ) {
					return array( 'queued' => false ); }
				self::check( in_array( $change['status'], array( 'requested', 'held', 'confirmed', 'canceled' ), true ) );
				$recipients = $this->access->managers( $f, null === $change['instructor'] );
				if ( is_int( $change['instructor'] ) ) {
					$recipients[] = $change['instructor']; }
				$this->emit( $f, $cmd, 'appointment.' . $change['event'], $change['id'], $recipients );
				return array( 'queued' => true );
			}
		);
	}
	/**
	 * Sink-only delivery; permissions/verified contact/consent are rechecked at delivery.
	 *
	 * @param CSF_Workflows_Sink $sink Sink.
	 * @param int                $now Worker clock in seconds.
	 * @return string
	 * @throws Throwable When validation, authorization or storage fails.
	 */
	public function deliver_one( CSF_Workflows_Sink $sink, int $now ): string {
		self::check( $sink->is_sink(), 'real_delivery_disabled' );
		$this->begin();
		try {
			$expired = $this->sql( 'SELECT id FROM ' . $this->table( 'outbox' ) . " WHERE state='leased' AND lease_until<=? ORDER BY id LIMIT 1 FOR UPDATE", array( $now ) )->get_result()->fetch_assoc();
			if ( $expired ) {
				$this->sql( 'UPDATE ' . $this->table( 'outbox' ) . " SET state='uncertain',lease=NULL,lease_until=NULL WHERE id=?", array( $expired['id'] ) );
				$this->sql( 'INSERT INTO ' . $this->table( 'attempt' ) . '(outbox_id,state,at_time) VALUES(?,?,?)', array( $expired['id'], 'uncertain', $now ) ); }
			$row = $this->sql( 'SELECT * FROM ' . $this->table( 'outbox' ) . " WHERE state IN ('pending','retry') AND next_after<=? ORDER BY id LIMIT 1 FOR UPDATE", array( $now ) )->get_result()->fetch_assoc();
			if ( ! $row ) {
				$this->db->commit();
				return 'empty'; }
			$lease = bin2hex( random_bytes( 16 ) );
			$this->sql( 'UPDATE ' . $this->table( 'outbox' ) . " SET state='leased',lease=?,lease_until=?,attempts=attempts+1 WHERE id=?", array( $lease, $now + 60, $row['id'] ) );
			$this->sql( 'INSERT INTO ' . $this->table( 'attempt' ) . '(outbox_id,state,at_time) VALUES(?,?,?)', array( $row['id'], 'leased', $now ) );
			$this->db->commit();
		} catch ( Throwable $e ) {
			$this->db->rollback();
			throw $e; }
		$recipient     = array();
		$lookup_failed = false;
		try {
			$recipient = $this->access->recipient( $row['facility'], (int) $row['recipient'], $row['event'], $row['subject'] );
		} catch ( Throwable $e ) {
			$lookup_failed = true;
		}
		$eligible = true === ( $recipient['active'] ?? false ) && true === ( $recipient['verified'] ?? false ) && true === ( $recipient['allowed'] ?? false ) && true === ( $recipient['contact_verified'] ?? false ) && true === ( $recipient['opt_in'] ?? false );
		if ( 'note.committed' === $row['event'] ) {
			$primary  = $this->get( $row['facility'], 'primary', $row['subject'] );
			$eligible = $eligible && ( $primary['data']['primary'] ?? null ) === (int) $row['recipient'];
		}
		try {
			if ( $lookup_failed ) {
				$state = 'retry';
			} elseif ( ! $eligible ) {
				$state = 'suppressed';
			} else {
				$state = $sink->deliver(
					$row['id'],
					(int) $row['recipient'],
					array(
						'event'     => $row['event'],
						'reference' => $row['subject'],
						'channel'   => 'email',
					)
				) ? 'sent' : 'retry';
			}
		} catch ( Throwable $e ) {
			$state = 'uncertain';
		}
		if ( 'retry' === $state && (int) $row['attempts'] >= 2 ) {
			$state = 'failed'; }
		$this->begin();
		try {
			$stmt = $this->sql( 'UPDATE ' . $this->table( 'outbox' ) . ' SET state=?,next_after=?,lease=NULL,lease_until=NULL WHERE id=? AND lease=? AND state=?', array( $state, $now + min( 3600, 30 * 2 ** (int) $row['attempts'] ), $row['id'], $lease, 'leased' ) );
			self::check( 1 === $stmt->affected_rows, 'lease_lost' );
			$this->sql( 'INSERT INTO ' . $this->table( 'attempt' ) . '(outbox_id,state,at_time) VALUES(?,?,?)', array( $row['id'], $state, $now ) );
			$this->db->commit();
			return $state;
		} catch ( Throwable $e ) {
			$this->db->rollback();
			throw $e;
		}
	}
}
