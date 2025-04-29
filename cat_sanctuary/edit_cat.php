<?php

session_start();
require_once 'config.php';
require_once 'functions.php';

checkAuth();

$error = '';
$success = '';
$cat = null;

// Get cat ID from URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$catID = $_GET['id'];

try {
    // Fetch cat data
    $stmt = $pdo->prepare("SELECT * FROM Cat WHERE catID = ?");
    $stmt->execute([$catID]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cat) {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token']);
    
    // Validate input
    $required_fields = ['name', 'intake_date'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $error = "Name and Intake Date are required";
            break;
        }
    }
    
    if (empty($error)) {
        try {
            // Update cat
            // Current problematic code in the update query:
            $sql = "UPDATE Cat SET 
                    Name = ?, 
                    DateOfBirth = ?, 
                    Breed = ?, 
                    Color = ?, 
                    Gender = ?, 
                    IntakeDate = ?, 
                    Status = ? 
                    WHERE catID = ?";

            $params = [
                $_POST['name'],
                !empty($_POST['dob']) ? $_POST['dob'] : NULL,
                $_POST['breed'],
                $_POST['color'],
                $_POST['gender'],
                $_POST['intake_date'],
                ($_POST['status'] === 'Inactive') ? 0 : 1,
                $catID
            ];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $success = "Cat updated successfully";
            
            // Redirect after short delay
            header("refresh:3;url=dashboard.php?tab=cats");
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
    <title>Edit Cat - Cat Sanctuary</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
            margin: 50px auto;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Edit Cat</h2>
                <a href="dashboard.php?tab=cats" class="btn btn-secondary">Back to Dashboard</a>
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
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($cat['Name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="dob" name="dob" 
                                   value="<?php echo htmlspecialchars($cat['DateOfBirth']); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="breed" class="form-label">Breed</label>
                            <input type="text" class="form-control" id="breed" name="breed" 
                                   value="<?php echo htmlspecialchars($cat['Breed']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color" 
                                   value="<?php echo htmlspecialchars($cat['Color']); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo $cat['Gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $cat['Gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="Active" <?php echo ($cat['Status'] == 1) ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($cat['Status'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="intake_date" class="form-label">Intake Date</label>
                            <input type="date" class="form-control" id="intake_date" name="intake_date" 
                                   value="<?php echo htmlspecialchars($cat['IntakeDate']); ?>" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>