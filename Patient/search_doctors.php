<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$search    = trim($_GET['q'] ?? '');
$specialty = trim($_GET['specialty'] ?? '');

$sql = "
    SELECT d.doctor_id, u.name, d.specialization, d.hospital, 
           d.consultation_fee, d.rating, d.is_verified
    FROM doctors d
    JOIN users u ON d.doctor_id = u.user_id
    WHERE d.is_verified = 1
";

$params = [];
$types  = '';

// Improved search – works with or without "Dr.", spaces, and is case-insensitive
if ($search !== '') {
    $sql .= " AND (
        u.name LIKE ? 
        OR d.specialization LIKE ? 
        OR d.hospital LIKE ?
        OR REPLACE(REPLACE(u.name, 'Dr.', ''), ' ', '') LIKE ?
    )";

    $like = '%' . $search . '%';
    $like_clean = '%' . str_replace([' ', '.', 'Dr', 'dr'], '', $search) . '%';

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like_clean;
    $types   .= 'ssss';
}

if ($specialty !== '') {
    $sql .= " AND d.specialization = ?";
    $params[] = $specialty;
    $types   .= 's';
}

$sql .= " ORDER BY d.rating DESC, u.name ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unique specialties for filter
$spec_result = $conn->query("SELECT DISTINCT specialization FROM doctors WHERE is_verified = 1 ORDER BY specialization");
$specialties = $spec_result->fetch_all(MYSQLI_ASSOC);

$page_title = "Find Doctors";
require_once '../header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Find Doctors</h2>
            <p class="text-muted mb-0">Search verified doctors and book appointments</p>
        </div>
        <a href="../index.php" class="btn btn-outline-secondary">← Dashboard</a>
    </div>

    <!-- Search Form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="q" class="form-control" 
                           placeholder="Search by name, specialty or hospital..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="specialty" class="form-select">
                        <option value="">All Specialties</option>
                        <?php foreach ($specialties as $s): ?>
                            <option value="<?php echo htmlspecialchars($s['specialization']); ?>"
                                <?php echo $specialty === $s['specialization'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['specialization']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Doctors List -->
    <?php if (empty($doctors)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-emoji-frown fs-1 text-muted d-block mb-3"></i>
                <h5>No doctors found</h5>
                <p class="text-muted">Try changing your search filters.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($doctors as $doc): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start mb-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                     style="width:55px;height:55px;font-size:1.4rem;">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0">
                                        <?php 
                                        // Avoid double "Dr." if name already contains it
                                        $name = $doc['name'];
                                        if (stripos($name, 'Dr.') === 0) {
                                            echo htmlspecialchars($name);
                                        } else {
                                            echo 'Dr. ' . htmlspecialchars($name);
                                        }
                                        ?>
                                    </h5>
                                    <small class="text-muted"><?php echo htmlspecialchars($doc['specialization']); ?></small>
                                </div>
                            </div>

                            <?php if (!empty($doc['hospital'])): ?>
                                <p class="mb-1 small">
                                    <i class="bi bi-hospital me-1"></i>
                                    <?php echo htmlspecialchars($doc['hospital']); ?>
                                </p>
                            <?php endif; ?>

                            <p class="mb-1 small">
                                <i class="bi bi-star-fill text-warning me-1"></i>
                                <?php echo number_format($doc['rating'], 1); ?> / 5.0
                            </p>

                            <p class="mb-3 fw-semibold text-success">
                                <?php echo number_format($doc['consultation_fee'], 0); ?> BDT
                            </p>

                            <a href="doctor_profile.php?doctor_id=<?php echo $doc['doctor_id']; ?>" 
                               class="btn btn-primary btn-sm w-100">
                                View Profile & Book
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>