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
<html lang="en">
<head>
<script src="PlayerBrain.js"></script>
<script>
  // Restrict direct access by level
  if (typeof checkLevelRequirement === 'function') {
    if (!checkLevelRequirement(1)) {
      throw new Error('Level restriction: redirecting to homepage');
    }
  }
</script>
  <meta charset="UTF-8" />
  <title>🗺️ WARNATION - Missions</title>
  <style>
    body {
      background-color: darkblue;
      color: white;
      font-family: Arial, sans-serif;
      padding: 20px;
    }
    h1 {
      text-align: center;
      color: gold;
    }
    .menu {
      margin-bottom: 20px;
    }
    .menu a {
      color: skyblue;
      margin-right: 15px;
      font-weight: bold;
      text-decoration: none;
    }
    .tabs {
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
    }
    .tab-button {
      background-color: navy;
      border: none;
      color: white;
      padding: 10px 20px;
      margin: 0 10px;
      cursor: pointer;
      font-weight: bold;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }
    .tab-button.active,
    .tab-button:hover {
      background-color: gold;
      color: black;
    }
    .mission-list {
      border: 2px solid white;
      padding: 15px;
      min-height: 150px;
      max-width: 600px;
      margin: 0 auto;
      border-radius: 8px;
      background-color: rgba(255, 255, 255, 0.05);
    }
    .mission-item {
      padding: 8px 0;
      border-bottom: 1px solid white;
    }
    .mission-item:last-child {
      border-bottom: none;
    }
  </style>
</head>
<body>

  <h1>🗺️ WARNATION - Missions</h1>

  <div class="menu">
    <a href="HomePage.php">🏠 Home</a>
    <a href="war.php">WAR</a>
    
    <a href="production.php">PRODUCTION</a>
    <a href="units.php">UNITS</a>
    <!-- Add other menu links as needed -->
  </div>

  <div class="tabs">
    <button id="dailyBtn" class="tab-button active">Daily Missions</button>
    <button id="hqBtn" class="tab-button">HQ Missions</button>
  </div>

  <div id="missionList" class="mission-list">
    <!-- Missions will appear here -->
  </div>

<script>
  // Sample missions data
  const dailyMissions = [
    "Collect 100 resources",
    "Win 3 battles",
    "Upgrade a unit",
  ];

  const hqMissions = [
    "Defend the HQ from invasion",
    "Complete 5 daily missions",
    "Recruit 10 soldiers",
  ];

  const missionList = document.getElementById('missionList');
  const dailyBtn = document.getElementById('dailyBtn');
  const hqBtn = document.getElementById('hqBtn');

  function renderMissions(missions) {
    missionList.innerHTML = '';
    missions.forEach(mission => {
      const div = document.createElement('div');
      div.className = 'mission-item';
      div.textContent = mission;
      missionList.appendChild(div);
    });
  }

  dailyBtn.addEventListener('click', () => {
    dailyBtn.classList.add('active');
    hqBtn.classList.remove('active');
    renderMissions(dailyMissions);
  });

  hqBtn.addEventListener('click', () => {
    hqBtn.classList.add('active');
    dailyBtn.classList.remove('active');
    renderMissions(hqMissions);
  });

  // Initial load - show daily missions
  renderMissions(dailyMissions);
</script>

</body>
</html>
