<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $fullName = trim($_POST['fullName'] ?? '');

    if ($username === '' || $password === '' || $fullName === '') {
        $error = "Please fill in all fields.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $result = registerUser($conn, $username, $password, $fullName);
        if ($result === true) {
            $success = "Account created. You can now <a href='login.php'>sign in</a>.";
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-box">
        <h1>Inventory System</h1>
        <p class="auth-subtitle">Create your account</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php else: ?>
        <form method="POST" action="register.php">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullName" required placeholder="Your full name"
                       value="<?= htmlspecialchars($_POST['fullName'] ?? '') ?>" autofocus>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Choose a username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Create a password">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirmPassword" required placeholder="Confirm your password">
            </div>
            <button type="submit" class="btn btn-success btn-block">Create Account</button>
        </form>

        <p class="auth-footer-text">
            Already have an account? <a href="login.php">Sign in</a>
        </p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
<?php mysqli_close($conn); ?>
