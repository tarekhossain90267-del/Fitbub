<?php
session_start();
include("db_connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all meals grouped by date
$sql = "SELECT d.date, f.name, m.meal_type, m.quantity, (f.calories * m.quantity) AS total_calories
        FROM Meals m
        JOIN Food_Items f ON m.food_id = f.food_id
        JOIN Diet_Plans d ON m.diet_id = d.diet_id
        WHERE d.user_id = ?
        ORDER BY d.date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[$row['date']][] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Meal History - FitnessHub</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .sidebar {
      position: fixed;
      left: -300px;
      top: 0;
      width: 300px;
      height: 100vh;
      background: linear-gradient(135deg, #2c3e50, #34495e);
      backdrop-filter: blur(20px);
      border-right: 1px solid rgba(255,255,255,0.1);
      transition: left 0.3s ease;
      z-index: 2000;
      box-shadow: 5px 0 20px rgba(0,0,0,0.3);
    }
    .sidebar.active { left: 0; }
    .sidebar-header { padding: 30px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
    .sidebar-header h3 { color: white; margin: 0; font-size: 1.5rem; font-weight: 700; }
    .sidebar-nav { padding: 20px 0; }
    .sidebar-nav a {
      display: block;
      padding: 15px 25px;
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
    }
    .sidebar-nav a:hover {
      background: rgba(255,255,255,0.1);
      color: white;
      border-left-color: #3498db;
      transform: translateX(10px);
    }
    .sidebar-nav a.active {
      background: rgba(52,152,219,0.2);
      color: white;
      border-left-color: #3498db;
    }
    .hamburger {
      position: fixed;
      top: 20px;
      left: 20px;
      width: 40px;
      height: 40px;
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      border-radius: 8px;
      cursor: pointer;
      z-index: 2001;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      gap: 4px;
      transition: all 0.3s ease;
      border: 1px solid rgba(255,255,255,0.2);
    }
    .hamburger:hover { background: rgba(255,255,255,0.2); transform: scale(1.05); }
    .hamburger.active { left: 320px; }
    .hamburger span { width: 20px; height: 2px; background: white; transition: all 0.3s ease; border-radius: 1px; }
    .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
    .hamburger.active span:nth-child(2) { opacity: 0; }
    .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(7px, -6px); }
    .sidebar-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      z-index: 1999;
    }
    .sidebar-overlay.active { opacity: 1; visibility: visible; }
    .main-content { margin-left: 0; transition: margin-left 0.3s ease; }
    .main-content.sidebar-open { margin-left: 300px; }
    .container {
      max-width: 900px;
      margin: 60px auto 0 auto;
      background: #fff;
      padding: 30px 30px 20px 30px;
      border-radius: 12px;
      box-shadow: 0 4px 24px rgba(44,62,80,0.08);
    }
    h1 { text-align: center; margin-bottom: 20px; }
    .summary-box {
      background: #e8f5e8;
      border: 1px solid #2ecc71;
      border-radius: 8px;
      padding: 15px;
      margin: 20px 0;
      text-align: center;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    th {
      background: #667eea;
      color: white;
      text-transform: uppercase;
    }
    tr:hover { background: #f1f1f1; }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <h3>💪 FitnessHub</h3>
    </div>
    <nav class="sidebar-nav">
      <a href="home.php">🏠 Home</a>
      <a href="add_meal.php">➕ Add Meal</a>
      <a href="daily_meals.php">📊 View Meals</a>
      <a href="meal_history.php" class="active">📅 Meal History</a>
      <a href="bmi_calculator.php">📊 BMI Calculator</a>
      <a href="diet_plan.php">🥗 Diet Plan</a>
      <a href="workout_manager.php">💪 Workouts</a>
      <a href="achievements.php">🏆 Achievements</a>
      <a href="performance_report.php">📈 Performance Report</a>
      <a href="profile.php">👤 My Profile</a>
      <a href="locations.php">📍 Gym & Jogging Locations</a>
      <a href="logout.php">🚪 Logout</a>
    </nav>
  </div>

  <!-- Hamburger -->
  <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
  <div class="sidebar-overlay" id="overlay"></div>

  <!-- Main -->
  <div class="main-content" id="mainContent">
    <div class="container">
      <h1>📅 Meal History</h1>

      <?php if (count($history) > 0): ?>
        <?php foreach ($history as $date => $meals): ?>
          <div class="summary-box">
            <h3>📅 <?php echo date("F j, Y", strtotime($date)); ?></h3>
            <table>
              <thead>
                <tr>
                  <th>Meal Type</th>
                  <th>Food Item</th>
                  <th>Quantity</th>
                  <th>Total Calories</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $daily_total = 0;
                foreach ($meals as $meal): 
                  $daily_total += $meal['total_calories'];
                ?>
                  <tr>
                    <td><?php echo ucfirst($meal['meal_type']); ?></td>
                    <td><?php echo $meal['name']; ?></td>
                    <td><?php echo $meal['quantity']; ?></td>
                    <td><?php echo $meal['total_calories']; ?> cal</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <p><strong>🔥 Daily Total: <?php echo $daily_total; ?> cal</strong></p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align:center;">No meal history available yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Sidebar toggle
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const mainContent = document.getElementById('mainContent');
    function toggleSidebar() {
      hamburger.classList.toggle('active');
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
      mainContent.classList.toggle('sidebar-open');
    }
    hamburger.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);

    // Responsive auto-close
    window.addEventListener('resize', () => {
      if (window.innerWidth <= 768) {
        hamburger.classList.remove('active');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        mainContent.classList.remove('sidebar-open');
      }
    });
  </script>
</body>
</html>

