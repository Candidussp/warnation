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
<html>
<head>
  <title>WARNATION - Login/Register</title>
  <style>
    body { background:black; color:white; font-family:Arial; text-align:center; }
    .box { background:#222; padding:20px; border-radius:10px; display:inline-block; margin-top:50px; }
    input, button { margin:5px; padding:10px; border-radius:5px; }
    button { cursor:pointer; }
  </style>
</head>
<body>
  <div class="box">
    <h2>⚔️ WARNATION</h2>
    <form id="authForm">
      <input type="text" name="username" placeholder="Username" required><br>
      <input type="password" name="password" placeholder="Password" required><br>
      <button type="button" onclick="submitForm('login')">Login</button>
      <button type="button" onclick="submitForm('register')">Register</button>
    </form>
    <p id="message"></p>
  </div>

  <script>
    async function submitForm(mode) {
      const form = document.getElementById("authForm");
      const formData = new FormData(form);
      formData.append("mode", mode);

      let res = await fetch("auth.php", { method:"POST", body:formData });
      let data = await res.json();

      document.getElementById("message").innerText = data.message;

      if (data.success && mode === "login") {
        // Save player info to localStorage (optional, for fast access in game)
        localStorage.setItem("playerStats", JSON.stringify(data.stats));
        localStorage.setItem("username", data.player.username);

        // Redirect to Homepage
        window.location.href = "Homepage.php";
      }
    }
  </script>
</body>
</html>