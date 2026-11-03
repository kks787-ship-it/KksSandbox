<?php
/**
 * Local workflow integration contract; no production implementation is registered.
 *
 * @package CSF_Workflows
 */

/** A production implementation must join authoritative authorization/transaction locks. */
interface CSF_Workflows_Access {
	/**
	 * Return current verified actor/membership/proof/permissions, never request-supplied claims.
	 *
	 * @param string $facility Facility scope.
	 * @param int    $id Scoped stable identity.
	 * @return array
	 */
	public function actor( string $facility, int $id ): array;
	/**
	 * Resolve current verified farm-management identities without hardcoded names or IDs.
	 *
	 * @param string $facility Facility scope.
	 * @param bool   $operations_only Operations only.
	 * @return array
	 */
	public function managers( string $facility, bool $operations_only = false ): array;
	/**
	 * Resolve current delivery permission, verified contact and opt-in; no email stored in events.
	 *
	 * @param string $facility Facility scope.
	 * @param int    $id Scoped stable identity.
	 * @param string $event Event.
	 * @param string $subject Subject.
	 * @return array
	 */
	public function recipient( string $facility, int $id, string $event, string $subject ): array;
}
