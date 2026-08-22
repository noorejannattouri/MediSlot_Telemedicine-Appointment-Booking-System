<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin'; ?> | MediSlot Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: #1e293b;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }
        .sidebar .nav-link {
            color: #cbd5e1;
            border-radius: 8px;
            margin: 2px 12px;
            padding: 10px 16px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #0d6efd;
            color: white;
        }
        .main-content {
            margin-left: 260px;
            padding: 24px;
        }
        .stat-card {
            border: none;
            border-radius: 14px;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-4px); }
        .table thead th { background: #f1f5f9; font-weight: 600; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar text-white">
        <div class="p-4 border-bottom border-secondary">
            <h5 class="mb-0 fw-bold"><i class="bi bi-heart-pulse-fill me-2 text-primary"></i>MediSlot</h5>
            <small class="text-secondary">Admin Panel</small>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <a class="nav-link <?php echo $current_page === 'verify_doctors.php' ? 'active' : ''; ?>" href="verify_doctors.php">
                <i class="bi bi-person-check me-2"></i> Verify Doctors
            </a>
            <a class="nav-link <?php echo $current_page === 'manage_users.php' ? 'active' : ''; ?>" href="manage_users.php">
                <i class="bi bi-people me-2"></i> Manage Users
            </a>
            <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                <i class="bi bi-bar-chart me-2"></i> Reports
            </a>
            <hr class="border-secondary mx-3">
            <a class="nav-link text-danger" href="logout.php">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0"><?php echo $page_title ?? 'Admin'; ?></h4>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Logged in as</span>
                <span class="badge bg-primary"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span>
            </div>
        </div>