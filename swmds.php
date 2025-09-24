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
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>SWMDs & Wheel Spin - WARNATION</title>
<style>
  body {
    background: #000;
    color: #e6e6e6;
    font-family: Arial, sans-serif;
    padding: 18px;
    text-align: center;
  }
  a.home {
    color: #ffd34d;
    text-decoration: none;
    font-weight: bold;
    display: inline-block;
    margin-bottom: 12px;
  }
  h1 {
    margin: 4px 0 10px 0;
  }
  table {
    margin: 0 auto 20px auto;
    border-collapse: collapse;
    width: 90%;
    max-width: 900px;
  }
  th, td {
    border: 1px solid #444;
    padding: 8px 12px;
    font-size: 14px;
  }
  th {
    background: #222;
    color: #ffd34d;
  }
  td {
    background: #111;
  }
  #wheelResult {
    margin-top: 20px;
    font-size: 18px;
    font-weight: 700;
    min-height: 50px;
    height: 50px;
  }
  button.spin {
    background: #ffd34d;
    border: none;
    color: #000;
    padding: 12px 20px;
    font-weight: bold;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 10px;
  }
  .small {
    font-size: 12px;
    color: #9fb0bf;
    margin-top: 12px;
  }
  #ownedSwmds {
    margin-top: 20px;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
    text-align: left;
  }
  #ownedSwmds h2 {
    color: #ffd34d;
    margin-bottom: 8px;
  }
  #ownedSwmds ul {
    list-style-type: none;
    padding-left: 0;
  }
  #ownedSwmds li {
    background: #111;
    margin-bottom: 6px;
    padding: 8px 12px;
    border-radius: 5px;
    font-size: 14px;
  }
  #swmdTotals {
    margin-top: 12px;
    font-weight: bold;
    color: #ffd34d;
  }

  /* Spin animation */
  #wheel {
    margin: 20px auto 10px auto;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    border: 6px solid #ffd34d;
    position: relative;
    overflow: hidden;
  }
  #wheel .segment {
    position: absolute;
    width: 50%;
    height: 50%;
    background: #222;
    border: 1px solid #444;
    transform-origin: 100% 100%;
  }
  #wheel .segment:nth-child(odd) {
    background: #333;
  }
  #pointer {
    width: 0; 
    height: 0; 
    border-left: 20px solid transparent;
    border-right: 20px solid transparent;
    border-bottom: 30px solid #ffd34d;
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
  }
</style>
</head>
<body>

<a class="home" href="Homepage.php">← HOME</a>
<h1>SWMDs - Strategic War Machines & Defenses</h1>

<table>
  <thead>
    <tr>
      <th>Name</th>
      <th>Attack</th>
      <th>Defense</th>
    </tr>
  </thead>
  <tbody id="swmdTableBody"></tbody>
</table>

<div id="wheel">
  <div id="pointer"></div>
</div>

<button class="spin" id="spinBtn">Spin Wheel (50 Gold)</button>
<div id="wheelResult"></div>
<div class="small" id="playerGoldDisplay"></div>

<div id="ownedSwmds">
  <h2>Your Owned SWMDs</h2>
  <ul id="ownedSwmdList"></ul>
  <p id="swmdTotals"></p>
</div>

