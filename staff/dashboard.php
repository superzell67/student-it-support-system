<?php

require_once "../includes/auth.php";
requireStaff();
require_once "../config/db.php";

$total_tickets = 0;
$pending_tickets = 0;
$in_progress_tickets = 0;
$resolved_tickets = 0;
$closed_tickets = 0;

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'in_progress') AS in_progress,
        SUM(status = 'resolved') AS resolved,
        SUM(status = 'closed') AS closed
    FROM tickets
    WHERE staff_deleted = 0
");

$stmt->execute();
$result = $stmt->get_result();
$counts = $result->fetch_assoc();

$total_tickets = $counts["total"] ?? 0;
$pending_tickets = $counts["pending"] ?? 0;
$in_progress_tickets = $counts["in_progress"] ?? 0;
$resolved_tickets = $counts["resolved"] ?? 0;
$closed_tickets = $counts["closed"] ?? 0;

$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/staff.css">
    <title>Staff Dashboard</title>
    <script src="../assets/js/script.js"></script>
</head>
<body>

<header class="dashboard-header">
    <div class="dashboard-nav">
        <h1>IT Support Staff Dashboard</h1>
        <div class="dashboard-user">
            <span><?php echo htmlspecialchars($_SESSION["full_name"]); ?></span>
            <button type="button" onclick="logout()">Logout</button>
        </div>
    </div>
</header>

<main class="dashboard-main">
    <div class="dashboard-layout">

        <aside class="dashboard-sidebar">
            <a href="tickets.php" class="sidebar-btn">
                <span class="sidebar-icon">&#9776;</span>
                <span>All Tickets</span>
            </a>
            <a href="profile.php" class="sidebar-btn">
                <span class="sidebar-icon">&#128100;</span>
                <span>My Profile</span>
            </a>
        </aside>

        <div class="dashboard-content">
            <section class="welcome-section">
                <p class="dashboard-label">STAFF PORTAL</p>
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!</h2>
                <p>You are logged in as IT support staff. Manage and monitor student IT support tickets below.</p>
            </section>

            <section class="stats-section">
                <h3 class="section-title">Ticket Summary</h3>
                <div class="stats-grid">
                    <div class="stat-card stat-total">
                        <span class="stat-label">Total Tickets</span>
                        <span class="stat-value"><?php echo $total_tickets; ?></span>
                    </div>
                    <div class="stat-card stat-pending">
                        <span class="stat-label">Pending</span>
                        <span class="stat-value"><?php echo $pending_tickets; ?></span>
                    </div>
                    <div class="stat-card stat-progress">
                        <span class="stat-label">In Progress</span>
                        <span class="stat-value"><?php echo $in_progress_tickets; ?></span>
                    </div>
                    <div class="stat-card stat-resolved">
                        <span class="stat-label">Resolved</span>
                        <span class="stat-value"><?php echo $resolved_tickets; ?></span>
                    </div>
                    <div class="stat-card stat-closed">
                        <span class="stat-label">Closed</span>
                        <span class="stat-value"><?php echo $closed_tickets; ?></span>
                    </div>
                </div>
            </section>

            <section class="help-section">
                <div>
                    <h3>Manage Tickets</h3>
                    <p>Review incoming requests, update statuses, and add remarks or resolution notes for students.</p>
                </div>
                <a href="tickets.php">View All Tickets</a>
            </section>
        </div>

    </div>
</main>

    <script src="../assets/js/staff.js"></script>
</body>
</html>