<?php

require_once "../includes/auth.php";
require_once "../config/db.php";

requireStudent();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $category_id = $_POST["category_id"];
    $subject = trim($_POST["subject"]);
    $description = trim($_POST["description"]);
    $priority = $_POST["priority"];

    if (empty($category_id) || empty($subject) || empty($description) || empty($priority)) {
        $message = "Please complete all fields.";
    } else {
        $user_id = $_SESSION["user_id"];

        $stmt = $conn->prepare("INSERT INTO tickets (user_id, category_id, subject, description, priority) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $category_id, $subject, $description, $priority);

        if ($stmt->execute()) {
            $message = "Ticket submitted successfully!";
        } else {
            $message = "Failed to submit ticket.";
        }

        $stmt->close();
    }
}

$categories = $conn->query("SELECT id, category_name FROM categories");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/student.css">
    <title>Create Ticket</title>
</head>
<body>

<header class="dashboard-header">
    <div class="dashboard-nav">
        <h1>Student IT Support System</h1>
        <div class="dashboard-user">
            <span><?php echo htmlspecialchars($_SESSION["full_name"]); ?></span>
            <button type="button" onclick="logout()">Logout</button>
        </div>
    </div>
</header>

<main class="dashboard-main">
    <div class="form-page">

        <section class="form-header">
            <p class="dashboard-label">STUDENT PORTAL</p>
            <h2>Create IT Support Ticket</h2>
            <p>Fill out the form below and our IT support staff will get back to you.</p>
        </section>

         <div class="form-actions">
              <a href="my_tickets.php" class="btn-link">&larr; Back to My Tickets</a>
             <a href="dashboard.php" class="btn-link">&larr; Back to Dashboard</a>
        </div>  

        <br>
        
        <?php if (!empty($message)): ?>
            <div class="form-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" class="ticket-form">
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Select Category --</option>
                    <?php while ($category = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $category["id"]; ?>">
                            <?php echo htmlspecialchars($category["category_name"]); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subject">Problem Subject</label>
                <select id="subject" name="subject" onchange="showOtherProblem()" required>
                    <option value="">-- Select Problem --</option>
                    <option value="Cannot connect to Wi-Fi">Cannot connect to Wi-Fi</option>
                    <option value="Slow Internet Connection">Slow Internet Connection</option>
                    <option value="Cannot Login to School Portal">Cannot Login</option>
                    <option value="Forgot Password">Forgot Password</option>
                    <option value="Computer/Laptop Problem">Computer/Laptop Problem</option>
                    <option value="Printer Problem">Printer Problem</option>
                    <option value="Software/Application Problem">Software/Application Problem</option>
                    <option value="School Email Problem">School Email Problem</option>
                    <option value="Account Problem">Account Problem</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group" id="otherProblemContainer" style="display: none;">
                <label for="other_problem">Specify Your Problem</label>
                <input type="text" id="other_problem" name="other_problem" placeholder="Enter your problem">
            </div>

            <div class="form-group">
                <label for="description">Describe Your Problem</label>
                <textarea id="description" name="description" rows="6" placeholder="Please provide more details about the problem..." required></textarea>
            </div>

            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority" required>
                    <option value="">-- Select Priority --</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Ticket</button>
            </div>
        </form>

    </div>
</main>

    <script src="../assets/js/student.js"></script>
</body>
</html>