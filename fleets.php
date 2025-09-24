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
<title>UNITS SHOP - WARNATION</title>
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
  .units-wrap {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 24px;
    max-width: 1000px;
    margin: 0 auto 20px;
  }
  .unit-card {
    background: #070707;
    border: 1px solid #333;
    border-radius: 6px;
    padding: 14px 16px;
    width: 300px;
    text-align: left;
  }
  .unit-card h2 {
    margin: 0 0 6px 0;
    color: #ffd34d;
    font-size: 18px;
  }
  .row {
    display: flex;
    justify-content: space-between;
    margin: 6px 0;
  }
  .small {
    font-size: 13px;
    color: #9fb0bf;
  }
  input[type="number"] {
    width: 90px;
    padding: 6px;
    border-radius: 4px;
    border: 1px solid #333;
    background: #070707;
    color: #e6e6e6;
    font-size: 14px;
  }
  button {
    background: #ffd34d;
    border: 0;
    padding: 6px 10px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 700;
    font-size: 14px;
  }
  button:disabled {
    background: #444;
    color: #999;
    cursor: not-allowed;
  }
  #playerStats {
    margin-bottom: 20px;
    font-size: 15px;
  }
  #msg {
    color: #6ff;
    margin-top: 12px;
    min-height: 24px;
  }
</style>
</head>
<body>

<a class="home" href="Homepage.php">← HOME</a>
<h1>UNITS SHOP</h1>

<center><h1> <a href="units.php"><button>BARRACKS</button> </a> <a href="fleets.php">
   <button> WAR VEHICLES</button>
  </a></h1</center>
  
<div id="playerStats">
  Level: <strong id="plLevel">0</strong> &nbsp;|&nbsp;
  Cash: <strong id="plCash">0</strong> &nbsp;|&nbsp;
  Total ATK: <strong id="totalAtk">0</strong> &nbsp;|&nbsp;
  Total DEF: <strong id="totalDef">0</strong>
</div>

<div class="units-wrap" id="unitsWrap"></div>

<div id="msg"></div>

