<?php

require_once "../includes/auth.php";
require_once "../config/db.php";

requireStudent();

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("
    SELECT
        tickets.id,
        categories.category_name,
        tickets.subject,
        tickets.status,
        tickets.priority,
        tickets.created_at
    FROM tickets
    JOIN categories ON tickets.category_id = categories.id
    WHERE tickets.user_id = ?
    AND tickets.student_deleted = 0
    ORDER BY tickets.created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/student.css">
    <title>My Tickets</title>
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
    <div class="form-page tickets-page">

        <section class="form-header">
            <p class="dashboard-label">STUDENT PORTAL</p>
            <h2>My Tickets</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>! Here's a list of your submitted tickets.</p>
        </section>

         <div class="form-actions">
            <a href="dashboard.php" class="btn-link">&larr; Back to Dashboard</a>
            <a href="create_ticket.php" class="btn btn-primary">Create New Ticket</a>
        </div>

        <br>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-wrapper">
                <table class="tickets-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Category</th>
                            <th>Problem</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Date Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ticket = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $ticket["id"]; ?></td>
                                <td><?php echo htmlspecialchars($ticket["category_name"]); ?></td>
                                <td><?php echo htmlspecialchars($ticket["subject"]); ?></td>
                                <td><?php echo htmlspecialchars($ticket["status"]); ?></td>
                                <td><?php echo htmlspecialchars($ticket["priority"]); ?></td>
                                <td><?php echo htmlspecialchars($ticket["created_at"]); ?></td>
                                <td class="action-cell">
                                    <a href="view_ticket.php?id=<?php echo $ticket["id"]; ?>" class="table-link">View</a>

                                    <?php if ($ticket["status"] === "closed"): ?>
                                        <form method="POST" action="delete_ticket.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this ticket?');">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket["id"]; ?>">
                                            <button type="submit" name="delete_ticket" class="table-delete-btn">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>You don't have any tickets yet.</p>
            </div>
        <?php endif; ?>

    
    </div>
</main>

<script src="../assets/js/student.js"></script>
</body>
</html>
<?php

$stmt->close();

?>