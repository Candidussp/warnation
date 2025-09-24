<?php
// Always start session at the top
session_start();

// Connect to database
require 'api/db.php';

// Check if player is logged in
if (!isset($_SESSION['player_id'])) {
    header("Location: login.php");
    exit;
}

// (Optional) Load player data from DB
$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$_SESSION['player_id']]);
$player = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>BLACK MARKET - WARNATION</title>
    <script src="PlayerBrain.js"></script>
 
    <style>
        body {
            background-color: #001f3f;
            color: white;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        h1, h2 {
            text-align: center;
            color: gold;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            margin: 10px 0;
            border-radius: 10px;
        }
        button {
            background-color: gold;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 5px;
        }
        button:hover {
            background-color: orange;
        }
        .stats {
            background: rgba(0, 0, 0, 0.3);
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .stat {
            display: inline-block;
            margin: 0 10px;
        }
        a {
            color: gold;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <a href="Homepage.php">🏠 Return to Homepage</a>
    <h1>BLACK MARKET</h1>

    <!-- Player Stats -->
    <div class="stats">
        <div class="stat">❤️ Health: <span id="statHealth"></span>/<span id="statMaxHealth"></span></div>
        <div class="stat">⚡ Energy: <span id="statEnergy"></span>/<span id="statMaxEnergy"></span></div>
        <div class="stat">🔫 Ammo: <span id="statAmmo"></span>/<span id="statMaxAmmo"></span></div>
        <div class="stat">💵 Cash: <span id="statCash"></span></div>
        <div class="stat">🏅 Gold: <span id="statGold"></span></div>
        <div class="stat">⭐ Level: <span id="statLevel"></span></div>
        <div class="stat">📈 XP: <span id="statXP"></span></div>
    </div>

    <ul>
        <li>
            <strong>AMMUNATION</strong><br />
            Restores 1 ammo for 5 gold<br />
            <button id="buyAmmoBtn">Buy Ammo</button>
        </li>
        <li>
            <strong>HEALTH PACK</strong><br />
            Fully refills your health<br />
            Cost: <span id="healthPackPrice"></span> cash<br />
            <button id="buyHealthBtn">Buy Health Pack</button>
        </li>
        <li>
            <strong>EXP BOOSTER</strong><br />
            Increases XP gained by 30%<br />
            Cost: 200 gold — Lasts 7 days<br />
            <button id="buyExpBoostBtn">Buy EXP Booster</button>
        </li>
        <li>
            <strong>REJUVENATION</strong><br />
            Gain NO XP while active<br />
            Cost: 200 gold — Lasts 7 days<br />
            <button id="buyRejuvenationBtn">Buy Rejuvenation</button>
        </li>
    </ul>

    <script>
    function getPlayerStats() {
        return JSON.parse(localStorage.getItem('playerStats')) || {};
    }

    function savePlayerStats(stats) {
        localStorage.setItem('playerStats', JSON.stringify(stats));
    }

    function updateStatsDisplay() {
        let stats = getPlayerStats();

        // Always recalc max values from PlayerBrain
        if (typeof calculateMaxStats === 'function') {
            let maxStats = calculateMaxStats(stats.level || 1);
            stats.maxHealth = maxStats.health;
            stats.maxEnergy = maxStats.energy;
            stats.maxAmmo = maxStats.ammo;

            // Ensure health/energy/ammo never exceed new max
            stats.health = Math.min(stats.health ?? 0, stats.maxHealth);
            stats.energy = Math.min(stats.energy ?? 0, stats.maxEnergy);
            stats.ammo = Math.min(stats.ammo ?? 0, stats.maxAmmo);

            // Save updated max stats back to storage so all pages sync
            savePlayerStats(stats);
        }

        // Display
        document.getElementById('statHealth').textContent = stats.health ?? 0;
        document.getElementById('statMaxHealth').textContent = stats.maxHealth ?? 0;
        document.getElementById('statEnergy').textContent = stats.energy ?? 0;
        document.getElementById('statMaxEnergy').textContent = stats.maxEnergy ?? 0;
        document.getElementById('statAmmo').textContent = stats.ammo ?? 0;
        document.getElementById('statMaxAmmo').textContent = stats.maxAmmo ?? 0;
        document.getElementById('statCash').textContent = stats.cash ?? 0;
        document.getElementById('statGold').textContent = stats.gold ?? 0;
        document.getElementById('statLevel').textContent = stats.level ?? 1;
        document.getElementById('statXP').textContent = stats.xp ?? 0;

        document.getElementById('healthPackPrice').textContent = calculateHealthPackPrice(stats.level);
    }

    // ✅ Health Pack price formula (3000 base, +7% per level)
    function calculateHealthPackPrice(level) {
        return Math.floor(3000 * Math.pow(1.07, (level ?? 1) - 1));
    }

    function formatTime(ms) {
        if (ms <= 0) return '00:00:00';
        let totalSeconds = Math.floor(ms / 1000);
        let days = Math.floor(totalSeconds / (3600 * 24));
        totalSeconds -= days * 3600 * 24;
        let hours = Math.floor(totalSeconds / 3600);
        let minutes = Math.floor((totalSeconds % 3600) / 60);
        let seconds = totalSeconds % 60;
        return (days > 0 ? days + 'd ' : '') +
            String(hours).padStart(2, '0') + ':' +
            String(minutes).padStart(2, '0') + ':' +
            String(seconds).padStart(2, '0');
    }

    // Timers
    const expBoostTimerDisplay = document.createElement('div');
    expBoostTimerDisplay.style.color = 'lightgreen';
    expBoostTimerDisplay.style.marginTop = '5px';
    expBoostTimerDisplay.style.fontWeight = 'bold';
    document.getElementById('buyExpBoostBtn').parentElement.appendChild(expBoostTimerDisplay);

    const rejuvenationTimerDisplay = document.createElement('div');
    rejuvenationTimerDisplay.style.color = 'lightcoral';
    rejuvenationTimerDisplay.style.marginTop = '5px';
    rejuvenationTimerDisplay.style.fontWeight = 'bold';
    document.getElementById('buyRejuvenationBtn').parentElement.appendChild(rejuvenationTimerDisplay);

    function updateTimers() {
        let stats = getPlayerStats();
        let now = Date.now();

        if (stats.expBoostActiveUntil && stats.expBoostActiveUntil > now) {
            expBoostTimerDisplay.textContent = '⏳ EXP Boost active: ' + formatTime(stats.expBoostActiveUntil - now);
        } else {
            expBoostTimerDisplay.textContent = 'EXP Boost inactive';
        }

        if (stats.rejuvenationActiveUntil && stats.rejuvenationActiveUntil > now) {
            rejuvenationTimerDisplay.textContent = '⏳ Rejuvenation active: ' + formatTime(stats.rejuvenationActiveUntil - now);
        } else {
            rejuvenationTimerDisplay.textContent = 'Rejuvenation inactive';
        }

        setTimeout(updateTimers, 1000);
    }

    // Buying
    document.getElementById('buyAmmoBtn').addEventListener('click', function () {
        let stats = getPlayerStats();
        if (stats.gold >= 5) {
            stats.gold -= 5;
            stats.ammo = Math.min((stats.ammo ?? 0) + 1, stats.maxAmmo);
            savePlayerStats(stats);
            updateStatsDisplay();
            alert('Ammo purchased!');
        } else {
            alert('Not enough gold!');
        }
    });

    document.getElementById('buyHealthBtn').addEventListener('click', function () {
        let stats = getPlayerStats();
        let price = calculateHealthPackPrice(stats.level);
        if (stats.cash >= price) {
            stats.cash -= price;
            stats.health = stats.maxHealth;
            savePlayerStats(stats);
            updateStatsDisplay();
            alert('Health fully restored!');
        } else {
            alert('Not enough cash!');
        }
    });

    document.getElementById('buyExpBoostBtn').addEventListener('click', function () {
        let stats = getPlayerStats();
        if (stats.gold >= 200) {
            stats.gold -= 200;
            stats.expBoostActiveUntil = Date.now() + (7 * 24 * 60 * 60 * 1000);
            savePlayerStats(stats);
            updateStatsDisplay();
            alert('EXP Booster activated for 7 days!');
        } else {
            alert('Not enough gold!');
        }
    });

    document.getElementById('buyRejuvenationBtn').addEventListener('click', function () {
        let stats = getPlayerStats();
        if (stats.gold >= 200) {
            stats.gold -= 200;
            stats.rejuvenationActiveUntil = Date.now() + (7 * 24 * 60 * 60 * 1000);
            savePlayerStats(stats);
            updateStatsDisplay();
            alert('Rejuvenation activated for 7 days!');
        } else {
            alert('Not enough gold!');
        }
    });

    updateStatsDisplay();
    updateTimers();
    setInterval(updateStatsDisplay, 3000);
    </script>

    <a href="swmds.php" style="font-weight:bold; color: gold; display:block; text-align:center; margin-top:20px;">Go to SWMDS</a>
</body>
</html>
