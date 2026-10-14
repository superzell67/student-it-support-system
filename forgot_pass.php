<?php

session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$message = "";
$message_type = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $message = "Email not found.";
            $message_type = "error";
        } else {
            $otp = (string) random_int(100000, 999999);
            $otpHash = hash("sha256", $otp);

            $delete = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $delete->bind_param("i", $user["id"]);
            $delete->execute();
            $delete->close();

            $reset = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at, attempts, verified) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0, 0)");
            $reset->bind_param("is", $user["id"], $otpHash);

            if ($reset->execute()) {
                $reset->close();

                try {
                    $mailConfig = require __DIR__ . '/config/mail.php';

                    if (empty($mailConfig["username"]) || empty($mailConfig["password"])) {
                        throw new RuntimeException("SMTP credentials are not configured.");
                    }

                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $mailConfig["host"];
                    $mail->SMTPAuth = true;
                    $mail->Username = $mailConfig["username"];
                    $mail->Password = $mailConfig["password"];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $mailConfig["port"];
                    $mail->CharSet = "UTF-8";

                    $mail->setFrom($mailConfig["username"], "Student IT Support System");
                    $mail->addAddress($user["email"]);
                    $mail->isHTML(true);
                    $mail->Subject = "Your Password Reset Verification Code";

                    $mail->Body = "
                        <h2>Password Reset Verification</h2>
                        <p>Use the following code to reset your password:</p>
                        <p style='font-size:30px;font-weight:bold;letter-spacing:8px;'>{$otp}</p>
                        <p>This code expires in 5 minutes and can only be used once.</p>
                        <p>If you did not request this code, you can ignore this email.</p>
                    ";

                    $mail->AltBody = "Your password reset verification code is: {$otp}\nThis code expires in 5 minutes and can only be used once.";

                    $mail->send();

                    $_SESSION["otp_user_id"] = (int) $user["id"];

                    header("Location: verify_otp.php");
                    exit;
                } catch (Throwable $e) {
                    error_log("Password reset OTP email failed: " . $e->getMessage());

                    $cleanup = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                    $cleanup->bind_param("i", $user["id"]);
                    $cleanup->execute();
                    $cleanup->close();

                    $message = "Unable to send the verification code right now. Please try again later.";
                    $message_type = "error";
                }
            } else {
                $reset->close();
                error_log("Could not save password reset OTP.");

                $message = "Unable to process your request right now. Please try again later.";
                $message_type = "error";
            }
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
    <title>Forgot Password - IT Support System</title>
</head>

<body class="auth-body">

    <div class="auth-shell">
        <div class="auth-panel">
            <div class="auth-panel-top">
                <span class="auth-panel-mark">IT</span>
                <span>Student IT Support System</span>
            </div>

            <div class="auth-panel-body">
                <h1>Reset your password.</h1>
                <p>Verify your email with a code to reset your password.</p>
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
                    <h2>Forgot Password?</h2>
                    <p class="auth-subtext">Enter your registered email address. We'll send you a 6-digit verification code.</p>

                    <?php if (!empty($message)): ?>
                        <div class="auth-<?php echo htmlspecialchars($message_type, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="forgot_pass.php" class="auth-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?>" placeholder="Enter your email" autocomplete="email" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Send Verification Code</button>
                    </form>
                </section>
            </main>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>

</html>