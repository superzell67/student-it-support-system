<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

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

$ticket_id = (int) $_POST["ticket_id"];

$stmt = $conn->prepare("SELECT id FROM tickets WHERE id = ? AND status = 'closed' AND staff_deleted = 0 LIMIT 1");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: tickets.php");
    exit;
}

$stmt->close();

$stmt = $conn->prepare("UPDATE tickets SET staff_deleted = 1 WHERE id = ? AND status = 'closed' AND staff_deleted = 0");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();

$stmt->close();

header("Location: tickets.php");
exit;

?>