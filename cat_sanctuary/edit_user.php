<?php

session_start();
require_once 'config.php';
require_once 'functions.php';

checkAuth();

// Check if user has admin role
if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';
$user = null;

// Get user ID from URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$userId = $_GET['id'];

try {
    // Fetch user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE userID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token']);
    
    // Validate input
    $required_fields = ['username', 'role', 'firstName', 'lastName', 'email', 'contactNumber', 'status'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $error = "All fields are required";
            break;
        }
    }
    
    if (empty($error)) {
        try {
            // Check if username exists (excluding current user)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Username = ? AND userID != ?");
            $stmt->execute([$_POST['username'], $userId]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Username already exists";
            } else {
                // Update user
                $sql = "UPDATE users SET 
                        Username = ?, 
                        Role = ?, 
                        firstName = ?, 
                        lastName = ?, 
                        Email = ?, 
                        ContactNumber = ?, 
                        Status = ?";

                $params = [
                    $_POST['username'],
                    $_POST['role'],
                    $_POST['firstName'],
                    $_POST['lastName'],
                    $_POST['email'],
                    $_POST['contactNumber'],
                    ($_POST['status'] === 'Inactive') ? 0 : 1 
                ];

                // If password is provided, update it
                if (!empty($_POST['password'])) {
                    $sql .= ", Password = ?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }

                $sql .= " WHERE userID = ?";
                $params[] = $userId;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                $success = "User updated successfully";
                
                // Redirect after short delay
                header("refresh:3;url=dashboard.php");
            }
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
    <title>Edit User - Cat Sanctuary</title>
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
                <h2>Edit User</h2>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
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
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['Username']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="Admin" <?php echo $user['Role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="Medical" <?php echo $user['Role'] === 'Medical' ? 'selected' : ''; ?>>Medical Staff</option>
                                <option value="Caretaker" <?php echo $user['Role'] === 'Caretaker' ? 'selected' : ''; ?>>Caretaker</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="Active" <?php echo ($user['Status'] == 1) ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($user['Status'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" 
                                   value="<?php echo htmlspecialchars($user['firstName']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" 
                                   value="<?php echo htmlspecialchars($user['lastName']); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contactNumber" class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" id="contactNumber" name="contactNumber" 
                                   value="<?php echo htmlspecialchars($user['ContactNumber']); ?>" required>
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

?>