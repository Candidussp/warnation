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
<title>SP's Riddle - Officers Mess</title>
<style>
  body {
    background-color: #001f3f;
    color: white;
    font-family: Arial, sans-serif;
    padding: 20px;
    text-align: center;
  }
  h1 { color: gold; }
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
  input[type="text"] { font-size: 24px; padding: 5px; width: 220px; margin-top: 10px; text-align: center; }
  button { font-size: 20px; padding: 8px 15px; margin-left: 10px; cursor: pointer; }
  #closestGuessWrapper { margin: 20px 0; }
  #closestLabel { font-size: 18px; color: gold; margin-bottom: 5px; }
  #closestGuess { font-size: 36px; color: yellow; font-weight: bold; }
  #riddleNumber { font-size: 32px; letter-spacing: 2px; margin: 20px 0; }
  #winnerInfo { margin-top: 20px; font-size: 20px; color: lightgreen; }
  #countdown { font-size: 18px; color: orange; margin-top: 5px; }
  #personalCooldown { font-size: 16px; color: yellow; margin-left: 10px; }
  #guessLog { margin-top: 20px; text-align: left; max-width: 400px; margin-left: auto; margin-right: auto; font-size: 16px; border-top: 1px solid gold; padding-top: 10px; max-height: 200px; overflow-y: auto; }
  a { color: gold; text-decoration: none; display: block; margin-bottom: 15px; }
</style>
</head>
<body>
<a href="Homepage.php">🏠 Return to Homepage</a>

<h1>SP's Riddle</h1>

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

<p>Guess the 6-digit number. Closest guess is displayed below.</p>

<div id="closestGuessWrapper">
  <div id="closestLabel">Closest guess</div>
  <div id="closestGuess">------</div>
</div>

<div id="riddleNumber">******</div>

<div>
  <!-- now allows up to 10 digits -->
  <input type="text" id="playerGuess" maxlength="10" placeholder="0000000000">
  <button onclick="submitGuess()">Guess</button>
  <span id="personalCooldown"></span>
</div>

<div id="winnerInfo"></div>
<div id="countdown"></div>
<div id="guessLog"></div>

<script>
const rewardGold = 50;

// Load player stats
let stats = JSON.parse(localStorage.getItem('playerStats')) || {
  health: 300, maxHealth: 300,
  energy: 500, maxEnergy: 500,
  ammo: 20, maxAmmo: 20,
  cash: 10000, gold: 0,
  xp: 0, level: 1
};

function updateStatsDisplay() {
  document.getElementById('statHealth').textContent = stats.health;
  document.getElementById('statMaxHealth').textContent = stats.maxHealth;
  document.getElementById('statEnergy').textContent = stats.energy;
  document.getElementById('statMaxEnergy').textContent = stats.maxEnergy;
  document.getElementById('statAmmo').textContent = stats.ammo;
  document.getElementById('statMaxAmmo').textContent = stats.maxAmmo;
  document.getElementById('statCash').textContent = stats.cash;
  document.getElementById('statGold').textContent = stats.gold;
  document.getElementById('statLevel').textContent = stats.level;
  document.getElementById('statXP').textContent = stats.xp;
}

function saveStats() {
  localStorage.setItem('playerStats', JSON.stringify(stats));
}

let riddleNumber = null;
let closestGuess = null;
let closestDiff = Infinity;
let winner = null;
const playerCooldowns = {};
let guessLog = [];

let cooldownEnd = parseInt(localStorage.getItem('riddleCooldown')) || 0;
let storedRiddle = localStorage.getItem('riddleNumber');
if(storedRiddle) riddleNumber = storedRiddle;

const guessInput = document.getElementById('playerGuess');
const guessButton = document.querySelector('button');
const personalCooldownEl = document.getElementById('personalCooldown');
const countdownEl = document.getElementById('countdown');

