<?php

session_start();
require_once 'config.php';
require_once 'functions.php';

checkAuth();
checkRole(['Medical']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token']);
    
    // Validate input
    $required_fields = ['catID', 'userID', 'appointmentDate', 'Type', 'Status', 'Notes'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $error = "All fields are required";
            break;
        }
    }
    
    if (empty($error)) {
        try {
            // Get the staffID from the userID
            $stmt = $pdo->prepare("SELECT userID FROM users WHERE userID = ? AND Role = 'Caretaker'");
            $stmt->execute([$_POST['userID']]);
            $userID = $stmt->fetchColumn();
            
            if (!$userID) {
                $error = "Selected staff member is not a Caretaker";
            } else {
                $stmt = $pdo->prepare("INSERT INTO MedicalSchedule (catID, staffID, appointmentDate, Type, Status, Notes) 
                           VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['catID'],
                    $_POST['userID'], // Using userID as staffID
                    $_POST['appointmentDate'],
                    $_POST['Type'],
                    $_POST['Status'],
                    $_POST['Notes']
                ]);
                
                $success = "Appointment scheduled successfully";
                
                // Redirect after short delay
                header("refresh:3;url=dashboard.php?tab=schedule");
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch list of cats for dropdown
try {
    $stmt = $pdo->query("SELECT catID, Name FROM Cat WHERE Status = 1 ORDER BY Name");
    $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching cats: " . $e->getMessage();
}

// Fetch list of caretakers from users table for dropdown
try {
    $stmt = $pdo->query("SELECT userID, CONCAT(firstName, ' ', lastName) AS StaffName FROM users WHERE Role = 'Caretaker' ORDER BY lastName");
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching staff: " . $e->getMessage();
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$defaultDate = date('Y-m-d\TH:i', strtotime('+1 day 9:00'));

$minTime = date('Y-m-d\TH:i', strtotime('-30 days 08:00'));
$maxTime = date('Y-m-d\TH:i', strtotime('+30 days 17:00'));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Appointment - Cat Sanctuary</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f0f2f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1, h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        button {
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #45a049;
        }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .success {
            color: #155724;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #ddd;
            padding: 15px 20px;
        }

        .card-body {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Schedule Medical Appointment</h2>
                <a href="dashboard.php?tab=schedule" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="catID">Cat Name</label>
                                <select name="catID" id="catID" required>
                                    <option value="">Select Cat</option>
                                    <?php foreach ($cats as $cat): ?>
                                        <option value="<?php echo $cat['catID']; ?>"><?php echo htmlspecialchars($cat['Name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="userID">Assigned Caretaker</label>
                                <select name="userID" id="userID" required>
                                    <option value="">Select Caretaker</option>
                                    <?php foreach ($staff as $member): ?>
                                        <option value="<?php echo $member['userID']; ?>"><?php echo htmlspecialchars($member['StaffName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="appointmentDate">Appointment Date & Time</label>
                                <input type="datetime-local" id="appointmentDate" name="appointmentDate" step="900"
                                    min="<?php echo $minTime; ?>" 
                                    max="<?php echo $maxTime; ?>" 
                                    value="<?php echo $defaultDate; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="Type">Appointment Type</label>
                                <select id="Type" name="Type" required>
                                    <option value="">Select Type</option>
                                    <option value="Checkup">Checkup</option>
                                    <option value="Emergency">Emergency</option>
                                    <option value="Vaccination">Vaccination</option>
                                    <option value="Surgery">Surgery</option>
                                    <option value="Follow-up">Follow-up</option>
                                    <option value="Dental">Dental</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="Status">Appointment Status</label>
                                <select id="Status" name="Status" required>
                                    <option value="">Select Status</option>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="Confirmed">Confirmed</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="Rescheduled">Rescheduled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="Notes">Notes</label>
                                <textarea id="Notes" name="Notes" rows="4" required></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit">Schedule Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
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

        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#appointmentDate", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minTime: "08:00",
                maxTime: "17:00",
                minuteIncrement: 15,
                time_24hr: true,
                minDate: "",
                defaultHour: 9
            });
        });
    </script>
</body>
</html>