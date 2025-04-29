<?php
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'errors.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<script>console.log('User Role: " . $_SESSION['role'] . "');</script>";

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// Define the correct default tab for each role
$role = $_SESSION['role'];
$defaultTab = ($role === "Admin") ? "users" : (($role === "Medical") ? "records" : "tasks");

// Force-reset sessionStorage to match PHP on every fresh login
echo "<script>sessionStorage.setItem('activeTab', '$defaultTab');</script>";

// Debugging the selected default tab
echo "<script>console.log('Default Tab: " . $defaultTab . "');</script>";

require_once 'config.php';
require_once 'functions.php';

checkAuth();

// Get data based on user role
try {
    // Common queries for all roles
    $stmt = $pdo->query("SELECT * FROM Cat");
    $cats = $stmt->fetchAll();

    // Role-specific queries
    if ($_SESSION['role'] === 'Admin') {
        // Admin specific queries
        $stmt = $pdo->query("SELECT * FROM users");
        $users = $stmt->fetchAll();

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM Cat");
        $totalCats = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM HealthRecord");
        $activeCases = $stmt->fetch()['total'];

    } 
    elseif ($_SESSION['role'] === 'Medical') {
        // Medical staff specific queries
        $stmt = $pdo->query("SELECT h.*, c.Name as cat_name FROM HealthRecord h 
                            JOIN Cat c ON h.catID = c.catID 
                            ORDER BY h.Date DESC");
        $medicalRecords = $stmt->fetchAll();

        $stmt = $pdo->query("SELECT a.scheduleID, a.appointmentDate, a.Type, a.Status, a.Notes, c.Name as cat_name 
                            FROM MedicalSchedule a 
                            JOIN Cat c ON a.catID = c.catID 
                            ORDER BY a.appointmentDate ASC;");
        $appointments = $stmt->fetchAll();

        $stmt = $pdo->query("
            (SELECT 
                'Feeding' AS activity_type,
                c.Name AS cat_name,
                d.FoodType AS details,
                d.Notes AS notes,
                d.FeedingTime AS activity_time
            FROM DailyCare d
            JOIN Cat c ON d.catID = c.catID
            WHERE d.FeedingTime >= DATE_SUB(NOW(), INTERVAL 14 DAY))

            UNION

            (SELECT 
                'Task' AS activity_type,
                dt.catArea AS cat_name,
                dt.taskName AS details,
                dt.taskType AS notes,
                dt.startTime AS activity_time
            FROM DailyTasks dt
            WHERE dt.startTime >= DATE_SUB(NOW(), INTERVAL 14 DAY))

            ORDER BY activity_time DESC
            LIMIT 20
        ");
        $recentActivities = $stmt->fetchAll();

        $stmt = $pdo->query("
            SELECT 
                activity_date,
                SUM(feeding_count) AS feeding_count,
                SUM(task_count) AS task_count,
                SUM(behavior_count) AS behavior_count
            FROM (
                (SELECT 
                    DATE(FeedingTime) AS activity_date,
                    COUNT(*) AS feeding_count,
                    0 AS task_count,
                    0 AS behavior_count
                FROM DailyCare
                WHERE FeedingTime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(FeedingTime))
                
                UNION ALL
                
                (SELECT 
                    DATE(startTime) AS activity_date,
                    0 AS feeding_count,
                    COUNT(*) AS task_count,
                    0 AS behavior_count
                FROM DailyTasks
                WHERE startTime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(startTime))
                
                UNION ALL
                
                (SELECT 
                    DATE(Date) AS activity_date,
                    0 AS feeding_count,
                    0 AS task_count,
                    COUNT(*) AS behavior_count
                FROM DailyCare
                WHERE Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    AND Behavior IS NOT NULL
                GROUP BY DATE(Date))
            ) AS combined
            GROUP BY activity_date
            ORDER BY activity_date DESC
        ");
        $activityTrends = $stmt->fetchAll();

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM Cat");
        $totalCats = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM HealthRecord");
        $activeCases = $stmt->fetch()['total'];

    }
    elseif ($_SESSION['role'] === 'Caretaker') {
        // Caretaker specific queries
        $stmt = $pdo->query("
            SELECT DISTINCT timePeriod, MIN(startTime) as startTime, MAX(endTime) as endTime 
            FROM DailyTasks 
            GROUP BY timePeriod 
            ORDER BY startTime
        ");
        $timePeriods = $stmt->fetchAll();

        // Fetch all tasks ordered by time period and start time
        $stmt = $pdo->query("
            SELECT * FROM DailyTasks 
            ORDER BY timePeriod, startTime
        ");
        $tasks = $stmt->fetchAll();

        $stmt = $pdo->query("SELECT f.*, c.Name as cat_name 
                            FROM DailyCare f 
                            JOIN Cat c ON f.catID = c.catID 
                            ORDER BY f.FeedingTime DESC");
        $feedingRecords = $stmt->fetchAll();

        $stmt = $pdo->query("SELECT b.*, c.Name as cat_name
                            FROM Behavior b 
                            JOIN Cat c ON b.catID = c.catID 
                            ORDER BY b.Date DESC");
        $behaviorNotes = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['flash'] = "An error occurred while fetching data.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cat Sanctuary</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #dae4f2;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard {
            background: white;
            border-radius: 8px;
            margin-top: 20px;
            padding: 20px;
        }

        h1, h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            color: #666;
            width: auto;
        }

        .tab.active {
            border-bottom: 2px solid #4CAF50;
            color: #4CAF50;
        }

        .tab-content {
            display: none;
            padding: 20px;
        }

        .tab-content.active {
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f5f5f5;
        }

        .no-underline {
            text-decoration: none;
        }

        .action-button {
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            display: inline-block;
        }

        .action-button:hover {
            background: #45a049;
        }

        .delete-button {
            background: #dc3545;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }

        .task-list {
            margin-top: 20px;
        }

        .daily-care {
            padding: 20px;
            max-width: 1000px;
            margin: auto;
        }

        /* Time Periods as Cards */
        .time-periods {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 50px;
            margin-top: 10px;
        }

        .time-box {
            flex: 1;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Task Table */
        .current-tasks {
            text-align: center;
        }

        .complete-button {
            color: blue;
            text-decoration: none;
            font-weight: bold;
        }

        .complete-button:hover {
            text-decoration: underline;
        }

        .completed {
            color: green;
            font-weight: bold;
        }

        .form-check {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Cat Sanctuary Record Tracker</h2>
                <form action="logout.php" method="POST" style="display: inline;">
                    <button type="submit" class="action-button">Logout</button>
                </form>
            </div>
            <div class="d-flex mb-1">
            <h4>Welcome, <?php echo htmlspecialchars($_SESSION['firstName'] . " " . $_SESSION['lastName']); ?>!</h4>
            </div>

            <?php if($_SESSION['role'] === 'Admin'): ?>
            <!-- Admin Dashboard -->
            <div id="adminDashboard">
                <div class="tabs">
                    <button class="tab active" onclick="showTab('users')">User Management</button>
                    <button class="tab" onclick="showTab('cats')">Cat Records</button>
                    <button class="tab" onclick="showTab('reports')">Reports</button>
                </div>

                <!-- Users Tab -->
                <div id="usersTab" class="tab-content active">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>User Management</h2>
                        <button onclick="window.location.href='add_user.php'" class="action-button">Add New User</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Username</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Contact Number</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['Role']); ?></td>
                                <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                <td><?php echo htmlspecialchars($user['firstName']); ?></td>
                                <td><?php echo htmlspecialchars($user['lastName']); ?></td>
                                <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                <td><?php echo htmlspecialchars($user['ContactNumber']); ?></td>
                                <td>
                                    <?php echo ($user['Status'] == 1) ? 'Active' : 'Inactive'; ?>
                                </td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $user['userID']; ?>" class="action-button no-underline">Edit</a>
                                    <form action="delete_user.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['userID']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="action-button delete-button" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Cats Tab -->
                <div id="catsTab" class="tab-content">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Cat Records</h2>
                        <button onclick="window.location.href='add_cat.php'" class="action-button">Add New Cat</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date of Birth</th>
                                <th>Breed</th>
                                <th>Color</th>
                                <th>Gender</th>
                                <th>Register Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cats as $cat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cat['Name']); ?></td>
                                <td><?php echo htmlspecialchars($cat['DateOfBirth']); ?></td>
                                <td><?php echo htmlspecialchars($cat['Breed']); ?></td>
                                <td><?php echo htmlspecialchars($cat['Color']); ?></td>
                                <td><?php echo htmlspecialchars($cat['Gender']); ?></td>
                                <td><?php echo htmlspecialchars($cat['IntakeDate']); ?></td>
                                <td>
                                    <?php echo ($cat['Status'] == 1) ? 'Active' : 'Inactive'; ?>
                                </td>
                                <td>
                                    <a href="edit_cat.php?id=<?php echo $cat['catID']; ?>" class="action-button no-underline">Edit</a>
                                    <form action="delete_cat.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="cat_id" value="<?php echo $cat['catID']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="action-button delete-button" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>

                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Reports Tab -->
                <div id="reportsTab" class="tab-content">
                    <h2>Reports</h2>
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <h3>Total Cats</h3>
                            <div class="stat-value"><?php echo $totalCats; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Active Medical Cases</h3>
                            <div class="stat-value"><?php echo $activeCases; ?></div>
                        </div>
                    </div>
                    <div class="report-actions mt-4">
                        <form action="generate_report.php" method="GET" class="mb-3">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="report_type" class="form-label">Report Type</label>
                                    <select name="report_type" id="report_type" class="form-control">
                                        <option value="dailycare">Daily Care Report</option>
                                        <option value="healthrecord">Health Record Report</option>
                                        <option value="medicalschedule">Medical Schedule Report</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="action-button">Generate Report</button>
                                    <button type="submit" name="export" value="1" class="action-button" style="background-color: #007bff;">Export to CSV</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <?php if(isset($_SESSION['report_data'])): ?>
                    <div class="report-results mt-4">
                        <h3>Report Results</h3>
                        <table>
                            <thead>
                                <tr>
                                    <?php foreach($_SESSION['report_headers'] as $header): ?>
                                    <th><?php echo htmlspecialchars($header); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($_SESSION['report_data'] as $row): ?>
                                <tr>
                                    <?php foreach($row as $value): ?>
                                    <td><?php echo htmlspecialchars($value); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if($_SESSION['role'] === 'Medical'): ?>
            <!-- Medical Staff Dashboard -->
            <div id="medicalDashboard">
                <div class="tabs">
                    <button class="tab active" onclick="showTab('records')">Health Records</button>
                    <button class="tab" onclick="showTab('appointments')">Medical Schedule</button>
                    <button class="tab" onclick="showTab('monitoring')">Care Monitoring</button>
                    <button class="tab" onclick="showTab('medicalreports')">Medical Reports</button>
                </div>

                <!-- Medical Records Tab -->
                <div id="recordsTab" class="tab-content active">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Medical Records</h2>
                        <button onclick="window.location.href='add_medical_record.php'" class="action-button">Add New Record</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Cat Name</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Treatment</th>
                                <th class="col-md-2">Description</th>
                                <th>Medication</th>
                                <th>Next Checkup</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($medicalRecords as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['cat_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['Date']); ?></td>
                                <td><?php echo htmlspecialchars($record['Type']); ?></td>
                                <td><?php echo htmlspecialchars($record['Treatment']); ?></td>
                                <td><?php echo htmlspecialchars($record['Description']); ?></td>
                                <td><?php echo htmlspecialchars($record['Medications']); ?></td>
                                <td><?php echo htmlspecialchars($record['nextCheckup']); ?></td>
                                <td>
                                    <a href="edit_medical_record.php?id=<?php echo $record['recordID']; ?>" class="action-button no-underline">Edit</a>
                                    <form action="delete_medical_record.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="record_id" value="<?php echo $record['recordID']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="action-button delete-button" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Medical Schedule Tab -->
                <div id="appointmentsTab" class="tab-content">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Medical Schedules</h2>
                        <button onclick="window.location.href='add_appointment.php'" class="action-button">Add Appointment</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Cat Name</th>
                                <th>Type</th>
                                <th>Notes</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($appointments as $appointment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appointment['cat_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['Type']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['Notes']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['appointmentDate']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['Status']); ?></td>
                            <td>
                                <a href="edit_appointment.php?id=<?php echo $appointment['scheduleID']; ?>" class="action-button no-underline">Edit</a>
                                <form action="delete_appointment.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['scheduleID']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="action-button delete-button" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Care Monitoring tab -->
                <div id="monitoringTab" class="tab-content">
                    <h2>Care Monitoring</h2>                   
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <h4>Total Cats</h4>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM Cat WHERE Status = 1");
                            $totalActiveCats = $stmt->fetch()['total'];
                            ?>
                            <div class="stat-value"><?php echo $totalActiveCats; ?></div>
                        </div>
                        <div class="stat-card">
                            <h4>Active Treatments</h4>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM HealthRecord WHERE Treatment != '' AND DATEDIFF(NOW(), Date) <= 14");
                            $activeTreatments = $stmt->fetch()['total'];
                            ?>
                            <div class="stat-value"><?php echo $activeTreatments; ?></div>
                        </div>
                        <div class="stat-card">
                            <h4>Today's Scheduled Tasks</h4>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM DailyTasks WHERE DATE(startTime) = CURDATE()");
                            $todayTasks = $stmt->fetch()['total'];
                            ?>
                            <div class="stat-value"><?php echo $todayTasks; ?></div>
                        </div>
                    </div>
                    
                    <!-- Recent Activities -->
                    <div class="mt-4">
                        <h4>Recent Care Activities</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Activity Type</th>
                                    <th>Cat/Area</th>
                                    <th>Details</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentActivities as $activity): ?>
                                <tr>
                                    <td><?php echo date("M d, Y g:i A", strtotime($activity['activity_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['cat_name']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['notes']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(count($recentActivities) == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No recent activities found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Care Activity Trends -->
                    <div class="mt-4">
                        <h4>Care Activity Trends (Last 7 Days)</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Feeding Records</th>
                                    <th>Tasks Completed</th>
                                    <th>Behavior Notes</th>
                                    <th>Total Activities</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($activityTrends as $trend): ?>
                                <tr>
                                    <td><?php echo date("M d, Y", strtotime($trend['activity_date'])); ?></td>
                                    <td><?php echo $trend['feeding_count']; ?></td>
                                    <td><?php echo $trend['task_count']; ?></td>
                                    <td><?php echo $trend['behavior_count']; ?></td>
                                    <td><strong><?php echo $trend['feeding_count'] + $trend['task_count'] + $trend['behavior_count']; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(count($activityTrends) == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No activity data available</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                 <!-- Medical Reports Tab -->
                <div id="medicalreportsTab" class="tab-content">
                    <h2>Medical Reports</h2>
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <h3>Total Cats</h3>
                            <div class="stat-value"><?php echo $totalCats; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Active Medical Cases</h3>
                            <div class="stat-value"><?php echo $activeCases; ?></div>
                        </div>
                    </div>
                    <div class="report-actions mt-4">
                        <form action="generate_report.php" method="GET" class="mb-3">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="report_type" class="form-label">Report Type</label>
                                    <select name="report_type" id="report_type" class="form-control">
                                        <option value="healthrecord">Health Record Report</option>
                                        <option value="medicalschedule">Medical Schedule Report</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="action-button">Generate Report</button>
                                    <button type="submit" name="export" value="1" class="action-button" style="background-color: #007bff;">Export to CSV</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <?php if(isset($_SESSION['report_data'])): ?>
                    <div class="report-results mt-4">
                        <h3>Report Results</h3>
                        <table>
                            <thead>
                                <tr>
                                    <?php foreach($_SESSION['report_headers'] as $header): ?>
                                    <th><?php echo htmlspecialchars($header); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($_SESSION['report_data'] as $row): ?>
                                <tr>
                                    <?php foreach($row as $value): ?>
                                    <td><?php echo htmlspecialchars($value); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if($_SESSION['role'] === 'Caretaker'): ?>
            <!-- Caretaker Dashboard -->
            <div id="caretakerDashboard">
                <div class="tabs">
                    <button class="tab active" onclick="showTab('tasks')">Daily Tasks</button>
                    <button class="tab" onclick="showTab('feeding')">Feeding Records</button>
                    <button class="tab" onclick="showTab('behavior')">Behavior Notes</button>
                </div>

                <!-- Daily Tasks Tab -->
                <div id="tasksTab" class="tab-content active">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Daily Tasks</h2>
                        <button onclick="window.location.href='add_task.php'" class="action-button">Add New Task</button>
                    </div>
                    <div class="daily-care">
                        <div class="time-periods">
                            <?php foreach ($timePeriods as $period): ?>
                                <div class="time-box">
                                    <h3><?php echo htmlspecialchars($period['timePeriod']); ?></h3>
                                    <p><?php echo date("g:i A", strtotime($period['startTime'])); ?> - <?php echo date("g:i A", strtotime($period['endTime'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="current-tasks">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($task['taskName']); ?></td>
                                        <td><?php echo htmlspecialchars($task['catArea']); ?></td>
                                        <td><?php echo htmlspecialchars($task['taskType']); ?></td>
                                        <td><?php echo date("g:i A", strtotime($task['startTime'])); ?> - <?php echo date("g:i A", strtotime($task['endTime'])); ?></td>
                                        <td id="status-<?php echo $task['taskID']; ?>" class="<?php echo ($task['status'] == 'Completed') ? 'completed' : ''; ?>">
                                            <?php if ($task['status'] == 'Completed'): ?>
                                                <span class="completed">Completed</span>
                                            <?php else: ?>
                                                <a href="#" class="complete-button" data-id="<?php echo $task['taskID']; ?>">Pending</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Feeding Records Tab -->
                <div id="feedingTab" class="tab-content">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Feeding Records</h2>
                        <button onclick="window.location.href='add_feeding.php'" class="action-button">Add Feeding Record</button>
                    </div>
                    <table>
                        <thead>
                            <tr class="col-md-3">
                                <th>Cat Name</th>
                                <th>Food Type</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Notes</th>
                                <th>Grooming</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($feedingRecords as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['cat_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['FoodType']); ?></td>
                                <td><?php echo htmlspecialchars($record['FoodAmount']); ?>g</td>
                                <td><?php echo htmlspecialchars($record['Date']); ?></td>
                                <td><?php echo htmlspecialchars($record['FeedingTime']); ?></td>
                                <td><?php echo htmlspecialchars($record['Notes']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($record['Grooming']); ?></td>
                                <td>
                                    <a href="edit_feeding.php?id=<?php echo $record['careID']; ?>" class="action-button no-underline">Edit</a>
                                    <form action="delete_feeding.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="care_id" value="<?php echo $record['careID']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="action-button delete-button" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Behavior Notes Tab -->
                <div id="behaviorTab" class="tab-content">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Behavior Notes</h2>
                        <button onclick="window.location.href='add_behavior.php'" class="action-button">Add Behavior Note</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Cat Name</th>
                                <th>Behaviour Type</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($behaviorNotes as $note): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($note['cat_name']); ?></td>
                                <td><?php echo htmlspecialchars($note['behaviorType']); ?></td>
                                <td><?php echo htmlspecialchars($note['Description']); ?></td>
                                <td><?php echo htmlspecialchars($note['Date']); ?></td>
                                <td><?php echo htmlspecialchars($note['CreatedAt']); ?></td>
                                <td>
                                    <a href="edit_behavior.php?id=<?php echo $note['behaviorID']; ?>" class="action-button no-underline">Edit</a>
                                    <form action="delete_behavior.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="behavior_id" value="<?php echo $note['behaviorID']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="action-button delete-button" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Tab switching functionality
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });

            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            document.getElementById(tabName + 'Tab').style.display = 'block';

            document.querySelectorAll('.tab').forEach(tab => {
                if (tab.getAttribute('onclick').includes(tabName)) {
                    tab.classList.add('active');
                }
            });

            // Store active tab in sessionStorage
            sessionStorage.setItem("activeTab", tabName);
        }

        // Add confirmation for delete actions
        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', function(e) {
                if(!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });
        });

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if(!field.value) {
                        e.preventDefault();
                        alert('Please fill in all required fields');
                        field.focus();
                    }
                });
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            let defaultTab = "<?php echo $defaultTab; ?>"; // Get default from PHP
            let savedTab = sessionStorage.getItem("activeTab"); // Check session storage

            let activeTab = savedTab ? savedTab : defaultTab; // Use stored tab or default
            console.log("Final Tab to Load:", activeTab); // Debugging in console

            showTab(activeTab);
        });

        $(document).ready(function() {
            $(".complete-button").click(function(e) {
                e.preventDefault();  // Prevent page reload
                
                var taskId = $(this).data("id");
                var button = $(this);

                $.post("update_task.php", { task_id: taskId, csrf_token: "<?= $_SESSION['csrf_token'] ?>" }, function(response) {
                    if (response.success) {
                        $("#status-" + taskId).html('<span class="completed">Completed</span>');
                        button.remove();
                    } else {
                        alert(response.message);
                    }
                }, "json");
            });
        });

    </script>
</body>
</html>