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

// Handle create new slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_slot'])) {
    $slot_date  = $_POST['slot_date'];
    $start_time = $_POST['start_time'];
    $end_time   = $_POST['end_time'];

    if (empty($slot_date) || empty($start_time) || empty($end_time)) {
        $errors[] = "All fields are required.";
    } elseif ($start_time >= $end_time) {
        $errors[] = "End time must be after start time.";
    } else {
        // Check for overlapping slot
        $stmt = $conn->prepare("
            SELECT slot_id FROM time_slots 
            WHERE doctor_id = ? AND slot_date = ? 
              AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))
        ");
        $stmt->bind_param("isssssss", $doctor_id, $slot_date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "This time slot overlaps with an existing one.";
        }
        $stmt->close();

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO time_slots (doctor_id, slot_date, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $doctor_id, $slot_date, $start_time, $end_time);
            if ($stmt->execute()) {
                $success = "Time slot created successfully.";
            } else {
                $errors[] = "Failed to create slot.";
            }
            $stmt->close();
        }
    }
}

// Handle delete slot
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $slot_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM time_slots WHERE slot_id = ? AND doctor_id = ? AND is_booked = 0");
    $stmt->bind_param("ii", $slot_id, $doctor_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $success = "Slot deleted successfully.";
    } else {
        $errors[] = "Cannot delete this slot (it may be booked or not found).";
    }
    $stmt->close();
}

// Fetch upcoming slots
$stmt = $conn->prepare("
    SELECT slot_id, slot_date, start_time, end_time, is_booked
    FROM time_slots
    WHERE doctor_id = ? AND slot_date >= CURDATE()
    ORDER BY slot_date ASC, start_time ASC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$slots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "Manage Schedule";
require_once '../header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Manage Schedule</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
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

    <!-- Create New Slot -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Add New Time Slot</div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="slot_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Time</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" name="create_slot" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i> Add Slot
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Slots -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Upcoming Slots</div>
        <div class="card-body p-0">
            <?php if (empty($slots)): ?>
                <div class="p-4 text-center text-muted">No upcoming slots yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slots as $slot): ?>
                                <tr>
                                    <td><?php echo date('d M Y (D)', strtotime($slot['slot_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($slot['start_time'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($slot['end_time'])); ?></td>
                                    <td>
                                        <?php if ($slot['is_booked']): ?>
                                            <span class="badge bg-danger">Booked</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$slot['is_booked']): ?>
                                            <a href="?delete=<?php echo $slot['slot_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Delete this slot?');">
                                                Delete
                                            </a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>