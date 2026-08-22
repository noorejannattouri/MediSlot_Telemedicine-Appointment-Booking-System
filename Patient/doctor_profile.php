<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;

if ($doctor_id <= 0) {
    header("Location: search_doctors.php");
    exit();
}

// Get doctor info
$stmt = $conn->prepare("
    SELECT d.*, u.name, u.email, u.phone
    FROM doctors d
    JOIN users u ON d.doctor_id = u.user_id
    WHERE d.doctor_id = ? AND d.is_verified = 1
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doctor) {
    die("Doctor not found or not verified.");
}

// Get available slots (future only)
$stmt = $conn->prepare("
    SELECT slot_id, slot_date, start_time, end_time
    FROM time_slots
    WHERE doctor_id = ? 
      AND is_booked = 0 
      AND slot_date >= CURDATE()
    ORDER BY slot_date ASC, start_time ASC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$slots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "Dr. " . $doctor['name'];
require_once '../header.php';
?>

<div class="container py-4">
    <div class="mb-3">
        <a href="search_doctors.php" class="btn btn-outline-secondary btn-sm">← Back to Doctors</a>
    </div>

    <div class="row g-4">
        <!-- Doctor Info -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width:90px;height:90px;font-size:2.5rem;">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h4 class="fw-bold">Dr. <?php echo htmlspecialchars($doctor['name']); ?></h4>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                    
                    <?php if ($doctor['hospital']): ?>
                        <p class="small mb-2">
                            <i class="bi bi-hospital me-1"></i>
                            <?php echo htmlspecialchars($doctor['hospital']); ?>
                        </p>
                    <?php endif; ?>

                    <p class="mb-2">
                        <i class="bi bi-star-fill text-warning me-1"></i>
                        <?php echo number_format($doctor['rating'], 1); ?> / 5.0
                    </p>

                    <h5 class="text-success fw-bold">
                        <?php echo number_format($doctor['consultation_fee'], 0); ?> BDT
                    </h5>

                    <?php if ($doctor['bio']): ?>
                        <hr>
                        <p class="text-start small text-muted"><?php echo nl2br(htmlspecialchars($doctor['bio'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Available Slots -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Available Time Slots</h5>

                    <?php if (empty($slots)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x fs-1 text-muted d-block mb-3"></i>
                            <h6>No available slots right now</h6>
                            <p class="text-muted small">Please check back later.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php 
                            $current_date = '';
                            foreach ($slots as $slot): 
                                $date = $slot['slot_date'];
                                if ($date !== $current_date):
                                    $current_date = $date;
                            ?>
                                <div class="col-12">
                                    <h6 class="text-primary fw-semibold mt-2 mb-2">
                                        <?php echo date('l, d F Y', strtotime($date)); ?>
                                    </h6>
                                </div>
                            <?php endif; ?>

                                <div class="col-md-4 col-sm-6">
                                    <a href="book_appointment.php?slot_id=<?php echo $slot['slot_id']; ?>&doctor_id=<?php echo $doctor_id; ?>"
                                       class="btn btn-outline-primary w-100 py-2">
                                        <?php echo date('h:i A', strtotime($slot['start_time'])); ?> – 
                                        <?php echo date('h:i A', strtotime($slot['end_time'])); ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>