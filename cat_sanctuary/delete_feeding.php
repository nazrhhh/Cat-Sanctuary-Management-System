<?php

require_once 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['care_id'])) {
    $careID = $_POST['care_id'];

    // CSRF Protection: Verify token before proceeding
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM DailyCare WHERE careID = ?");
        $stmt->execute([$careID]);

        // Redirect back to the dashboard after deletion
        header("Location: dashboard.php?tab=feedings&delete=success");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>