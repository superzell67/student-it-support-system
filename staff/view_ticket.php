<?php

require_once "../includes/auth.php";
require_once "../config/db.php";

requireStaff();

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: tickets.php");
    exit;
}

$ticket_id = $_GET["id"];

$stmt = $conn->prepare("
    SELECT
        tickets.id,
        users.full_name,
        users.email,
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
    JOIN users ON tickets.user_id = users.id
    JOIN categories ON tickets.category_id = categories.id
    WHERE tickets.id = ?
    AND tickets.staff_deleted = 0
    LIMIT 1
");

$stmt->bind_param("i", $ticket_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: tickets.php");
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
    <link rel="stylesheet" href="../assets/css/staff.css">
    <title>View Ticket</title>
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
    <div class="form-page ticket-detail-page">

        <section class="form-header">
            <p class="dashboard-label">STAFF PORTAL</p>
            <h2>Ticket Details</h2>
            <p>Ticket #<?php echo $ticket["id"]; ?> &mdash; <?php echo htmlspecialchars($ticket["subject"]); ?></p>
        </section>

         <div class="form-actions">
            <a href="tickets.php" class="btn-link">&larr; Back to All Tickets</a>
            <a href="dashboard.php" class="btn-link">&larr; Back to Dashboard</a>
        </div>

        <br>

        <div class="detail-card">
            <div class="detail-section">
                <h3>Student Information</h3>
                <div class="detail-meta">
                    <div class="meta-item">
                        <span class="meta-label">Name</span>
                        <span class="meta-value"><?php echo htmlspecialchars($ticket["full_name"]); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Email</span>
                        <span class="meta-value"><?php echo htmlspecialchars($ticket["email"]); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="detail-card">
            <div class="detail-card-header">
                <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', $ticket["status"])); ?>">
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
                                        <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', $history["status"])); ?>">
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

        <div class="ticket-form update-ticket-form">
            <h3 class="update-form-title">Update Ticket</h3>

            <form method="POST" action="update_ticket.php">
                <input type="hidden" name="ticket_id" value="<?php echo $ticket["id"]; ?>">

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <option value="pending" <?php echo $ticket["status"] === "pending" ? "selected" : ""; ?>>Pending</option>
                        <option value="in_progress" <?php echo $ticket["status"] === "in_progress" ? "selected" : ""; ?>>In Progress</option>
                        <option value="resolved" <?php echo $ticket["status"] === "resolved" ? "selected" : ""; ?>>Resolved</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="staff_remarks">Staff Remarks</label>
                    <textarea name="staff_remarks" id="staff_remarks" rows="5" placeholder="Enter your remarks about this ticket..."></textarea>
                </div>

                <div class="form-group">
                    <label for="resolution_notes">Resolution Notes</label>
                    <textarea name="resolution_notes" id="resolution_notes" rows="5" placeholder="Explain how the problem was resolved..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Ticket</button>
                </div>
            </form>
        </div>

    </div>
</main>

    <script src="../assets/js/staff.js"></script>
</body>
</html>