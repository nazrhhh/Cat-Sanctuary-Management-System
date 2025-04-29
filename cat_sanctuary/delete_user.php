<?php
require_once 'config.php'; // Include your database connection
session_start(); // Start session to access CSRF token

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $userID = $_POST['user_id'];

    // CSRF Protection: Verify token before proceeding
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE userID = ?");
        $stmt->execute([$userID]);

        // Redirect back to the dashboard after deletion
        header("Location: dashboard.php?tab=users&delete=success");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>
