<?php

session_start();
require_once 'config.php';
require_once 'functions.php';

checkAuth();
checkRole(['Medical']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'])) {
    $appointmentID = (int)$_POST['appointment_id'];

    // CSRF Protection: Verify token before proceeding
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    try {
        // First check if the appointment exists
        $checkStmt = $pdo->prepare("SELECT scheduleID FROM MedicalSchedule WHERE scheduleID = ?");
        $checkStmt->execute([$appointmentID]);
        
        if (!$checkStmt->fetch()) {
            header("Location: dashboard.php?tab=schedule&delete=error&message=appointment_not_found");
            exit();
        }

        // Delete the appointment
        $stmt = $pdo->prepare("DELETE FROM MedicalSchedule WHERE scheduleID = ?");
        $stmt->execute([$appointmentID]);

        // Redirect after successful deletion
        header("Location: dashboard.php?tab=schedule&delete=success");
        exit();
    } catch (PDOException $e) {
        header("Location: dashboard.php?tab=schedule&delete=error&message=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: dashboard.php?tab=schedule&delete=error&message=invalid_request");
    exit();
}
?>