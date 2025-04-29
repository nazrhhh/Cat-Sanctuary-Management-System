<?php

session_start();
require_once 'config.php';
require_once 'functions.php';

checkAuth();

// Check if user has appropriate role
if (!in_array($_SESSION['role'], ['Admin', 'Medical'])) {
    header("Location: dashboard.php");
    exit();
}

function getDailyCareReport($pdo, $startDate = null, $endDate = null) {
    // Only Admin can access daily care reports
    if ($_SESSION['role'] !== 'Admin') {
        return [];
    }
    
    $query = "SELECT dc.*, c.Name as CatName,
                CONCAT(u.firstName, ' ', u.lastName) as CaretakerName,
                TIME_FORMAT(dc.FeedingTime, '%H:%i') as FormattedFeedingTime
              FROM DailyCare dc
              JOIN Cat c ON dc.CatID = c.CatID
              JOIN users u ON dc.caretakerID = u.UserID";
    
    if ($startDate && $endDate) {
        $query .= " WHERE dc.Date BETWEEN :startDate AND :endDate";
    }
    $query .= " ORDER BY dc.Date DESC";
    
    $stmt = $pdo->prepare($query);
    if ($startDate && $endDate) {
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getHealthRecordReport($pdo, $startDate = null, $endDate = null) {
    // Both Admin and Medical can access health records
    $query = "SELECT hr.*, c.Name as CatName,
                CONCAT(u.firstName, ' ', u.lastName) as StaffName,
                DATE_FORMAT(hr.Date, '%Y-%m-%d %H:%i:%s') as FormattedDate,
                DATE_FORMAT(hr.nextCheckup, '%Y-%m-%d') as FormattedNextCheckup
              FROM HealthRecord hr
              JOIN Cat c ON hr.CatID = c.CatID
              JOIN users u ON hr.StaffID = u.UserID";
    
    if ($startDate && $endDate) {
        $query .= " WHERE hr.Date BETWEEN :startDate AND :endDate";
    }
    $query .= " ORDER BY hr.Date DESC";
    
    $stmt = $pdo->prepare($query);
    if ($startDate && $endDate) {
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMedicalScheduleReport($pdo, $startDate = null, $endDate = null) {
    // Both Admin and Medical can access medical schedules
    $query = "SELECT ms.*, c.Name as CatName,
                CONCAT(u.firstName, ' ', u.lastName) as StaffName,
                DATE_FORMAT(ms.appointmentDate, '%Y-%m-%d %H:%i:%s') as FormattedAppointmentDate
              FROM MedicalSchedule ms
              JOIN Cat c ON ms.CatID = c.CatID
              JOIN users u ON ms.StaffID = u.UserID";
    
    if ($startDate && $endDate) {
        $query .= " WHERE ms.appointmentDate BETWEEN :startDate AND :endDate";
    }
    $query .= " ORDER BY ms.appointmentDate DESC";
    
    $stmt = $pdo->prepare($query);
    if ($startDate && $endDate) {
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle export to CSV
if (isset($_GET['export']) && isset($_GET['report_type'])) {
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    // Check role-specific access for report types
    if ($_SESSION['role'] === 'Medical' && $_GET['report_type'] === 'dailycare') {
        header("Location: dashboard.php");
        exit();
    }
    
    switch ($_GET['report_type']) {
        case 'dailycare':
            $data = getDailyCareReport($pdo, $startDate, $endDate);
            $filename = "dailycare_report_" . date('Y-m-d') . ".csv";
            $headers = ['Date', 'Cat Name', 'Caretaker', 'Feeding Time', 'Food Type', 'Food Amount', 'Behavior', 'Grooming', 'Notes'];
            break;
        case 'healthrecord':
            $data = getHealthRecordReport($pdo, $startDate, $endDate);
            $filename = "health_report_" . date('Y-m-d') . ".csv";
            $headers = ['Date', 'Cat Name', 'Staff Name', 'Type', 'Description', 'Treatment', 'Medications', 'Next Checkup'];
            break;
        case 'medicalschedule':
            $data = getMedicalScheduleReport($pdo, $startDate, $endDate);
            $filename = "medical_schedule_" . date('Y-m-d') . ".csv";
            $headers = ['Appointment Date', 'Cat Name', 'Staff Name', 'Type', 'Status', 'Notes'];
            break;
    }
    
    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        switch ($_GET['report_type']) {
            case 'dailycare':
                fputcsv($output, [
                    $row['Date'],
                    $row['CatName'],
                    $row['CaretakerName'],
                    $row['FormattedFeedingTime'],
                    $row['FoodType'],
                    $row['FoodAmount'],
                    $row['Behavior'],
                    $row['Grooming'] ? 'Yes' : 'No',
                    $row['Notes']
                ]);
                break;
            case 'healthrecord':
                fputcsv($output, [
                    $row['FormattedDate'],
                    $row['CatName'],
                    $row['StaffName'],
                    $row['Type'],
                    $row['Description'],
                    $row['Treatment'],
                    $row['Medications'],
                    $row['FormattedNextCheckup']
                ]);
                break;
            case 'medicalschedule':
                fputcsv($output, [
                    $row['FormattedAppointmentDate'],
                    $row['CatName'],
                    $row['StaffName'],
                    $row['Type'],
                    $row['Status'],
                    $row['Notes']
                ]);
                break;
        }
    }
    fclose($output);
    exit();
}

// Get report data based on filters
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$reportType = $_GET['report_type'] ?? 'healthrecord'; // Default to health record for Medical role

// Check role-specific access for report types
if ($_SESSION['role'] === 'Medical' && $reportType === 'dailycare') {
    header("Location: dashboard.php");
    exit();
}

switch ($reportType) {
    case 'dailycare':
        $reportData = getDailyCareReport($pdo, $startDate, $endDate);
        $_SESSION['report_headers'] = ['Date', 'Cat Name', 'Caretaker', 'Feeding Time', 'Food Type', 'Food Amount', 'Behavior', 'Grooming', 'Notes'];
        break;
    case 'healthrecord':
        $reportData = getHealthRecordReport($pdo, $startDate, $endDate);
        $_SESSION['report_headers'] = ['Date', 'Cat Name', 'Staff Name', 'Type', 'Description', 'Treatment', 'Medications', 'Next Checkup'];
        break;
    case 'medicalschedule':
        $reportData = getMedicalScheduleReport($pdo, $startDate, $endDate);
        $_SESSION['report_headers'] = ['Appointment Date', 'Cat Name', 'Staff Name', 'Type', 'Status', 'Notes'];
        break;
}

$_SESSION['report_data'] = [];
foreach ($reportData as $row) {
    switch ($reportType) {
        case 'dailycare':
            $_SESSION['report_data'][] = [
                $row['Date'],
                $row['CatName'],
                $row['CaretakerName'],
                $row['FormattedFeedingTime'],
                $row['FoodType'],
                $row['FoodAmount'],
                $row['Behavior'],
                $row['Grooming'] ? 'Yes' : 'No',
                $row['Notes']
            ];
            break;
        case 'healthrecord':
            $_SESSION['report_data'][] = [
                $row['FormattedDate'],
                $row['CatName'],
                $row['StaffName'],
                $row['Type'],
                $row['Description'],
                $row['Treatment'],
                $row['Medications'],
                $row['FormattedNextCheckup']
            ];
            break;
        case 'medicalschedule':
            $_SESSION['report_data'][] = [
                $row['FormattedAppointmentDate'],
                $row['CatName'],
                $row['StaffName'],
                $row['Type'],
                $row['Status'],
                $row['Notes']
            ];
            break;
    }
}

// Redirect to appropriate tab based on role
$redirectTab = ($_SESSION['role'] === 'Medical') ? 'medicalreportsTab' : 'reportsTab';
header("Location: dashboard.php#" . $redirectTab);
exit();

?>