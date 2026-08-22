<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
$patient_id = $_SESSION['user_id'];

if ($appointment_id <= 0) {
    die("Invalid appointment ID.");
}

// Get appointment details + doctor fee
$stmt = $conn->prepare("
    SELECT a.appointment_id, a.doctor_id, a.status,
           d.consultation_fee, 
           u.name AS doctor_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN users u ON a.doctor_id = u.user_id
    WHERE a.appointment_id = ? AND a.patient_id = ?
");
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appt) {
    die("Appointment not found or access denied.");
}

$amount = (float)($appt['consultation_fee'] ?: 500);
$doctor_name = $appt['doctor_name'];

// Create unique transaction ID
$transaction_id = 'MS_' . time() . '_' . $appointment_id;

// Insert pending payment
$stmt = $conn->prepare("
    INSERT INTO payments (appointment_id, patient_id, doctor_id, amount, transaction_id, status)
    VALUES (?, ?, ?, ?, ?, 'pending')
");
$stmt->bind_param("iiids", $appointment_id, $patient_id, $appt['doctor_id'], $amount, $transaction_id);
$stmt->execute();
$stmt->close();

// =============== STRIPE SECRET KEY ===============
$stripe_secret_key = 'sk_test_YOUR_SECRET_KEY_HERE';
// =================================================

// Create Stripe Checkout Session
$post_data = [
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price_data' => [
            'currency'     => 'bdt',
            'product_data' => [
                'name'        => 'Consultation Fee - Dr. ' . $doctor_name,
                'description' => 'Appointment #' . $appointment_id,
            ],
            'unit_amount'  => (int)($amount * 100),
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    'success_url' => 'http://localhost/medislot/payment_success.php?tran_id=' . urlencode($transaction_id),
    'cancel_url'  => 'http://localhost/medislot/my_appointments.php?cancelled=1',
    'client_reference_id' => $transaction_id,
    'metadata[appointment_id]' => $appointment_id,
    'metadata[patient_id]'     => $patient_id,
    'metadata[transaction_id]' => $transaction_id,
];

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($post_data),
    CURLOPT_USERPWD        => $stripe_secret_key . ':',
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$session = json_decode($response, true);

if ($http_code === 200 && isset($session['url'])) {
    header("Location: " . $session['url']);
    exit();
} else {
    echo "<h3>Stripe Error</h3>";
    echo "<pre>" . htmlspecialchars(print_r($session, true)) . "</pre>";
}