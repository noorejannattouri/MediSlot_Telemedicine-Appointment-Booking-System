<?php
require_once 'auth_check.php';
$page_title = "Manage Users";

$success = '';
$error   = '';

// Delete user (only patients for safety)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $uid = (int)$_POST['delete_user_id'];
    $admin_id = (int)$_SESSION['user_id'];

    // Only allow deleting patients
    $check = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $check->bind_param("i", $uid);
    $check->execute();
    $role = $check->get_result()->fetch_assoc()['role'] ?? '';
    $check->close();

    if ($role === 'patient') {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        if ($stmt->execute()) {
            $log = $conn->prepare("INSERT INTO activity_logs (admin_id, action, target_type, target_id) VALUES (?, 'deleted_user', 'user', ?)");
            $log->bind_param("ii", $admin_id, $uid);
            $log->execute();
            $log->close();
            $success = "User deleted successfully.";
        } else {
            $error = "Could not delete user.";
        }
        $stmt->close();
    } else {
        $error = "You can only delete patient accounts from this page.";
    }
}

// Fetch all users
$users = $conn->query("
    SELECT u.user_id, u.name, u.email, u.phone, u.role, u.created_at,
           d.is_verified, d.specialization
    FROM users u
    LEFT JOIN doctors d ON u.user_id = d.doctor_id
    ORDER BY u.created_at DESC
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

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold">All Users</span>
        <span class="badge bg-secondary"><?php echo $users->num_rows; ?> total</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Extra</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $u['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>
                        <td>
                            <?php
                            $badge = match($u['role']) {
                                'admin'   => 'bg-danger',
                                'doctor'  => 'bg-primary',
                                default   => 'bg-success'
                            };
                            ?>
                            <span class="badge <?php echo $badge; ?>"><?php echo ucfirst($u['role']); ?></span>
                        </td>
                        <td>
                            <?php if ($u['role'] === 'doctor'): ?>
                                <?php echo htmlspecialchars($u['specialization'] ?? ''); ?>
                                <?php if ($u['is_verified']): ?>
                                    <span class="badge bg-success">Verified</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <?php if ($u['role'] === 'patient'): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this patient permanently?');">
                                    <input type="hidden" name="delete_user_id" value="<?php echo $u['user_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>