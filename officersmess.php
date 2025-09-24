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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Officers Mess - Mini Games</title>
<style>
  body {
    background-color: #001f3f;
    color: white;
    font-family: Arial, sans-serif;
    text-align: center;
    padding: 40px;
  }
  h1 {
    color: gold;
    margin-bottom: 30px;
  }
  .game-links {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 300px;
    margin: 0 auto;
  }
  a.game-link {
    display: block;
    padding: 18px 20px;
    background-color: #003366;
    color: gold;
    text-decoration: none;
    font-size: 18px;
    border-radius: 10px;
    font-weight: bold;
    transition: 0.2s;
  }
  a.game-link:hover {
    background-color: #004080;
    transform: scale(1.05);
  }
</style>
</head>
<body>
<script>
  // Restrict direct access by level
  if (typeof checkLevelRequirement === 'function') {
    if (!checkLevelRequirement(3)) {
      // checkLevelRequirement will redirect if not allowed
      throw new Error('Level restriction: redirecting to homepage');
    }
  }
</script>

<h1>Officers Mess - Mini Games</h1>

<div class="game-links">
  <a href="SPsRiddle.php" class="game-link">1. SP's Riddle</a>
  <a href="RussianRoulette.php" class="game-link">2. Russian Roulette</a>
  <a href="spyinterrogation.php" class="game-link">3. Spy Interrogation</a>
</div>

<a href="Homepage.php" style="display:block; margin-top:30px; color: gold; text-decoration:none;">&larr; Back to Homepage</a>

</body>
</html>
