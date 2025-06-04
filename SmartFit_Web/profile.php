<?php
session_start();
require_once 'common.php'; // This will handle the initial session check and redirect

// Fetch user data using PDO
$user_id = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT id, name AS username, height, weight, gender, age FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user === false) {
    $user = [
        'username' => 'Unknown',
        'height' => 'N/A',
        'weight' => 'N/A',
        'gender' => 'N/A',
        'age' => 'N/A'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - SMARTFIT</title>
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
        .profile-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .profile-item:last-child {
            border-bottom: none;
        }
        .profile-label {
            font-weight: 600;
            color: #4b5563;
        }
        .profile-value {
            color: #2d3748;
        }
        .input-field {
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            background: #ffffff;
            color: #2d3748;
            padding: 0.5rem;
            border-radius: 0.5rem;
            width: 100%;
            max-width: 150px;
            box-sizing: border-box;
            z-index: 101;
        }
        .input-field:focus {
            border-color: #00ddeb;
            box-shadow: 0 0 15px rgba(0, 221, 235, 0.6);
            outline: none;
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
            z-index: 101;
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
        <!-- User Profile -->
        <div class="card mb-6">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-6">User Profile</h2>
            <form method="POST" action="update_profile.php" class="space-y-4">
                <div class="profile-item">
                    <span class="profile-label">Username</span>
                    <input type="text" name="username" class="input-field" value="<?php echo htmlspecialchars($user['username']); ?>" placeholder="e.g., JohnDoe">
                </div>
                <div class="profile-item">
                    <span class="profile-label">Height (cm)</span>
                    <input type="number" step="0.1" name="height" class="input-field" value="<?php echo $user['height'] !== 'N/A' ? htmlspecialchars($user['height']) : ''; ?>" placeholder="e.g., 175.5">
                </div>
                <div class="profile-item">
                    <span class="profile-label">Weight (kg)</span>
                    <input type="number" step="0.1" name="weight" class="input-field" value="<?php echo $user['weight'] !== 'N/A' ? htmlspecialchars($user['weight']) : ''; ?>" placeholder="e.g., 70.2">
                </div>
                <div class="profile-item">
                    <span class="profile-label">Gender</span>
                    <select name="gender" class="input-field">
                        <option value="" <?php echo $user['gender'] === 'N/A' ? 'selected' : ''; ?>>Select Gender</option>
                        <option value="Male" <?php echo $user['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $user['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $user['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="profile-item">
                    <span class="profile-label">Age (years)</span>
                    <input type="number" name="age" class="input-field" value="<?php echo $user['age'] !== 'N/A' ? htmlspecialchars($user['age']) : ''; ?>" placeholder="e.g., 25">
                </div>
                <div class="profile-item">
                    <span class="profile-label">New Password</span>
                    <input type="password" name="password" class="input-field" placeholder="Enter new password">
                </div>
                <div class="profile-item">
                    <span class="profile-label">Confirm Password</span>
                    <input type="password" name="confirm_password" class="input-field" placeholder="Confirm new password">
                </div>
                <div class="text-center mt-6">
                    <button type="submit" class="gradient-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // GSAP animations
        gsap.from(".navbar-top, .navbar-bottom", { opacity: 0, y: -10, duration: 1, ease: "power3.out" });
        gsap.from(".card", { opacity: 0, y: 50, duration: 1.2, ease: "power3.out" });

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
        });
    </script>
</body>
</html>