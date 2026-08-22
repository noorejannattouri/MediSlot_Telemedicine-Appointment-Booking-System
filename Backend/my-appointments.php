<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT a.appointment_id, a.status, a.created_at,
           u.name AS doctor_name, d.specialization, d.consultation_fee,
           ts.slot_date, ts.start_time, ts.end_time,
           p.status AS payment_status, p.transaction_id
    FROM appointments a
    JOIN users u ON a.doctor_id = u.user_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN time_slots ts ON a.slot_id = ts.slot_id
    LEFT JOIN payments p ON a.appointment_id = p.appointment_id
    WHERE a.patient_id = ?
    ORDER BY ts.slot_date DESC, ts.start_time DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "My Appointments";
require_once 'header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Appointments</h2>
            <p class="text-muted mb-0">View and manage your appointments</p>
        </div>
    </div>

    <?php if (isset($_GET['cancelled'])): ?>
        <div class="alert alert-warning">Payment was cancelled. You can try again anytime.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($appointments)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                    You have no appointments yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Fee</th>
                                <th>Appointment Status</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $a): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo date('d M Y', strtotime($a['slot_date'])); ?></div>
                                        <small class="text-muted">
                                            <?php echo date('h:i A', strtotime($a['start_time'])); ?> –
                                            <?php echo date('h:i A', strtotime($a['end_time'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        Dr. <?php echo htmlspecialchars($a['doctor_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($a['specialization']); ?></small>
                                    </td>
                                    <td>৳ <?php echo number_format($a['consultation_fee'], 2); ?></td>
                                    <td>
                                        <?php
                                        $badge = match($a['status']) {
                                            'confirmed' => 'success',
                                            'pending'   => 'warning',
                                            'completed' => 'primary',
                                            'cancelled' => 'danger',
                                            default     => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($a['status']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($a['payment_status'] === 'paid'): ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php elseif ($a['payment_status'] === 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($a['payment_status'] !== 'paid'): ?>
                                            <a href="payment.php?appointment_id=<?php echo $a['appointment_id']; ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="bi bi-credit-card me-1"></i> Pay Now
                                            </a>
                                        <?php else: ?>
                                            <a href="payment_success.php?tran_id=<?php echo urlencode($a['transaction_id']); ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                View Receipt
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>