<?php
session_start();
include("db_connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// ===== Weekly Calories =====
$sql = "SELECT d.date, SUM(f.calories * m.quantity) AS total_calories
        FROM Meals m
        JOIN Food_Items f ON m.food_id = f.food_id
        JOIN Diet_Plans d ON m.diet_id = d.diet_id
        WHERE d.user_id = ? AND d.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY d.date ORDER BY d.date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$weekly_meals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch last goal (for daily calorie target)
$stmt = $conn->prepare("SELECT daily_calorie_target FROM Goals WHERE user_id=? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$goal = $stmt->get_result()->fetch_assoc();
$stmt->close();
$daily_goal = $goal['daily_calorie_target'] ?? 0;

// ===== BMI Trend =====
$stmt = $conn->prepare("SELECT bmi_value, recorded_date FROM BMI_History WHERE user_id=? ORDER BY recorded_date DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bmi_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ===== Workouts This Week =====
$stmt = $conn->prepare("SELECT COUNT(*) AS sessions FROM Workout_Sessions WHERE user_id=? AND session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$workout = $stmt->get_result()->fetch_assoc();
$stmt->close();
$sessions_done = $workout['sessions'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
  <title>Performance Report - FitnessHub</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Sidebar (same style as others) */
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
    .summary-box { background:#f8f9fa; border:1px solid #ccc; border-radius:8px; padding:15px; margin:15px 0; text-align:center; }
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
      <a href="diet_plan.php">🥗 Diet Plan</a>
      <a href="workout_manager.php">💪 Workouts</a>
      <a href="achievements.php">🏆 Achievements</a>
      <a href="performance_report.php" class="active">📈 Performance Report</a>
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
      <h1>📈 Performance Report</h1>

      <!-- Weekly Calories -->
      <div class="summary-box">
        <h3>🔥 Weekly Calorie Intake</h3>
        <?php if (count($weekly_meals) > 0): ?>
          <table>
            <tr><th>Date</th><th>Calories Consumed</th><th>Goal</th></tr>
            <?php foreach ($weekly_meals as $day): ?>
              <tr>
                <td><?= $day['date'] ?></td>
                <td><?= $day['total_calories'] ?> cal</td>
                <td><?= $daily_goal ?: "N/A" ?> cal</td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php else: ?>
          <p>No meal records this week.</p>
        <?php endif; ?>
      </div>

      <!-- BMI Trend -->
      <div class="summary-box">
        <h3>⚖️ BMI Trend</h3>
        <?php if (count($bmi_history) > 0): ?>
          <table>
            <tr><th>Date</th><th>BMI</th></tr>
            <?php foreach ($bmi_history as $b): ?>
              <tr>
                <td><?= $b['recorded_date'] ?></td>
                <td><?= $b['bmi_value'] ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php else: ?>
          <p>No BMI records available.</p>
        <?php endif; ?>
      </div>

      <!-- Workouts -->
      <div class="summary-box">
        <h3>🏋️ Workouts This Week</h3>
        <p>Sessions Completed: <strong><?= $sessions_done ?></strong></p>
      </div>
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
