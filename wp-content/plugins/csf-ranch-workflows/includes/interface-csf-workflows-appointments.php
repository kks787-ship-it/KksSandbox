<?php
/**
 * Local workflow integration contract; no production implementation is registered.
 *
 * @package CSF_Workflows
 */

/** Future adapter must read an authoritative scheduler change on the SAME transaction connection. */
interface CSF_Workflows_Appointments {
	/**
	 * No client committed flag or independent appointment writer is accepted.
	 *
	 * @param mysqli $db Caller-owned transaction connection.
	 * @param string $facility Facility scope.
	 * @param string $command Actor-bound command identity.
	 * @return array|null
	 */
	public function change( mysqli $db, string $facility, string $command ): ?array;
}
