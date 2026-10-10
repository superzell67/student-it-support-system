
<?php

session_start();

require_once __DIR__ . '/config/db.php';

$message = "";
$message_type = "";
$token = $_GET["token"] ?? "";

function validResetToken(mysqli $conn, string $token): ?array
{
    if ($token === "" || !ctype_xdigit($token) || strlen($token) !== 64) {
        return null;
    }

    $tokenHash = hash("sha256", $token);

    $stmt = $conn->prepare(
        "SELECT id, user_id
         FROM password_resets
         WHERE token = ? AND expires_at > NOW()
         LIMIT 1"
    );
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();

    $result = $stmt->get_result();
    $reset = $result->fetch_assoc() ?: null;

    $stmt->close();

    return $reset;
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
        $reset = validResetToken($conn, $token);

        if ($reset === null) {
            $message = "This password reset link is invalid or has expired.";
            $message_type = "error";
            $token = "";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                $conn->begin_transaction();

                // Lock the reset record to prevent concurrent reuse.
                $lock = $conn->prepare(
                    "SELECT id, user_id
                     FROM password_resets
                     WHERE id = ? AND expires_at > NOW()
                     FOR UPDATE"
                );
                $lock->bind_param("i", $reset["id"]);
                $lock->execute();
                $lockedReset = $lock->get_result()->fetch_assoc();
                $lock->close();

                if (!$lockedReset) {
                    throw new RuntimeException("Reset token is no longer valid.");
                }

                $update = $conn->prepare(
                    "UPDATE users
                     SET password = ?, updated_at = NOW()
                     WHERE id = ?"
                );
                $update->bind_param(
                    "si",
                    $hashed_password,
                    $lockedReset["user_id"]
                );
                $update->execute();

                if ($update->affected_rows !== 1) {
                    $update->close();
                    throw new RuntimeException("Password update failed.");
                }
                $update->close();

                // Consume this token and invalidate any other reset links
                // for the same account.
                $delete = $conn->prepare(
                    "DELETE FROM password_resets WHERE user_id = ?"
                );
                $delete->bind_param("i", $lockedReset["user_id"]);
                $delete->execute();
                $delete->close();

                $conn->commit();

                $message = "Your password has been reset successfully. You can now login.";
                $message_type = "success";
                $token = "";
            } catch (Throwable $e) {
                $conn->rollback();
                error_log("Password reset failed: " . $e->getMessage());

                $message = "Unable to reset your password. Please request a new reset link.";
                $message_type = "error";
                $token = "";
            }
        }
    }
} elseif ($token !== "") {
    if (validResetToken($conn, $token) === null) {
        $message = "This password reset link is invalid or has expired.";
        $message_type = "error";
        $token = "";
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
                    <div class="auth-<?php echo htmlspecialchars($message_type); ?>">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($token)): ?>
                    <form method="POST" action="reset_pass.php" class="auth-form">
                        <input type="hidden" name="token"
                               value="<?php echo htmlspecialchars($token, ENT_QUOTES, "UTF-8"); ?>">

                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password"
                                   placeholder="Enter your new password"
                                   autocomplete="new-password" minlength="8" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   placeholder="Confirm your new password"
                                   autocomplete="new-password" minlength="8" required>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="showPassword">
                            <label for="showPassword">Show password</label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Reset Password
                        </button>
                    </form>
                <?php else: ?>
                    <a href="forgot_pass.php" class="page-link auth-return-button">
                        Request a New Reset Link
                    </a>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<script src="assets/js/auth.js"></script>
</body>
</html>
