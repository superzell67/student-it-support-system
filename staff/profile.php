<?php

require_once "../includes/auth.php";
require_once "../config/db.php";

requireStaff();

$user_id = $_SESSION["user_id"];
$message = "";

$stmt = $conn->prepare("
    SELECT id, full_name, email, role, profile, created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: dashboard.php");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["upload_profile"])) {
    if (isset($_FILES["profile"]) && $_FILES["profile"]["error"] === UPLOAD_ERR_OK) {
        $file_name = $_FILES["profile"]["name"];
        $file_tmp = $_FILES["profile"]["tmp_name"];
        $file_size = $_FILES["profile"]["size"];

        $allowed_types = ["jpg", "jpeg", "png", "gif"];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_types)) {
            $message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        } elseif ($file_size > 5 * 1024 * 1024) {
            $message = "Profile photo must not exceed 5MB.";
        } else {
            $new_file_name = "profile_" . $user_id . "_" . time() . "." . $file_extension;
            $upload_path = "../uploads/" . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                if (!empty($user["profile"])) {
                    $old_file = "../uploads/" . $user["profile"];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }

                $stmt = $conn->prepare("
                    UPDATE users
                    SET profile = ?
                    WHERE id = ?
                ");

                $stmt->bind_param("si", $new_file_name, $user_id);
                $stmt->execute();
                $stmt->close();

                $user["profile"] = $new_file_name;
                $message = "Profile photo uploaded successfully.";
            } else {
                $message = "Failed to upload profile photo.";
            }
        }
    } else {
        $message = "Please select a profile photo.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_profile"])) {
    if (!empty($user["profile"])) {
        $file_path = "../uploads/" . $user["profile"];

        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $stmt = $conn->prepare("
            UPDATE users
            SET profile = NULL
            WHERE id = ?
        ");

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $user["profile"] = "";
        $message = "Profile photo removed.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/staff.css">
    <title>Staff Profile</title>
</head>
<body>

<header class="dashboard-header">
    <div class="dashboard-nav">
        <h1>IT Support Staff Dashboard</h1>
        <div class="dashboard-user">
            <span><?php echo htmlspecialchars($user["full_name"]); ?></span>
            <button type="button" onclick="logout()">Logout</button>
        </div>
    </div>
</header>

<main class="dashboard-main">
    <div class="form-page profile-page">

        <section class="form-header">
            <p class="dashboard-label">STAFF PORTAL</p>
            <h2>My Profile</h2>
            <p>Manage your profile information.</p>
        </section>

         <div class="form-actions">
            <a href="dashboard.php" class="btn-link">&larr; Back to Dashboard</a>
        </div>

        <br>

        <?php if (!empty($message)): ?>
            <div class="form-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="detail-card profile-photo-card">
            <h3>Profile Photo</h3>

            <div class="profile-photo-wrapper">
                <?php if (!empty($user["profile"])): ?>
                    <button type="button" class="profile-photo-button" onclick="toggleProfileOptions()">
                        <img src="../uploads/<?php echo htmlspecialchars($user["profile"]); ?>" alt="Profile Photo">
                    </button>

                    <div id="profileOptions" class="profile-options" style="display: none;">
                        <form method="POST" enctype="multipart/form-data" id="newProfileForm">
                            <button type="button" class="btn-outline-dark" onclick="document.getElementById('newProfile').click();">
                                Change photo
                            </button>
                            <input type="file" id="newProfile" name="profile" accept=".jpg,.jpeg,.png,.gif" hidden onchange="document.getElementById('newProfileForm').submit();">
                            <input type="hidden" name="upload_profile" value="1">
                        </form>

                        <form method="POST">
                            <button type="submit" name="remove_profile" class="remove-button">
                                Remove Photo
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <button type="button" class="profile-photo-button" onclick="document.getElementById('profileInput').click();">
                        <div class="profile-placeholder">
                            No profile photo
                        </div>
                    </button>

                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <input type="file" id="profileInput" name="profile" accept=".jpg,.jpeg,.png,.gif" onchange="document.getElementById('uploadForm').submit();">
                        <input type="hidden" name="upload_profile" value="1">
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-card">
            <h3>Personal Information</h3>

            <div class="profile-info">
                <div class="profile-field">
                    <span class="meta-label">Full Name</span>
                    <span class="meta-value"><?php echo htmlspecialchars($user["full_name"]); ?></span>
                </div>

                <div class="profile-field">
                    <span class="meta-label">Email</span>
                    <span class="meta-value"><?php echo htmlspecialchars($user["email"]); ?></span>
                </div>

                <div class="profile-field">
                    <span class="meta-label">Role</span>
                    <span class="meta-value profile-role-badge"><?php echo htmlspecialchars($user["role"]); ?></span>
                </div>

                <div class="profile-field">
                    <span class="meta-label">Account Created</span>
                    <span class="meta-value"><?php echo htmlspecialchars($user["created_at"]); ?></span>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="../assets/js/student.js"></script>
</body>
</html>