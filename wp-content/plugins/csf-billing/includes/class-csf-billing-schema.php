<?php
/** Explicitly installed billing-only schema.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;
/** No activation/upgrade hook invokes this class. */
final class CSF_Billing_Schema {
	/** Return reviewed DDL, without executing it.
	 */
	public static function statements() {
		global $wpdb;
		$p = $wpdb->prefix . 'csf_billing_';
		CSF_Billing_Domain::need( 1 === preg_match( '/^[A-Za-z0-9_]+$/D', $p ) );
		return array(
			"CREATE TABLE {$p}guard (id tinyint unsigned NOT NULL, schema_version int unsigned NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
			"CREATE TABLE {$p}orders (id char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, session_hash char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, provider_order_id varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL, capture_id varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL, status varchar(32) NOT NULL, document longtext NOT NULL, revision bigint unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY (id), UNIQUE KEY provider_order (provider_order_id), UNIQUE KEY capture (capture_id)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
			"CREATE TABLE {$p}certificates (id char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, order_id char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, number_hash char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, number_sealed text NOT NULL, value_json longtext NOT NULL, purchased int unsigned NOT NULL, redeemed int unsigned NOT NULL, status varchar(32) NOT NULL, revision bigint unsigned NOT NULL, PRIMARY KEY (id), UNIQUE KEY one_per_order (order_id), UNIQUE KEY certificate_number (number_hash)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
			"CREATE TABLE {$p}commands (id char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, payload_hash char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, result longtext NOT NULL, created_at datetime NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
			"CREATE TABLE {$p}audit (id bigint unsigned NOT NULL AUTO_INCREMENT, kind varchar(64) NOT NULL, actor bigint unsigned NOT NULL, object_id char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, details longtext NOT NULL, created_at datetime NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
			"CREATE TABLE {$p}throttle (id char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, hits int unsigned NOT NULL, expires bigint unsigned NOT NULL, PRIMARY KEY (id), KEY expires (expires)) ENGINE=InnoDB",
			"INSERT INTO {$p}guard (id,schema_version) VALUES (1,3)",
		);
	}
}
