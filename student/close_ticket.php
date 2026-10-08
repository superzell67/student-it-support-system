<?php

require_once "../includes/auth.php";
require_once "../config/db.php";

requireStudent();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: my_tickets.php");
    exit;
}

if (!isset($_POST["ticket_id"]) || !is_numeric($_POST["ticket_id"])) {
    header("Location: my_tickets.php");
    exit;
}

$ticket_id = $_POST["ticket_id"];
$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("
    UPDATE tickets
    SET status = 'closed',
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ? AND user_id = ? AND status = 'resolved'
");

$stmt->bind_param("ii", $ticket_id, $user_id);

if (!$stmt->execute()) {
    die("Failed to close ticket: " . $stmt->error);
}

$stmt->close();

$history_remarks = "Ticket closed by student.";

$history_stmt = $conn->prepare("
    INSERT INTO ticket_history (ticket_id, status, remarks)
    VALUES (?, 'closed', ?)
");

if (!$history_stmt) {
    die("History prepare failed: " . $conn->error);
}

$history_stmt->bind_param("is", $ticket_id, $history_remarks);

if (!$history_stmt->execute()) {
    die("History insert failed: " . $history_stmt->error);
}

$history_stmt->close();

header("Location: view_ticket.php?id=" . $ticket_id);
exit;

?>