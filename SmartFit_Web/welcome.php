<?php
session_start();
require_once 'common.php'; // Assumes this includes PDO connection ($pdo), $user_id, $today, $user['level'], $user['weight'], and targets

// Fetch today's progress
$stmt = $pdo->prepare("
    SELECT movement_type, SUM(reps) as total_reps
    FROM workout_logs
    WHERE user_id = ? AND DATE(created_at) = ?
    GROUP BY movement_type
");
$stmt->execute([$user_id, $today]);
$today_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize progress
$progress_pushups = 0;
$progress_situps = 0;
$progress_squatjumps = 0;

foreach ($today_logs as $log) {
    switch ($log['movement_type']) {
        case 'pushup':
            $progress_pushups = $log['total_reps'];
            break;
        case 'situp':
            $progress_situps = $log['total_reps'];
            break;
        case 'squatjump':
            $progress_squatjumps = $log['total_reps'];
            break;
    }
}

// Calculate progress percentages
$pushup_percentage = $target_pushups > 0 ? min(($progress_pushups / $target_pushups) * 100, 100) : 0;
$situp_percentage = $target_situps > 0 ? min(($progress_situps / $target_situps) * 100, 100) : 0;
$squatjump_percentage = $target_squatjumps > 0 ? min(($progress_squatjumps / $target_squatjumps) * 100, 100) : 0;

// Adjust MET values based on user level
$level = isset($user['level']) ? $user['level'] : 1; // Default to 1 (Beginner) if not set
$met_adjustment = 1 + ($level - 1) * 0.2; // Increase MET by 20% per level (e.g., Level 1: 1.0, Level 2: 1.2, Level 3: 1.4)

$met_pushups = 0.8 * $met_adjustment; // Base MET for push-ups
$met_situps = 0.8 * $met_adjustment; // Base MET for sit-ups (assuming same as push-ups)
$met_squatjumps = 0.8 * $met_adjustment; // Base MET for squat jumps
$met_armcircles = 0.6 * $met_adjustment; // Base MET for arm circles
$armcircles = 0; // No arm circles data provided, set to 0

// Use user's weight from profile, default to 65 kg if not set
$user_weight = isset($user['weight']) && $user['weight'] !== 'N/A' ? $user['weight'] : 65;

// Calculate calories burned
$calories = (
    (($armcircles * 4 / 60) * $met_armcircles * 3.5 * $user_weight) / 200 +
    (($progress_pushups * 2 / 60) * $met_pushups * 3.5 * $user_weight) / 200 +
    (($progress_situps * 2 / 60) * $met_situps * 3.5 * $user_weight) / 200 +
    (($progress_squatjumps * 4 / 60) * $met_squatjumps * 3.5 * $user_weight) / 200
);
$calories = number_format($calories, 2);

// Adjust calorie target based on user level
$calorie_target = 500 + ($level - 1) * 100; // e.g., Level 1: 500 kcal, Level 2: 600 kcal, Level 3: 700 kcal
$calorie_percentage = $calories > 0 ? min(($calories / $calorie_target) * 100, 100) : 0;

// Save calories to users table
$stmt = $pdo->prepare("
    UPDATE users 
    SET calories_burned = ? 
    WHERE id = ?
");
$stmt->execute([$calories, $user_id]);

// Fetch last 5 days of workout data
$last_5_days_data = [];
$dates = [];
$pushups_data = [];
$situps_data = [];
$squatjumps_data = [];

$stmt = $pdo->prepare("
    SELECT DATE(created_at) as workout_date, movement_type, SUM(reps) as total_reps
    FROM workout_logs
    WHERE user_id = ? AND DATE(created_at) >= DATE_SUB(?, INTERVAL 4 DAY)
    GROUP BY DATE(created_at), movement_type
    ORDER BY workout_date ASC
");
$stmt->execute([$user_id, $today]);
$graph_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$start_date = date('Y-m-d', strtotime($today . ' -4 days'));
for ($i = 0; $i < 5; $i++) {
    $current_date = date('Y-m-d', strtotime($start_date . " +$i days"));
    $dates[] = $current_date;
    $pushups_data[$current_date] = 0;
    $situps_data[$current_date] = 0;
    $squatjumps_data[$current_date] = 0;
}

foreach ($graph_logs as $log) {
    $date = $log['workout_date'];
    switch ($log['movement_type']) {
        case 'pushup':
            $pushups_data[$date] = $log['total_reps'];
            break;
        case 'situp':
            $situps_data[$date] = $log['total_reps'];
            break;
        case 'squatjump':
            $squatjumps_data[$date] = $log['total_reps'];
            break;
    }
}

$pushups_array = array_values($pushups_data);
$situps_array = array_values($situps_data);
$squatjumps_array = array_values($squatjumps_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - SMARTFIT</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
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
            padding: 1rem 1rem;
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
        .progress-bar {
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #00ddeb, #ff007a);
            transition: width 0.5s ease;
        }
        .calorie-chart-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 1rem auto;
        }
        .calorie-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            font-weight: 600;
            color: #1f2937;
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
            .navbar-bottom a {
                display: flex;
                flex-direction: column;
                align-items: center;
                font-size: 0.75rem;
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
                <a href="welcome.php" class="text-white active">Dashboard</a>
                <a href="realtime.php" class="text-white">Realtime</a>
                <a href="history.php" class="text-white">History</a>
                <a href="profile.php" class="text-white">Profile</a>
                <a href="logout.php" class="text-white">Logout</a>
            </div>
        </div>
    </nav>
    <!-- Bottom Navbar (Mobile) -->
    <nav class="navbar-bottom flex justify-around items-center md:hidden">
        <a href="welcome.php" data-page="welcome" class="active">
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
        <a href="profile.php" data-page="profile">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            Profile
        </a>
        <a href="logout.php" data-page="logout">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Logout
        </a>
    </nav>
    
    <div class="container mx-auto">
        <!-- User Info -->
        <div class="card mb-6">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
            <p class="text-gray-600 mb-6">Level: <?php echo $user['level']; ?> | Today's Targets: <?php echo $target_pushups; ?> Push-ups, <?php echo $target_situps; ?> Sit-ups, <?php echo $target_squatjumps; ?> Squat Jumps (Date: <?php echo $today; ?>)</p>
        </div>
        <!-- Today's Mission -->
        <div class="card mb-6">
            <div class="grid grid-cols-1">
                <div class="text-left">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Today's Mission</h3>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Push-ups: <?php echo $progress_pushups; ?> / <?php echo $target_pushups; ?></label>
                        <div class="progress-bar"><div class="progress-fill" id="pushupProgress" style="width: <?php echo $pushup_percentage; ?>%;"></div></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Sit-ups: <?php echo $progress_situps; ?> / <?php echo $target_situps; ?></label>
                        <div class="progress-bar"><div class="progress-fill" id="situpProgress" style="width: <?php echo $situp_percentage; ?>%;"></div></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Squat Jumps: <?php echo $progress_squatjumps; ?> / <?php echo $target_squatjumps; ?></label>
                        <div class="progress-bar"><div class="progress-fill" id="squatjumpProgress" style="width: <?php echo $squatjump_percentage; ?>%;"></div></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Calories Burned: <span id="todays_calorie"><?php echo $calories; ?></span> / <?php echo $calorie_target; ?> kcal (Level <?php echo $level; ?>)</label>
                        <div class="calorie-chart-container">
                            <canvas id="calorieChart"></canvas>
                            <div class="calorie-text">
                                <span id="caloriePercentage"><?php echo round($calorie_percentage); ?>%</span>
                                <div class="text-sm">of daily goal</div>
                            </div>
                        </div>
                    </div>
                    <p id="motivationMessage" class="text-center text-gray-600 mt-4 hidden">Keep pushing! You're almost there!</p>
                </div>
            </div>
        </div>
        <div class="card mb-6">
            <h3 class="text-2xl font-semibold text-gray-800 mb-4">Progress Graph</h3>
            <canvas id="progressChart" width="1080" height="720"></canvas>
            <p id="progressChartDebug" class="text-gray-600 text-sm mt-2 hidden">No data available for the past 5 days.</p>
        </div>
    </div>
    <script>
        // GSAP animations
        gsap.from(".navbar-top, .navbar-bottom", { opacity: 0, y: -50, duration: 1, ease: "power3.out" });
        gsap.from(".card", { opacity: 0, y: 50, duration: 1.2, stagger: 0.3, ease: "power3.out" });
        gsap.from(".calorie-chart-container", { opacity: 0, scale: 0.8, duration: 1, ease: "power3.out", delay: 0.5 });

        // Set active navbar item dynamically
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = window.location.pathname.split('/').pop() || 'welcome.php';
            const navLinks = document.querySelectorAll('.navbar-bottom a');
            navLinks.forEach(link => {
                const page = link.getAttribute('data-page') + '.php';
                if (page === currentPage) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });

            // Calorie Progress Chart
            const ctxCalorie = document.getElementById('calorieChart').getContext('2d');
            const calorieChart = new Chart(ctxCalorie, {
                type: 'doughnut',
                data: {
                    labels: ['Calories Burned', 'Remaining'],
                    datasets: [{
                        data: [<?php echo $calories; ?>, <?php echo $calorie_target - $calories; ?>],
                        backgroundColor: ['#00ddeb', '#e2e8f0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });

            // Progress Chart
            const ctxProgress = document.getElementById('progressChart').getContext('2d');
            const pushupsData = <?php echo json_encode($pushups_array); ?>;
            const situpsData = <?php echo json_encode($situps_array); ?>;
            const squatjumpsData = <?php echo json_encode($squatjumps_array); ?>;
            const progressChartDebug = document.getElementById('progressChartDebug');

            // Check if data is empty
            const hasData = pushupsData.some(val => val > 0) || situpsData.some(val => val > 0) || squatjumpsData.some(val => val > 0);
            if (!hasData) {
                progressChartDebug.classList.remove('hidden');
            } else {
                const progressChart = new Chart(ctxProgress, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($dates); ?>,
                        datasets: [
                            { label: 'Push-ups', data: pushupsData, borderColor: '#3b82f6', fill: false, tension: 0.4 },
                            { label: 'Sit-ups', data: situpsData, borderColor: '#10b981', fill: false, tension: 0.4 },
                            { label: 'Squat Jumps', data: squatjumpsData, borderColor: '#ef4444', fill: false, tension: 0.4 }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: { tooltip: { enabled: true }, legend: { position: 'top' } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            // Today's Mission Motivation
            const pushupProgress = <?php echo $progress_pushups; ?>;
            const situpProgress = <?php echo $progress_situps; ?>;
            const squatjumpProgress = <?php echo $progress_squatjumps; ?>;
            const targetPushups = <?php echo $target_pushups; ?>;
            const targetSitups = <?php echo $target_situps; ?>;
            const targetSquatjumps = <?php echo $target_squatjumps; ?>;
            const motivationMessage = document.getElementById('motivationMessage');

            function checkProgress() {
                if (pushupProgress >= targetPushups && situpProgress >= targetSitups && squatjumpProgress >= targetSquatjumps) {
                    motivationMessage.textContent = "Great job! You've completed today's mission!";
                    motivationMessage.classList.remove('hidden', 'text-gray-600');
                    motivationMessage.classList.add('text-green-600', 'font-semibold');
                } else if (pushupProgress > 0 || situpProgress > 0 || squatjumpProgress > 0) {
                    motivationMessage.textContent = "Keep pushing! You're almost there!";
                    motivationMessage.classList.remove('hidden', 'text-green-600');
                    motivationMessage.classList.add('text-gray-600');
                } else {
                    motivationMessage.classList.add('hidden');
                }
            }

            checkProgress();

            // Update calorie display
            document.getElementById('todays_calorie').textContent = <?php echo json_encode($calories); ?>;
        });
    </script>
</body>
</html>