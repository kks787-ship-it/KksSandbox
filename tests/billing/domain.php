<?php
/** Standalone deterministic contract tests; synthetic data only. */
define( 'ABSPATH', __DIR__ . '/' );
function esc_html($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function wp_json_encode($v,$f=0) { return json_encode($v,$f); }
foreach (array('failure','domain','value','config','access','fulfillment','pdf','dispatch','booking') as $module) { require __DIR__ . '/../../wp-content/plugins/csf-billing/includes/class-csf-billing-'.$module.'.php'; }
require __DIR__ . '/../../wp-content/plugins/csf-billing/includes/class-csf-billing-service.php';
define( 'CSF_BILLING_DATA_KEY', base64_encode( str_repeat( 'x', 32 ) ) );
$count = 0;
function check( $ok, $label ) {
	global $count;
	if ( ! $ok ) { throw new RuntimeException( $label ); }
	++$count;
}
function rejected( $callback, $label ) {
	try { $callback(); } catch ( CSF_Billing_Failure $error ) { check( true, $label ); return; }
	throw new RuntimeException( 'Expected rejection: ' . $label );
}
$product = array( 'id' => '11111111-1111-4111-8111-111111111111', 'revision' => 1, 'label' => 'SYNTHETIC test package; not an offer', 'rides' => 2, 'minutes_per_ride' => 60, 'amount_minor' => 12345, 'currency' => 'USD', 'payment_mode' => 'one_time', 'approved' => true );
$terms = array( 'version' => 'synthetic-1', 'text' => 'SYNTHETIC fixture terms only, not a legal agreement.', 'sha256' => '', 'approved' => true, 'legal_reviewed' => true );
$terms['sha256'] = hash( 'sha256', $terms['text'] );
check( CSF_Billing_Domain::product( $product ) === $product, 'valid fixture product' );
check( CSF_Billing_Domain::product( array_reverse( $product, true ) )['id'] === $product['id'], 'JSON key order irrelevant' );
foreach ( array( 'approved' => false, 'payment_mode' => 'quarterly', 'amount_minor' => 123.45, 'rides' => 0, 'minutes_per_ride' => 61, 'currency' => 'CAD' ) as $key => $value ) {
	$bad = array_replace( $product, array( $key => $value ) );
	rejected( fn() => CSF_Billing_Domain::product( $bad ), 'invalid ' . $key );
}
rejected( fn() => CSF_Billing_Domain::product( $product + array( 'unknown' => 1 ) ), 'unknown field' );
foreach ( array( '123.450', '1e2', '123', '-1.00', '01.00', 123.45, null ) as $bad ) {
	rejected( fn() => CSF_Billing_Domain::minor( $bad ), 'noncanonical amount' );
}
check( '123.45' === CSF_Billing_Domain::amount( 12345 ), 'decimal encoding' );
check( 12345 === CSF_Billing_Domain::minor( '123.45' ), 'integer parsing' );
$consent = array( 'accepted' => true, 'terms_version' => $terms['version'], 'terms_hash' => $terms['sha256'] );
CSF_Billing_Domain::consent( $consent, $terms );
check( true, 'exact consent' );
foreach ( array( 'accepted' => 'true', 'terms_version' => 'old', 'terms_hash' => str_repeat( '0', 64 ) ) as $key => $value ) {
	rejected( fn() => CSF_Billing_Domain::consent( array_replace( $consent, array( $key => $value ) ), $terms ), 'stale or missing consent' );
}
rejected( fn() => CSF_Billing_Domain::terms( array_replace( $terms, array( 'legal_reviewed' => false ) ) ), 'unreviewed terms' );
rejected( fn() => CSF_Billing_Domain::terms( array_replace( $terms, array( 'text' => $terms['text'] . ' changed' ) ) ), 'changed text' );
$local = array( 'id' => $product['id'], 'provider_order_id' => 'TESTORDER12345', 'amount_minor' => 12345, 'currency' => 'USD' );
$capture = array( 'id' => 'TESTCAPTURE123', 'status' => 'COMPLETED', 'final_capture' => true, 'amount' => array( 'currency_code' => 'USD', 'value' => '123.45' ), 'supplementary_data' => array( 'related_ids' => array( 'order_id' => $local['provider_order_id'] ) ) );
$unit = array( 'reference_id' => $local['id'], 'custom_id' => $local['id'], 'invoice_id' => 'CSF-' . $local['id'], 'payee' => array( 'merchant_id' => 'TESTMERCHANT123' ), 'amount' => $capture['amount'], 'payments' => array( 'captures' => array( $capture ) ) );
$order = array( 'id' => $local['provider_order_id'], 'intent' => 'CAPTURE', 'status' => 'COMPLETED', 'purchase_units' => array( $unit ) );
check( 'TESTCAPTURE123' === CSF_Billing_Domain::paid( $local, $order, $capture, 'TESTMERCHANT123' ), 'exact verified payment' );
foreach ( array( 'PENDING', 'DECLINED', 'PARTIALLY_REFUNDED', 'REFUNDED', 'FAILED' ) as $status ) {
	$bad = array_replace( $capture, array( 'status' => $status ) );
	rejected( fn() => CSF_Billing_Domain::paid( $local, $order, $bad, 'TESTMERCHANT123' ), 'no issuance for ' . $status );
}
foreach ( array( 'reference_id', 'custom_id', 'invoice_id' ) as $key ) {
	$bad = $order; $bad['purchase_units'][0][$key] = 'mismatch';
	rejected( fn() => CSF_Billing_Domain::paid( $local, $bad, $capture, 'TESTMERCHANT123' ), 'wrong ' . $key );
}
rejected( fn() => CSF_Billing_Domain::paid( $local, $order, $capture, 'OTHERMERCHANT' ), 'wrong merchant' );
$bad = $order; $bad['purchase_units'][] = $unit;
rejected( fn() => CSF_Billing_Domain::paid( $local, $bad, $capture, 'TESTMERCHANT123' ), 'extra purchase unit' );
$bad = $capture; $bad['amount']['value'] = '123.44';
rejected( fn() => CSF_Billing_Domain::paid( $local, $order, $bad, 'TESTMERCHANT123' ), 'underpayment' );
$bad = $capture; $bad['supplementary_data']['related_ids']['order_id'] = 'OTHERORDER123';
rejected( fn() => CSF_Billing_Domain::paid( $local, $order, $bad, 'TESTMERCHANT123' ), 'capture other order' );
$bad = $capture; $bad['final_capture'] = false;
rejected( fn() => CSF_Billing_Domain::paid( $local, $order, $bad, 'TESTMERCHANT123' ), 'partial capture' );
$certificate = array( 'purchased' => 2, 'redeemed' => 1, 'revision' => 2, 'status' => 'active' );
check( array( 'redeemed' => 2, 'revision' => 3 ) === CSF_Billing_Service::redemption( $certificate, 1, 2 ), 'final ride consumed' );
foreach ( array( 0, -1, 2, 1.0, '1', PHP_INT_MAX ) as $bad ) {
	rejected( fn() => CSF_Billing_Service::redemption( $certificate, $bad, 2 ), 'invalid debit' );
}
rejected( fn() => CSF_Billing_Service::redemption( $certificate, 1, 1 ), 'stale revision' );
rejected( fn() => CSF_Billing_Service::redemption( array_replace( $certificate, array( 'status' => 'frozen' ) ), 1, 2 ), 'frozen certificate' );
$sealed = CSF_Billing_Config::seal( 'synthetic-private-certificate' );
check( 'synthetic-private-certificate' === CSF_Billing_Config::open( $sealed ), 'authenticated encryption round trip' );
check( $sealed !== CSF_Billing_Config::seal( 'synthetic-private-certificate' ), 'unique nonce' );
$raw = base64_decode( $sealed ); $raw[20] = chr( ord( $raw[20] ) ^ 1 );
rejected( fn() => CSF_Billing_Config::open( base64_encode( $raw ) ), 'tampered ciphertext' );
check( false === CSF_Billing_Config::enabled(), 'default disabled' );
check( array( 'products' => array(), 'terms' => null ) === CSF_Billing_Config::catalog(), 'no speculative offers' );
echo "PASS {$count} billing contract assertions\n";
