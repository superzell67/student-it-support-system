<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin()
{
    if (!isset($_SESSION["user_id"])) {
        header("Location: ../login.php");
        exit;
    }
}

function requireStudent()
{
    requireLogin();

    if ($_SESSION["role"] !== "student") {
        header("Location: ../staff/dashboard.php");
        exit;
    }
}

function requireStaff()
{
    requireLogin();

    if ($_SESSION["role"] !== "staff") {
        header("Location: ../student/dashboard.php");
        exit;
    }
}

?>