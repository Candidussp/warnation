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
<meta charset="UTF-8" />
<title>Player Profile - WARNATION</title>
<style>
  body {
    background-color: darkblue;
    color: white;
    font-family: Arial, sans-serif;
    padding: 20px;
    max-width: 500px;
    margin: auto;
  }
  h1 {
    text-align: center;
    text-decoration: underline;
  }
  .stat {
    margin: 10px 0;
  }
  a.back-link {
    color: yellow;
    font-weight: bold;
    text-decoration: none;
    display: block;
    margin-top: 20px;
    text-align: center;
  }
</style>
</head>
<body>

<h1>Player Profile</h1>

<div id="profileContent">
  <p>Loading player data...</p>
</div>

<a href="war.php" class="back-link">← Back to War Page</a>

<script>
  function getQueryParam(param) {
    const params = new URLSearchParams(window.location.search);
    return params.get(param);
  }

  function loadPlayerProfile() {
    const playerName = getQueryParam('player');
    const contentDiv = document.getElementById('profileContent');

    if (!playerName) {
      contentDiv.innerHTML = '<p>No player specified.</p>';
      return;
    }

    // For now, only support "YOU" real player profile from localStorage
    if (playerName.toUpperCase() === 'YOU') {
      let playerStats = JSON.parse(localStorage.getItem('playerStats')) || {};
      let ach = JSON.parse(localStorage.getItem('achievements')) || {};

      contentDiv.innerHTML = `
        <div class="stat"><strong>Name:</strong> YOU (Real Player)</div>
        <div class="stat"><strong>Level:</strong> ${playerStats.level || 1}</div>
        <div class="stat"><strong>Health:</strong> ${playerStats.health || 0} / ${playerStats.maxHealth || 300}</div>
        <div class="stat"><strong>Energy:</strong> ${playerStats.energy || 0} / ${playerStats.maxEnergy || 500}</div>
        <div class="stat"><strong>Ammo:</strong> ${playerStats.ammo || 0} / ${playerStats.maxAmmo || 20}</div>
        <div class="stat"><strong>Cash:</strong> $${playerStats.cash || 0}</div>
        <div class="stat"><strong>Gold:</strong> ${playerStats.gold || 0}</div>
        <div class="stat"><strong>XP:</strong> ${playerStats.xp || 0}</div>
        <hr>
        <div class="stat"><strong>Kills:</strong> ${ach.kills || 0}</div>
        <div class="stat"><strong>Deaths:</strong> ${ach.deaths || 0}</div>
        <div class="stat"><strong>Quests Completed:</strong> ${ach.questsCompleted || 0}</div>
        <div class="stat"><strong>Ears Cut:</strong> ${ach.earsCut || 0}</div>
        <div class="stat"><strong>Players Pardoned:</strong> ${ach.playersPardoned || 0}</div>
        <div class="stat"><strong>Victories:</strong> ${ach.victories || 0}</div>
        <div class="stat"><strong>Sanctions Executed:</strong> ${ach.sanctionsExecuted || 0}</div>
        <div class="stat"><strong>Days in Game:</strong> ${ach.daysInGame || 1}</div>
      `;
    } else {
      contentDiv.innerHTML = `<p>Profile for player "${playerName}" not found.</p>`;
    }
  }

  window.onload = loadPlayerProfile;
</script>

</body>
</html>
