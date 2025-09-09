<?php
session_start();
include("db_connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Fetch user data (needed for BMR calculation)
$stmt = $conn->prepare("SELECT Age, Gender, Height, Weight FROM Users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($age, $gender, $height_cm, $weight_kg);
$stmt->fetch();
$stmt->close();

// Handle new goal submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $goal_type = $_POST['goal_type'];

    if (in_array($goal_type, ['lose','maintain','gain'])) {
        // ---- BMR Calculation (Mifflin-St Jeor) ----
        if ($gender == "male") {
            $bmr = (10 * $weight_kg) + (6.25 * $height_cm) - (5 * $age) + 5;
        } else {
            $bmr = (10 * $weight_kg) + (6.25 * $height_cm) - (5 * $age) - 161;
        }

        // Assume moderate activity (×1.55)
        $tdee = $bmr * 1.55;

        // Adjust based on goal
        if ($goal_type == "lose") {
            $daily_calories = $tdee - 500; // deficit
        } elseif ($goal_type == "gain") {
            $daily_calories = $tdee + 300; // surplus
        } else {
            $daily_calories = $tdee; // maintain
        }

        $daily_calories = round($daily_calories);

        $stmt = $conn->prepare("INSERT INTO Goals (user_id, goal_type, daily_calorie_target) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $user_id, $goal_type, $daily_calories);
        if ($stmt->execute()) {
            $success = "✅ New goal set successfully! Suggested Target: $daily_calories cal/day";
        } else {
            $error = "❌ Failed to set goal.";
        }
        $stmt->close();
    } else {
        $error = "❌ Invalid goal selection.";
    }
}

// Fetch current goal (latest one)
$stmt = $conn->prepare("SELECT goal_type, daily_calorie_target, created_at 
                        FROM Goals 
                        WHERE user_id=? 
                        ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_goal = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch goal history
$stmt = $conn->prepare("SELECT goal_type, daily_calorie_target, created_at 
                        FROM Goals 
                        WHERE user_id=? 
                        ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$goal_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch today's calories
$stmt = $conn->prepare("SELECT SUM(f.calories * m.quantity) as total_calories
                        FROM Meals m
                        JOIN Food_Items f ON m.food_id=f.food_id
                        JOIN Diet_Plans d ON m.diet_id=d.diet_id
                        WHERE d.user_id=? AND d.date=CURDATE()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$today = $stmt->get_result()->fetch_assoc();
$stmt->close();

$today_calories = $today['total_calories'] ?? 0;

// Helper: format goal type for display
function formatGoalType($type) {
    if ($type == 'lose') return "Lose Weight";
    if ($type == 'maintain') return "Maintain Weight";
    if ($type == 'gain') return "Gain Muscle";
    return ucfirst($type);
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Diet Plan - FitnessHub</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Sidebar Navigation */
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
    /* Hamburger */
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
    .summary-box { background:#e8f5e8; border:1px solid #2ecc71; border-radius:8px; padding:15px; margin-bottom:20px; text-align:center; }
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
      <a href="diet_plan.php" class="active">🥗 Diet Plan</a>
      <a href="workout_manager.php">💪 Workouts</a>
      <a href="achievements.php">🏆 Achievements</a>
      <a href="performance_report.php">📈 Performance Report</a>
      <a href="profile.php">👤 My Profile</a>
      <a href="locations.php">📍 Locations</a>
      <a href="logout.php">🚪 Logout</a>
    </nav>
  </div>

  <!-- Hamburger -->
  <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
  <div class="sidebar-overlay" id="overlay"></div>

  <!-- Main -->
  <div class="main-content" id="mainContent">
    <div class="container">
      <h1>🥗 Diet Plan</h1>
      <?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>
      <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>

      <!-- Current Goal -->
      <?php if ($current_goal): ?>
        <div class="summary-box">
          <h3>🎯 Current Goal</h3>
          <p>Type: <strong><?= formatGoalType($current_goal['goal_type']) ?></strong></p>
          <p>Daily Target: <strong><?= $current_goal['daily_calorie_target'] ?> cal</strong></p>
          <p>Set On: <?= $current_goal['created_at'] ?></p>
        </div>
      <?php else: ?>
        <p style="text-align:center;">No goal set yet.</p>
      <?php endif; ?>

      <!-- Today's Calories -->
      <div class="summary-box">
        <h3>📊 Today</h3>
        <p>Calories Consumed: <strong><?= $today_calories ?> cal</strong></p>
        <?php if ($current_goal): ?>
          <p>Remaining: <strong><?= $current_goal['daily_calorie_target'] - $today_calories ?> cal</strong></p>
        <?php endif; ?>
      </div>

      <!-- Set New Goal -->
      <h2>➕ Set New Goal</h2>
      <form method="POST">
        <label>Goal Type:</label>
        <select name="goal_type" required>
          <option value="">Select</option>
          <option value="lose">Lose Weight</option>
          <option value="maintain">Maintain Weight</option>
          <option value="gain">Gain Muscle</option>
        </select>
        <button type="submit">Save Goal</button>
      </form>

      <!-- Goal History -->
      <h2>📜 Goal History</h2>
      <?php if (count($goal_history) > 0): ?>
        <table>
          <tr><th>Date</th><th>Type</th><th>Target (cal)</th></tr>
          <?php foreach ($goal_history as $g): ?>
            <tr>
              <td><?= $g['created_at'] ?></td>
              <td><?= formatGoalType($g['goal_type']) ?></td>
              <td><?= $g['daily_calorie_target'] ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <p style="text-align:center;">No goals recorded yet.</p>
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


