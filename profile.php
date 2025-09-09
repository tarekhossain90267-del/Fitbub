<?php
session_start();
include("db_connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT Name, Age, Gender, Height, Weight, Phone, Email 
                        FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT goal_type, daily_calorie_target 
                        FROM Goals WHERE user_id = ? ORDER BY goal_id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$goal = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <title>My Profile - FitnessHub</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Sidebar Navigation */
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

    .sidebar.active {
      left: 0;
    }

    .sidebar-header {
      padding: 30px 20px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      text-align: center;
    }

    .sidebar-header h3 {
      color: white;
      margin: 0;
      font-size: 1.5rem;
      font-weight: 700;
    }

    .sidebar-nav {
      padding: 20px 0;
    }

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

    /* Hamburger Menu */
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

    .hamburger:hover {
      background: rgba(255,255,255,0.2);
      transform: scale(1.05);
    }

    .hamburger.active {
      left: 320px;
    }

    .hamburger span {
      width: 20px;
      height: 2px;
      background: white;
      transition: all 0.3s ease;
      border-radius: 1px;
    }

    .hamburger.active span:nth-child(1) {
      transform: rotate(45deg) translate(5px, 5px);
    }

    .hamburger.active span:nth-child(2) {
      opacity: 0;
    }

    .hamburger.active span:nth-child(3) {
      transform: rotate(-45deg) translate(7px, -6px);
    }

    /* Overlay */
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

    .sidebar-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    /* Main Content */
    .main-content {
      margin-left: 0;
      transition: margin-left 0.3s ease;
    }

    .main-content.sidebar-open {
      margin-left: 300px;
    }
  </style>
</head>
<body>
  <!-- Sidebar Navigation -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <h3>💪 FitnessHub</h3>
    </div>
    <nav class="sidebar-nav">
      <a href="home.php">🏠 Home</a>
      <a href="add_meal.php">➕ Add Meal</a>
      <a href="daily_meals.php">📊 View Meals</a>
      <a href="profile.php" class="active">👤 My Profile</a>
      <a href="locations.php">📍 Locations</a>
      <a href="logout.php">🚪 Logout</a>
    </nav>
  </div>

  <!-- Hamburger Menu -->
  <div class="hamburger" id="hamburger">
    <span></span>
    <span></span>
    <span></span>
  </div>

  <!-- Overlay -->
  <div class="sidebar-overlay" id="overlay"></div>

  <div class="main-content" id="mainContent">
    <div class="container">
    <h1>👤 My Profile</h1>
    <p><strong>Name:</strong> <?php echo $user['Name']; ?></p>
    <p><strong>Email:</strong> <?php echo $user['Email']; ?></p>
    <p><strong>Phone:</strong> <?php echo $user['Phone']; ?></p>
    <p><strong>Age:</strong> <?php echo $user['Age']; ?></p>
    <p><strong>Gender:</strong> <?php echo ucfirst($user['Gender']); ?></p>
    <p><strong>Height:</strong> <?php echo $user['Height']; ?> cm</p>
    <p><strong>Weight:</strong> <?php echo $user['Weight']; ?> kg</p>

    <?php if ($goal): ?>
      <h2>🎯 Goal: <?php echo ucfirst($goal['goal_type']); ?></h2>
      <p><strong>Daily Calorie Target:</strong> <?php echo $goal['daily_calorie_target']; ?> cal</p>
    <?php else: ?>
      <p><em>No goal set yet.</em></p>
    <?php endif; ?>

    <a href="edit_profile.php" class="btn-edit">✏️ Edit Profile</a>
      </div>
    </div>
  </div>

  <script>
    // Sidebar functionality
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

    // Close sidebar on window resize
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



