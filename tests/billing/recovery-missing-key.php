<?php
/** Standalone missing key rejection; no WordPress, credentials or database loaded. */
define('ABSPATH',__DIR__.'/');
function esc_html($value){return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
require __DIR__.'/../../wp-content/plugins/csf-billing/includes/class-csf-billing-failure.php';
require __DIR__.'/../../wp-content/plugins/csf-billing/includes/class-csf-billing-domain.php';
require __DIR__.'/../../wp-content/plugins/csf-billing/includes/class-csf-billing-config.php';
try{CSF_Billing_Config::key();throw new RuntimeException('Missing key accepted');}catch(CSF_Billing_Failure $e){if($e->getMessage()!=='configuration_incomplete'){throw $e;}echo 'MISSING_RECOVERY_KEY_FAILS_CLOSED';}
