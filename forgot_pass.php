<?php

session_start();

require_once __DIR__ . '/config/db.php';

$message = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);

    if (empty($email)) {
        $message = "Please enter your email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            $token = bin2hex(random_bytes(32));

            $delete = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $delete->bind_param("i", $user["id"]);
            $delete->execute();
            $delete->close();

            $reset = $conn->prepare(" INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
            $reset->bind_param("is", $user["id"], $token);
            $reset->execute();
            $reset->close();

            header("Location: reset_pass.php?token=" . urlencode($token)); 
            exit;
        } else {
            $message = "Email not found.";
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
            <p>Enter your registered email address and we'll help you reset your password.</p>
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
                <p class="auth-subtext">Enter the email address connected to your account.</p>

                <?php if (!empty($message)): ?>
                    <div class="auth-error"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="POST" action="forgot_pass.php" class="auth-form">

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" autocomplete="email" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>

                </form>

            </section>

        </main>

    </div>

</div>

    <script src="assets/js/"auth.js></script>
</body>
</html>