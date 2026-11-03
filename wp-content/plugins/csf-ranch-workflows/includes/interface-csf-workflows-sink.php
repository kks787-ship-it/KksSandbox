<?php
/**
 * Local workflow integration contract; no production implementation is registered.
 *
 * @package CSF_Workflows
 */

/** Only a sink transport is allowed by this unreleased prototype. */
interface CSF_Workflows_Sink {
	/**
	 * Must be a fixture/sink, not a real mail or push provider.
	 *
	 * @return bool
	 */
	public function is_sink(): bool;
	/**
	 * Stable key is required; false means known not accepted, exception means uncertain outcome.
	 *
	 * @param string $key Key.
	 * @param int    $recipient Resolved recipient identity.
	 * @param array  $minimal_message Reference-only event envelope.
	 * @return bool
	 */
	public function deliver( string $key, int $recipient, array $minimal_message ): bool;
}
