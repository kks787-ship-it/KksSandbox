<?php
/** Private bounded PDF generation without remote assets or script execution.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;

/** A compiled printable template; artifacts are authenticated encrypted ledger records. */
final class CSF_Billing_PDF {
	/** Escape PDF literal strings; unsupported text remains pending rather than corrupted.
	 *
	 * @param string $text Plain template text.
	 */
	private static function literal( $text ) {
		CSF_Billing_Domain::need( is_string( $text ) && ! preg_match( '/[^\x20-\x7e\r\n]/', $text ), 'pdf_text_review_required' );
		return str_replace( array( '\\', '(', ')', "\r", "\n" ), array( '\\\\', '\\(', '\\)', '', ' ' ), $text );
	}
	/** Render a one-page certificate; full exact UTF8 terms remain a separate attachment.
	 *
	 * @param array $receipt Authorized immutable receipt.
	 * @return string PDF bytes.
	 */
	public static function render( $receipt ) {
		// Reuse issuance/status, signature-envelope and design validation.
		CSF_Billing_Fulfillment::render( $receipt );
		$f      = $receipt['presentation'];
		$colors = array(
			'pasture-v1' => '0.16 0.25 0.14',
			'sunset-v1'  => '0.53 0.33 0.11',
			'classic-v1' => '0.14 0.22 0.29',
		);
		$stream = $colors[ $f['design'] ] . " RG 3 w 30 30 732 552 re S\n";
		$y      = 540;
		$lines  = array( 'SANDBOX TEST - NOT VALID FOR RIDES', 'Corner Stone Farm', 'Gift Certificate', $receipt['package'], 'Number: ' . $receipt['certificate_number'], 'Originally purchased rides: ' . $receipt['purchased'], 'Purchased value: ' . CSF_Billing_Domain::amount( $receipt['amount_minor'] ) . ' USD', 'Accepted terms: ' . $receipt['terms']['version'], 'Keep this certificate number private.' );
		foreach ( $lines as $index => $line ) {
			foreach ( explode( "\n", wordwrap( $line, 80, "\n", true ) ) as $part ) {
				$size    = 1 === $index ? 28 : ( 2 === $index ? 20 : 12 );
				$stream .= "BT /F1 $size Tf 50 $y Td (" . self::literal( $part ) . ") Tj ET\n";
				$y      -= 1 === $index || 2 === $index ? 34 : 22;
			}
		}
		$image = null;
		if ( ! empty( $f['signature_sealed'] ) ) {
			CSF_Billing_Domain::need( function_exists( 'imagecreatefromstring' ) && function_exists( 'imagejpeg' ), 'pdf_signature_renderer_unavailable' );
			$s = json_decode( CSF_Billing_Config::open( $f['signature_sealed'] ), true, 8, JSON_THROW_ON_ERROR );
			// Private authorized raster only; no URL/file/SVG interpretation.
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$source = imagecreatefromstring( base64_decode( $s['png_base64'], true ) );
			CSF_Billing_Domain::need( false !== $source, 'signature_invalid' );
			$width  = imagesx( $source );
			$height = imagesy( $source );
			$flat   = imagecreatetruecolor( $width, $height );
			imagefill( $flat, 0, 0, imagecolorallocate( $flat, 255, 255, 255 ) );
			imagecopy( $flat, $source, 0, 0, 0, 0, $width, $height );
			ob_start();
			$ok    = imagejpeg( $flat, null, 95 );
			$image = ob_get_clean();
			imagedestroy( $source );
			imagedestroy( $flat );
			CSF_Billing_Domain::need( $ok && is_string( $image ), 'signature_invalid' );
			$h       = min( 80, 220 * $height / $width );
			$w       = $h * $width / $height;
			$y      -= 85;
			$stream .= "q $w 0 0 $h 50 $y cm /Sig Do Q\n";
		} else {
			$stream .= "BT /F1 12 Tf 50 $y Td (Signature pending authorized artwork setup) Tj ET\n";
		}
		$y           -= 35;
		$instructions = '' !== $f['instructions'] ? $f['instructions'] : 'Scheduling instructions pending owner setup. This is not a booking confirmation.';
		foreach ( explode( "\n", wordwrap( $instructions, 65, "\n", true ) ) as $line ) {
			CSF_Billing_Domain::need( $y > 65, 'pdf_layout_review_required' );
			$stream .= "BT /F1 12 Tf 50 $y Td (" . self::literal( $line ) . ") Tj ET\n";
			$y      -= 18;
		}
		$objects = array( '<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 792 612] /Resources << /Font << /F1 4 0 R >>' . ( null === $image ? '' : ' /XObject << /Sig 6 0 R >>' ) . ' >> /Contents 5 0 R >>', '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>', '<< /Length ' . strlen( $stream ) . ">>\nstream\n" . $stream . 'endstream' );
		if ( null !== $image ) {
			$objects[] = "<< /Type /XObject /Subtype /Image /Width $width /Height $height /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen( $image ) . ">>\nstream\n" . $image . "\nendstream";
		}
		$pdf     = "%PDF-1.4\n";
		$offsets = array( 0 );
		foreach ( $objects as $index => $object ) {
			$offsets[] = strlen( $pdf );
			$pdf      .= ( $index + 1 ) . " 0 obj\n" . $object . "\nendobj\n";
		}
		$xref = strlen( $pdf );
		$pdf .= "xref\n0 " . count( $offsets ) . "\n0000000000 65535 f \n";
		foreach ( array_slice( $offsets, 1 ) as $offset ) {
			$pdf .= sprintf( "%010d 00000 n \n", $offset );
		}
		return $pdf . 'trailer << /Size ' . count( $offsets ) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
	}
	/** Persist once for the current bearer generation; reprinting never changes balances.
	 *
	 * @param object      $store Billing store.
	 * @param string      $order_id Order UUID.
	 * @param string|null $session Customer session digest, null for verified management.
	 * @return array Private bytes and digest; HTTP caller must keep no-store.
	 */
	public static function document( $store, $order_id, $session ) {
		CSF_Billing_Domain::uuid( $order_id );
		return $store->command(
			hash( 'sha256', 'pdf_read:' . wp_generate_uuid4() ),
			array( 'order_id' => $order_id ),
			function ( $locked ) use ( $order_id, $session ) {
				$order = $locked->row( 'orders', $order_id );
				CSF_Billing_Domain::need( $order && 'issued' === $order['status'] && ( null === $session || ( is_string( $session ) && hash_equals( $order['session_hash'], $session ) ) ), 'certificate_unavailable' );
				$cert = $locked->find( 'certificates', 'order_id', $order_id );
				CSF_Billing_Domain::need( $cert && 'active' === $cert['status'], 'certificate_unavailable' );
				$key = hash( 'sha256', 'pdf:' . $cert['id'] . ':' . $cert['number_hash'] );
				$row = $locked->row( 'commands', $key );
				if ( ! $row ) {
					$d        = json_decode( $order['document'], true, 32, JSON_THROW_ON_ERROR );
					$pdf      = self::render(
						array(
							'status'             => 'issued',
							'certificate_number' => CSF_Billing_Config::open( $cert['number_sealed'] ),
							'presentation'       => $d['fulfillment'] ?? null,
							'package'            => $d['product']['label'],
							'purchased'          => (int) $cert['purchased'],
							'amount_minor'       => $d['amount_minor'],
							'terms'              => $d['terms'],
						)
					);
					$artifact = array(
						'version'        => 1,
						'certificate_id' => $cert['id'],
						'number_hash'    => $cert['number_hash'],
						'sha256'         => hash( 'sha256', $pdf ),
						'sealed'         => CSF_Billing_Config::seal( $pdf ),
					);
					$locked->insert(
						'commands',
						array(
							'id'           => $key,
							'payload_hash' => hash( 'sha256', CSF_Billing_Domain::json( $artifact ) ),
							'result'       => CSF_Billing_Domain::json( $artifact ),
							'created_at'   => gmdate( 'Y-m-d H:i:s' ),
						)
					);
					$locked->audit(
						'certificate_pdf_persisted',
						0,
						$cert['id'],
						array(
							'artifact_id' => $key,
							'number_hash' => $cert['number_hash'],
							'sha256'      => $artifact['sha256'],
						)
					);
				} else {
					$artifact = json_decode( $row['result'], true, 16, JSON_THROW_ON_ERROR );
				}
				CSF_Billing_Domain::need( $artifact['certificate_id'] === $cert['id'] && $artifact['number_hash'] === $cert['number_hash'], 'pdf_binding_mismatch' );
				return array(
					'artifact_id' => $key,
					'sha256'      => $artifact['sha256'],
					'sealed'      => $artifact['sealed'],
				);
			},
			static function () use ( $session ) {
				if ( null === $session ) {
					CSF_Billing_Access::require_cap( 'csf_billing_manage' );
				}
			}
		);
	}
}
