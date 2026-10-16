<?php

session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$mailConfig = require __DIR__ . '/config/mail.php';

$message = "";
$message_type = "error";

if (isset($_SESSION["login_message"])) {
    $message = $_SESSION["login_message"];
    $message_type = $_SESSION["login_message_type"] ?? "error";

    unset($_SESSION["login_message"], $_SESSION["login_message_type"]);
}

$userId = (int) ($_SESSION["pending_verification_user_id"] ?? 0);

if ($userId <= 0) {
    header("Location: register.php");
    exit;
}

$stmt = $conn->prepare("SELECT full_name, email, email_verified FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || (int) $user["email_verified"] === 1) {
    unset($_SESSION["pending_verification_user_id"]);
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "verify";

    if ($action === "resend") {
        $lastResend = (int) ($_SESSION["verification_last_resend"] ?? 0);

        if (time() - $lastResend < 60) {
            $wait = 60 - (time() - $lastResend);
            $message = "Please wait {$wait} seconds before requesting another code.";
        } else {
            try {
                $otp = (string) random_int(100000, 999999);
                $otpHash = hash("sha256", $otp);

                $conn->begin_transaction();

                $updateCode = $conn->prepare("UPDATE email_verifications SET token = ?, expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), attempts = 0 WHERE user_id = ?");
                $updateCode->bind_param("si", $otpHash, $userId);
                $updateCode->execute();
                $changed = $updateCode->affected_rows;
                $updateCode->close();

                if ($changed === 0) {
                    $checkCode = $conn->prepare("SELECT id FROM email_verifications WHERE user_id = ? LIMIT 1");
                    $checkCode->bind_param("i", $userId);
                    $checkCode->execute();
                    $codeExists = $checkCode->get_result()->num_rows > 0;
                    $checkCode->close();

                    if (!$codeExists) {
                        $insertCode = $conn->prepare("INSERT INTO email_verifications (user_id, token, expires_at, attempts) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)");
                        $insertCode->bind_param("is", $userId, $otpHash);
                        $insertCode->execute();
                        $insertCode->close();
                    }
                }

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $mailConfig["host"];
                $mail->SMTPAuth = true;
                $mail->Username = $mailConfig["username"];
                $mail->Password = $mailConfig["password"];
                $mail->SMTPSecure = $mailConfig["encryption"];
                $mail->Port = (int) $mailConfig["port"];

                $mail->setFrom($mailConfig["username"], "Student IT Support System");
                $mail->addAddress($user["email"], $user["full_name"]);
                $mail->isHTML(true);
                $mail->Subject = "Your New Email Verification Code";

                $safeName = htmlspecialchars($user["full_name"], ENT_QUOTES, "UTF-8");

                $mail->Body = "<p>Hello {$safeName},</p><p>Your new email verification code is:</p><h2 style='letter-spacing: 5px;'>{$otp}</h2><p>This code expires in 10 minutes. Do not share it.</p>";
                $mail->AltBody = "Your new email verification code is: {$otp}\nThis code expires in 10 minutes. Do not share it.";

                $mail->send();

                $conn->commit();

                $_SESSION["verification_last_resend"] = time();

                $message = "A new verification code has been sent. Check your inbox and spam folder.";
                $message_type = "success";
            } catch (Throwable $e) {
                if ($conn->errno !== 0) {
                    try {
                        $conn->rollback();
                    } catch (Throwable $rollbackError) {
                        error_log("Verification resend rollback error: " . $rollbackError->getMessage());
                    }
                }

                error_log("Verification resend error: " . $e->getMessage());

                $message = "We couldn't send a new code. Please try again later.";
                $message_type = "error";
            }
        }
    } else {
        $code = trim($_POST["verification_code"] ?? "");

        if (!preg_match('/^\d{6}$/', $code)) {
            $message = "Please enter a valid 6-digit verification code.";
        } else {
            $stmt = $conn->prepare("SELECT token, expires_at, attempts FROM email_verifications WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $verification = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$verification) {
                $message = "Verification code not found. Request a new code.";
            } elseif ((int) $verification["attempts"] >= 5) {
                $message = "Too many incorrect attempts. Request a new code.";
            } elseif (strtotime($verification["expires_at"]) <= time()) {
                $message = "Your code has expired. Request a new code.";
            } elseif (!hash_equals($verification["token"], hash("sha256", $code))) {
                $update = $conn->prepare("UPDATE email_verifications SET attempts = attempts + 1 WHERE user_id = ?");
                $update->bind_param("i", $userId);
                $update->execute();
                $update->close();

                $message = "Incorrect verification code. Please try again.";
            } else {
                $conn->begin_transaction();

                try {
                    $updateUser = $conn->prepare("UPDATE users SET email_verified = 1 WHERE id = ? AND email_verified = 0");
                    $updateUser->bind_param("i", $userId);
                    $updateUser->execute();

                    if ($updateUser->affected_rows !== 1) {
                        $updateUser->close();
                        throw new RuntimeException("Account could not be verified.");
                    }

                    $updateUser->close();

                    $deleteCode = $conn->prepare("DELETE FROM email_verifications WHERE user_id = ?");
                    $deleteCode->bind_param("i", $userId);
                    $deleteCode->execute();
                    $deleteCode->close();

                    $conn->commit();

                    unset($_SESSION["pending_verification_user_id"], $_SESSION["verification_last_resend"]);

                    $_SESSION["login_message"] = "Email verified successfully! You can now log in.";
                    $_SESSION["login_message_type"] = "success";

                    header("Location: login.php");
                    exit;
                } catch (Throwable $e) {
                    $conn->rollback();

                    error_log("Email verification error: " . $e->getMessage());

                    $message = "Something went wrong. Please try again.";
                    $message_type = "error";
                }
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
    <title>Verify Email - IT Support System</title>
</head>

<body class="auth-body">

    <div class="auth-shell">

        <div class="auth-panel">
            <div class="auth-panel-top">
                <span class="auth-panel-mark">IT</span>
                <span>Student IT Support System</span>
            </div>

            <div class="auth-panel-body">
                <h1>Verify your email address.</h1>
                <p>Enter the verification code sent to your email to activate your account.</p>
            </div>

            <div class="auth-panel-bottom">&copy; 2026 Student IT Support System</div>
        </div>

        <div class="auth-formside">

            <div class="auth-topbar">
                <a href="index.php" class="page-link">Home</a>
            </div>

            <main class="auth-main">

                <section class="auth-card">

                    <p class="dashboard-label">EMAIL VERIFICATION</p>
                    <h2>Enter your code</h2>
                    <p class="auth-subtext">Check your inbox and enter the 6-digit code.</p>

                    <?php if ($message !== ""): ?>
                        <div class="<?php echo $message_type === "success" ? "auth-success" : "auth-error"; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="verify_email.php" class="auth-form">

                        <input type="hidden" name="action" value="verify">

                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <input type="text" id="verification_code" name="verification_code" placeholder="Enter 6-digit code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Verify Email</button>

                    </form>

                    <form method="POST" action="verify_email.php" class="auth-form">
                        <input type="hidden" name="action" value="resend">
                        <button type="submit" class="btn btn-block">Resend Verification Code</button>
                    </form>

                    <div class="auth-switch">
                        <a href="login.php" class="page-link">Back to Login</a>
                    </div>

                    <p class="auth-switch">
                        <a href="register.php" class="page-link">Register again</a>
                    </p>

                </section>

            </main>

        </div>

    </div>

</body>

</html>