<script>
  const SWMDS = [
    {name:"Destroyer", attack:2893, defense:1344},
    {name:"Guardian Shield", attack:812, defense:850},
    {name:"Blazing Cannon", attack:1400, defense:900},
    {name:"Fortress Armor", attack:850, defense:2769},
    {name:"Thunderbolt Missile", attack:1550, defense:810},
    {name:"Aegis Barrier", attack:900, defense:1400},
    {name:"Spectre Cloak", attack:1300, defense:1000},
    {name:"Iron Fist", attack:880, defense:850},
    {name:"Titan Hammer", attack:1500, defense:850},
    {name:"Phantom Lance", attack:800, defense:900}
  ];

  const NOTHING_CHANCE = 0.40; // 40% chance of nothing

  function getPlayerStats() {
    const raw = localStorage.getItem('playerStats');
    if (!raw) return {health:300, energy:500, ammo:20, cash:10000, gold:0, level:1, xp:0, xpNeeded:1500, crit:5, dodge:5, skillPoints:5};
    return JSON.parse(raw);
  }

  function setPlayerStats(stats) {
    localStorage.setItem('playerStats', JSON.stringify(stats));
    updateGoldDisplay();
  }

  function getPlayerGold() { return getPlayerStats().gold || 0; }
  function setPlayerGold(amount) { let stats = getPlayerStats(); stats.gold = amount; setPlayerStats(stats); }
  function updateGoldDisplay() { document.getElementById("playerGoldDisplay").textContent = `Your Gold: ${getPlayerGold()}`; }

  function getOwnedSwmds() {
    const raw = localStorage.getItem("warnation_player_swmds");
    return raw ? JSON.parse(raw) : [];
  }

  function setOwnedSwmds(arr) { localStorage.setItem("warnation_player_swmds", JSON.stringify(arr)); }

  function renderOwnedSwmds() {
    const owned = getOwnedSwmds();
    const list = document.getElementById("ownedSwmdList");
    const totals = document.getElementById("swmdTotals");
    list.innerHTML = "";
    if (owned.length === 0) {
      list.innerHTML = "<li>You do not own any SWMDs yet.</li>";
      totals.textContent = "";
      return;
    }

    let totalAttack = 0, totalDefense = 0;
    const swmdMap = {}; // aggregate duplicates
    owned.forEach(s => {
      if (!swmdMap[s.name]) swmdMap[s.name] = {count:0, attack:0, defense:0};
      swmdMap[s.name].count++;
      swmdMap[s.name].attack += s.attack;
      swmdMap[s.name].defense += s.defense;
    });

    for (let name in swmdMap) {
      const s = swmdMap[name];
      list.innerHTML += `<li><strong>${name}</strong> x${s.count} (ATK: ${s.attack}, DEF: ${s.defense})</li>`;
      totalAttack += s.attack;
      totalDefense += s.defense;
    }
    totals.textContent = `Total Attack: ${totalAttack} | Total Defense: ${totalDefense}`;
  }

  function renderSWMDs() {
    const tbody = document.getElementById("swmdTableBody");
    tbody.innerHTML = "";
    SWMDS.forEach((s) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `<td>${s.name}</td><td>${s.attack}</td><td>${s.defense}</td>`;
      tbody.appendChild(tr);
    });
  }

  const wheel = document.getElementById('wheel');
  const spinBtn = document.getElementById('spinBtn');
  const wheelResult = document.getElementById('wheelResult');

  function spinWheel() {
    const gold = getPlayerGold();
    if (gold < 50) { alert("You need at least 50 Gold to spin the wheel."); return; }

    setPlayerGold(gold - 50);
    wheelResult.textContent = "";
    spinBtn.disabled = true;

    const spins = Math.floor(Math.random() * 3) + 3;
    const segmentCount = SWMDS.length;
    const segmentAngle = 360 / segmentCount;
    const winningIndex = getWinningIndex();
    const totalRotation = spins * 360 + (360 - (winningIndex * segmentAngle) - segmentAngle/2);

    wheel.style.transition = 'transform 4s cubic-bezier(0.33, 1, 0.68, 1)';
    wheel.style.transform = `rotate(${totalRotation}deg)`;

    setTimeout(() => {
      spinBtn.disabled = false;
      wheel.style.transition = 'none';
      wheel.style.transform = `rotate(${360 - (winningIndex * segmentAngle) - segmentAngle/2}deg)`;

      if (Math.random() < NOTHING_CHANCE) {
        wheelResult.textContent = "Sorry, you won nothing this spin.";
        return;
      }

      const swmd = SWMDS[winningIndex];
      let owned = getOwnedSwmds();
      owned.push(swmd); // add regardless of duplicates
      setOwnedSwmds(owned);
      renderOwnedSwmds();
      wheelResult.textContent = `🎉 Congratulations! You won: ${swmd.name} (ATK: ${swmd.attack}, DEF: ${swmd.defense}) and it has been added to your inventory!`;
    }, 4000);
  }

  function getWinningIndex() { return Math.floor(Math.random() * SWMDS.length); }

  renderSWMDs();
  updateGoldDisplay();
  renderOwnedSwmds();

  spinBtn.addEventListener('click', spinWheel);
</script>


</body>
</html>
