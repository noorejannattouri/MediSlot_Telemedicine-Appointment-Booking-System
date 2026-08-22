<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];
$errors  = [];
$success = "";

// Fetch current data
$stmt = $conn->prepare("
    SELECT u.name, u.email, u.phone,
           p.blood_group, p.date_of_birth, p.address
    FROM users u
    LEFT JOIN patients p ON u.user_id = p.patient_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name']);
    $phone         = trim($_POST['phone']);
    $blood_group   = trim($_POST['blood_group']);
    $date_of_birth = $_POST['date_of_birth'];
    $address       = trim($_POST['address']);
    $new_password  = $_POST['new_password'] ?? '';
    $confirm_pass  = $_POST['confirm_password'] ?? '';

    if (empty($name)) {
        $errors[] = "Name is required.";
    }

    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        } elseif ($new_password !== $confirm_pass) {
            $errors[] = "Passwords do not match.";
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Update users table
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $name, $phone, $patient_id);
            $stmt->execute();
            $stmt->close();

            // Update patients table
            $stmt = $conn->prepare("
                UPDATE patients 
                SET blood_group = ?, date_of_birth = ?, address = ?
                WHERE patient_id = ?
            ");
            $stmt->bind_param("sssi", $blood_group, $date_of_birth, $address, $patient_id);
            $stmt->execute();
            $stmt->close();

            // Update password if provided
            if (!empty($new_password)) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hash, $patient_id);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();

            $_SESSION['name'] = $name;
            $success = "Profile updated successfully.";

            // Refresh data
            $user['name'] = $name;
            $user['phone'] = $phone;
            $user['blood_group'] = $blood_group;
            $user['date_of_birth'] = $date_of_birth;
            $user['address'] = $address;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Update failed. Please try again.";
        }
    }
}

$page_title = "My Profile";
require_once '../header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">My Profile</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Full Name *</label>
                        <input type="text" name="name" class="form-control form-control-lg" 
                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Email (cannot change)</label>
                        <input type="email" class="form-control form-control-lg" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-lg" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Blood Group</label>
                        <select name="blood_group" class="form-select form-select-lg">
                            <option value="">Select</option>
                            <?php
                            $groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                            foreach ($groups as $g) {
                                $selected = ($user['blood_group'] ?? '') === $g ? 'selected' : '';
                                echo "<option value=\"$g\" $selected>$g</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control form-control-lg" 
                               value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Address</label>
                        <input type="text" name="address" class="form-control form-control-lg" 
                               value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="fw-semibold mb-3">Change Password (optional)</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control form-control-lg">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control form-control-lg">
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>