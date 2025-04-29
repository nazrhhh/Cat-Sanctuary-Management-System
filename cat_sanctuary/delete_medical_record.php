<?php

session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['record_id'])) {
    $recordID = (int)$_POST['record_id'];

    // CSRF Protection: Verify token before proceeding
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM HealthRecord WHERE recordID = ?");
        $stmt->execute([$recordID]);

        // Redirect after successful deletion
        header("Location: dashboard.php?tab=records&delete=success");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>
