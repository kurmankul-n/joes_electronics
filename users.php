<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

requireAdmin();

$message = "";
$messageType = "";

if (isset($_POST['delete'])) {
    $deleteId = intval($_POST['delete']);

    if ($deleteId == $_SESSION['user_id']) {
        $message = "You cannot delete your own account.";
        $messageType = "error";
    } else {
        if (mysqli_query($conn, "DELETE FROM users WHERE UserID = $deleteId")) {
            $message = "User deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Error deleting user.";
            $messageType = "error";
        }
    }
}

if (isset($_POST['changeRole'])) {
    $targetId = intval($_POST['userId']);
    $newRole = mysqli_real_escape_string($conn, $_POST['newRole']);

    if ($targetId == $_SESSION['user_id']) {
        $message = "You cannot change your own role.";
        $messageType = "error";
    } elseif ($newRole === 'admin' || $newRole === 'user') {
        if (mysqli_query($conn, "UPDATE users SET Role = '$newRole' WHERE UserID = $targetId")) {
            $message = "User role updated.";
            $messageType = "success";
        } else {
            $message = "Error updating role.";
            $messageType = "error";
        }
    }
}

$usersResult = mysqli_query($conn, "SELECT UserID, Username, FullName, Role, CreatedAt FROM users ORDER BY UserID ASC");
$usersList = [];
while ($row = mysqli_fetch_assoc($usersResult)) {
    $usersList[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users — Inventory System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">Inventory</a>
    <a href="index.php">Dashboard</a>
    <a href="users.php" class="active">Users</a>
    <a href="products.php">Products</a>
    <a href="sales.php">Sales</a>
    <a href="reports.php">Reports</a>
    <span class="nav-user"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    <a href="profile.php">Profile</a>
    <a href="logout.php" class="btn-logout">Logout</a>
</nav>

<div class="container">
    <h1>Manage Users</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <div class="table-header">
            <h2>All Users</h2>
            <span class="badge badge-blue"><?= count($usersList) ?> total</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($usersList as $user) {
                    $roleBadge = $user['Role'] === 'admin'
                        ? "<span class='badge badge-blue'>Admin</span>"
                        : "<span class='badge badge-green'>User</span>";

                    echo "<tr>";
                    echo "<td>" . $user['UserID'] . "</td>";
                    echo "<td>" . htmlspecialchars($user['Username']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['FullName']) . "</td>";
                    echo "<td>" . $roleBadge . "</td>";
                    echo "<td>" . date('d/m/Y', strtotime($user['CreatedAt'])) . "</td>";
                    echo "<td>";

                    if ($user['UserID'] != $_SESSION['user_id']) {
                        echo "<form method='POST' style='display:inline; margin-right:5px;'>";
                        echo "<input type='hidden' name='userId' value='" . $user['UserID'] . "'>";
                        echo "<select name='newRole' style='padding:4px; border-radius:4px; border:1px solid #2a2d36; background:#22252d; color:#d4d6dc;'>";
                        echo "<option value='user'" . ($user['Role'] === 'user' ? ' selected' : '') . ">User</option>";
                        echo "<option value='admin'" . ($user['Role'] === 'admin' ? ' selected' : '') . ">Admin</option>";
                        echo "</select>";
                        echo "<button type='submit' name='changeRole' class='btn btn-edit' style='padding:4px 8px;'>Set</button>";
                        echo "</form>";

                        echo "<form method='POST' style='display:inline;'>";
                        echo "<button type='submit' name='delete' value='" . $user['UserID'] . "' class='btn btn-danger'
                               onclick=\"return confirm('Delete this user?')\">Delete</button>";
                        echo "</form>";
                    } else {
                        echo "<span style='color:#999; font-size:13px;'>Current user</span>";
                    }

                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <a href="register.php" class="btn btn-success">+ Register New User</a>
</div>

<footer>Inventory Management System — Joe's Electronics &copy; <?= date('Y') ?></footer>
</body>
</html>
<?php mysqli_close($conn); ?>
