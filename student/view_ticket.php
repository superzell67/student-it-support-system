<?php

require_once "../includes/auth.php";
require_once "../config/db.php";

requireStudent();

$user_id = $_SESSION["user_id"];

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: my_tickets.php");
    exit;
}

$ticket_id = $_GET["id"];

$stmt = $conn->prepare("
    SELECT
        tickets.id,
        categories.category_name,
        tickets.subject,
        tickets.description,
        tickets.status,
        tickets.priority,
        tickets.staff_remarks,
        tickets.resolution_notes,
        tickets.created_at,
        tickets.updated_at
    FROM tickets
    JOIN categories ON tickets.category_id = categories.id
    WHERE tickets.id = ? 
    AND tickets.user_id = ?
    AND tickets.student_deleted = 0
    LIMIT 1
");

$stmt->bind_param("ii", $ticket_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: my_tickets.php");
    exit;
}

$ticket = $result->fetch_assoc();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/student.css">
    <title>View Ticket</title>
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
    <div class="form-page ticket-detail-page">

        <section class="form-header">
            <p class="dashboard-label">STUDENT PORTAL</p>
            <h2>Ticket Details</h2>
            <p>Ticket #<?php echo $ticket["id"]; ?> &mdash; <?php echo htmlspecialchars($ticket["subject"]); ?></p>
        </section>

        <div class="form-actions">
            <a href="my_tickets.php" class="btn-link">&larr; Back to My Tickets</a>
            <a href="dashboard.php" class="btn-link">&larr; Back to Dashboard</a>
        </div>

        <br>

        <div class="detail-card">
            <div class="detail-card-header">
                <span class="status-badge status-<?php echo strtolower(preg_replace('/\s+/', '-', $ticket["status"])); ?>">
                    <?php echo htmlspecialchars($ticket["status"]); ?>
                </span>
                <span class="priority-badge priority-<?php echo strtolower($ticket["priority"]); ?>">
                    <?php echo htmlspecialchars($ticket["priority"]); ?> priority
                </span>
            </div>

            <div class="detail-meta">
                <div class="meta-item">
                    <span class="meta-label">Ticket ID</span>
                    <span class="meta-value">#<?php echo $ticket["id"]; ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Category</span>
                    <span class="meta-value"><?php echo htmlspecialchars($ticket["category_name"]); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Problem</span>
                    <span class="meta-value"><?php echo htmlspecialchars($ticket["subject"]); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Date Created</span>
                    <span class="meta-value"><?php echo htmlspecialchars($ticket["created_at"]); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Last Updated</span>
                    <span class="meta-value"><?php echo $ticket["updated_at"] ? htmlspecialchars($ticket["updated_at"]) : "Not updated yet"; ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($ticket["description"])); ?></p>
            </div>
        </div>

        <div class="detail-card">
            <div class="detail-section">
                <h3>Staff Remarks</h3>
                <p class="<?php echo empty($ticket["staff_remarks"]) ? 'text-muted-note' : ''; ?>">
                    <?php echo !empty($ticket["staff_remarks"]) ? nl2br(htmlspecialchars($ticket["staff_remarks"])) : "No staff remarks yet."; ?>
                </p>
            </div>

            <div class="detail-section">
                <h3>Resolution Notes</h3>
                <p class="<?php echo empty($ticket["resolution_notes"]) ? 'text-muted-note' : ''; ?>">
                    <?php echo !empty($ticket["resolution_notes"]) ? nl2br(htmlspecialchars($ticket["resolution_notes"])) : "No resolution notes yet."; ?>
                </p>
            </div>
        </div>

        <div class="detail-card">
            <div class="detail-section">
                <h3>Ticket History</h3>

                <?php

                $history_stmt = $conn->prepare("
                    SELECT status, remarks, changed_at
                    FROM ticket_history
                    WHERE ticket_id = ?
                    ORDER BY changed_at ASC
                ");

                $history_stmt->bind_param("i", $ticket_id);
                $history_stmt->execute();
                $history_result = $history_stmt->get_result();

                ?>

                <?php if ($history_result->num_rows > 0): ?>
                    <ul class="history-timeline">
                        <?php while ($history = $history_result->fetch_assoc()): ?>
                            <li class="history-item">
                                <div class="history-dot"></div>
                                <div class="history-content">
                                    <div class="history-top">
                                        <span class="status-badge status-<?php echo strtolower(preg_replace('/\s+/', '-', $history["status"])); ?>">
                                            <?php echo htmlspecialchars($history["status"]); ?>
                                        </span>
                                        <span class="history-date"><?php echo htmlspecialchars($history["changed_at"]); ?></span>
                                    </div>
                                    <p class="history-remarks">
                                        <?php echo !empty($history["remarks"]) ? nl2br(htmlspecialchars($history["remarks"])) : "No remarks."; ?>
                                    </p>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted-note">No ticket history yet.</p>
                <?php endif; ?>

                <?php $history_stmt->close(); ?>
            </div>
        </div>

        <?php if ($ticket["status"] === "resolved"): ?>
            <div class="detail-card resolved-card">
                <h3>Ticket Resolved</h3>
                <p>The IT staff has marked this ticket as resolved.</p>

                <form method="POST" action="close_ticket.php" class="inline-form">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket["id"]; ?>">
                    <button type="submit" class="btn btn-primary">Confirm and Close Ticket</button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</main>

    <script src="../assets/js/student.js"></script>
</body>
</html>