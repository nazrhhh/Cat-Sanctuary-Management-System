<?php

session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is authenticated
checkAuth();

// Initialize response variables
$error = '';
$success = '';

// Verify that this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?tab=behavior&error=invalid_request');
    exit;
}

// Validate CSRF token
validateCSRFToken($_POST['csrf_token']);

// Get and validate behavior ID
$behaviorID = isset($_POST['behavior_id']) ? (int)$_POST['behavior_id'] : 0;

if (!$behaviorID) {
    header('Location: dashboard.php?tab=behavior&error=invalid_id');
    exit;
}

try {
    // check if the behavior record exists
    $stmt = $pdo->prepare("SELECT behaviorID FROM Behavior WHERE behaviorID = ?");
    $stmt->execute([$behaviorID]);
    
    if (!$stmt->fetch()) {
        header('Location: dashboard.php?tab=behavior&error=record_not_found');
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM Behavior WHERE behaviorID = ?");
    $stmt->execute([$behaviorID]);
    
    if ($stmt->rowCount() > 0) {
        // Successful deletion
        header('Location: dashboard.php?tab=behavior&success=record_deleted');
        exit;
    } else {
        // No rows were affected
        header('Location: dashboard.php?tab=behavior&error=delete_failed');
        exit;
    }
    
} catch (PDOException $e) {
    // Log the error (you should implement proper error logging)
    error_log("Error deleting behavior record: " . $e->getMessage());
    
    // Redirect with error message
    header('Location: dashboard.php?tab=behavior&error=database_error');
    exit;
}

?>