<script>
  // Helper to generate random between min and max inclusive
  function randomBetween(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  }

  // Units data with corrected attack/defense and level requirements
  const unitsData = {
    // Jets - Attack focused
    jet1: { name: "Jet Level 1", atk: 150, def: 30, cost: 10, levelReq: 0 },
    jet2: { name: "Jet Level 2", atk: 300, def: 60, cost: 16, levelReq: 10 },
    jet3: { name: "Jet Level 3", atk: 600, def: 120, cost: 25.6, levelReq: 20 },

    // Tanks - Defense focused (inverted)
    tank1: { name: "Tank Level 1", atk: 30, def: 150, cost: 20, levelReq: 0 },
    tank2: { name: "Tank Level 2", atk: 60, def: 300, cost: 32, levelReq: randomBetween(8, 15) },
    tank3: { name: "Tank Level 3", atk: 120, def: 600, cost: 51.2, levelReq: 20 },

    // Ships - Balanced attack and defense
    ship1: { name: "Ship Level 1", atk: 100, def: 100, cost: 15, levelReq: 0 },
    ship2: { name: "Ship Level 2", atk: 200, def: 200, cost: 24, levelReq: randomBetween(9, 17) },
    ship3: { name: "Ship Level 3", atk: 400, def: 400, cost: 38.4, levelReq: 20 },
  };

  // Storage keys for purchased units
  const STORE_KEY = "warnation_units_v2";

  // Player data keys to read from localStorage
  const PLAYER_KEYS = ["playerStats","warnation_player","player","playerData","warnation_player_v2"];

  // Read player info from localStorage (level, cash)
  function readPlayer() {
    for (const k of PLAYER_KEYS) {
      const raw = localStorage.getItem(k);
      if (!raw) continue;
      try {
        const p = JSON.parse(raw);
        return {
          level: Number(p.level ?? p.lvl ?? p.lv ?? 0),
          cash: Number(p.cash ?? p.money ?? p.coins ?? 0)
        };
      } catch(e){}
    }
    return { level: 0, cash: 0 };
  }

  // Save updated cash for player (attempt best effort)
  function savePlayerCash(newCash) {
    for (const k of PLAYER_KEYS) {
      const raw = localStorage.getItem(k);
      if (!raw) continue;
      try {
        const p = JSON.parse(raw);
        p.cash = newCash;
        localStorage.setItem(k, JSON.stringify(p));
        return true;
      } catch(e){}
    }
    // fallback: save to "playerStats"
    localStorage.setItem("playerStats", JSON.stringify({ level: 0, cash: newCash }));
    return true;
  }

  // Read purchased units count from localStorage
  function readUnits() {
    const raw = localStorage.getItem(STORE_KEY);
    if (!raw) return {};
    try {
      return JSON.parse(raw);
    } catch(e) {
      return {};
    }
  }

  // Save purchased units count to localStorage
  function saveUnits(unitsObj) {
    localStorage.setItem(STORE_KEY, JSON.stringify(unitsObj));
  }

  // Calculate total ATK and DEF from purchased units
  function calcTotals(units) {
    let totalAtk = 0;
    let totalDef = 0;
    for (const id in units) {
      const count = units[id];
      if (!count || count <= 0) continue;
      const unit = unitsData[id];
      if (!unit) continue;
      totalAtk += unit.atk * count;
      totalDef += unit.def * count;
    }
    return { totalAtk, totalDef };
  }

  // Render player stats and units list
  function renderPage() {
    const player = readPlayer();
    const units = readUnits();

    // Update player info display
    document.getElementById("plLevel").textContent = player.level;
    document.getElementById("plCash").textContent = player.cash.toLocaleString();

    // Calculate totals
    const totals = calcTotals(units);
    document.getElementById("totalAtk").textContent = totals.totalAtk.toLocaleString();
    document.getElementById("totalDef").textContent = totals.totalDef.toLocaleString();

    // Update total attack/defense in localStorage for sync across pages
    localStorage.setItem("warnation_totalAttack", JSON.stringify(totals.totalAtk));
    localStorage.setItem("warnation_totalDefense", JSON.stringify(totals.totalDef));

    const wrap = document.getElementById("unitsWrap");
    wrap.innerHTML = "";

    for (const [id, unit] of Object.entries(unitsData)) {
      const countOwned = units[id] || 0;
      const canBuy = player.level >= unit.levelReq;
      const isFree = unit.levelReq === 0;

      // Cost display (rounded cash)
      const costStr = `${Math.round(unit.cost).toLocaleString()} Cash`;

      // Unit card container
      const card = document.createElement("div");
      card.className = "unit-card";

      // Title + level requirement
      let titleHTML = `<h2>${unit.name}</h2>`;
      if (!canBuy && unit.levelReq > 0) {
        titleHTML += `<div class="small" style="color:#f55;">Requires Player Level ${unit.levelReq}</div>`;
      }

      card.innerHTML = titleHTML +
        `<div class="row"><div>Attack</div><div><strong>${unit.atk}</strong></div></div>` +
        `<div class="row"><div>Defense</div><div><strong>${unit.def}</strong></div></div>` +
        `<div class="row"><div>Cost</div><div><strong>${costStr}</strong></div></div>` +

        `<div style="margin-top:10px; display:flex; gap:8px; align-items:center;">` +
          `<input type="number" id="qty_${id}" min="1" value="1" ${canBuy ? "" : "disabled"} />` +
          `<button id="buyBtn_${id}" ${canBuy ? "" : "disabled"}>Buy</button>` +
        `</div>` +

        `<div class="small" style="margin-top:6px;">Owned: <strong id="owned_${id}">${countOwned}</strong></div>`;

      wrap.appendChild(card);

      // Attach event listener for buy button
      const buyBtn = card.querySelector(`#buyBtn_${id}`);
      const qtyInput = card.querySelector(`#qty_${id}`);

      buyBtn.addEventListener("click", () => {
        const qty = Math.floor(Number(qtyInput.value));
        if (isNaN(qty) || qty < 1) {
          showMessage("Please enter a valid amount to buy.");
          return;
        }
        if (!canBuy) {
          showMessage(`You do not meet the player level requirement (${unit.levelReq}) to buy this unit.`);
          return;
        }
        const totalCost = unit.cost * qty;
        if (player.cash < totalCost) {
          showMessage("You do not have enough Cash for this purchase.");
          return;
        }

        // Deduct cash and add units
        player.cash -= totalCost;
        savePlayerCash(player.cash);

        units[id] = (units[id] || 0) + qty;
        saveUnits(units);

        renderPage();
        showMessage(`Successfully bought ${qty} x ${unit.name}.`);
      });
    }
  }

  // Show message below units
  function showMessage(msg) {
    const el = document.getElementById("msg");
    el.textContent = msg;
    setTimeout(() => { el.textContent = ""; }, 6000);
  }

  // Initial render
  renderPage();

  // Listen to localStorage changes to update totals (sync across tabs/pages)
  window.addEventListener("storage", (e) => {
    if (e.key === STORE_KEY || e.key === "playerStats" || e.key === "warnation_player") {
      renderPage();
    }
  });
</script>

</body>
</html>
