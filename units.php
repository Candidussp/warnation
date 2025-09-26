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
    if (!checkLevelRequirement(2)) {
      throw new Error('Level restriction: redirecting to homepage');
    }
  }
</script>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>WOODEN BARRACKS - WARNATION</title>
<style>
  body { background: #000; color:#e6e6e6; font-family: Arial, sans-serif; padding:18px; text-align:center; }
  a.home { color:#ffd34d; text-decoration:none; font-weight:bold; display:inline-block; margin-bottom:12px; margin-right: 16px; }
  h1 { margin:4px 0 10px 0; }
  .stats { margin-bottom:10px; font-size:14px; }
  .bar-wrap { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
  .bar { background:#070707; border:1px solid #333; padding:12px; width:340px; border-radius:6px; text-align:left; }
  .bar h2 { margin:0 0 6px 0; color:#ffd34d; font-size:16px; }
  .muted { color:#9fb0bf; font-size:13px; }
  .row { display:flex; justify-content:space-between; margin:6px 0; align-items:center; gap:8px; }
  input[type=number] { width:110px; padding:6px; border-radius:4px; border:1px solid #333; background:#070707; color:#e6e6e6; }
  button { background:#ffd34d; border:0; padding:6px 10px; border-radius:5px; cursor:pointer; font-weight:700; }
  button.ghost { background:transparent; color:#ffd34d; border:1px solid #333; }
  button:disabled { background:#444; color:#999; cursor:not-allowed; }
  .small { font-size:12px; color:#bcd8e8; }
  #queueList { margin-top:10px; font-size:13px; color:#dbeff8; text-align:left; max-width:1000px; margin-left:auto; margin-right:auto; white-space: pre-line; }
  .top-links {
    margin-bottom: 12px;
    text-align: left;
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
  }
  .page-tabs {
  display: inline-block;
  background: #001f3f; /* dark blue */
  border-radius: 12px;
  padding: 5px 15px;
  box-shadow: 0 0 8px rgba(0,0,0,0.4);
}

.page-tabs .tab-link {
  color: #fff;
  text-decoration: none;
  padding: 8px 18px;
  display: inline-block;
  border-radius: 8px;
  transition: background 0.3s;
}

.page-tabs .tab-link:hover {
  background: #004080;
}

.page-tabs .tab-link.active {
  background: #0074D9;
  font-weight: bold;
}

</style>
</head>
<body>


</div> <!-- Navigation Tabs -->
<div class="page-tabs" style="margin: 15px 0; text-align: center;">
 <a class="home" href="Homepage.php">← HOME</a> 
  <a href="Units.php" class="tab-link">Units</a>
  <a href="Fleets.php" class="tab-link">Fleets</a>
  <a href="SWMDS.php" class="tab-link">SWMDS</a>
</div>

<hr>


<h1> BARRACKS</h1>

<div class="stats">
  <span>Player Level: <strong id="plLevel">1</strong></span> &nbsp;|&nbsp;
  <span>Cash: <strong id="plCash">0</strong></span> &nbsp;|&nbsp;
  <span>Gold: <strong id="plGold">0</strong></span> &nbsp;|&nbsp;
  <span>Total ATK: <strong id="totalAtk">0</strong></span> &nbsp;|&nbsp;
  <span>Total DEF: <strong id="totalDef">0</strong></span>
</div>

<div class="bar-wrap" id="bars"></div>

<div id="queueList" class="small">No tasks running.</div>

<script>
/* ============================
   WARNATION: Wooden Barracks page
   Training time is 10 seconds per soldier for lvl 1 barrack
   Training time increases by 50% for every level
   Attack and defense per soldier based on user specs:
   lvl 1: 30/30
   lvl 2: 100/100
   lvl 3: 250/250
   lvl 4: 400/400
   lvl 5: 600/600
============================ */

/* ------------ CONFIG ------------ */
const NUM_BARRACKS = 3;

// training
const TRAIN_TIME_L1_SEC = 10;        // 10 seconds per soldier for lvl 1
const TRAIN_TIME_LEVEL_MULT = 1.5;   // 50% increase per level

// upgrades
const UPGRADE_L1_TO_L2_COST = 80000;   // fixed cost for upgrade L1->L2
const UPGRADE_GROWTH = 1.8;            // 80% increase per level after L2
const MAX_LEVEL = 5;

const LEVEL5_PLAYER_REQ = 40;
const LEVEL5_GOLD_REQ = 500;

const LEVEL_NAMES = {
  1: "Wooden Barrack",
  2: "Stone Barrack",
  3: "Iron Barrack",
  4: "Steel Barrack",
  5: "Royal Barrack"
};

// Attack and Defense per soldier by level
const ATK_DEF_VALUES = {
  1: 30,
  2: 100,
  3: 250,
  4: 400,
  5: 600
};

/* storage keys */
const STATE_KEY = "warnation_barracks_state_v3";
const PLAYER_CANDIDATES = ["playerStats","warnation_player","player","playerData","warnation_player_v2"];

/* ------------ STORAGE HELPERS ------------ */
function readPlayerWrapper() {
  for (const k of PLAYER_CANDIDATES) {
    const raw = localStorage.getItem(k);
    if (!raw) continue;
    try {
      const parsed = JSON.parse(raw);
      return { key: k, obj: {
        level: Number(parsed.level ?? parsed.lvl ?? parsed.lv ?? 1),
        cash: Number(parsed.cash ?? parsed.money ?? parsed.coins ?? parsed.Cash ?? 0),
        gold: Number(parsed.gold ?? parsed.Gold ?? parsed.g ?? 0),
        raw: parsed
      }};
    } catch(e){}
  }
  // fallback default
  const defaultP = { level:1, cash:0, gold:0, raw: {} };
  return { key: null, obj: defaultP };
}

function savePlayerWrapper(wrapper) {
  const key = wrapper.key;
  const obj = wrapper.obj;
  if (!key) {
    localStorage.setItem("playerStats", JSON.stringify({ level: obj.level, cash: obj.cash, gold: obj.gold }));
    return;
  }
  const raw = localStorage.getItem(key);
  try {
    const parsed = raw ? JSON.parse(raw) : {};
    parsed.level = obj.level;
    parsed.cash = obj.cash;
    parsed.gold = obj.gold;
    localStorage.setItem(key, JSON.stringify(parsed));
  } catch(e) {
    localStorage.setItem("playerStats", JSON.stringify({ level: obj.level, cash: obj.cash, gold: obj.gold }));
  }
}

function readState() {
  const raw = localStorage.getItem(STATE_KEY);
  if (!raw) {
    const init = {
      barracks: Array.from({length: NUM_BARRACKS}, ()=>({ level:1, soldiers:0 })),
      queues: [],
      upgrades: []
    };
    localStorage.setItem(STATE_KEY, JSON.stringify(init));
    return init;
  }
  try {
    return JSON.parse(raw);
  } catch(e) {
    const init = {
      barracks: Array.from({length: NUM_BARRACKS}, ()=>({ level:1, soldiers:0 })),
      queues: [],
      upgrades: []
    };
    localStorage.setItem(STATE_KEY, JSON.stringify(init));
    return init;
  }
}

function saveState(s) {
  localStorage.setItem(STATE_KEY, JSON.stringify(s));
}

/* ------------ MATH / GAME RULES ------------ */

function upgradeCostNext(level) {
  if (level === 1) return UPGRADE_L1_TO_L2_COST;
  if (level >= MAX_LEVEL) return null;
  let c = UPGRADE_L1_TO_L2_COST;
  for (let L = 2; L <= level; L++) c = Math.round(c * UPGRADE_GROWTH);
  return c;
}

function trainCostPerUnit(level) {
  if (level >= 5) return { currency: "gold", amount: 10 };
  const baseCost = 50;
  const cost = Math.round(baseCost * Math.pow(1.5, level - 1));
  return { currency: "cash", amount: cost };
}

function trainTimePerUnitSec(level) {
  return Math.round(TRAIN_TIME_L1_SEC * Math.pow(TRAIN_TIME_LEVEL_MULT, level - 1));
}

function atkDefPerUnit(level) {
  return ATK_DEF_VALUES[level] || 30;
}

function upgradeDurationMsFor(level) {
  let dur = 30 * 60 * 1000;  // 30 minutes base
  for (let L = 2; L <= level; L++) dur = Math.round(dur * 1.5);
  return dur;
}

/* ------------ QUEUE PROCESSING (background) ------------ */

function processBackgroundTasks(state) {
  const now = Date.now();
  let changed = false;
  const remainingUpgrades = [];
  for (const up of state.upgrades) {
    if (now >= up.finishTs) {
      state.barracks[up.barId].level = Math.min(MAX_LEVEL, up.toLevel);
      changed = true;
    } else {
      remainingUpgrades.push(up);
    }
  }
  state.upgrades = remainingUpgrades;

  const remainingQueues = [];
  for (const q of state.queues) {
    if (now >= q.finishTs) {
      state.barracks[q.barId].soldiers += q.units;
      changed = true;
    } else {
      remainingQueues.push(q);
    }
  }
  state.queues = remainingQueues;

  if (changed) saveState(state);
}

/* ------------ UTIL ------------ */

function fmt(n){ return n.toLocaleString(); }
function secondsToDhms(sec) {
  sec = Math.max(0, Math.floor(sec));
  const d = Math.floor(sec / 86400); sec -= d*86400;
  const h = Math.floor(sec/3600); sec -= h*3600;
  const m = Math.floor(sec/60); sec -= m*60;
  if (d) return `${d}d ${h}h ${m}m`;
  if (h) return `${h}h ${m}m ${sec}s`;
  if (m) return `${m}m ${sec}s`;
  return `${sec}s`;
}

/* ------------ RENDER & ACTIONS ------------ */

function renderAll() {
  const state = readState();
  processBackgroundTasks(state);
  const playerWrap = readPlayerWrapper();
  const player = playerWrap.obj;

  // compute total atk/def
  let totalAtk = 0, totalDef = 0;
  state.barracks.forEach((b) => {
    totalAtk += (b.soldiers || 0) * atkDefPerUnit(b.level || 1);
    totalDef += (b.soldiers || 0) * atkDefPerUnit(b.level || 1);
  });

  localStorage.setItem("warnation_totalAttack", JSON.stringify(totalAtk));
  localStorage.setItem("warnation_totalDefense", JSON.stringify(totalDef));
  localStorage.setItem("totalAttack", String(totalAtk));
  localStorage.setItem("totalDefense", String(totalDef));

  // Update combined totals across modules (units, fleets, swmds, etc.)
  if (typeof updateCombinedTotals === 'function') {
    updateCombinedTotals();
  } else {
    // ensure combined keys exist as a fallback
    if (!localStorage.getItem('combinedTotalAttack')) localStorage.setItem('combinedTotalAttack', String(totalAtk));
    if (!localStorage.getItem('combinedTotalDefense')) localStorage.setItem('combinedTotalDefense', String(totalDef));
  }

  // header stats
  document.getElementById("plLevel").innerText = fmt(player.level);
  document.getElementById("plCash").innerText = fmt(player.cash);
  document.getElementById("plGold").innerText = fmt(player.gold);
  document.getElementById("totalAtk").innerText = fmt(totalAtk);
  document.getElementById("totalDef").innerText = fmt(totalDef);

  // bars listing
  const barsEl = document.getElementById("bars");
  barsEl.innerHTML = "";

  state.barracks.forEach((b, idx) => {
    const level = b.level || 1;
    const name = LEVEL_NAMES[level] || ("Barrack L" + level);
    const soldiers = b.soldiers || 0;
    const perAtkDef = atkDefPerUnit(level);
    const trainCostObj = trainCostPerUnit(level);
    const trainTimeSec = trainTimePerUnitSec(level);

    const queue = state.queues.find(q => q.barId === idx) || null;
    const upgradeTask = state.upgrades.find(u => u.barId === idx) || null;

    const bar = document.createElement("div"); bar.className = "bar";
    bar.innerHTML = `
      <h2>${name} <span class="muted">(Lvl ${level})</span></h2>
      <div class="row"><div class="muted">Soldiers</div><div><strong id="sold_${idx}">${fmt(soldiers)}</strong></div></div>
      <div class="row"><div class="muted">ATK / unit</div><div><strong>${fmt(perAtkDef)}</strong></div></div>
      <div class="row"><div class="muted">DEF / unit</div><div><strong>${fmt(perAtkDef)}</strong></div></div>
      <div class="row"><div class="muted">Train cost / unit</div><div><strong>${fmt(trainCostObj.amount)} ${trainCostObj.currency.toUpperCase()}</strong></div></div>
      <div class="row"><div class="muted">Time / unit</div><div><strong>${secondsToDhms(trainTimeSec)}</strong></div></div>

      <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <input type="number" id="qty_${idx}" min="1" value="1" />
        <button id="trainBtn_${idx}">Train</button>
        <button class="ghost" id="q1_${idx}">+1</button>
        <button class="ghost" id="q10_${idx}">+10</button>
        <button class="ghost" id="q100_${idx}">+100</button>
        <button class="ghost" id="q500_${idx}">+500</button>
      </div>

      <div id="taskArea_${idx}" style="margin-top:8px;">
      </div>

      <div style="margin-top:10px; display:flex; gap:8px; align-items:center; justify-content:space-between;">
        <div class="small muted" id="upReq_${idx}"></div>
        <div>
          <button id="upBtn_${idx}">Upgrade</button>
        </div>
      </div>
    `;

    barsEl.appendChild(bar);

    // show queue / upgrade task status
    const taskArea = bar.querySelector(`#taskArea_${idx}`);
    if (upgradeTask) {
      const remMs = Math.max(0, upgradeTask.finishTs - Date.now());
      taskArea.innerHTML = `<div class="small">Upgrade in progress → to Lvl ${upgradeTask.toLevel} — finishes in <strong id="uprem_${idx}">${secondsToDhms(Math.ceil(remMs/1000))}</strong></div>`;
    } else if (queue) {
      const remMs = Math.max(0, queue.finishTs - Date.now());
      taskArea.innerHTML = `<div class="small">Training: ${queue.units} units queued — finishes in <strong id="qrem_${idx}">${secondsToDhms(Math.ceil(remMs/1000))}</strong></div>`;
    } else {
      taskArea.innerHTML = `<div class="small">No active training or upgrade</div>`;
    }

    // upgrade button & requirements
    const upBtn = bar.querySelector(`#upBtn_${idx}`);
    const upReqEl = bar.querySelector(`#upReq_${idx}`);
    if (level >= MAX_LEVEL) {
      upReqEl.innerText = "Max level";
      upBtn.disabled = true;
    } else if (level + 1 === MAX_LEVEL) {
      upReqEl.innerText = `Requires Player Lvl ${LEVEL5_PLAYER_REQ} & ${fmt(LEVEL5_GOLD_REQ)} Gold`;
      const can = (player.level >= LEVEL5_PLAYER_REQ && player.gold >= LEVEL5_GOLD_REQ);
      upBtn.disabled = !can || Boolean(upgradeTask);
      upBtn.innerText = can ? `Upgrade to Lvl ${level+1}` : `Upgrade (locked)`;
    } else {
      const cost = upgradeCostNext(level);
      upReqEl.innerText = `Upgrade cost: ${fmt(cost)} Cash`;
      upBtn.disabled = (player.cash < cost) || Boolean(upgradeTask);
      upBtn.innerText = `Upgrade to Lvl ${level+1}`;
    }

    // training button & qty
    const trainBtn = bar.querySelector(`#trainBtn_${idx}`);
    const qtyInput = bar.querySelector(`#qty_${idx}`);
    const q1 = bar.querySelector(`#q1_${idx}`);
    const q10 = bar.querySelector(`#q10_${idx}`);
    const q100 = bar.querySelector(`#q100_${idx}`);
    const q500 = bar.querySelector(`#q500_${idx}`);

    // disable train button if training active
    trainBtn.disabled = Boolean(queue);

    // quick qty buttons - add to current input value (minimum 1)
    q1.addEventListener("click", () => { 
      let val = Math.max(1, Number(qtyInput.value) || 1); 
      qtyInput.value = val + 1; 
    });
    q10.addEventListener("click", () => { 
      let val = Math.max(1, Number(qtyInput.value) || 1); 
      qtyInput.value = val + 10; 
    });
    q100.addEventListener("click", () => { 
      let val = Math.max(1, Number(qtyInput.value) || 1); 
      qtyInput.value = val + 100; 
    });
    q500.addEventListener("click", () => { 
      let val = Math.max(1, Number(qtyInput.value) || 1); 
      qtyInput.value = val + 500; 
    });

    // train action
    trainBtn.addEventListener("click", () => {
      const qty = Math.floor(Number(qtyInput.value) || 0);
      if (qty <= 0) return alert("Enter a positive number of soldiers to queue.");
      const tc = trainCostPerUnit(level);
      const totalCost = tc.amount * qty;
      const playerW = readPlayerWrapper();
      const pl = playerW.obj;

      if (tc.currency === "cash") {
        if (pl.cash < totalCost) return alert("Not enough cash.");
        pl.cash -= totalCost;
      } else {
        if (pl.gold < totalCost) return alert("Not enough gold.");
        pl.gold -= totalCost;
      }

      savePlayerWrapper(playerW);

      // Calculate finish timestamp
      const perUnitSec = trainTimePerUnitSec(level);
      const durationMs = perUnitSec * 1000 * qty;

      const finishTs = Date.now() + durationMs;

      // add to queues
      const s = readState();
      s.queues.push({
        barId: idx,
        units: qty,
        finishTs: finishTs
      });
      saveState(s);

      renderAll();
    });

    // upgrade action
    upBtn.addEventListener("click", () => {
      const s = readState();
      const bar = s.barracks[idx];
      if (!bar) return alert("Invalid barrack.");
      if (bar.level >= MAX_LEVEL) return alert("Already max level.");
      const nextLevel = bar.level + 1;
      const playerW = readPlayerWrapper();
      const pl = playerW.obj;

      // Check requirements
      if (nextLevel === MAX_LEVEL) {
        if (pl.level < LEVEL5_PLAYER_REQ || pl.gold < LEVEL5_GOLD_REQ) return alert("Requirements not met for level 5 upgrade.");
        // Deduct gold
        pl.gold -= LEVEL5_GOLD_REQ;
      } else {
        const cost = upgradeCostNext(bar.level);
        if (pl.cash < cost) return alert("Not enough cash to upgrade.");
        pl.cash -= cost;
      }
      savePlayerWrapper(playerW);

      // Schedule upgrade
      const durMs = upgradeDurationMsFor(nextLevel);
      s.upgrades.push({
        barId: idx,
        toLevel: nextLevel,
        finishTs: Date.now() + durMs
      });

      saveState(s);
      renderAll();
    });
  });

  // Update timers every second
  if (window._timerInterval) clearInterval(window._timerInterval);
  window._timerInterval = setInterval(() => {
    const s = readState();

    let anyChange = false;
    s.upgrades.forEach((u) => {
      const el = document.getElementById(`uprem_${u.barId}`);
      if (!el) return;
      const diff = u.finishTs - Date.now();
      if (diff <= 0) {
        // complete upgrade instantly on next renderAll call
        anyChange = true;
        return;
      }
      el.innerText = secondsToDhms(Math.ceil(diff/1000));
    });
    s.queues.forEach((q) => {
      const el = document.getElementById(`qrem_${q.barId}`);
      if (!el) return;
      const diff = q.finishTs - Date.now();
      if (diff <= 0) {
        anyChange = true;
        return;
      }
      el.innerText = secondsToDhms(Math.ceil(diff/1000));
    });

    if (anyChange) {
      // reprocess tasks
      processBackgroundTasks(s);
      saveState(s);
      renderAll();
    }
  }, 1000);

  // Show queue list below
  const queueList = document.getElementById("queueList");
  const stateNow = readState();
  let lines = [];
  if (stateNow.upgrades.length === 0 && stateNow.queues.length === 0) {
    queueList.innerText = "No tasks running.";
  } else {
    stateNow.upgrades.forEach(u => {
      lines.push(`Barrack #${u.barId + 1} upgrading to Lvl ${u.toLevel} finishing in ${secondsToDhms(Math.ceil((u.finishTs - Date.now()) / 1000))}`);
    });
    stateNow.queues.forEach(q => {
      lines.push(`Barrack #${q.barId + 1} training ${fmt(q.units)} soldiers finishing in ${secondsToDhms(Math.ceil((q.finishTs - Date.now()) / 1000))}`);
    });
    queueList.innerText = lines.join("\n");
  }
}

renderAll();
</script>
<script>
  // Highlight current page tab
  const current = location.pathname.split("/").pop();
  document.querySelectorAll(".page-tabs .tab-link").forEach(link => {
    if(link.getAttribute("href") === current){
      link.classList.add("active");
    }
  });
</script>


<!-- ... existing content above ... -->

<div id="queueList" class="small">No tasks running.</div>


</div>
</div>


</body>
</html>
