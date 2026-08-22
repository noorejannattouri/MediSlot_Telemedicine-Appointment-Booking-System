<?php
require_once 'auth_check.php';
$page_title = "Reports";

// Appointments by status
$appt_status = $conn->query("
    SELECT status, COUNT(*) AS cnt 
    FROM appointments 
    GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

// Payments by status
$pay_status = $conn->query("
    SELECT status, COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total
    FROM payments 
    GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

// Revenue last 7 days
$revenue_daily = $conn->query("
    SELECT DATE(paid_at) AS day, SUM(amount) AS total
    FROM payments
    WHERE status = 'completed' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(paid_at)
    ORDER BY day
")->fetch_all(MYSQLI_ASSOC);

// Top specializations
$top_specs = $conn->query("
    SELECT d.specialization, COUNT(a.appointment_id) AS bookings
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    GROUP BY d.specialization
    ORDER BY bookings DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Recent activity logs
$logs = $conn->query("
    SELECT l.*, u.name AS admin_name
    FROM activity_logs l
    JOIN users u ON l.admin_id = u.user_id
    ORDER BY l.created_at DESC
    LIMIT 15
");

require_once 'header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold">Appointments by Status</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($appt_status as $row): ?>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <?php echo ucfirst($row['status']); ?>
                            <span class="badge bg-primary"><?php echo $row['cnt']; ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($appt_status)): ?>
                        <li class="list-group-item text-muted">No appointments yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold">Payments Summary</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($pay_status as $row): ?>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><?php echo ucfirst($row['status']); ?> (<?php echo $row['cnt']; ?>)</span>
                            <strong>৳<?php echo number_format($row['total'], 2); ?></strong>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($pay_status)): ?>
                        <li class="list-group-item text-muted">No payments yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Top Specializations (by bookings)</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Specialization</th><th>Bookings</th></tr></thead>
                    <tbody>
                        <?php if (empty($top_specs)): ?>
                            <tr><td colspan="2" class="text-muted text-center">No data</td></tr>
                        <?php else: ?>
                            <?php foreach ($top_specs as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['specialization']); ?></td>
                                    <td><?php echo $s['bookings']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Revenue (Last 7 Days)</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Amount</th></tr></thead>
                    <tbody>
                        <?php if (empty($revenue_daily)): ?>
                            <tr><td colspan="2" class="text-muted text-center">No completed payments in last 7 days</td></tr>
                        <?php else: ?>
                            <?php foreach ($revenue_daily as $r): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($r['day'])); ?></td>
                                    <td>৳<?php echo number_format($r['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">Recent Admin Activity</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Target</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs->num_rows === 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No activity yet.</td></tr>
                    <?php else: ?>
                        <?php while ($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['admin_name']); ?></td>
                            <td><code><?php echo htmlspecialchars($log['action']); ?></code></td>
                            <td>
                                <?php echo htmlspecialchars($log['target_type'] ?? ''); ?>
                                #<?php echo $log['target_id'] ?? ''; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>