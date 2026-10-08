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

$ticket_id = (int)$_POST["ticket_id"];
$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("
    SELECT id 
    FROM tickets 
    WHERE id = ? 
    AND user_id = ? 
    AND status = 'closed' 
    AND student_deleted = 0 
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
$stmt->close();

$stmt = $conn->prepare("
    UPDATE tickets 
    SET student_deleted = 1 
    WHERE id = ? 
    AND user_id = ? 
    AND status = 'closed' 
    AND student_deleted = 0
");

$stmt->bind_param("ii", $ticket_id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: my_tickets.php");
exit;

?>