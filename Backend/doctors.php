<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Optional: only logged-in patients can see this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

// Search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$specialization = isset($_GET['specialization']) ? trim($_GET['specialization']) : '';

// Build query
$sql = "
    SELECT d.doctor_id, d.specialization, d.hospital, d.consultation_fee, d.rating, d.bio, d.is_verified,
           u.name, u.phone
    FROM doctors d
    JOIN users u ON d.doctor_id = u.user_id
    WHERE d.is_verified = 1
";

$params = [];
$types  = "";

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR d.specialization LIKE ? OR d.hospital LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if (!empty($specialization)) {
    $sql .= " AND d.specialization = ?";
    $params[] = $specialization;
    $types .= "s";
}

$sql .= " ORDER BY d.rating DESC, u.name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unique specializations for filter
$spec_result = $conn->query("SELECT DISTINCT specialization FROM doctors WHERE is_verified = 1 ORDER BY specialization");
$specializations = $spec_result->fetch_all(MYSQLI_ASSOC);

$page_title = "Find Doctors";
require_once 'header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Find Doctors</h2>
            <p class="text-muted mb-0">Search verified doctors and book appointments</p>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-lg" 
                           placeholder="Search by name, specialization or hospital..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="specialization" class="form-select form-select-lg">
                        <option value="">All Specializations</option>
                        <?php foreach ($specializations as $spec): ?>
                            <option value="<?php echo htmlspecialchars($spec['specialization']); ?>"
                                <?php echo $specialization === $spec['specialization'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($spec['specialization']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Doctors List -->
    <?php if (empty($doctors)): ?>
        <div class="alert alert-info text-center">
            No verified doctors found. Please try a different search.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($doctors as $doc): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 50px; height: 50px; font-size: 1.3rem;">
                                    <?php echo strtoupper(substr($doc['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($doc['name']); ?></h5>
                                    <small class="text-muted"><?php echo htmlspecialchars($doc['specialization']); ?></small>
                                </div>
                            </div>

                            <p class="mb-1">
                                <i class="bi bi-hospital me-1 text-primary"></i>
                                <?php echo htmlspecialchars($doc['hospital'] ?: 'Not specified'); ?>
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-star-fill text-warning me-1"></i>
                                Rating: <?php echo number_format($doc['rating'], 1); ?>
                            </p>
                            <p class="mb-3">
                                <i class="bi bi-cash me-1 text-success"></i>
                                Fee: <strong><?php echo number_format($doc['consultation_fee'], 0); ?> BDT</strong>
                            </p>

                            <?php if (!empty($doc['bio'])): ?>
                                <p class="text-muted small mb-3">
                                    <?php echo htmlspecialchars(substr($doc['bio'], 0, 100)); ?>
                                    <?php echo strlen($doc['bio']) > 100 ? '...' : ''; ?>
                                </p>
                            <?php endif; ?>

                            <a href="book_appointment.php?doctor_id=<?php echo $doc['doctor_id']; ?>" 
                               class="btn btn-primary w-100">
                                <i class="bi bi-calendar-check me-1"></i> Book Appointment
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>