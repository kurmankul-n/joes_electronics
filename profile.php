<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

requireLogin();

$message = "";
$messageType = "";
$userData = null;

$userId = intval($_SESSION['user_id']);
$userResult = mysqli_query($conn, "SELECT UserID, Username, FullName, Role, CreatedAt FROM users WHERE UserID = $userId");
$userData = mysqli_fetch_assoc($userResult);

if (!$userData) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['updateProfile'])) {
        $fullName = mysqli_real_escape_string($conn, trim($_POST['fullName']));

        if ($fullName !== '') {
            mysqli_query($conn, "UPDATE users SET FullName = '$fullName' WHERE UserID = $userId");
            $_SESSION['full_name'] = $fullName;
            $message = "Profile updated successfully!";
            $messageType = "success";

            $userResult = mysqli_query($conn, "SELECT UserID, Username, FullName, Role, CreatedAt FROM users WHERE UserID = $userId");
            $userData = mysqli_fetch_assoc($userResult);
        } else {
            $message = "Full name cannot be empty.";
            $messageType = "error";
        }
    }

    if (isset($_POST['changePassword'])) {
        $currentPassword = $_POST['currentPassword'] ?? '';
        $newPassword = $_POST['newPassword'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $message = "Please fill in all password fields.";
            $messageType = "error";
        } elseif (strlen($newPassword) < 4) {
            $message = "New password must be at least 4 characters.";
            $messageType = "error";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "New passwords do not match.";
            $messageType = "error";
        } else {
            $passResult = mysqli_query($conn, "SELECT Password FROM users WHERE UserID = $userId");
            $passRow = mysqli_fetch_assoc($passResult);

            if (!password_verify($currentPassword, $passRow['Password'])) {
                $message = "Current password is incorrect.";
                $messageType = "error";
            } else {
                $hashedNew = password_hash($newPassword, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET Password = '$hashedNew' WHERE UserID = $userId");
                $message = "Password changed successfully!";
                $messageType = "success";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile — Inventory System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">Inventory</a>
    <a href="index.php">Dashboard</a>
    <?php if (isAdmin()): ?>
    <a href="users.php">Users</a>
    <?php endif; ?>
    <a href="products.php">Products</a>
    <a href="sales.php">Sales</a>
    <a href="reports.php">Reports</a>
    <span class="nav-user"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    <a href="profile.php" class="active">Profile</a>
    <a href="logout.php" class="btn-logout">Logout</a>
</nav>

<div class="container">
    <h1>My Profile</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div style="display:flex; gap:30px; flex-wrap:wrap;">

        <div style="flex:1; min-width:280px;">
            <div class="form-box">
                <h2>Account Information</h2>
                <div class="profile-info">
                    <div class="info-row">
                        <span class="info-label">Username:</span>
                        <span><?= htmlspecialchars($userData['Username']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Full Name:</span>
                        <span><?= htmlspecialchars($userData['FullName']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Role:</span>
                        <span class="badge <?= isAdmin() ? 'badge-blue' : 'badge-green' ?>">
                            <?= ucfirst($userData['Role']) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Member Since:</span>
                        <span><?= date('d/m/Y H:i', strtotime($userData['CreatedAt'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div style="flex:1; min-width:280px;">
            <div class="form-box">
                <h2>Edit Profile</h2>
                <form method="POST" action="profile.php">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullName" required
                               value="<?= htmlspecialchars($userData['FullName']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" disabled value="<?= htmlspecialchars($userData['Username']) ?>"
                               style="background:#f5f5f5; color:#999;">
                    </div>
                    <button type="submit" name="updateProfile" class="btn btn-primary btn-block">Update Profile</button>
                </form>
            </div>

            <div class="form-box" style="margin-top:20px;">
                <h2>Change Password</h2>
                <form method="POST" action="profile.php">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="currentPassword" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="newPassword" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirmPassword" required>
                    </div>
                    <button type="submit" name="changePassword" class="btn btn-success btn-block">Change Password</button>
                </form>
            </div>
        </div>

    </div>
</div>

<footer>Inventory Management System — Joe's Electronics &copy; <?= date('Y') ?></footer>
</body>
</html>
<?php mysqli_close($conn); ?>
