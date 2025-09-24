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
<title>Russian Roulette - Officers Mess</title>
<style>
  body { background: #001f3f; color: #fff; font-family: Arial, sans-serif; padding: 20px; text-align:center; }
  a.home { color: gold; text-decoration: none; display: block; margin-bottom: 12px; font-weight: bold; }
  h1 { color: gold; margin-bottom: 6px; }
  p.lead { margin-top:0; color:#ddd; }
  .card { background:#00264d; border-radius:10px; padding:12px; max-width:760px; margin:12px auto; box-shadow:0 6px 18px rgba(0,0,0,0.6); text-align:left; }
  .row { display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
  label { display:block; margin-bottom:6px; color:#ddd; }
  input[type="text"], input[type="number"] { padding:8px; border-radius:6px; border:1px solid #004080; width:220px; background:#012140; color:white; }
  button { background:gold; color:#001f3f; border:0; padding:10px 14px; border-radius:6px; cursor:pointer; font-weight:bold; }
  button.danger { background:#cc3333; color:#fff; }
  ul { padding-left:18px; margin:6px 0; color:#ddd; }
  .players-list { display:flex; gap:12px; flex-wrap:wrap; margin:8px 0; }
  .player-slot { background:#003569; padding:8px; border-radius:8px; min-width:180px; }
  .player-name { color:#ffda77; font-weight:bold; }
  .player-meta { font-size:13px; color:#cfe7ff; margin-top:4px; }
  #log { max-height:320px; overflow:auto; background:#001f4d; padding:10px; border-radius:8px; color:#eef; border:1px solid #003b6b; white-space:pre-line; }
  .center { text-align:center; }
  .big { font-size:18px; font-weight:bold; color: #fff; }
  .pull-area { margin-top:12px; display:flex; gap:10px; align-items:center; justify-content:center; flex-wrap:wrap; }
  .muted { color:#a8c5dd; font-size:13px; }
  .winner { color: lightgreen; font-weight:bold; font-size:18px; }
  @media (max-width:600px) {
    input[type="text"], input[type="number"] { width: 100%; }
    .player-slot { min-width: 100%; }
  }
</style>
</head>
<body>

<a class="home" href="Homepage.php">&larr; Back to Homepage</a>
<h1>Russian Roulette</h1>
<p class="lead">3 players. 12-chamber gun, 1 bullet. Entry fee: <strong>10,000</strong> cash per player. Last survivor wins <strong>100,000</strong> cash.</p>

<div class="card" id="lobbyCard">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;">
    <div>
      <label for="joinName">Join as (name):</label>
      <input id="joinName" type="text" placeholder="Enter your name (e.g. You)" />
    </div>
    <div style="min-width:220px;">
      <label style="margin-bottom:6px;">Use my account?</label>
      <div style="display:flex;gap:8px;align-items:center;">
        <input id="useMyAccount" type="checkbox" checked /> <span class="muted">If checked, entry fee is deducted from local playerStats cash</span>
      </div>
    </div>
    <div style="align-self:flex-end;">
      <button id="joinBtn">Join Game (10,000)</button>
    </div>
  </div>

  <hr style="border:0;border-top:1px solid #003b6b;margin:12px 0;" />

  <div>
    <div class="big">Players:</div>
    <div class="players-list" id="playersList"></div>
  </div>

  <div style="margin-top:12px;">
    <button id="startBtn" disabled>Start Game</button>
    <button id="resetBtn" class="danger">Reset Game</button>
  </div>

  <div style="margin-top:10px;" class="muted">Note: When you join using your account, your local <code>playerStats.cash</code> must have ≥ 10,000.</div>
</div>

<div class="card">
  <div class="center big">Turn</div>
  <div id="turnInfo" class="center muted">No active game</div>

  <div class="pull-area" id="pullArea" style="display:none;">
    <button id="pullBtn">Pull the Trigger</button>
    <div id="pullNotice" class="muted">Waiting for your turn...</div>
  </div>

  <div id="winnerBox" class="center winner" style="margin-top:10px;"></div>

  <hr style="border:0;border-top:1px solid #003b6b;margin:12px 0;" />

  <div id="log" aria-live="polite"></div>
</div>

<script>
/*
  Russian Roulette game state saved in localStorage key: 'russianRouletteGame'
  Structure of saved object:
  {
    players: [ {id,name,isLocal,virtualCash}... ], // up to 3
    started: false,
    aliveOrder: [playerId,...],
    currentIndex: 0, // index into aliveOrder whose turn it is
    chambers: [true,false,...] // remaining chambers, one true
    log: [strings...],
    winner: null,
  }
*/

const STORAGE_KEY = 'russianRouletteGame';
const ENTRY_FEE = 10000;
const WIN_REWARD = 100000;
const LOCAL_PLAYER_KEY = 'playerStats'; // matches your PlayerBrain.js

// helper: get saved game or default
function loadGame() {
  return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {
    players: [], started: false, aliveOrder: [], currentIndex: 0,
    chambers: [], log: [], winner: null
  };
}
function saveGame(g) { localStorage.setItem(STORAGE_KEY, JSON.stringify(g)); }

// UI elements
const joinNameEl = document.getElementById('joinName');
const useMyAccountEl = document.getElementById('useMyAccount');
const joinBtn = document.getElementById('joinBtn');
const startBtn = document.getElementById('startBtn');
const resetBtn = document.getElementById('resetBtn');
const playersListEl = document.getElementById('playersList');
const pullArea = document.getElementById('pullArea');
const pullBtn = document.getElementById('pullBtn');
const pullNotice = document.getElementById('pullNotice');
const turnInfo = document.getElementById('turnInfo');
const logEl = document.getElementById('log');
const winnerBox = document.getElementById('winnerBox');

joinBtn.addEventListener('click', joinGame);
startBtn.addEventListener('click', startGame);
resetBtn.addEventListener('click', resetGame);
pullBtn.addEventListener('click', pullTrigger);

// Helper: local player stats from PlayerBrain.js
function getLocalPlayerStats() {
  return JSON.parse(localStorage.getItem(LOCAL_PLAYER_KEY)) || null;
}
function saveLocalPlayerStats(p) {
  localStorage.setItem(LOCAL_PLAYER_KEY, JSON.stringify(p));
}

// Utility: generate 12-chamber array with 1 true bullet at random
function makeChambers() {
  const arr = Array(12).fill(false);
  const idx = Math.floor(Math.random() * 12);
  arr[idx] = true;
  return arr;
}

// Render functions
function renderPlayers() {
  const g = loadGame();
  playersListEl.innerHTML = '';
  g.players.forEach((p, i) => {
    const div = document.createElement('div');
    div.className = 'player-slot';
    div.innerHTML = `
      <div class="player-name">${p.name}</div>
      <div class="player-meta">Slot: ${i+1} ${p.isLocal ? '(You)' : ''}</div>
      <div class="player-meta">Cash: ${p.isLocal ? (getLocalPlayerStats()?.cash ?? 0) : (p.virtualCash?.toLocaleString() ?? '—')}</div>
      <div style="margin-top:8px;">
        <button data-idx="${i}" class="kickBtn">Remove</button>
      </div>
    `;
    playersListEl.appendChild(div);
  });

  // attach remove handlers
  document.querySelectorAll('.kickBtn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const idx = parseInt(btn.dataset.idx);
      removePlayer(idx);
    });
  });

  startBtn.disabled = g.players.length !== 3 || g.started;
}

function renderLog() {
  const g = loadGame();
  logEl.innerHTML = g.log.join("\n") || "No actions yet.";
  logEl.scrollTop = logEl.scrollHeight;
}

function renderTurn() {
  const g = loadGame();
  if (!g.started) {
    turnInfo.textContent = "No active game";
    pullArea.style.display = 'none';
    winnerBox.textContent = '';
    return;
  }
  if (g.winner) {
    turnInfo.textContent = "Game finished";
    pullArea.style.display = 'none';
    winnerBox.textContent = `Winner: ${g.winner} — Reward paid: ${WIN_REWARD.toLocaleString()} cash`;
    return;
  }
  const currentId = g.aliveOrder[g.currentIndex];
  const currentPlayer = g.players.find(p => p.id === currentId);
  turnInfo.textContent = `Turn: ${currentPlayer.name}`;
  winnerBox.textContent = '';
  // show pull button only if the local player is the one whose turn it is and they joined as local
  const localJoinedIndex = g.players.findIndex(p => p.isLocal);
  const localPlayer = g.players[localJoinedIndex];
  if (localPlayer && currentPlayer && localPlayer.id === currentPlayer.id && !g.winner) {
    pullArea.style.display = 'flex';
    pullBtn.disabled = false;
    pullNotice.textContent = "It's your turn — Pull the trigger!";
  } else {
    pullArea.style.display = 'flex';
    pullBtn.disabled = true;
    pullNotice.textContent = `Waiting for ${currentPlayer.name}...`;
  }
}

// Player helpers
function generatePlayerId(name) {
  return `${name}_${Math.floor(Math.random()*1000000)}`;
}

function joinGame() {
  const name = (joinNameEl.value || '').trim();
  if (!name) return alert('Enter a name to join.');
  const useLocal = useMyAccountEl.checked;
  const g = loadGame();
  if (g.players.length >= 3) return alert('Game already has 3 players.');

  // Check duplicate names
  if (g.players.some(p => p.name.toLowerCase() === name.toLowerCase())) {
    return alert('This name is already joined.');
  }

  // if using local account, check local player cash
  if (useLocal) {
    const local = getLocalPlayerStats();
    if (!local) return alert('No local playerStats found.');
    if ((local.cash || 0) < ENTRY_FEE) return alert('You do not have enough cash to join (10,000).');
    // deduct fee
    local.cash -= ENTRY_FEE;
    saveLocalPlayerStats(local);
  }

  // For non-local players, create a virtual wallet with enough cash to cover entry fee
  const virtualCashStart = 50000; // starter for non-local
  const playerObj = {
    id: generatePlayerId(name),
    name,
    isLocal: !!useLocal,
    virtualCash: useLocal ? undefined : (virtualCashStart - ENTRY_FEE)
  };

  // push and save
  g.players.push(playerObj);
  g.log = g.log || [];
  g.log.push(`${name} joined the game (paid ${ENTRY_FEE.toLocaleString()}).`);
  saveGame(g);
  joinNameEl.value = '';
  renderPlayers();
  renderLog();
  renderTurn();
}

function removePlayer(idx) {
  const g = loadGame();
  if (!g.players[idx]) return;
  const p = g.players[idx];
  // Refund entry fee if not started
  if (!g.started) {
    if (p.isLocal) {
      const local = getLocalPlayerStats();
      if (local) { local.cash = (local.cash||0) + ENTRY_FEE; saveLocalPlayerStats(local); }
    } else {
      // put virtualCash back (we had deducted on join)
      p.virtualCash = (p.virtualCash || 0) + ENTRY_FEE;
    }
    g.log.push(`${p.name} left the lobby (entry fee refunded).`);
    g.players.splice(idx,1);
    saveGame(g);
    renderPlayers();
    renderLog();
    renderTurn();
    return;
  }
  alert('Cannot remove player after game started.');
}

function startGame() {
  const g = loadGame();
  if (g.players.length !== 3) return alert('Need exactly 3 players to start.');
  if (g.started) return alert('Game already started.');

  // Prepare alive order (player ids in join order)
  g.aliveOrder = g.players.map(p => p.id);
  g.currentIndex = 0;
  g.chambers = makeChambers();
  g.log = g.log || [];
  g.log.push('Game started. Chambers loaded (12), 1 bullet.');
  g.started = true;
  g.winner = null;
  saveGame(g);
  renderPlayers();
  renderLog();
  renderTurn();
}

function pullTrigger() {
  const g = loadGame();
  if (!g.started || g.winner) return;

  // find current player
  const currentId = g.aliveOrder[g.currentIndex];
  const currentPlayer = g.players.find(p=>p.id === currentId);
  if (!currentPlayer) return;

  // pick random chamber index from remaining chambers
  if (!g.chambers || g.chambers.length === 0) {
    g.chambers = makeChambers();
    g.log.push('Chambers reloaded.');
  }
  const idx = Math.floor(Math.random() * g.chambers.length);
  const fired = g.chambers[idx];
  // remove used chamber
  g.chambers.splice(idx,1);

  if (fired) {
    // player dies
    g.log.push(`${currentPlayer.name} pulled the trigger and died.`);
    // remove player from aliveOrder
    const removedIndex = g.currentIndex;
    g.aliveOrder.splice(removedIndex, 1);

    // mark if local: no refund; they lose entry
    // if the removed player was local, nothing extra needed (we already deducted entry fee)
    // If there is only one left, declare winner
    if (g.aliveOrder.length === 1) {
      const winnerId = g.aliveOrder[0];
      const winnerPlayer = g.players.find(p=>p.id===winnerId);
      g.winner = winnerPlayer.name;
      g.log.push(`Winner: ${g.winner}`);
      // pay winner
      if (winnerPlayer.isLocal) {
        const local = getLocalPlayerStats();
        local.cash = (local.cash || 0) + WIN_REWARD;
        saveLocalPlayerStats(local);
      } else {
        // virtual player gets virtual cash updated
        winnerPlayer.virtualCash = (winnerPlayer.virtualCash || 0) + WIN_REWARD;
        // also update players array
        g.players = g.players.map(p => p.id === winnerId ? winnerPlayer : p);
      }
      g.started = false;
      saveGame(g);
      renderLog(); renderTurn(); renderPlayers();
      return;
    } else {
      // if some players remain, currentIndex remains same because after removal next player shifts into this index
      if (g.currentIndex >= g.aliveOrder.length) g.currentIndex = 0;
    }
  } else {
    // player survived: log & move to next
    g.log.push(`${currentPlayer.name} pulled the trigger and survived.`);
    g.currentIndex = (g.currentIndex + 1) % g.aliveOrder.length;
  }

  // If chambers empty, reload automatically next pull (handled at top)
  saveGame(g);
  renderLog();
  renderTurn();
  renderPlayers();
}

// Reset function clears the saved game
function resetGame() {
  if (!confirm('Reset the Russian Roulette game? This will clear lobby and logs.')) return;
  localStorage.removeItem(STORAGE_KEY);
  renderPlayers();
  renderLog();
  renderTurn();
}

// init render
renderPlayers();
renderLog();
renderTurn();

// periodically refresh UI (in case other code modifies localStorage)
setInterval(() => {
  renderPlayers();
  renderLog();
  renderTurn();
}, 1500);

</script>
</body>
</html>

