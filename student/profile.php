<?php

require_once "../includes/auth.php";
require_once "../config/db.php";

requireStudent();

$user_id = $_SESSION["user_id"];

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["upload_profile"])) {
    if (!isset($_FILES["profile"]) || $_FILES["profile"]["error"] !== UPLOAD_ERR_OK) {
        $error = "Please select a profile photo.";
    } else {
        $file = $_FILES["profile"];
        $max_size = 5 * 1024 * 1024;

        $allowed_types = [
            "image/jpeg" => "jpg",
            "image/png"  => "png",
            "image/gif"  => "gif"
        ];

        if ($file["size"] > $max_size) {
            $error = "Profile photo must not exceed 5MB.";
        } else {
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $file["tmp_name"]);
            finfo_close($file_info);

            if (!isset($allowed_types[$mime_type])) {
                $error = "Only JPG, PNG, and GIF files are allowed.";
            } else {
                $extension = $allowed_types[$mime_type];
                $new_file_name = "profile_" . $user_id . "_" . time() . "." . $extension;
                $upload_folder = "../uploads/";

                if (!is_dir($upload_folder)) {
                    mkdir($upload_folder, 0777, true);
                }

                $upload_path = $upload_folder . $new_file_name;

                if (move_uploaded_file($file["tmp_name"], $upload_path)) {
                    $stmt = $conn->prepare("SELECT profile FROM users WHERE id = ? LIMIT 1");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    $result = $stmt->get_result();
                    $old_user = $result->fetch_assoc();
                    $stmt->close();

                    if (!empty($old_user["profile"])) {
                        $old_file = "../uploads/" . $old_user["profile"];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }

                    $stmt = $conn->prepare("UPDATE users SET profile = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_file_name, $user_id);
                    $stmt->execute();
                    $stmt->close();

                    $message = "Profile photo updated successfully.";
                } else {
                    $error = "Failed to upload profile photo.";
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_profile"])) {
    $stmt = $conn->prepare("SELECT profile FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user_profile = $result->fetch_assoc();
    $stmt->close();

    if (!empty($user_profile["profile"])) {
        $file_path = "../uploads/" . $user_profile["profile"];

        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $stmt = $conn->prepare("UPDATE users SET profile = NULL WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $message = "Profile photo removed successfully.";
    } else {
        $error = "You do not have a profile photo.";
    }
}

$stmt = $conn->prepare("SELECT id, full_name, email, role, profile, created_at FROM users WHERE id = ? LIMIT 1");
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/student.css">
    <title>My Profile</title>
</head>
<body>

<header class="dashboard-header">
    <div class="dashboard-nav">
        <h1>Student IT Support System</h1>
        <div class="dashboard-user">
            <span><?php echo htmlspecialchars($user["full_name"]); ?></span>
            <button type="button" onclick="logout()">Logout</button>
        </div>
    </div>
</header>

<main class="dashboard-main">
    <div class="form-page profile-page">

        <section class="form-header">
            <p class="dashboard-label">STUDENT PORTAL</p>
            <h2>My Profile</h2>
            <p>View your account information.</p>
        </section>

        <div class="form-actions">
            <a href="dashboard.php" class="btn-link">&larr; Back to Dashboard</a>
        </div>

        <br>

        <?php if ($message !== ""): ?>
            <div class="auth-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="detail-card profile-photo-card">
            <h3>Profile</h3>

            <div class="profile-photo-wrapper">
                <?php if (!empty($user["profile"])): ?>
                    <button type="button" class="profile-photo-button" onclick="toggleProfileOptions()">
                        <img src="../uploads/<?php echo htmlspecialchars($user["profile"]); ?>" alt="Profile Photo">
                    </button>
                <?php else: ?>
                    <button type="button" class="profile-photo-button" onclick="toggleProfileOptions()">
                        <div class="profile-placeholder">No profile photo</div>
                    </button>
                <?php endif; ?>

                <div id="profileOptions" class="profile-options" style="display: none;">
                    <button type="button" class="btn-outline-dark" onclick="openFilePicker()">
                        <?php if (!empty($user["profile"])): ?>
                            Change Photo
                        <?php else: ?>
                            Upload Photo
                        <?php endif; ?>
                    </button>

                    <?php if (!empty($user["profile"])): ?>
                        <form method="POST">
                            <button type="submit" name="remove_profile" class="remove-button" onclick="return confirm('Are you sure you want to remove your profile photo?');">
                                Remove Photo
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="file" id="profileInput" name="profile" accept=".jpg,.jpeg,.png,.gif" onchange="uploadProfilePhoto()">
                <input type="hidden" name="upload_profile" value="1">
            </form>
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