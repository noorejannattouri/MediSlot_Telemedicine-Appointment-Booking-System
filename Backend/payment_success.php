<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$tran_id = $_GET['tran_id'] ?? '';

if (empty($tran_id)) {
    die("Invalid transaction.");
}

$stmt = $conn->prepare("
    SELECT p.*, 
           u.name AS patient_name,
           d_user.name AS doctor_name,
           doc.specialization,
           doc.hospital
    FROM payments p
    JOIN users u ON p.patient_id = u.user_id
    JOIN users d_user ON p.doctor_id = d_user.user_id
    LEFT JOIN doctors doc ON p.doctor_id = doc.doctor_id
    WHERE p.transaction_id = ?
");
$stmt->bind_param("s", $tran_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    die("Payment not found.");
}

$page_title = "Payment Successful";
require_once 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5 text-center">

                    <div class="mb-4">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 90px; height: 90px;">
                            <i class="bi bi-check-lg text-success" style="font-size: 3rem;"></i>
                        </div>
                    </div>

                    <h2 class="fw-bold text-success mb-2">Payment Successful!</h2>
                    <p class="text-muted mb-4">Your consultation fee has been paid successfully.</p>

                    <!-- Payment Slip -->
                    <div class="border rounded-4 p-4 text-start bg-light mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Payment Receipt</h5>
                            <span class="badge bg-success">Paid</span>
                        </div>

                        <hr>

                        <div class="row mb-2">
                            <div class="col-5 text-muted">Transaction ID</div>
                            <div class="col-7 fw-semibold"><?php echo htmlspecialchars($payment['transaction_id']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Patient</div>
                            <div class="col-7 fw-semibold"><?php echo htmlspecialchars($payment['patient_name']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Doctor</div>
                            <div class="col-7 fw-semibold">Dr. <?php echo htmlspecialchars($payment['doctor_name']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Specialization</div>
                            <div class="col-7"><?php echo htmlspecialchars($payment['specialization'] ?? '—'); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Hospital</div>
                            <div class="col-7"><?php echo htmlspecialchars($payment['hospital'] ?? '—'); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted">Amount Paid</div>
                            <div class="col-7 fw-bold text-success fs-5">৳ <?php echo number_format($payment['amount'], 2); ?></div>
                        </div>
                        <div class="row">
                            <div class="col-5 text-muted">Date & Time</div>
                            <div class="col-7"><?php echo date('d M Y, h:i A', strtotime($payment['created_at'])); ?></div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <button onclick="window.print()" class="btn btn-outline-primary">
                            <i class="bi bi-printer me-1"></i> Print Receipt
                        </button>
                        <a href="my_appointments.php" class="btn btn-primary">
                            My Appointments
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            Dashboard
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>