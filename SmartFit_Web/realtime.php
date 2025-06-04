<?php
ini_set('display_errors', 1); // Remove in production
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Load common.php, which handles session_start() and user validation
    require_once 'common.php';

    // Load environment variables
    require_once 'vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Retrieve MQTT configuration from .env
    $mqtt_host = $_ENV['MQTT_HOST'] ?? die('MQTT_HOST not defined');
    // Sanitize MQTT_HOST
    $mqtt_host = trim($mqtt_host, '[]'); // Remove square brackets
    $mqtt_host = preg_replace('#(/mqtt)#', '', $mqtt_host); // Remove /mqtt suffix
    $mqtt_host = explode(':', $mqtt_host)[0]; // Remove any port
    $mqtt_username = $_ENV['MQTT_USERNAME'] ?? die('MQTT_USERNAME not defined');
    $mqtt_password = $_ENV['MQTT_PASSWORD'] ?? die('MQTT_PASSWORD not defined');
    $mqtt_topic_movement = $_ENV['MQTT_TOPIC_MOVEMENT'] ?? die('MQTT_TOPIC_MOVEMENT not defined');
    $mqtt_topic_device_id = 'iot/web/test/device_id';

    // Fetch user's device ID from database
    $stmt = $pdo->prepare("SELECT id_device FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_device_id = $user['id_device'] ?? '';

} catch (Exception $e) {
    error_log('Error in realtime.php: ' . $e->getMessage());
    http_response_code(500);
    die('Server Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realtime Workout - SMARTFIT</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.js"></script>
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
        .input-field {
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            background: #ffffff;
            color: #2d3748;
            padding: 0.75rem;
            border-radius: 0.5rem;
            width: 100%;
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
        .gradient-btn:disabled {
            background: #a0aec0;
            cursor: not-allowed;
            transform: none;
            animation: none;
        }
        .gradient-btn:hover:not(:disabled) {
            transform: scale(1.05);
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 221, 235, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(0, 221, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 221, 235, 0); }
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
        .mqtt-status-text {
            padding: 0.5rem;
            border-radius: 0.25rem;
            text-align: center;
            margin-top: 1rem;
        }
        .mqtt-status-text.connected {
            background-color: #d1fae5;
            color: #065f46;
        }
        .mqtt-status-text.disconnected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .mqtt-status-text.receiving {
            background-color: #bfdbfe;
            color: #1e40af;
        }
        .device-status-text {
            padding: 0.5rem;
            border-radius: 0.25rem;
            text-align: center;
            margin-top: 1rem;
        }
        .device-status-text.connected {
            background-color: #d1fae5;
            color: #065f46;
        }
        .device-status-text.disconnected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .device-status-text.synchronizing {
            background-color: #e0e7ff;
            color: #312e81;
        }
        #workoutIcon {
            transition: opacity 0.5s ease;
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
        .spinner::before {
            content: '⏳';
            display: inline-block;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                <a href="realtime.php" class="text-white active">Realtime</a>
                <a href="history.php" class="text-white">History</a>
                <a href="profile.php" class="text-white">Profile</a>
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
        <a href="realtime.php" data-page="realtime" class="active">
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
        <!-- Realtime Workout Tracker -->
        <div class="card mb-6">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Realtime Workout Tracker</h2>
            <div class="flex justify-center mb-6">
                <img id="workoutIcon" src="assets/dumbell.gif" alt="Workout Icon" class="w-32 h-24">
            </div>
            <div class="grid grid-cols-1">
                <div class="text-left">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Device Setup</h3>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Device ID</label>
                        <input type="text" id="deviceIdInput" class="input-field" value="<?php echo htmlspecialchars($user_device_id); ?>" placeholder="Enter Device ID">
                        <button id="connectDeviceBtn" class="gradient-btn mt-2">Connect Device</button>
                    </div>
                    <div id="deviceStatus" class="device-status-text disconnected">Device Offline</div>
                    <div id="mqttStatus" class="mqtt-status-text disconnected">Disconnected</div>
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4 mt-6">Live Workout Stats</h3>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Push-ups</label>
                        <input type="number" id="pushupInput" class="input-field" value="0" readonly placeholder="0">
                        <div class="progress-bar"><div class="progress-fill" id="pushupProgressRealtime" style="width: 0%;"></div></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Sit-ups</label>
                        <input type="number" id="situpInput" class="input-field" value="0" readonly placeholder="0">
                        <div class="progress-bar"><div class="progress-fill" id="situpProgressRealtime" style="width: 0%;"></div></div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Squat Jumps</label>
                        <input type="number" id="squatjumpInput" class="input-field" value="0" readonly placeholder="0">
                        <div class="progress-bar"><div class="progress-fill" id="squatjumpProgressRealtime" style="width: 0%;"></div></div>
                    </div>
                    <p id="realtimeMotivation" class="text-center text-gray-600 mt-4 hidden">Keep going! You're crushing it!</p>
                </div>
            </div>
            <div class="text-center mt-6">
                <button id="startWorkoutBtn" class="gradient-btn" disabled>Start Workout</button>
            </div>
        </div>
        <div class="card mb-6">
            <h3 class="text-2xl font-semibold text-gray-800 mb-4">Workout Progress</h3>
            <canvas id="workoutChart" width="1080" height="720"></canvas>
        </div>
    </div>
    <script>
    // GSAP animations
    gsap.from(".navbar-top, .navbar-bottom", { opacity: 0, y: -10, duration: 1, ease: "power3.out" });
    gsap.from(".card", { opacity: 0, y: 50, duration: 1.2, stagger: 0.3, ease: "power3.out" });

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

    // Workout Chart
    const ctxWorkout = document.getElementById('workoutChart').getContext('2d');
    const workoutChart = new Chart(ctxWorkout, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'Push-ups', data: [], borderColor: '#3b82f6', fill: false, tension: 0.4, hidden: false },
                { label: 'Sit-ups', data: [], borderColor: '#10b981', fill: false, tension: 0.4, hidden: false },
                { label: 'Squat Jumps', data: [], borderColor: '#ef4444', fill: false, tension: 0.4, hidden: false }
            ]
        },
        options: {
            responsive: true,
            plugins: { tooltip: { enabled: true }, legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // MQTT Configuration
    let client = null;
    let isWorkoutActive = false;
    let isRealtimeActive = false;
    let deviceConnected = false;
    const mqttStatusDiv = document.getElementById('mqttStatus');
    const deviceStatusDiv = document.getElementById('deviceStatus');
    const startWorkoutBtn = document.getElementById('startWorkoutBtn');
    const deviceIdInput = document.getElementById('deviceIdInput');
    const connectDeviceBtn = document.getElementById('connectDeviceBtn');
    const host = "<?php echo htmlspecialchars($mqtt_host, ENT_QUOTES, 'UTF-8'); ?>";
    const topicMovementType = "<?php echo htmlspecialchars($mqtt_topic_movement, ENT_QUOTES, 'UTF-8'); ?>";
    const topicDeviceId = "<?php echo htmlspecialchars($mqtt_topic_device_id, ENT_QUOTES, 'UTF-8'); ?>";
    let currentMovement = 'unknown';
    let movementData = { pushup: 0, situp: 0, squatjump: 0 };
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 3;
    const reconnectDelay = 5000;
    let receivedDeviceId = null;

    function updateMqttStatus(status, message) {
        mqttStatusDiv.textContent = message;
        mqttStatusDiv.classList.remove('connected', 'disconnected', 'receiving');
        mqttStatusDiv.classList.add(status);
    }

    function updateDeviceStatus(status, message) {
        deviceStatusDiv.textContent = message;
        deviceStatusDiv.classList.remove('connected', 'disconnected', 'synchronizing');
        deviceStatusDiv.classList.add(status);
        deviceConnected = (status === 'connected');
        updateStartButton();
    }

    function updateStartButton() {
        const userDeviceId = deviceIdInput.value.trim();
        startWorkoutBtn.disabled = !(deviceConnected && userDeviceId && userDeviceId === receivedDeviceId);
    }

    function startMQTT() {
        if (!window.Paho || !window.Paho.MQTT || !window.Paho.MQTT.Client) {
            console.error("Paho MQTT library not loaded.");
            updateMqttStatus('disconnected', 'MQTT Error: Library not loaded');
            updateDeviceStatus('disconnected', 'Device Offline');
            connectDeviceBtn.disabled = false;
            connectDeviceBtn.textContent = 'Connect Device';
            return;
        }
        const clientId = 'webclient-' + Math.random().toString(16).substr(2, 8);
        client = new Paho.MQTT.Client(host, Number(8884), clientId);

        const options = {
            useSSL: true,
            userName: "<?php echo htmlspecialchars($mqtt_username, ENT_QUOTES, 'UTF-8'); ?>",
            password: "<?php echo htmlspecialchars($mqtt_password, ENT_QUOTES, 'UTF-8'); ?>",
            onSuccess: onConnect,
            onFailure: onFailure,
            timeout: 10
        };

        client.onConnectionLost = onConnectionLost;
        client.onMessageArrived = onMessageArrived;

        console.log("Attempting MQTT connection...");
        updateMqttStatus('disconnected', 'Connecting to MQTT...');
        try {
            client.connect(options);
        } catch (error) {
            console.error("MQTT connect error:", error);
            updateMqttStatus('disconnected', 'MQTT Error: Connection failed');
            updateDeviceStatus('disconnected', 'Device Offline');
            connectDeviceBtn.disabled = false;
            connectDeviceBtn.textContent = 'Connect Device';
        }
    }

    function stopMQTT() {
        if (client && client.isConnected()) {
            client.disconnect();
            console.log("MQTT disconnected.");
        }
        client = null;
        isWorkoutActive = false;
        isRealtimeActive = false;
        reconnectAttempts = 0;
        updateMqttStatus('disconnected', 'Disconnected');
        updateDeviceStatus('disconnected', 'Device Offline');
        movementData = { pushup: 0, situp: 0, squatjump: 0 };
        document.getElementById('pushupInput').value = 0;
        document.getElementById('situpInput').value = 0;
        document.getElementById('squatjumpInput').value = 0;
        document.getElementById('pushupProgressRealtime').style.width = '0%';
        document.getElementById('situpProgressRealtime').style.width = '0%';
        document.getElementById('squatjumpProgressRealtime').style.width = '0%';
        document.getElementById('realtimeMotivation').classList.add('hidden');
        workoutChart.data.labels = [];
        workoutChart.data.datasets.forEach(ds => ds.data = []);
        workoutChart.update();
        currentMovement = 'unknown';
        document.getElementById('workoutIcon').src = 'assets/dumbell.gif';
        receivedDeviceId = null;
        localStorage.removeItem('cachedDeviceId');
        console.log('Cleared cached Device ID');
        updateStartButton();
        connectDeviceBtn.disabled = false;
        connectDeviceBtn.textContent = 'Connect Device';
        deviceIdInput.disabled = false;
        startWorkoutBtn.textContent = 'Start Workout';
    }

    function onConnect() {
        console.log("✅ Connected to HiveMQ Cloud");
        updateMqttStatus('connected', '✓ MQTT Connected');
        client.subscribe(topicDeviceId, { onSuccess: () => console.log("Subscribed:", topicDeviceId) });
        reconnectAttempts = 0;
        connectDeviceBtn.disabled = false;
        connectDeviceBtn.textContent = 'Connect Device';
    }

    function onFailure(error) {
        console.error("❌ Connection failed:", error.errorMessage);
        updateMqttStatus('disconnected', `MQTT Error: ${error.errorMessage}`);
        updateDeviceStatus('disconnected', 'Device Offline');
        attemptReconnect();
        connectDeviceBtn.disabled = false;
        connectDeviceBtn.textContent = 'Connect Device';
    }

    function onConnectionLost(responseObject) {
        if (responseObject.errorCode !== 0) {
            console.error("🔌 Connection lost:", responseObject.errorMessage);
            updateMqttStatus('disconnected', 'Disconnected');
            updateDeviceStatus('disconnected', 'Device Offline');
            attemptReconnect();
            connectDeviceBtn.disabled = false;
            connectDeviceBtn.textContent = 'Connect Device';
        }
    }

    function attemptReconnect() {
        if (reconnectAttempts < maxReconnectAttempts && isWorkoutActive) {
            reconnectAttempts++;
            console.log(`Reconnect attempt ${reconnectAttempts}/${maxReconnectAttempts} in ${reconnectDelay/1000}s`);
            updateMqttStatus('disconnected', `Reconnecting... (${reconnectAttempts}/${maxReconnectAttempts})`);
            setTimeout(() => {
                if (isWorkoutActive && (!client || !client.isConnected())) {
                    startMQTT();
                }
            }, reconnectDelay);
        } else if (reconnectAttempts >= maxReconnectAttempts) {
            console.error("Max reconnect attempts reached.");
            updateMqttStatus('disconnected', 'MQTT Failed: Max reconnect attempts');
            updateDeviceStatus('disconnected', 'Device Offline');
            stopMQTT();
        }
    }

    function onMessageArrived(message) {
        const topic = message.destinationName;
        const payload = message.payloadString.toLowerCase();
        console.log("Received:", topic, payload);

        if (topic === topicDeviceId) {
            receivedDeviceId = payload;
            const userDeviceId = deviceIdInput.value.trim();
            const cachedDeviceId = localStorage.getItem('cachedDeviceId');
            console.log('Input Device ID:', userDeviceId);
            console.log('Cached Device ID:', cachedDeviceId);
            console.log('Received Device ID:', receivedDeviceId);

            if (userDeviceId && cachedDeviceId && userDeviceId === receivedDeviceId && cachedDeviceId === receivedDeviceId) {
                updateDeviceStatus('connected', `Device Connected (ID: ${receivedDeviceId})`);
            } else {
                updateDeviceStatus('disconnected', 'Device ID Mismatch');
            }
            updateStartButton();
            return;
        }

        if (topic !== topicMovementType || !isRealtimeActive) return;

        const maxReps = 100;
        let movementType, inputId, progressId, datasetIndex, chartData;

        if (payload === 'pushup') {
            movementType = 'pushup';
            inputId = 'pushupInput';
            progressId = 'pushupProgressRealtime';
            datasetIndex = 0;
            movementData.pushup++;
            chartData = movementData.pushup;
        } else if (payload === 'situp') {
            movementType = 'situp';
            inputId = 'situpInput';
            progressId = 'situpProgressRealtime';
            datasetIndex = 1;
            movementData.situp++;
            chartData = movementData.situp;
        } else if (payload === 'squatjump') {
            movementType = 'squatjump';
            inputId = 'squatjumpInput';
            progressId = 'squatjumpProgressRealtime';
            datasetIndex = 2;
            movementData.squatjump++;
            chartData = movementData.squatjump;
        } else {
            console.log("Unknown movement type:", payload);
            return;
        }

        currentMovement = movementType;
        updateMqttStatus('receiving', `Movement: ${payload}`);

        // Update UI
        document.getElementById(inputId).value = movementData[movementType];
        document.getElementById(progressId).style.width = `${Math.min((movementData[movementType] / maxReps) * 100, 100)}%`;

        // Update workout icon
        const workoutIcon = document.getElementById('workoutIcon');
        let newSrc;
        switch (movementType) {
            case 'pushup':
                newSrc = 'assets/pushup.gif';
                workoutIcon.alt = 'Push-up Icon';
                workoutChart.data.datasets.forEach((ds, i) => ds.hidden = i !== 0);
                break;
            case 'situp':
                newSrc = 'assets/situp.gif';
                workoutIcon.alt = 'Sit-up Icon';
                workoutChart.data.datasets.forEach((ds, i) => ds.hidden = i !== 1);
                break;
            case 'squatjump':
                newSrc = 'assets/squatjump.gif';
                workoutIcon.alt = 'Squat Jump Icon';
                workoutChart.data.datasets.forEach((ds, i) => ds.hidden = i !== 2);
                break;
            default:
                newSrc = 'assets/dumbell.gif';
                workoutIcon.alt = 'Workout Icon';
                currentMovement = 'unknown';
                workoutChart.data.datasets.forEach(ds => ds.hidden = true);
        }
        gsap.to(workoutIcon, {
            opacity: 0,
            duration: 0.3,
            onComplete: () => {
                workoutIcon.src = newSrc;
                gsap.to(workoutIcon, { opacity: 1, duration: 0.3 });
            }
        });

        // Update chart
        const timeLabel = new Date().toLocaleTimeString('en-US', { hour12: false });
        workoutChart.data.labels.push(timeLabel);
        workoutChart.data.datasets[datasetIndex].data.push(chartData);
        if (workoutChart.data.labels.length > 10) {
            workoutChart.data.labels.shift();
            workoutChart.data.datasets.forEach(ds => ds.data.shift());
        }
        workoutChart.update();

        // Motivation
        checkRealtimeMotivation(movementData[movementType], <?php echo $intensity ?? 0; ?>);

        // Save to database
        fetch('save_workout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ movement_type: movementType, reps: 1, device_id: receivedDeviceId })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to save workout data:', data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function checkRealtimeMotivation(reps, intensity) {
        const motivationMessage = document.getElementById('realtimeMotivation');
        if (reps > 3 || intensity > 3) {
            motivationMessage.textContent = "Keep going! You're crushing it!";
            motivationMessage.classList.remove('hidden', 'text-gray-600');
            motivationMessage.classList.add('text-green-600', 'font-semibold');
        } else if (reps > 0 || intensity > 2) {
            motivationMessage.textContent = "Stay strong! You're doing great!";
            motivationMessage.classList.remove('hidden', 'text-green-600');
            motivationMessage.classList.add('text-gray-600');
        } else {
            motivationMessage.classList.add('hidden');
        }
    }

    // Connect Device (Save Device ID, Cache, and Start MQTT)
    connectDeviceBtn.addEventListener('click', () => {
        const deviceId = deviceIdInput.value.trim();
        if (!deviceId) {
            deviceId = 'default'; // Fallback to a default value if empty
        }
        connectDeviceBtn.disabled = true;
        connectDeviceBtn.innerHTML = '<span class="spinner"></span> Connecting...';
        updateDeviceStatus('synchronizing', 'Synchronizing with Device...');

        // Save Device ID to database
        fetch('save_device_id.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ device_id: deviceId })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Device ID save response:', data);
            // Always proceed, even if save fails
            // Cache the Device ID
            localStorage.setItem('cachedDeviceId', deviceId);
            console.log('Device ID cached in localStorage:', deviceId);
            // Disable input to prevent changes during validation
            deviceIdInput.disabled = true;
            // Start MQTT connection
            isWorkoutActive = true;
            startMQTT();
        })
        .catch(error => {
            console.error('Error saving Device ID:', error);
            // Proceed with caching and MQTT even on error
            localStorage.setItem('cachedDeviceId', deviceId);
            console.log('Device ID cached in localStorage despite error:', deviceId);
            deviceIdInput.disabled = true;
            isWorkoutActive = true;
            startMQTT();
        });
    });

    // Workout Button Logic
    function toggleWorkout(start) {
        if (start) {
            startWorkoutBtn.textContent = 'Stop Workout';
            startWorkoutBtn.classList.add('cursor-not-allowed', 'opacity-75');
            gsap.to(startWorkoutBtn, {
                scale: 1.1,
                duration: 0.3,
                yoyo: true,
                repeat: 1,
                onComplete: () => {
                    isRealtimeActive = true;
                    client.subscribe(topicMovementType, { onSuccess: () => console.log("Subscribed:", topicMovementType) });
                    startWorkoutBtn.disabled = false;
                    startWorkoutBtn.classList.remove('cursor-not-allowed', 'opacity-75');
                }
            });
        } else {
            startWorkoutBtn.textContent = 'Start Workout';
            startWorkoutBtn.classList.remove('cursor-not-allowed', 'opacity-75');
            isRealtimeActive = false;
            if (client && client.isConnected()) {
                client.unsubscribe(topicMovementType, {
                    onSuccess: () => console.log("Unsubscribed:", topicMovementType),
                    onFailure: (err) => console.error("Unsubscribe failed:", err)
                });
            }
            movementData = { pushup: 0, situp: 0, squatjump: 0 };
            document.getElementById('pushupInput').value = 0;
            document.getElementById('situpInput').value = 0;
            document.getElementById('squatjumpInput').value = 0;
            document.getElementById('pushupProgressRealtime').style.width = '0%';
            document.getElementById('situpProgressRealtime').style.width = '0%';
            document.getElementById('squatjumpProgressRealtime').style.width = '0%';
            document.getElementById('realtimeMotivation').classList.add('hidden');
            workoutChart.data.labels = [];
            workoutChart.data.datasets.forEach(ds => ds.data = []);
            workoutChart.update();
            currentMovement = 'unknown';
            document.getElementById('workoutIcon').src = 'assets/dumbell.gif';
            gsap.to(startWorkoutBtn, { scale: 1.0, duration: 0.3 });
            updateStartButton();
        }
    }

    startWorkoutBtn.addEventListener('click', () => {
        if (!startWorkoutBtn.disabled) {
            toggleWorkout(!isRealtimeActive);
        }
    });

    // Initialize device status
    updateDeviceStatus('disconnected', 'Device Offline');
    </script>
</body>
</html>