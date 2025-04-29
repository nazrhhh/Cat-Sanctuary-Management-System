<?php

session_start();
require_once 'config.php';
require_once 'functions.php';

checkAuth();

$error = '';
$success = '';

// Get current user's ID from session
$currentUserID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get the feeding record ID from URL
$careID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$careID) {
    header('Location: dashboard.php?tab=feedings');
    exit;
}

// Get all cats
try {
    $stmt = $pdo->prepare("SELECT catID, name FROM Cat");
    $stmt->execute();
    $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching cats: " . $e->getMessage();
    $cats = [];
}

// Get all caretakers
try {
    $stmt = $pdo->prepare("SELECT UserID, CONCAT(firstName, ' ', lastName) AS CaretakerName FROM users WHERE Role = 'Caretaker'");
    $stmt->execute();
    $caretakers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching caretakers: " . $e->getMessage();
    $caretakers = [];
}

// Fetch existing feeding record
try {
    $stmt = $pdo->prepare("SELECT * FROM DailyCare WHERE careID = ?");
    $stmt->execute([$careID]);
    $feeding = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$feeding) {
        header('Location: dashboard.php?tab=feedings');
        exit;
    }
} catch (PDOException $e) {
    $error = "Error fetching feeding record: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token']);
    
    // Validate input
    $required_fields = ['catID', 'caretakerID', 'Date', 'FeedingTime', 'FoodType', 'FoodAmount'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $error = "All required fields must be filled out";
            break;
        }
    }
    
    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("UPDATE DailyCare SET 
                                 catID = ?, 
                                 caretakerID = ?, 
                                 Date = ?, 
                                 FeedingTime = ?, 
                                 FoodType = ?, 
                                 FoodAmount = ?, 
                                 Behavior = ?, 
                                 Grooming = ?, 
                                 Notes = ? 
                                 WHERE careID = ?");
            
            $stmt->execute([
                $_POST['catID'],
                $_POST['caretakerID'],
                $_POST['Date'],
                $_POST['FeedingTime'],
                $_POST['FoodType'],
                $_POST['FoodAmount'],
                $_POST['Behavior'] ?? null,
                isset($_POST['Grooming']) ? 1 : 0,
                $_POST['Notes'] ?? null,
                $careID
            ]);
            
            $success = "Feeding record updated successfully";
            
            // Redirect after short delay
            header("refresh:3;url=dashboard.php?tab=feedings");
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
    <title>Edit Feeding Record - Cat Sanctuary</title>
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
                <h2>Edit Feeding Record</h2>
                <a href="dashboard.php?tab=feedings" class="btn btn-secondary">Back to Dashboard</a>
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
                                <label for="catID">Cat</label>
                                <select id="catID" name="catID" required>
                                    <option value="">Select Cat</option>
                                    <?php foreach ($cats as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['catID']); ?>" 
                                                <?php echo ($cat['catID'] == $feeding['catID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="caretakerID">Caretaker</label>
                                <select id="caretakerID" name="caretakerID" required>
                                    <option value="">Select Caretaker</option>
                                    <?php foreach ($caretakers as $caretaker): ?>
                                        <option value="<?php echo htmlspecialchars($caretaker['UserID']); ?>"
                                                <?php echo ($caretaker['UserID'] == $feeding['caretakerID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($caretaker['CaretakerName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="Date">Date</label>
                                <input type="date" id="Date" name="Date" value="<?php echo htmlspecialchars($feeding['Date']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="FeedingTime">Feeding Time</label>
                                <input type="time" id="FeedingTime" name="FeedingTime" value="<?php echo htmlspecialchars($feeding['FeedingTime']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="FoodType">Food Type</label>
                                <input type="text" id="FoodType" name="FoodType" maxlength="50" value="<?php echo htmlspecialchars($feeding['FoodType']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="FoodAmount">Food Amount (grams)</label>
                                <input type="number" id="FoodAmount" name="FoodAmount" step="0.01" value="<?php echo htmlspecialchars($feeding['FoodAmount']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="Behavior">Behavior</label>
                                <textarea id="Behavior" name="Behavior" rows="3"><?php echo htmlspecialchars($feeding['Behavior'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="Notes">Additional Notes</label>
                                <textarea id="Notes" name="Notes" rows="3"><?php echo htmlspecialchars($feeding['Notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="checkbox-container">
                                <input type="checkbox" id="Grooming" name="Grooming" value="1" <?php echo ($feeding['Grooming'] == 1) ? 'checked' : ''; ?>>
                                <label for="Grooming">Grooming Performed</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit">Update Feeding Record</button>
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

        // Date validation
        document.getElementById('Date').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            
            if (selectedDate > today) {
                alert('Cannot select future dates');
                this.value = '';
            }
        });

        // Food amount validation
        document.getElementById('FoodAmount').addEventListener('change', function() {
            if (this.value <= 0) {
                alert('Food amount must be greater than 0');
                this.value = '';
            }
        });
    </script>
</body>
</html>