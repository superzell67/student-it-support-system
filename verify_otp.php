<?php

session_start();

require_once __DIR__ . '/config/db.php';

$message = "";
$message_type = "";

if (!isset($_SESSION["otp_user_id"])) {
    header("Location: forgot_pass.php");
    exit;
}

$userId = (int) $_SESSION["otp_user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp = trim($_POST["otp"] ?? "");

    if (!preg_match('/^\d{6}$/', $otp)) {
        $message = "Please enter the 6-digit verification code.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT id, token, expires_at, attempts, verified FROM password_resets WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $reset = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$reset) {
            $message = "No active verification code found. Request a new one.";
            $message_type = "error";
        } elseif ((int) $reset["verified"] === 1) {
            $message = "This code has already been verified.";
            $message_type = "error";
        } elseif (strtotime($reset["expires_at"]) <= time()) {
            $message = "This code has expired. Please request a new one.";
            $message_type = "error";
        } elseif ((int) $reset["attempts"] >= 5) {
            $message = "Too many incorrect attempts. Please request a new code.";
            $message_type = "error";
        } elseif (hash_equals($reset["token"], hash("sha256", $otp))) {
            $update = $conn->prepare("UPDATE password_resets SET verified = 1 WHERE id = ? AND attempts < 5 AND verified = 0");
            $update->bind_param("i", $reset["id"]);
            $update->execute();
            $updated = $update->affected_rows;
            $update->close();

            if ($updated === 1) {
                $_SESSION["otp_verified_user_id"] = $userId;
                unset($_SESSION["otp_user_id"]);

                header("Location: new_pass.php");
                exit;
            }

            $message = "Unable to verify the code. Please try again.";
            $message_type = "error";
        } else {
            $update = $conn->prepare("UPDATE password_resets SET attempts = attempts + 1 WHERE id = ? AND attempts < 5 AND verified = 0");
            $update->bind_param("i", $reset["id"]);
            $update->execute();
            $update->close();

            $message = "Incorrect verification code.";
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
    <title>Verify Code - IT Support System</title>
</head>

<body class="auth-body">

    <div class="auth-shell">
        <div class="auth-panel">
            <div class="auth-panel-top">
                <span class="auth-panel-mark">IT</span>
                <span>Student IT Support System</span>
            </div>

            <div class="auth-panel-body">
                <h1>One more step.</h1>
                <p>Verify your email code to continue resetting your password.</p>
            </div>

            <div class="auth-panel-bottom">&copy; 2026 Student IT Support System</div>
        </div>

        <div class="auth-formside">
            <div class="auth-topbar">
                <a href="forgot_pass.php" class="page-link">Request New Code</a>
            </div>

            <main class="auth-main">
                <section class="auth-card">
                    <p class="dashboard-label">EMAIL VERIFICATION</p>
                    <h2>Enter your code</h2>
                    <p class="auth-subtext">Enter the 6-digit code sent to your email. The code expires in 5 minutes.</p>

                    <?php if ($message !== ""): ?>
                        <div class="auth-<?php echo htmlspecialchars($message_type, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="verify_otp.php" class="auth-form">
                        <div class="form-group">
                            <label for="otp">6-Digit Verification Code</label>
                            <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="Enter your 6-digit code" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Verify Code</button>
                    </form>
                </section>
            </main>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>

</html>