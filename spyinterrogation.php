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
<title>Spy Interrogation - WARNATION</title>
<style>
  body {
    background: black;
    color: white;
    font-family: Arial, sans-serif;
    text-align: center;
    padding: 20px;
  }
  h1 { color: red; }
  .spy {
    position: relative;
    width: 300px;
    margin: 20px auto;
  }
  .spy img {
    width: 100%;
  }
  .target {
    position: absolute;
    cursor: pointer;
    border: 2px solid yellow;
    border-radius: 50%;
    opacity: 0.3;
    pointer-events: auto;
  }
  .target:hover { opacity: 0.7; }
  .disabled { pointer-events: none; opacity: 0.1; }
  #log {
    background: #111;
    padding: 10px;
    margin-top: 20px;
    height: 150px;
    overflow-y: auto;
    border: 1px solid #333;
    text-align: left;
  }
  #status { margin: 15px; font-size: 18px; }
  button {
    margin-top: 15px;
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 8px;
  }
</style>
</head>
<body>
  <a href="Homepage.php" style="color:yellow;display:block;margin-bottom:15px;font-weight:bold;">🏠 Back to Homepage</a>
  <h1>Spy Interrogation</h1>
  <p>Throw darts to scare the spy. Reach <b>500 points</b> before your 11 darts run out!</p>

  <div class="spy">
    <img src="https://i.imgur.com/TvnhK2W.png" alt="Spy tied on table"> <!-- Placeholder image -->
    <!-- Targets -->
    <div class="target" style="top:10%;left:40%;width:50px;height:50px;" onclick="throwDart('head')"></div>
    <div class="target" style="top:30%;left:70%;width:60px;height:60px;" onclick="throwDart('arm')"></div>
    <div class="target" style="top:55%;left:40%;width:80px;height:80px;" onclick="throwDart('thighs')"></div>
    <div class="target" style="top:85%;left:45%;width:60px;height:60px;" onclick="throwDart('feet')"></div>
  </div>

  <div id="status">
    🎯 Darts Left: <span id="darts">11</span> | 😱 Scare Points: <span id="points">0</span>
  </div>

  <div id="log"></div>

  <button onclick="restartGame()">Restart</button>

<script>
let darts = 11;
let points = 0;
let gameOver = false;
let cooldown = false;

const targets = {
  head: { chance: 0.40, reward: 100, label: "Head" },
  arm: { chance: 0.55, reward: 70, label: "Arm" },
  thighs: { chance: 0.65, reward: 50, label: "Thighs" },
  feet: { chance: 0.75, reward: 30, label: "Feet" }
};

function log(msg) {
  const logBox = document.getElementById("log");
  logBox.innerHTML += msg + "<br>";
  logBox.scrollTop = logBox.scrollHeight;
}

function updateStatus() {
  document.getElementById("darts").textContent = darts;
  document.getElementById("points").textContent = points;
}

function toggleTargets(disable) {
  document.querySelectorAll(".target").forEach(t => {
    if (disable) {
      t.classList.add("disabled");
    } else {
      t.classList.remove("disabled");
    }
  });
}

function throwDart(target) {
  if (gameOver || cooldown || darts <= 0) return;

  darts--;
  cooldown = true;
  toggleTargets(true);

  let t = targets[target];
  if (Math.random() < t.chance) {
    points += t.reward;
    log(`🎯 Hit the ${t.label}! +${t.reward} points`);
  } else {
    log(`❌ Missed the ${t.label}! No points`);
  }

  updateStatus();

  if (points >= 500) {
    log("<b style='color:lime;'>✅ You scared the spy enough! Interrogation successful!</b>");
    rewardCash(30000);
    gameOver = true;
  } else if (darts <= 0) {
    log("<b style='color:red;'>💀 You ran out of darts. Interrogation failed.</b>");
    gameOver = true;
  }

  setTimeout(() => {
    cooldown = false;
    if (!gameOver) toggleTargets(false);
  }, 3000);
}

function rewardCash(amount) {
  let stats = JSON.parse(localStorage.getItem("playerStats")) || {};
  stats.cash = (stats.cash || 0) + amount;
  localStorage.setItem("playerStats", JSON.stringify(stats));
  log(`<b style='color:yellow;'>💰 You earned $${amount} cash!</b>`);
}

function restartGame() {
  darts = 11;
  points = 0;
  gameOver = false;
  cooldown = false;
  document.getElementById("log").innerHTML = "";
  updateStatus();
  toggleTargets(false);
}
</script>
</body>
</html>
