<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session to access user data
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Include database connection
require_once 'config/db.php';

// Get JSON input from request
$input = json_decode(file_get_contents('php://input'), true);
$device_id = $input['device_id'] ?? '';

try {
    // Validate device ID
    if (empty($device_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Device ID is required']);
        exit;
    }

    // Update device ID in the users table
    $stmt = $pdo->prepare("UPDATE users SET id_device = ? WHERE id = ?");
    $stmt->execute([$device_id, $_SESSION['user']['id']]);

    // Check if update was successful
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Device ID saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or user not found']);
    }
} catch (PDOException $e) {
    error_log('Error saving device ID: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>