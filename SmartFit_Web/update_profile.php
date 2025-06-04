<?php
session_start();
require_once 'common.php'; // Assumes this includes PDO database connection as $pdo

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $height = filter_input(INPUT_POST, 'height', FILTER_VALIDATE_FLOAT);
    $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate username
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } else {
        // Check if username is already taken (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE name = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Username is already taken.';
        }
    }

    // Validate height
    if ($height !== false && ($height < 50 || $height > 250)) {
        $errors[] = 'Height must be between 50 and 250 cm.';
    }

    // Validate weight
    if ($weight !== false && ($weight < 20 || $weight > 300)) {
        $errors[] = 'Weight must be between 20 and 300 kg.';
    }

    // Validate gender
    if (!empty($gender) && !in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors[] = 'Invalid gender selected.';
    }

    // Validate age
    if ($age !== false && ($age < 13 || $age > 120)) {
        $errors[] = 'Age must be between 13 and 120 years.';
    }

    // Validate password
    if (!empty($password) || !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
    }

    // If no errors, proceed with database update
    if (empty($errors)) {
        try {
            // Prepare update query
            $query = "UPDATE users SET name = ?, height = ?, weight = ?, gender = ?, age = ?";
            $params = [$username, $height ?: null, $weight ?: null, $gender ?: null, $age ?: null];

            // Include password update if provided
            if (!empty($password)) {
                $query .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $query .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            // Update session data
            $_SESSION['user']['name'] = $username;

            $success = 'Profile updated successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - SMARTFIT</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(45deg, #1a1a2e, #16213e, #0f3460, #1a1a2e);
            background-size: 400%;
            animation: gradientAnimation 15s ease infinite;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .container {
            padding: 2rem;
            padding-bottom: 5rem;
        }
        .card {
            background: white;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
            padding: 2.5rem;
            border-radius: 1.5rem;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .gradient-btn {
            background: linear-gradient(45deg, #00ddeb, #ff007a);
            padding: 0.75rem;
            border-radius: 0.5rem;
            width: 100%;
            color: white;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-align: center;
            display: inline-block;
        }
        .gradient-btn:hover {
            transform: scale(1.05);
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 221, 235, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(0, 221, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 221, 235, 0); }
        }
    </style>
</head>
<body>
    <div class="container mx-auto">
        <div class="card">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-6">Update Profile</h2>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <div class="text-center">
                <a href="profile.php" class="gradient-btn">Back to Profile</a>
            </div>
        </div>
    </div>
</body>
</html>