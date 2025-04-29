<?php

function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) { 
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}


function checkRole($allowed_roles) {
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: dashboard.php");
        exit();
    }
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
}

function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}   

function generatePDFReport($type, $data, $date) {
    // Implementation for PDF generation
    // This would typically use a library like TCPDF or FPDF
    require_once('tcpdf/tcpdf.php');
    
    $pdf = new TCPDF();
    $pdf->AddPage();
    
    // Add report header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, ucfirst($type) . ' Report - ' . $date, 0, 1, 'C');
    
    // Add report content based on type
    $pdf->SetFont('helvetica', '', 12);
    
    switch($type) {
        case 'monthly':
            // Add monthly statistics
            break;
        case 'medical':
            // Add medical records
            break;
        case 'adoption':
            // Add adoption records
            break;
    }
    
    // Output PDF
    $pdf->Output('report.pdf', 'D');
}

function backupDatabase() {
    global $pdo;
    
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $backup = "";
    
    foreach($tables as $table) {
        $result = $pdo->query("SELECT * FROM {$table}");
        $numColumns = $result->columnCount();
        
        $backup .= "DROP TABLE IF EXISTS {$table};";
        
        $row2 = $pdo->query("SHOW CREATE TABLE {$table}")->fetch(PDO::FETCH_NUM);
        $backup .= "\n\n" . $row2[1] . ";\n\n";
        
        while($row = $result->fetch(PDO::FETCH_NUM)) {
            $backup .= "INSERT INTO {$table} VALUES(";
            for($j=0; $j<$numColumns; $j++) {
                $row[$j] = addslashes($row[$j]);
                $backup .= ($j === 0) ? "'{$row[$j]}'" : ",'{$row[$j]}'";
            }
            $backup .= ");\n";
        }
        
        $backup .= "\n\n\n";
    }
    
    // Save backup file
    $backup_path = 'backups/' . date("Y-m-d-H-i-s") . '_backup.sql';
    file_put_contents($backup_path, $backup);
    
    return $backup_path;
}

?>