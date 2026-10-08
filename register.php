<?php

require_once __DIR__ . '/config/db.php';

$message = "";
$message_type = "";
$full_name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
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

        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "This email address is already registered.";
            $message_type = "error";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = "student";

            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $message = "Registration successful!";
                $message_type = "success";
                $full_name = "";
                $email = "";
                header("refresh:2;url=login.php");
            } else {
                $message = "Something went wrong while creating your account.";
                $message_type = "error";
            }

            $stmt->close();
        }

        $check->close();
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
                        <div class="auth-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
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

                    <p class="auth-switch">Already have an account? <a href="login.php" class="page-link">Login here</a></p>
                </section>
            </main>
        </div>

    </div>

    <script src="assets/js/register.js"></script>
</body>
</html>