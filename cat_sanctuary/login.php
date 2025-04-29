<?php
session_start();
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

function setFlashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE Username = ? AND Status = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Use password_verify() to check the password against the hash
        if (password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['userID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['firstName'] = $user['firstName'];
            $_SESSION['lastName'] = $user['lastName'];
            $_SESSION['role'] = $user['Role'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            echo "<script>sessionStorage.removeItem('activeTab');</script>";

            header("Location: dashboard.php");
            exit();
        } else {
            setFlashMessage('error', 'Invalid username or password');
        }
    } else {
        setFlashMessage('error', 'Invalid username or password');
    }
}
?>