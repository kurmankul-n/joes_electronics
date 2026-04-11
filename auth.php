<?php
function registerUser($conn, $username, $password, $fullName, $role = 'user') {
    $checkQuery = "SELECT UserID FROM users WHERE Username = '" . mysqli_real_escape_string($conn, $username) . "'";
    $checkResult = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        return "Username already exists.";
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $usernameEscaped = mysqli_real_escape_string($conn, $username);
    $fullNameEscaped = mysqli_real_escape_string($conn, $fullName);
    $roleEscaped = mysqli_real_escape_string($conn, $role);

    $insertQuery = "INSERT INTO users (Username, Password, FullName, Role) 
                    VALUES ('$usernameEscaped', '$hashedPassword', '$fullNameEscaped', '$roleEscaped')";

    if (mysqli_query($conn, $insertQuery)) {
        return true;
    }
    return "Registration failed: " . mysqli_error($conn);
}

function loginUser($conn, $username, $password) {
    $usernameEscaped = mysqli_real_escape_string($conn, $username);
    $query = "SELECT UserID, Username, Password, FullName, Role FROM users WHERE Username = '$usernameEscaped'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['Password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['full_name'] = $user['FullName'];
            $_SESSION['role'] = $user['Role'];
            return true;
        }
    }
    return "Invalid username or password.";
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index.php");
        exit;
    }
}

function logout() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
