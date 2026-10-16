<?php

session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$mailConfig = require __DIR__ . '/config/mail.php';

$message = "";
$message_type = "";
$full_name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $full_name === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {
        $message = "Please complete all required fields.";
        $message_type = "error";
    } elseif (strlen($full_name) < 2) {
        $message = "Please enter a valid full name.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } else {
        $check = $conn->prepare("SELECT id, email_verified FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();
        $existingUser = $result->fetch_assoc();
        $check->close();

        if ($existingUser) {
            if ((int) $existingUser["email_verified"] === 1) {
                $message = "This email address is already registered.";
                $message_type = "error";
            } else {
                $message = "This email has a pending verification. If you can access its inbox, you can continue verification. Otherwise, please use a different email address.";
                $message_type = "error";
            }
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = "student";
            $userId = 0;
            $transactionStarted = false;

            try {
                $conn->begin_transaction();
                $transactionStarted = true;

                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, email_verified) VALUES (?, ?, ?, ?, 0)");
                $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);
                $stmt->execute();

                $userId = (int) $conn->insert_id;
                $stmt->close();

                $otp = (string) random_int(100000, 999999);
                $otpHash = hash("sha256", $otp);

                $insertCode = $conn->prepare("INSERT INTO email_verifications (user_id, token, expires_at, attempts) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)");
                $insertCode->bind_param("is", $userId, $otpHash);
                $insertCode->execute();
                $insertCode->close();

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $mailConfig["host"];
                $mail->SMTPAuth = true;
                $mail->Username = $mailConfig["username"];
                $mail->Password = $mailConfig["password"];
                $mail->SMTPSecure = $mailConfig["encryption"];
                $mail->Port = (int) $mailConfig["port"];

                $mail->setFrom($mailConfig["username"], "Student IT Support System");
                $mail->addAddress($email, $full_name);
                $mail->isHTML(true);
                $mail->Subject = "Verify Your Student IT Support Account";

                $safeName = htmlspecialchars($full_name, ENT_QUOTES, "UTF-8");

                $mail->Body = "<p>Hello {$safeName},</p><p>Your email verification code is:</p><h2 style='letter-spacing: 5px;'>{$otp}</h2><p>This code expires in 10 minutes. Do not share it.</p>";
                $mail->AltBody = "Your email verification code is: {$otp}\nThis code expires in 10 minutes. Do not share it.";

                $mail->send();

                $conn->commit();
                $transactionStarted = false;

                $_SESSION["pending_verification_user_id"] = $userId;

                header("Location: verify_email.php");
                exit;
            } catch (Throwable $e) {
                if ($transactionStarted) {
                    try {
                        $conn->rollback();
                    } catch (Throwable $rollbackError) {
                        error_log("Registration rollback error: " . $rollbackError->getMessage());
                    }
                }

                error_log("Registration error: " . $e->getMessage());

                $message = "We couldn't complete registration or send the verification email. Please check the address and try again.";
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
    <title>Create Account - IT Support System</title>
</head>

<body class="auth-body">

    <div class="auth-shell">

        <div class="auth-panel">
            <div class="auth-panel-top">
                <span class="auth-panel-mark">IT</span>
                <span>Student IT Support System</span>
            </div>

            <div class="auth-panel-body">
                <h1>Create your account and get support in minutes.</h1>
                <p>One account for reporting problems, tracking tickets, and staying in the loop with IT support.</p>

                <ul class="auth-panel-features">
                    <li><span class="feature-dot">&#10003;</span> Free for all students</li>
                    <li><span class="feature-dot">&#10003;</span> Track every ticket you submit</li>
                    <li><span class="feature-dot">&#10003;</span> Direct line to IT support staff</li>
                </ul>
            </div>

            <div class="auth-panel-bottom">&copy; 2026 Student IT Support System</div>
        </div>

        <div class="auth-formside">

            <div class="auth-topbar">
                <a href="index.php" class="page-link">Home</a>
            </div>

            <main class="auth-main">

                <section class="auth-card">

                    <p class="dashboard-label">IT SUPPORT PORTAL</p>
                    <h2>Create your account</h2>
                    <p class="auth-subtext">Register to submit and track technical support requests.</p>

                    <?php if (!empty($message)): ?>
                        <div class="auth-<?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="register.php" class="auth-form">

                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" placeholder="Enter your full name" autocomplete="name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email address" autocomplete="email" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password" minlength="8" required>
                            <small class="auth-hint">Password must contain at least 8 characters.</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" autocomplete="new-password" minlength="8" required>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="showPassword">
                            <label for="showPassword">Show password</label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Create Account</button>

                    </form>

                    <p class="auth-switch">
                        Already have an account?
                        <a href="login.php" class="page-link">Login here</a>
                    </p>

                </section>

            </main>

        </div>

    </div>

    <script src="assets/js/register.js"></script>
</body>

</html>