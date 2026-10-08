<?php

session_start();

require_once __DIR__ . '/config/db.php';

$message = "";
$message_type = "";
$token = $_GET["token"] ?? "";

if (empty($token)) {
    $message = "Invalid password reset link.";
    $message_type = "error";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["token"] ?? "";
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (empty($token)) {
        $message = "Invalid password reset link.";
        $message_type = "error";
    } elseif (empty($password) || empty($confirm_password)) {
        $message = "Please enter and confirm your new password.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT id, user_id FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $reset = $result->fetch_assoc();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $update->bind_param("si", $hashed_password, $reset["user_id"]);
            $update->execute();
            $update->close();

            $delete = $conn->prepare("DELETE FROM password_resets WHERE id = ?");
            $delete->bind_param("i", $reset["id"]);
            $delete->execute();
            $delete->close();

            $message = "Your password has been reset successfully. You can now login.";
            $message_type = "success";
            $token = "";
        } else {
            $message = "This password reset link is invalid or has expired.";
            $message_type = "error";
        }

        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/theme.css">
    <title>Reset Password - IT Support System</title>
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
            <p>Choose a new password for your IT Support System account.</p>
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
                <h2>Reset Password</h2>
                <p class="auth-subtext">Enter your new password below.</p>

                <?php if (!empty($message)): ?>
                    <div class="auth-<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($token)): ?>

                    <form method="POST" action="reset_pass.php" class="auth-form">

                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your new password" autocomplete="new-password" minlength="8" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" autocomplete="new-password" minlength="8" required>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="showPassword">
                            <label for="showPassword">Show password</label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Reset Password</button>

                    </form>

                <?php else: ?>

                    <a href="login.php" class="page-link auth-return-button">Return to Login</a>

                <?php endif; ?>

            </section>

        </main>

    </div>

</div>

<script src="assets/js/auth.js"></script>

</body>
</html>