<?php

require_once "../includes/auth.php";
require_once "../config/db.php";

requireStaff();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';

$sql = "SELECT tickets.id, users.full_name, categories.category_name, tickets.subject, tickets.status, tickets.priority, tickets.created_at 
        FROM tickets 
        JOIN users ON tickets.user_id = users.id 
        JOIN categories ON tickets.category_id = categories.id 
        WHERE tickets.staff_deleted = 0";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (users.full_name LIKE ? OR tickets.subject LIKE ?)";
    $search_value = "%" . $search . "%";
    $params[] = $search_value;
    $params[] = $search_value;
    $types .= "ss";
}

if ($status !== '') {
    $sql .= " AND tickets.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($priority !== '') {
    $sql .= " AND tickets.priority = ?";
    $params[] = $priority;
    $types .= "s";
}

$sql .= " ORDER BY tickets.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/staff.css">
    <title>All Tickets</title>
</head>
<body>

<header class="dashboard-header">
    <div class="dashboard-nav">
        <h1>IT Support Staff Dashboard</h1>
        <div class="dashboard-user">
            <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <button type="button" onclick="logout()">Logout</button>
        </div>
    </div>
</header>

<main class="dashboard-main">
    <div class="tickets-page">

        <section class="form-header">
            <p class="dashboard-label">STAFF PORTAL</p>
            <h2>All Support Tickets</h2>
            <p>Search, filter, and manage every ticket submitted by students.</p>
        </section>

        <div class="form-actions">
            <a href="dashboard.php" class="btn-link">&larr; Back to Dashboard</a>
        </div>

        <br>

        <div class="filter-card">
            <form method="GET" action="tickets.php" class="filter-form">
                <div class="filter-group filter-search">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Student name or problem">
                </div>

                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="priority">Priority</label>
                    <select name="priority" id="priority">
                        <option value="">All Priorities</option>
                        <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Search / Filter</button>
                    <a href="tickets.php" class="btn-link">Clear</a>
                </div>
            </form>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-wrapper">
                <table class="tickets-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Student Name</th>
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
                                <td><?php echo $ticket['id']; ?></td>
                                <td><?php echo htmlspecialchars($ticket['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower(str_replace('_', '-', $ticket['status'])); ?>"><?php echo htmlspecialchars($ticket['status']); ?></span></td>
                                <td><span class="priority-badge priority-<?php echo strtolower($ticket['priority']); ?>"><?php echo htmlspecialchars($ticket['priority']); ?></span></td>
                                <td><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                                <td class="action-cell">
                                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="table-link">View</a>
                                    <?php if ($ticket['status'] === 'closed'): ?>
                                        <form method="POST" action="delete_ticket.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this ticket?');">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
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
                <p>No support tickets found.</p>
            </div>
        <?php endif; ?>

    </div>
</main>

    <script src="../assets/js/staff.js"></script>
</body>
</html>
<?php
$stmt->close();
?>