<?php
require_once 'common.php';

// Fetch workout history (last 30 days)
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as workout_date, movement_type, SUM(reps) as total_reps
    FROM workout_logs
    WHERE user_id = :user_id AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at), movement_type
    ORDER BY workout_date DESC
");
$stmt->execute(['user_id' => $user_id]);
$history_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize history by date for table display
$history_by_date = [];
foreach ($history_logs as $log) {
    $date = $log['workout_date'];
    if (!isset($history_by_date[$date])) {
        $history_by_date[$date] = [
            'pushup' => 0,
            'situp' => 0,
            'squatjump' => 0
        ];
    }
    $history_by_date[$date][$log['movement_type']] = $log['total_reps'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout History - SMARTFIT</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.2/dist/gsap.min.js"></script>
    <style>
        body {
            background: linear-gradient(45deg, #1a1a2e, #16213e, #0f3460, #1a1a2e);
            background-size: 400%;
            animation: gradientAnimation 15s ease infinite;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            min-height: 100vh;
            position: relative;
            z-index: 0;
        }
        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .navbar-top {
            background: linear-gradient(90deg, rgba(7, 3, 67, 0.8), rgba(14, 0, 214, 0.8));
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            padding: 1rem 2rem;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        .navbar-bottom {
            background: linear-gradient(90deg, rgba(7, 3, 67, 0.8), rgba(14, 0, 214, 0.8));
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.2);
            padding: 0.5rem;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        .navbar-bottom a {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.75rem;
            color: white;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .navbar-bottom a:hover {
            color: #00ddeb;
            transform: scale(1.05);
        }
        .navbar-bottom a.active {
            color: #00ddeb;
            font-weight: 600;
        }
        .navbar-bottom a.active svg {
            stroke: #00ddeb;
        }
        .container {
            padding: 2rem;
            padding-bottom: 5rem;
            z-index: 10;
        }
        .card {
            background: white;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
            padding: 2.5rem;
            border-radius: 1.5rem;
            width: 100%;
            position: relative;
            z-index: 100;
            overflow: hidden;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .history-table th, .history-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .history-table th {
            background: #f7fafc;
            font-weight: 600;
        }
        .history-table tr:hover {
            background: #f1f5f9;
        }
        .voice-btn {
            background: linear-gradient(45deg, #34d399, #059669);
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            color: white;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .voice-btn:hover {
            transform: scale(1.05);
        }
        .voice-btn.off {
            background: linear-gradient(45deg, #ef4444, #b91c1c);
        }
        .status-text {
            color: #4b5563;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        @media (min-width: 768px) {
            .navbar-bottom {
                display: none;
            }
            .container {
                padding-bottom: 2rem;
            }
        }
        @media (max-width: 767px) {
            .navbar-top {
                display: none;
            }
            .history-table {
                font-size: 0.875rem;
            }
            .history-table th, .history-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navbar (Tablet/PC) -->
    <nav class="navbar-top">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-white">SMARTFIT</h1>
            <div class="space-x-6">
                <a href="welcome.php" class="text-white">Dashboard</a>
                <a href="realtime.php" class="text-white">Realtime</a>
                <a href="history.php" class="text-white">History</a>
                <a href="profile.php" class="text-white active">Profile</a>
                <a href="logout.php" class="text-white">Logout</a>
            </div>
        </div>
    </nav>
<!-- Bottom Navbar (Mobile) -->
    <nav class="navbar-bottom flex justify-around items-center md:hidden">
        <a href="welcome.php" data-page="welcome">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Dashboard
        </a>
        <a href="realtime.php" data-page="realtime">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Realtime
        </a>
        <a href="history.php" data-page="history">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            History
        </a>
        <a href="profile.php" data-page="profile" class="active">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            Profile
        </a>
        <a href="logout.php" data-page="logout">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Logout
        </a>
    </nav>
    
    <div class="container mx-auto">
        <!-- Workout History -->
        <div class="card mb-6">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Workout History</h2>
            <?php if (empty($history_by_date)): ?>
                <p class="text-gray-600">No workout history found for the last 30 days.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Push-ups</th>
                                <th>Sit-ups</th>
                                <th>Squat Jumps</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history_by_date as $date => $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($date); ?></td>
                                    <td><?php echo $data['pushup']; ?></td>
                                    <td><?php echo $data['situp']; ?></td>
                                    <td><?php echo $data['squatjump']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // GSAP animations
        gsap.from(".navbar-top, .navbar-bottom", { opacity: 0, y: -50, duration: 1, ease: "power3.out" });
        gsap.from(".card", { opacity: 0, y: 50, duration: 1.2, ease: "power3.out" });
        gsap.from(".history-table tr", { opacity: 0, y: 20, duration: 0.8, stagger: 0.1, ease: "power3.out" });

        // Set active navbar item dynamically
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = window.location.pathname.split('/').pop() || 'welcome.php' || 'history.php' || 'realtime.php';
            const navLinks = document.querySelectorAll('.navbar-bottom a');
            navLinks.forEach(link => {
                const page = link.getAttribute('data-page') + '.php';
                if (page === currentPage) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>