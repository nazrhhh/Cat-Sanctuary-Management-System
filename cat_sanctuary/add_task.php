<?php

session_start();
require_once 'config.php';
require_once 'functions.php';

checkAuth();

$error = '';
$success = '';

// Get current user's ID from session
$currentUserID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get all users with Caretaker role
try {
    $stmt = $pdo->prepare("SELECT UserID, CONCAT(firstName, ' ', lastName) AS CaretakerName FROM Users WHERE Role = 'Caretaker'");
    $stmt->execute();
    $caretakers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching caretakers: " . $e->getMessage();
    $caretakers = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token']);
    
    // Validate input
    $required_fields = ['catArea', 'taskName', 'taskType', 'timePeriod'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $error = "All required fields must be filled out";
            break;
        }
    }
    
    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO DailyTasks (catArea, taskName, taskType, timePeriod, startTime, endTime, status, caretakerID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['catArea'],
                $_POST['taskName'],
                $_POST['taskType'],
                $_POST['timePeriod'],
                $_POST['startTime'],
                $_POST['endTime'],
                'Pending', // Default status is Pending
                $_POST['caretakerID']
            ]);
            
            $success = "Task added successfully";
            
            // Redirect after short delay
            header("refresh:3;url=dashboard.php?tab=tasks");
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Task - Cat Sanctuary</title>
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
            background-color: #f0f2f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            max-width: 800px;
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

        input, select {
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

        .checkbox-container {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .checkbox-container input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Add New Daily Task</h2>
                <a href="dashboard.php?tab=tasks" class="btn btn-secondary">Back to Dashboard</a>
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="catArea">Cat Area</label>
                                <input type="text" id="catArea" name="catArea" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="taskName">Task Name</label>
                                <input type="text" id="taskName" name="taskName" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="taskType">Task Type</label>
                                <select id="taskType" name="taskType" required>
                                    <option value="">Select Task Type</option>
                                    <option value="Cleaning">Cleaning</option>
                                    <option value="Feeding">Feeding</option>
                                    <option value="Monitoring">Monitoring</option>
                                    <option value="Medical">Medical</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="timePeriod">Time Period</label>
                                <select id="timePeriod" name="timePeriod" required>
                                    <option value="">Select Time Period</option>
                                    <option value="Morning">Morning</option>
                                    <option value="Afternoon">Afternoon</option>
                                    <option value="Evening">Evening</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="startTime">Start Time</label>
                                <input type="time" class="form-control" id="startTime" name="startTime" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="endTime">End Time</label>
                                <input type="time" class="form-control" id="endTime" name="endTime" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="caretakerID">Assign to Caretaker</label>
                                <select class="form-control" id="caretakerID" name="caretakerID">
                                    <option value="">Select Caretaker</option>     
                                        <?php foreach ($caretakers as $caretaker): ?>
                                            <option value="<?php echo htmlspecialchars($caretaker['UserID']); ?>">
                                                <?php echo htmlspecialchars($caretaker['CaretakerName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                     </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit">Add Task</button>
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

        // Time validation
        document.getElementById('endTime').addEventListener('change', function() {
            const startTime = document.getElementById('startTime').value;
            const endTime = this.value;
            
            if (startTime && endTime && startTime >= endTime) {
                alert('End time must be after start time');
                this.value = '';
            }
        });
    </script>
</body>
</html>