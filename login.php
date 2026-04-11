<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Please fill in all fields.";
    } else {
        $result = loginUser($conn, $username, $password);
        if ($result === true) {
            header("Location: index.php");
            exit;
        }
        $error = $result;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-box">
        <h1>Inventory System</h1>
        <p class="auth-subtitle">Sign in to your account</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Enter your username"
                       value="<?= htmlspecialchars($username ?? '') ?>" autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <p class="auth-footer-text">
            Don't have an account? <a href="register.php">Create one</a>
        </p>
    </div>
</div>

</body>
</html>
<?php mysqli_close($conn); ?>
