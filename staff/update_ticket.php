<?php

require_once "../includes/auth.php";
require_once "../config/db.php";

requireStaff();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: tickets.php");
    exit;
}

if (!isset($_POST["ticket_id"]) || !is_numeric($_POST["ticket_id"])) {
    header("Location: tickets.php");
    exit;
}

$ticket_id = $_POST["ticket_id"];

$status = $_POST["status"] ?? "";
$staff_remarks = trim($_POST["staff_remarks"] ?? "");
$resolution_notes = trim($_POST["resolution_notes"] ?? "");

$allowed_statuses = ["pending", "in_progress", "resolved"];

if (!in_array($status, $allowed_statuses)) {
    header("Location: view_ticket.php?id=" . $ticket_id);
    exit;
}

$stmt = $conn->prepare("UPDATE tickets SET status = ?, staff_remarks = ?, resolution_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND staff_deleted = 0");
$stmt->bind_param("sssi", $status, $staff_remarks, $resolution_notes, $ticket_id);
$stmt->execute();
$stmt->close();

$history_remarks = "";

if (!empty($staff_remarks)) {
    $history_remarks = $staff_remarks;
}

if (!empty($resolution_notes)) {
    if (!empty($history_remarks)) {
        $history_remarks .= "\n\nResolution: " . $resolution_notes;
    } else {
        $history_remarks = "Resolution: " . $resolution_notes;
    }
}

$history_stmt = $conn->prepare("
    INSERT INTO ticket_history (ticket_id, status, remarks)
    VALUES (?, ?, ?)
");

$history_stmt->bind_param("iss", $ticket_id, $status, $history_remarks);
$history_stmt->execute();
$history_stmt->close();

header("Location: view_ticket.php?id=" . $ticket_id);
exit;

?>