<?php
session_start();
include("db_connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch achievements
$stmt = $conn->prepare("SELECT a.title, a.description, ua.is_completed, ua.earned_date 
                        FROM Achievements a 
                        LEFT JOIN User_Achievements ua ON a.achievement_id = ua.achievement_id AND ua.user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$achievements = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Achievements - FitnessHub</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Sidebar (same inline CSS) */
    .sidebar {
      position: fixed; left: -300px; top: 0;
      width: 300px; height: 100vh;
      background: linear-gradient(135deg, #2c3e50, #34495e);
      backdrop-filter: blur(20px);
      border-right: 1px solid rgba(255,255,255,0.1);
      transition: left 0.3s ease; z-index: 2000;
      box-shadow: 5px 0 20px rgba(0,0,0,0.3);
    }
    .sidebar.active { left: 0; }
    .sidebar-header { padding: 30px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
    .sidebar-header h3 { color: white; margin: 0; font-size: 1.5rem; font-weight: 700; }
    .sidebar-nav { padding: 20px 0; }
    .sidebar-nav a { display: block; padding: 15px 25px; color: rgba(255,255,255,0.8); text-decoration: none; font-weight: 500; transition: all 0.3s ease; border-left: 3px solid transparent; }
    .sidebar-nav a:hover { background: rgba(255,255,255,0.1); color: white; border-left-color: #3498db; transform: translateX(10px); }
    .sidebar-nav a.active { background: rgba(52,152,219,0.2); color: white; border-left-color: #3498db; }
    .hamburger { position: fixed; top: 20px; left: 20px; width: 40px; height: 40px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 8px; cursor: pointer; z-index: 2001; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 4px; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.2); }
    .hamburger span { width: 20px; height: 2px; background: white; border-radius: 1px; transition: all 0.3s ease; }
    .hamburger.active { left: 320px; }
    .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
    .hamburger.active span:nth-child(2) { opacity: 0; }
    .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(7px, -6px); }
    .sidebar-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); opacity:0; visibility:hidden; transition:all 0.3s ease; z-index:1999; }
    .sidebar-overlay.active { opacity:1; visibility:visible; }
    .main-content { margin-left:0; transition: margin-left 0.3s ease; }
    .main-content.sidebar-open { margin-left:300px; }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header"><h3>💪 FitnessHub</h3></div>
    <nav class="sidebar-nav">
      <a href="home.php">🏠 Home</a>
      <a href="add_meal.php">➕ Add Meal</a>
      <a href="daily_meals.php">📊 View Meals</a>
      <a href="meal_history.php">📅 Meal History</a>
      <a href="bmi_calculator.php">📊 BMI Calculator</a>
      <a href="workout_manager.php">💪 Workouts</a>
      <a href="achievements.php" class="active">🏆 Achievements</a>
      <a href="profile.php">👤 My Profile</a>
      <a href="locations.php">📍 Locations</a>
      <a href="logout.php">🚪 Logout</a>
    </nav>
  </div>

  <!-- Hamburger -->
  <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
  <div class="sidebar-overlay" id="overlay"></div>

  <!-- Main Content -->
  <div class="main-content" id="mainContent">
    <div class="container">
      <h1>🏆 Achievements</h1>
      <?php if (count($achievements) > 0): ?>
        <table>
          <tr><th>Title</th><th>Description</th><th>Status</th><th>Date Earned</th></tr>
          <?php foreach ($achievements as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['title']) ?></td>
              <td><?= htmlspecialchars($a['description']) ?></td>
              <td><?= $a['is_completed'] ? "✅ Completed" : "⏳ In Progress" ?></td>
              <td><?= $a['earned_date'] ?? "-" ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <p style="text-align:center;">No achievements available yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <script>
    const hamburger=document.getElementById('hamburger'),sidebar=document.getElementById('sidebar'),overlay=document.getElementById('overlay'),mainContent=document.getElementById('mainContent');
    function toggleSidebar(){hamburger.classList.toggle('active');sidebar.classList.toggle('active');overlay.classList.toggle('active');mainContent.classList.toggle('sidebar-open');}
    hamburger.addEventListener('click',toggleSidebar);overlay.addEventListener('click',toggleSidebar);
    window.addEventListener('resize',()=>{if(window.innerWidth<=768){hamburger.classList.remove('active');sidebar.classList.remove('active');overlay.classList.remove('active');mainContent.classList.remove('sidebar-open');}});
  </script>
</body>
</html>
