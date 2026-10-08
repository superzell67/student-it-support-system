<?php

session_start();

require_once __DIR__ . '/config/db.php';

$message = "";
$email = "";

if (isset($_SESSION["login_message"])) {
    $message = $_SESSION["login_message"];
    unset($_SESSION["login_message"]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $_SESSION["login_message"] = "Please enter your email and password.";
        $_SESSION["login_email"] = $email;
        header("Location: login.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            if ($user["role"] === "staff") {
                header("Location: staff/dashboard.php");
                exit;
            } else {
                header("Location: student/dashboard.php");
                exit;
            }
        } else {
            $_SESSION["login_message"] = "Invalid Email or Password.";
            $_SESSION["login_email"] = $email;
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION["login_message"] = "Invalid Email or Password.";
        $_SESSION["login_email"] = $email;
        header("Location: login.php");
        exit;
    }

    $stmt->close();
}

if (isset($_SESSION["login_email"])) {
    $email = $_SESSION["login_email"];
    unset($_SESSION["login_email"]);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/theme.css">
    <title>Login - IT Support System</title>
</head>
<body class="auth-body">

    <div class="auth-shell">

        <div class="auth-panel">
            <div class="auth-panel-top">
                <span class="auth-panel-mark">IT</span>
                <span>Student IT Support System</span>
            </div>

            <div class="auth-panel-body">
                <h1>Get technical help without leaving your dashboard.</h1>
                <p>Submit tickets, track progress, and hear back from IT support staff — all in one place.</p>

                <ul class="auth-panel-features">
                    <li><span class="feature-dot">&#10003;</span> Submit and track support tickets</li>
                    <li><span class="feature-dot">&#10003;</span> Get updates from IT staff in real time</li>
                    <li><span class="feature-dot">&#10003;</span> View your full request history</li>
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
                    <h2>Welcome back</h2>
                    <p class="auth-subtext">Sign in to access the IT Support System.</p>

                    <?php if (!empty($message)): ?>
                        <div class="auth-error"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" class="auth-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" autocomplete="email" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="showPassword">
                            <label for="showPassword">Show password</label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Login</button>

                        <a href="forgot_pass.php" class="forgot-password">Forgot Password?</a>
                    </form>

                    <p class="auth-switch">Don't have an account? <a href="register.php" class="page-link">Create an account</a></p>
                </section>
            </main>
        </div>

    </div>

    <script src="assets/js/auth.js"></script>
</body>
</html>