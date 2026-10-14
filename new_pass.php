<?php

session_start();

require_once __DIR__ . '/config/db.php';

$message = "";
$message_type = "";

if (!isset($_SESSION["otp_verified_user_id"])) {
    header("Location: forgot_pass.php");
    exit;
}

$userId = (int) $_SESSION["otp_verified_user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $message_type = "error";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } else {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("SELECT id FROM password_resets WHERE user_id = ? AND verified = 1 AND expires_at > NOW() LIMIT 1 FOR UPDATE");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $reset = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$reset) {
                $conn->rollback();

                unset($_SESSION["otp_verified_user_id"]);

                header("Location: forgot_pass.php");
                exit;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $updateUser = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateUser->bind_param("si", $passwordHash, $userId);
            $updateUser->execute();
            $updateUser->close();

            $deleteReset = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $deleteReset->bind_param("i", $userId);
            $deleteReset->execute();
            $deleteReset->close();

            $conn->commit();

            unset($_SESSION["otp_verified_user_id"]);

            header("Location: login.php?password_reset=success");
            exit;
        } catch (Throwable $e) {
            $conn->rollback();

            error_log("Password reset failed: " . $e->getMessage());

            $message = "Unable to reset your password right now. Please try again.";
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/theme.css">
    <title>Set New Password - IT Support System</title>
</head>

<body class="auth-body">

    <div class="auth-shell">
        <div class="auth-panel">
            <div class="auth-panel-top">
                <span class="auth-panel-mark">IT</span>
                <span>Student IT Support System</span>
            </div>

            <div class="auth-panel-body">
                <h1>Create a new password.</h1>
                <p>Choose a new password for your account.</p>
            </div>

            <div class="auth-panel-bottom">&copy; 2026 Student IT Support System</div>
        </div>

        <div class="auth-formside">
            <div class="auth-topbar">
                <a href="login.php" class="page-link">Back to Login</a>
            </div>

            <main class="auth-main">
                <section class="auth-card">
                    <p class="dashboard-label">PASSWORD RESET</p>
                    <h2>Set New Password</h2>
                    <p class="auth-subtext">Enter your new password below.</p>

                    <?php if ($message !== ""): ?>
                        <div class="auth-<?php echo htmlspecialchars($message_type, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="new_pass.php" class="auth-form">
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                        </div>

                        <label for="showPassword" class="show-password-label">
                            <input type="checkbox" id="showPassword">
                            Show Password
                        </label>

                        <button type="submit" class="btn btn-primary btn-block">Update Password</button>
                    </form>
                </section>
            </main>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>

</html>