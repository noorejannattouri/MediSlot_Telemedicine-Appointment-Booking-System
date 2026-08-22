<?php
/**
 * MediSlot - Stripe Webhook
 * File: api/payment_callback.php
 */

require_once '../config.php';

// =============== STRIPE KEYS ===============
$stripe_secret_key     = 'sk_test_YOUR_SECRET_KEY_HERE';
$stripe_webhook_secret = 'whsec_YOUR_WEBHOOK_SECRET_HERE';
// ===========================================

$payload    = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!$payload) {
    http_response_code(400);
    exit('No payload');
}

$event = json_decode($payload, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit('Invalid JSON');
}

$event_type = $event['type'] ?? '';

try {
    if ($event_type === 'checkout.session.completed' || $event_type === 'payment_intent.succeeded') {

        $session = $event['data']['object'];
        $transaction_id = $session['client_reference_id'] 
                          ?? $session['metadata']['transaction_id'] 
                          ?? $session['id'] 
                          ?? null;

        if ($transaction_id) {
            $status = 'paid';
            $gateway_response = json_encode($session);

            // Update payment
            $stmt = $conn->prepare("
                UPDATE payments 
                SET status = ?, gateway_response = ?, updated_at = NOW()
                WHERE transaction_id = ?
                LIMIT 1
            ");
            $stmt->bind_param("sss", $status, $gateway_response, $transaction_id);
            $stmt->execute();
            $stmt->close();

            // Confirm the appointment
            $stmt2 = $conn->prepare("
                UPDATE appointments a
                JOIN payments p ON a.appointment_id = p.appointment_id
                SET a.status = 'confirmed'
                WHERE p.transaction_id = ?
            ");
            $stmt2->bind_param("s", $transaction_id);
            $stmt2->execute();
            $stmt2->close();
        }
    }

    if ($event_type === 'payment_intent.payment_failed' || $event_type === 'checkout.session.expired') {
        $session = $event['data']['object'];
        $transaction_id = $session['client_reference_id'] 
                          ?? $session['metadata']['transaction_id'] 
                          ?? $session['id'] 
                          ?? null;

        if ($transaction_id) {
            $status = 'failed';
            $gateway_response = json_encode($session);

            $stmt = $conn->prepare("
                UPDATE payments 
                SET status = ?, gateway_response = ?, updated_at = NOW()
                WHERE transaction_id = ?
                LIMIT 1
            ");
            $stmt->bind_param("sss", $status, $gateway_response, $transaction_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    http_response_code(200);
    echo json_encode(['received' => true]);

} catch (Exception $e) {
    error_log('Stripe Webhook Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true]);
}