<?php
require_once "../includes/auth.php";

requireStudent();
?>
<?php
require_once "../includes/auth.php";

requireStudent();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/student.css">
    <title>Student Dashboard</title>
</head>
<body>

<header class="dashboard-header">
    <div class="dashboard-nav">
        <h1>Student IT Support System</h1>
        <div class="dashboard-user">
            <span><?php echo htmlspecialchars($_SESSION["full_name"]); ?></span>
            <button type="button" onclick="logout()">Logout</button>
        </div>
    </div>
</header>

<main class="dashboard-main">
    <div class="dashboard-layout">

        <aside class="dashboard-sidebar">
            <a href="create_ticket.php" class="sidebar-btn">
                <span class="sidebar-icon">+</span>
                <span>Create Ticket</span>
            </a>
            <a href="my_tickets.php" class="sidebar-btn">
                <span class="sidebar-icon">&#10003;</span>
                <span>My Tickets</span>
            </a>
            <a href="profile.php" class="sidebar-btn">
                <span class="sidebar-icon">&#9776;</span>
                <span>My Profile</span>
            </a>
        </aside>

        <div class="dashboard-content">
            <section class="welcome-section">
                <p class="dashboard-label">STUDENT PORTAL</p>
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!</h2>
                <p>Need help with a technical problem? Submit a support ticket and track its progress here.</p>
            </section>

            <section class="help-section">
                <div>
                    <h3>Need Technical Assistance?</h3>
                    <p>Submit a ticket if you are experiencing problems with computers, Wi-Fi, software, accounts, or other school technology.</p>
                </div>
                <a href="create_ticket.php">Report a Problem</a>
            </section>
        </div>

    </div>
</main>

<script src="../assets/js/student.js"></script>
</body>
</html>