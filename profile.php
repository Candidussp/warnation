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

// Load player data from DB
$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$_SESSION['player_id']]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <script src="PlayerBrain.js"></script>
    <script>
        // Restrict direct access by level
        if (typeof checkLevelRequirement === 'function') {
            if (!checkLevelRequirement(2)) {
                throw new Error('Level restriction: redirecting to homepage');
            }
        }
    </script>

    <title>Player Profile - <?= htmlspecialchars($player['username']) ?> - WARNATION</title>
    <style>
        body { background-color: black; color: white; font-family: Arial, sans-serif; padding: 20px; }
        h1 { text-align: center; }
        .nav { display: flex; justify-content: space-around; background-color: #222; padding: 10px; margin-bottom: 20px; }
        .nav button { background: none; color: yellow; font-weight: bold; border: none; font-size: 18px; cursor: pointer; }
        .nav button.active { text-decoration: underline; color: lime; }
        .section { display: none; }
        .section.active { display: block; }
        .profile-header { font-size: 20px; margin-bottom: 10px; }
        .profile-info { font-size: 16px; background: #333; padding: 10px; border-radius: 10px; width: fit-content; }
        .skill-list, .trophy-list, .achievement-list { list-style: none; padding: 0; margin: 0; }
        .skill-item { margin: 10px 0; display: flex; justify-content: space-between; }
        .upgrade-button { background-color: green; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 5px; }
        .back-home { color: yellow; display: block; margin-bottom: 20px; text-align: left; font-weight: bold; text-decoration: none; }
        .skills-left { margin-bottom: 15px; font-size: 16px; color: lime; }
        .green-bold { color: lime; font-weight: bold; }
    </style>
</head>
<body>
<a href="HomePage.php" class="back-home">← Back to Home</a>

<h1>Player Profile</h1>
<div class="nav">
    <button class="nav-btn active" onclick="showSection('profile', this)">Profile</button>
    <button class="nav-btn" onclick="showSection('skills', this)">Skills</button>
    <button class="nav-btn" onclick="showSection('trophies', this)">Trophies</button>
    <button class="nav-btn" onclick="showSection('achievements', this)">Achievements</button>
</div>

<!-- PROFILE SECTION -->
<div id="profile" class="section active">
    <h2>Profile</h2>
    <div class="profile-info">
        <div class="profile-header">
             <?= htmlspecialchars($player['username']) ?> 
            al.<span id="allianceCount">1</span> 
            Rating <span id="rating">500</span>
        </div>
        <p>Name: <?= htmlspecialchars($player['username']) ?></p>
        <p>Alliances: <span id="allianceList">1</span></p>
        <p>Power Rating: <span id="ratingDisplay">500</span></p>
        <p class="green-bold">Attack: 0</p>
        <p class="green-bold">Defense: 0</p>
        <p>Enemies Killed: 0</p>
        <p>Ears Cut: 0</p>
        <p>Pardoned: 0</p>
        <p>Critical Hit Chance: <span id="profileCrit">5%</span></p>
        <p>Dodge Chance: <span id="profileDodge">5%</span></p>
        <p>Victories: 0</p>
        <p>Deaths: 0</p>
    </div>
</div>

<!-- SKILLS SECTION -->
<div id="skills" class="section">
    <h2>Skills</h2>
    <div class="skills-left">Skills left: <span id="skillsLeft">5</span></div>
    <ul class="skill-list">
        <li class="skill-item">
            Health: <span id="healthStat">300</span>
            <button class="upgrade-button" onclick="upgradeStat('health', 1, 5)">+5</button>
        </li>
        <li class="skill-item">
            Energy: <span id="energyStat">500</span>
            <button class="upgrade-button" onclick="upgradeStat('energy', 1, 5)">+5</button>
        </li>
        <li class="skill-item">
            Ammo Capacity: <span id="ammoStat">20</span>
            <button class="upgrade-button" onclick="upgradeStat('ammo', 2, 1)">+1</button>
        </li>
        <li class="skill-item">
            Critical Hit Chance: <span id="critStat">5%</span>
            <button class="upgrade-button" onclick="upgradeStat('crit', 2, 1)">+1%</button>
        </li>
        <li class="skill-item">
            Dodge Chance: <span id="dodgeStat">5%</span>
            <button class="upgrade-button" onclick="upgradeStat('dodge', 2, 1)">+1%</button>
        </li>
    </ul>
</div>

<!-- TROPHIES SECTION -->
<div id="trophies" class="section">
    <h2>Trophies</h2>
    <ul class="trophy-list">
        <li>First Blood Trophy</li>
        <li>100 Wins Trophy</li>
        <li>Unstoppable Trophy</li>
    </ul>
</div>

<!-- ACHIEVEMENTS SECTION -->
<div id="achievements" class="section">
    <h2>Achievements</h2>
    <ul class="achievement-list">
        <li>First Battle Won</li>
        <li>Level 10 Reached</li>
        <li>Alliance Leader</li>
    </ul>
</div>

<script> 
    let stats = { health: 300, energy: 500, ammo: 20, crit: 5, dodge: 5 };

    function loadPlayerStats() {
        try {
            const savedStats = JSON.parse(localStorage.getItem('playerStats'));

            if (savedStats) {
                stats.health = savedStats.health || 300;
                stats.energy = savedStats.energy || 500;
                stats.ammo = savedStats.ammo || 20;
                stats.crit = savedStats.critChance ?? 5;
                stats.dodge = savedStats.dodgeChance ?? 5;
                stats.skillPoints = savedStats.skillPoints ?? 0;
            } else {
                stats.skillPoints = 0;
            }
        } catch (e) {
            console.error('Failed to load stats from localStorage:', e);
            stats.skillPoints = 0;
        }

        updateDisplay();
    }

    function updateDisplay() {
        document.getElementById('skillsLeft').innerText = stats.skillPoints;
        document.getElementById('healthStat').innerText = stats.health;
        document.getElementById('energyStat').innerText = stats.energy;
        document.getElementById('ammoStat').innerText = stats.ammo;
        document.getElementById('critStat').innerText = stats.crit + '%';
        document.getElementById('dodgeStat').innerText = stats.dodge + '%';

        document.getElementById('profileCrit').innerText = stats.crit + '%';
        document.getElementById('profileDodge').innerText = stats.dodge + '%';
    }

    function upgradeStat(stat, cost, increment) {
        if (stats.skillPoints >= cost) {
            stats.skillPoints -= cost;
            stats[stat] += increment;
            saveStats();
            updateDisplay();
        } else {
            alert('Not enough Skill Points!');
        }
    }

    function saveStats() {
        let player = JSON.parse(localStorage.getItem('playerStats')) || {}; 
        player.health = stats.health;
        player.energy = stats.energy;
        player.ammo = stats.ammo;
        player.critChance = stats.crit;
        player.dodgeChance = stats.dodge;
        player.skillPoints = stats.skillPoints;
        localStorage.setItem('playerStats', JSON.stringify(player));
    }

    function showSection(sectionId, btn) {
        document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
        document.getElementById(sectionId).classList.add('active');
        document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    loadPlayerStats();
</script>

</body>
</html>
