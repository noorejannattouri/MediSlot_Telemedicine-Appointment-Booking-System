<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$name = $email = $phone = $specialization = $hospital = $bio = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name            = trim($_POST['name']);
    $email           = trim($_POST['email']);
    $phone           = trim($_POST['phone']);
    $password        = $_POST['password'];
    $confirm_pass    = $_POST['confirm_password'];
    $specialization  = trim($_POST['specialization']);
    $hospital        = trim($_POST['hospital']);
    $consultation_fee = $_POST['consultation_fee'];
    $bio             = trim($_POST['bio']);

    if (empty($name)) $errors[] = "Full name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm_pass) $errors[] = "Passwords do not match.";
    if (empty($specialization)) $errors[] = "Specialization is required.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "This email is already registered.";
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'doctor';

        $conn->begin_transaction();
        try {
            // Insert into users
            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $password_hash, $phone, $role);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $stmt->close();

            // Insert into doctors
            $stmt = $conn->prepare("INSERT INTO doctors (doctor_id, specialization, hospital, consultation_fee, bio) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issds", $user_id, $specialization, $hospital, $consultation_fee, $bio);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $_SESSION['success_msg'] = "Doctor account created successfully! Please login.";
            header("Location: login.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

$page_title = "Doctor Sign Up";
require_once 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">
        <div class="card shadow border-0">
            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <h2 class="fw-bold">Create Doctor Account</h2>
                    <p class="text-muted">Join MediSlot as a Doctor</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name *</label>
                        <input type="text" name="name" class="form-control form-control-lg" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address *</label>
                        <input type="email" name="email" class="form-control form-control-lg" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control form-control-lg">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Password *</label>
                            <input type="password" name="password" class="form-control form-control-lg" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Confirm Password *</label>
                            <input type="password" name="confirm_password" class="form-control form-control-lg" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Specialization *</label>
                        <input type="text" name="specialization" class="form-control form-control-lg" placeholder="e.g. Cardiology, Dermatology" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hospital / Clinic</label>
                        <input type="text" name="hospital" class="form-control form-control-lg">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Consultation Fee (BDT)</label>
                        <input type="number" name="consultation_fee" class="form-control form-control-lg" value="500" min="0" step="50">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Bio / About</label>
                        <textarea name="bio" class="form-control form-control-lg" rows="3"></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Create Doctor Account
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0">Already have an account? <a href="login.php" class="fw-semibold">Login here</a></p>
                    <p class="mt-2 mb-0">Are you a Patient? <a href="signup.php" class="fw-semibold">Register as Patient</a></p>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>