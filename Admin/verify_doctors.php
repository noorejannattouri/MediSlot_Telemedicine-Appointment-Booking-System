<?php
require_once 'auth_check.php';
$page_title = "Verify Doctors";

$success = '';
$error   = '';

// Handle Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['doctor_id'])) {
    $doctor_id = (int)$_POST['doctor_id'];
    $action    = $_POST['action'];
    $admin_id  = (int)$_SESSION['user_id'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE doctors SET is_verified = 1, verified_by = ?, verified_at = NOW() WHERE doctor_id = ?");
        $stmt->bind_param("ii", $admin_id, $doctor_id);
        if ($stmt->execute()) {
            // Log activity
            $log = $conn->prepare("INSERT INTO activity_logs (admin_id, action, target_type, target_id) VALUES (?, 'verified_doctor', 'doctor', ?)");
            $log->bind_param("ii", $admin_id, $doctor_id);
            $log->execute();
            $log->close();
            $success = "Doctor approved successfully.";
        } else {
            $error = "Failed to approve doctor.";
        }
        $stmt->close();
    } elseif ($action === 'reject') {
        // Optional: you can delete or just leave unverified. Here we just log and keep unverified.
        $log = $conn->prepare("INSERT INTO activity_logs (admin_id, action, target_type, target_id) VALUES (?, 'rejected_doctor', 'doctor', ?)");
        $log->bind_param("ii", $admin_id, $doctor_id);
        $log->execute();
        $log->close();
        $success = "Doctor rejected (remains unverified).";
    }
}

// Fetch pending doctors
$pending = $conn->query("
    SELECT d.doctor_id, u.name, u.email, u.phone, d.specialization, d.hospital, d.consultation_fee, d.bio, u.created_at
    FROM doctors d
    JOIN users u ON d.doctor_id = u.user_id
    WHERE d.is_verified = 0
    ORDER BY u.created_at DESC
");

// Fetch recently verified
$verified = $conn->query("
    SELECT d.doctor_id, u.name, u.email, d.specialization, d.hospital, d.verified_at, a.name AS verified_by_name
    FROM doctors d
    JOIN users u ON d.doctor_id = u.user_id
    LEFT JOIN users a ON d.verified_by = a.user_id
    WHERE d.is_verified = 1
    ORDER BY d.verified_at DESC
    LIMIT 10
");

require_once 'header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">
        Pending Verification (<?php echo $pending->num_rows; ?>)
    </div>
    <div class="card-body p-0">
        <?php if ($pending->num_rows === 0): ?>
            <p class="text-muted text-center py-4 mb-0">No pending doctors.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email / Phone</th>
                            <th>Specialization</th>
                            <th>Hospital</th>
                            <th>Fee</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($doc = $pending->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($doc['name']); ?></strong>
                                <?php if ($doc['bio']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($doc['bio'], 0, 60)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($doc['email']); ?><br>
                                <small><?php echo htmlspecialchars($doc['phone'] ?? '—'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($doc['specialization']); ?></td>
                            <td><?php echo htmlspecialchars($doc['hospital'] ?? '—'); ?></td>
                            <td>৳<?php echo number_format($doc['consultation_fee'], 2); ?></td>
                            <td><?php echo date('d M Y', strtotime($doc['created_at'])); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="doctor_id" value="<?php echo $doc['doctor_id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success me-1"
                                            onclick="return confirm('Approve this doctor?')">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Reject this doctor?')">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">Recently Verified Doctors</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Specialization</th>
                        <th>Hospital</th>
                        <th>Verified At</th>
                        <th>Verified By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($verified->num_rows === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No verified doctors yet.</td></tr>
                    <?php else: ?>
                        <?php while ($doc = $verified->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($doc['name']); ?></td>
                            <td><?php echo htmlspecialchars($doc['email']); ?></td>
                            <td><?php echo htmlspecialchars($doc['specialization']); ?></td>
                            <td><?php echo htmlspecialchars($doc['hospital'] ?? '—'); ?></td>
                            <td><?php echo $doc['verified_at'] ? date('d M Y H:i', strtotime($doc['verified_at'])) : '—'; ?></td>
                            <td><?php echo htmlspecialchars($doc['verified_by_name'] ?? '—'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>