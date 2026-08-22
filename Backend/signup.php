<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// If already logged in → go to index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$name = $email = $phone = $blood_group = $date_of_birth = $address = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name          = trim($_POST['name']);
    $email         = trim($_POST['email']);
    $phone         = trim($_POST['phone']);
    $password      = $_POST['password'];
    $confirm_pass  = $_POST['confirm_password'];
    $blood_group   = trim($_POST['blood_group']);
    $date_of_birth = $_POST['date_of_birth'];
    $address       = trim($_POST['address']);

    if (empty($name)) $errors[] = "Full name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm_pass) $errors[] = "Passwords do not match.";

    // Check email exists
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
        $role = 'patient';

        $conn->begin_transaction();
        try {
            // Insert into users
            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $password_hash, $phone, $role);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $stmt->close();

            // Insert into patients
            $stmt = $conn->prepare("INSERT INTO patients (patient_id, blood_group, date_of_birth, address) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $blood_group, $date_of_birth, $address);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $_SESSION['success_msg'] = "Account created successfully! Please login.";
            header("Location: login.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

$page_title = "Patient Sign Up";
require_once 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">
        <div class="card shadow border-0">
            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <h2 class="fw-bold">Create Patient Account</h2>
                    <p class="text-muted">Join MediSlot as a Patient</p>
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
                        <input type="text" name="name" class="form-control form-control-lg" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address *</label>
                        <input type="email" name="email" class="form-control form-control-lg" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control form-control-lg" value="<?php echo htmlspecialchars($phone); ?>">
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

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Blood Group</label>
                            <select name="blood_group" class="form-select form-select-lg">
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control form-control-lg">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" class="form-control form-control-lg" rows="2"></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Create Patient Account
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0">Already have an account? <a href="login.php" class="fw-semibold">Login here</a></p>
                    <p class="mt-2 mb-0">Are you a Doctor? <a href="signup_doctor.php" class="fw-semibold">Register as Doctor</a></p>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>