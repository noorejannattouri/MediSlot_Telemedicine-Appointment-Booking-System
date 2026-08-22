<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$errors = [];
$success = "";

// Fetch current data
$stmt = $conn->prepare("
    SELECT u.name, u.email, u.phone, 
           d.specialization, d.hospital, d.consultation_fee, d.bio
    FROM users u
    JOIN doctors d ON u.user_id = d.doctor_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = trim($_POST['name']);
    $phone            = trim($_POST['phone']);
    $specialization   = trim($_POST['specialization']);
    $hospital         = trim($_POST['hospital']);
    $consultation_fee = (float)$_POST['consultation_fee'];
    $bio              = trim($_POST['bio']);
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name)) $errors[] = "Name is required.";
    if (empty($specialization)) $errors[] = "Specialization is required.";
    if ($consultation_fee < 0) $errors[] = "Consultation fee cannot be negative.";

    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Update users table
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $name, $phone, $doctor_id);
            $stmt->execute();
            $stmt->close();

            // Update doctors table
            $stmt = $conn->prepare("
                UPDATE doctors 
                SET specialization = ?, hospital = ?, consultation_fee = ?, bio = ?
                WHERE doctor_id = ?
            ");
            $stmt->bind_param("ssdsi", $specialization, $hospital, $consultation_fee, $bio, $doctor_id);
            $stmt->execute();
            $stmt->close();

            // Update password if provided
            if (!empty($new_password)) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hash, $doctor_id);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();

            // Refresh session name
            $_SESSION['name'] = $name;
            $success = "Profile updated successfully.";

            // Refresh data
            $doctor['name'] = $name;
            $doctor['phone'] = $phone;
            $doctor['specialization'] = $specialization;
            $doctor['hospital'] = $hospital;
            $doctor['consultation_fee'] = $consultation_fee;
            $doctor['bio'] = $bio;

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
                               value="<?php echo htmlspecialchars($doctor['name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Email (cannot change)</label>
                        <input type="email" class="form-control form-control-lg" 
                               value="<?php echo htmlspecialchars($doctor['email']); ?>" disabled>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-lg" 
                               value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Specialization *</label>
                        <input type="text" name="specialization" class="form-control form-control-lg" 
                               value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Hospital / Clinic</label>
                        <input type="text" name="hospital" class="form-control form-control-lg" 
                               value="<?php echo htmlspecialchars($doctor['hospital'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Consultation Fee (BDT)</label>
                        <input type="number" name="consultation_fee" class="form-control form-control-lg" 
                               value="<?php echo htmlspecialchars($doctor['consultation_fee']); ?>" min="0" step="50">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Bio / About</label>
                    <textarea name="bio" class="form-control form-control-lg" rows="3"><?php 
                        echo htmlspecialchars($doctor['bio'] ?? ''); 
                    ?></textarea>
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