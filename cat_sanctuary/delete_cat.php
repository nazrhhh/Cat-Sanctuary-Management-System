<?php

require_once 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cat_id'])) {
    $catID = $_POST['cat_id'];

    // CSRF Protection: Verify token before proceeding
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM Cat WHERE catID = ?");
        $stmt->execute([$catID]);

        // Redirect back to the dashboard after deletion
        header("Location: dashboard.php?tab=cats&delete=success");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>
