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

$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$_SESSION['player_id']]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>⛏️ WARNATION - Production</title>
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
  .mines-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
    max-width: 400px;
    margin: 0 auto;
  }
  .mine {
    border: 2px solid white;
    border-radius: 8px;
    padding: 15px;
    background-color: rgba(255, 255, 255, 0.05);
  }
  .mine h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: gold;
  }
  .status {
    margin: 8px 0;
    font-weight: bold;
  }
  button {
    background-color: navy;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
  }
  button:hover:not(:disabled) {
    background-color: gold;
    color: black;
  }
  button:disabled {
    background-color: gray;
    cursor: default;
  }
</style>
</head>
<body>

<h1>⛏️ WARNATION - Production</h1>

<div class="menu">
  <a href="HomePage.php">🏠 Home</a>
  <a href="war.php">WAR</a>
  <a href="missions.php">MISSIONS</a>
  <a href="production.php"><strong>PRODUCTION</strong></a>
  <a href="units.php">UNITS</a>
</div>

<div class="mines-container" id="mines">
  <!-- Mines will load here -->
</div>

<script>
const miningDuration = 30 * 60 * 1000; // 30 minutes in ms

async function loadMines() {
  const res = await fetch("production_actions.php?action=get_mines");
  const mines = await res.json();
  const container = document.getElementById("mines");
  container.innerHTML = "";

  mines.forEach(mine => {
    const div = document.createElement("div");
    div.className = "mine";

    let html = `<h3>Gold Mine ${mine.mine_number}</h3>`;

    const statusId = `status${mine.mine_number}`;

    if (mine.unlocked == 0) {
      const cost = mine.mine_number === 2 ? 500 : 1000;
      html += `<div class="status" id="${statusId}">Locked. Purchase to unlock.</div>
               <button onclick="purchaseMine(${mine.mine_number}, ${cost})">Purchase for ${cost} Gold</button>`;
    } else {
      // unlocked
      if (!mine.mining_start) {
        html += `<div class="status" id="${statusId}">Ready to start mining.</div>
                 <button onclick="startMining(${mine.mine_number})">Start Mining</button>`;
      } else {
        const start = new Date(mine.mining_start);
        const readyAt = new Date(start.getTime() + miningDuration);
        const now = new Date();
        if (now >= readyAt) {
          html += `<div class="status" id="${statusId}">Mining complete! You can claim your gold.</div>
                   <button onclick="claimReward(${mine.mine_number})">Claim Gold</button>`;
        } else {
          const mins = Math.floor((readyAt - now) / 60000);
          const secs = Math.floor(((readyAt - now) % 60000) / 1000);
          html += `<div class="status" id="${statusId}">Mining... Time left: ${mins}m ${secs}s</div>`;
        }
      }
    }

    div.innerHTML = html;
    container.appendChild(div);
  });
}

async function purchaseMine(mine, cost) {
  const res = await fetch("production_actions.php?action=purchase", {
    method: "POST",
    headers: {"Content-Type":"application/x-www-form-urlencoded"},
    body: `mine=${mine}&cost=${cost}`
  });
  const data = await res.json();
  if (data.success) {
    alert(`Gold Mine ${mine} unlocked!`);
    loadMines();
  } else {
    alert(data.error || "Purchase failed");
  }
}

async function startMining(mine) {
  const res = await fetch("production_actions.php?action=start", {
    method: "POST",
    headers: {"Content-Type":"application/x-www-form-urlencoded"},
    body: `mine=${mine}`
  });
  const data = await res.json();
  if (data.success) {
    loadMines();
  } else {
    alert(data.error || "Start mining failed");
  }
}

async function claimReward(mine) {
  const res = await fetch("production_actions.php?action=claim", {
    method: "POST",
    headers: {"Content-Type":"application/x-www-form-urlencoded"},
    body: `mine=${mine}`
  });
  const data = await res.json();
  if (data.success) {
    alert(`You claimed ${data.goldReward} gold from Gold Mine ${mine}!`);
    loadMines();
  } else {
    alert(data.error || "Claim failed");
  }
}

loadMines();
setInterval(loadMines, 1000);
</script>

</body>
</html>
