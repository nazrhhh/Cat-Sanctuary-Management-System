<?php

require_once 'config.php';
require_once 'functions.php';

checkAuth();
checkRole(['Caretaker']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token']);

    try {
        $stmt = $pdo->prepare("UPDATE DailyTasks SET status = 'Completed' WHERE taskID = ?");
        $stmt->execute([$_POST['task_id']]);        

        echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating task: ' . $e->getMessage()]);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit();

?>
