<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Calculate mission targets based on user level
$level = $user['level'];
$target_pushups = $level * 10;
$target_situps = $level * 8;
$target_squatjumps = $level * 6;

// Fetch min_pushups, min_situps, min_squatjumps for intensity
$stmt = $pdo->prepare("SELECT min_pushups, min_situps, min_squatjumps FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$mission = $stmt->fetch();

// Calculate intensity (BPM)
$intensity = $mission ? ($mission['min_pushups'] + $mission['min_situps'] + $mission['min_squatjumps']) * 2 : 0;

// Fetch latest workout date
$stmt = $pdo->prepare("SELECT DATE(created_at) as latest_date FROM workout_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$latest_date_row = $stmt->fetch(PDO::FETCH_ASSOC);
$today = $latest_date_row ? $latest_date_row['latest_date'] : date('Y-m-d');
?>