// Generate padded riddle (still 6 digits only)
function generateRiddle() {
  const now = Date.now();
  if(cooldownEnd > now) return;

  riddleNumber = String(Math.floor(Math.random() * 999999) + 1).padStart(6, '0');
  localStorage.setItem('riddleNumber', riddleNumber);

  closestGuess = null;
  closestDiff = Infinity;
  winner = null;

  document.getElementById('riddleNumber').textContent = '******';
  document.getElementById('closestGuess').textContent = '------';
  document.getElementById('winnerInfo').textContent = '';
  countdownEl.textContent = '';
  document.getElementById('guessLog').innerHTML = '';
  guessLog = [];

  localStorage.removeItem('riddleWinner');
  localStorage.removeItem('riddleLog');

  enableInput(true);
}

function submitGuess() {
  const playerName = 'You';
  const now = Date.now();
  if(guessInput.disabled) return;

  const lastGuess = playerCooldowns[playerName] || 0;
  if(now - lastGuess < 30000) return;

  const guess = guessInput.value.trim();

  // Allow up to 10 digits, but enforce range 000001–999999
  if (!/^\d{1,10}$/.test(guess)) {
    return alert('Enter a valid number (up to 10 digits).');
  }
  if (parseInt(guess, 10) < 1 || parseInt(guess, 10) > 999999) {
    return alert('Guess must be between 000001 and 999999.');
  }

  const diff = Math.abs(parseInt(riddleNumber,10) - parseInt(guess,10));
  if(diff < closestDiff) {
    closestDiff = diff;
    closestGuess = guess;
    document.getElementById('closestGuess').textContent = closestGuess;
  }

  // --- FIX: latest guess on top, max 10 entries ---
  guessLog.unshift(`${playerName} guessed ${guess}`);
  if(guessLog.length > 10) guessLog.pop();
  document.getElementById('guessLog').innerHTML = guessLog.join('<br>');

  if(guess === riddleNumber) {
    winner = playerName;
    stats.gold += rewardGold;
    saveStats();
    updateStatsDisplay();

    document.getElementById('winnerInfo').textContent =
      `Winner: ${winner} | Number: ${riddleNumber} | Reward: ${rewardGold} gold`;
    cooldownEnd = now + 3600000;
    localStorage.setItem('riddleCooldown', cooldownEnd);
    localStorage.setItem('riddleWinner', winner);
    localStorage.setItem('riddleLog', JSON.stringify(guessLog));
    startCooldownCountdown();
    enableInput(false);
  }

  playerCooldowns[playerName] = now;
  guessInput.value = '';
}

function enableInput(enable) {
  guessInput.disabled = !enable;
  guessButton.disabled = !enable;
  personalCooldownEl.textContent = '';
}

function updatePersonalCooldown() {
  const playerName = 'You';
  const now = Date.now();
  const lastGuess = playerCooldowns[playerName] || 0;
  const remaining = Math.max(0, 30000 - (now - lastGuess));
  personalCooldownEl.textContent = remaining > 0 && !guessInput.disabled
    ? `Wait ${Math.ceil(remaining/1000)}s`
    : '';
}

function startCooldownCountdown() {
  const interval = setInterval(() => {
    const now = Date.now();
    const remaining = cooldownEnd - now;
    if(remaining <= 0) {
      clearInterval(interval);
      countdownEl.textContent = '';
      generateRiddle();
    } else {
      const hrs = Math.floor(remaining / 3600000);
      const mins = Math.floor((remaining % 3600000) / 60000);
      const secs = Math.floor((remaining % 60000) / 1000);
      countdownEl.textContent = `Next riddle in ${String(hrs).padStart(2,'0')}:${String(mins).padStart(2,'0')}:${String(secs).padStart(2,'0')}`;
    }
  }, 1000);
}

window.onload = () => {
  const storedWinner = localStorage.getItem('riddleWinner');
  const storedLog = JSON.parse(localStorage.getItem('riddleLog') || '[]');
  guessLog = storedLog; // restore saved log (already top-first order)

  const now = Date.now();
  if(storedWinner && cooldownEnd > now) {
    winner = storedWinner;
    document.getElementById('winnerInfo').textContent = `Winner: ${winner} | Reward: ${rewardGold} gold`;
    document.getElementById('riddleNumber').textContent = '******';
    document.getElementById('guessLog').innerHTML = guessLog.join('<br>');
    enableInput(false);
    startCooldownCountdown();
  } else {
    generateRiddle();
  }

  updateStatsDisplay();
  setInterval(updatePersonalCooldown, 1000);
};
</script>
</body>
</html>
