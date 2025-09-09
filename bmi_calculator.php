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

// Fetch user height/weight
$stmt = $conn->prepare("SELECT Height, Weight FROM Users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($height_cm, $weight_kg);
$stmt->fetch();
$stmt->close();

// Convert height from cm → feet + inches for display
$height_in = $height_cm / 2.54;
$height_feet = floor($height_in / 12);
$height_inches = round($height_in % 12);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $weight = floatval($_POST['weight']);
    $feet = intval($_POST['height_feet']);
    $inches = intval($_POST['height_inches']);
    $total_inches = ($feet * 12) + $inches;
    $height = round($total_inches * 2.54, 2); // convert to cm

    if ($height <= 0 || $weight <= 0) {
        $error = "❌ Invalid height or weight.";
    } else {
        $bmi = round($weight / pow(($height / 100), 2), 2);

        $stmt = $conn->prepare("INSERT INTO BMI_History (user_id, bmi_value, weight, height, recorded_date) VALUES (?, ?, ?, ?, CURDATE())");
        $stmt->bind_param("iddi", $user_id, $bmi, $weight, $height);

        if ($stmt->execute()) {
            $success = "✅ BMI recorded successfully! Your BMI is $bmi";
        } else {
            $error = "❌ Failed to record BMI.";
        }
        $stmt->close();

        // Update user table with latest values
        $stmt = $conn->prepare("UPDATE Users SET Weight=?, Height=? WHERE user_id=?");
        $stmt->bind_param("ddi", $weight, $height, $user_id);
        $stmt->execute();
        $stmt->close();

        $weight_kg = $weight;
        $height_cm = $height;
        $height_in = $height_cm / 2.54;
        $height_feet = floor($height_in / 12);
        $height_inches = round($height_in % 12);
    }
}

// Fetch BMI history
$stmt = $conn->prepare("SELECT bmi_value, weight, height, recorded_date FROM BMI_History WHERE user_id=? ORDER BY recorded_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$history = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <title>BMI Calculator - FitnessHub</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Sidebar Navigation */
    .sidebar { position:fixed; left:-300px; top:0; width:300px; height:100vh;
      background:linear-gradient(135deg,#2c3e50,#34495e); backdrop-filter:blur(20px);
      border-right:1px solid rgba(255,255,255,0.1); transition:left 0.3s ease;
      z-index:2000; box-shadow:5px 0 20px rgba(0,0,0,0.3);}
    .sidebar.active{left:0;}
    .sidebar-header{padding:30px 20px; border-bottom:1px solid rgba(255,255,255,0.1); text-align:center;}
    .sidebar-header h3{color:white; margin:0; font-size:1.5rem; font-weight:700;}
    .sidebar-nav{padding:20px 0;}
    .sidebar-nav a{display:block; padding:15px 25px; color:rgba(255,255,255,0.8);
      text-decoration:none; font-weight:500; transition:all 0.3s ease; border-left:3px solid transparent;}
    .sidebar-nav a:hover{background:rgba(255,255,255,0.1); color:white; border-left-color:#3498db; transform:translateX(10px);}
    .sidebar-nav a.active{background:rgba(52,152,219,0.2); color:white; border-left-color:#3498db;}
    /* Hamburger Menu */
    .hamburger{position:fixed; top:20px; left:20px; width:40px; height:40px;
      background:rgba(255,255,255,0.1); backdrop-filter:blur(10px); border-radius:8px;
      cursor:pointer; z-index:2001; display:flex; flex-direction:column; justify-content:center;
      align-items:center; gap:4px; transition:all 0.3s ease; border:1px solid rgba(255,255,255,0.2);}
    .hamburger:hover{background:rgba(255,255,255,0.2); transform:scale(1.05);}
    .hamburger.active{left:320px;}
    .hamburger span{width:20px; height:2px; background:white; transition:all 0.3s ease; border-radius:1px;}
    .hamburger.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px);}
    .hamburger.active span:nth-child(2){opacity:0;}
    .hamburger.active span:nth-child(3){transform:rotate(-45deg) translate(7px,-6px);}
    /* Overlay */
    .sidebar-overlay{position:fixed; top:0; left:0; width:100%; height:100%;
      background:rgba(0,0,0,0.5); opacity:0; visibility:hidden; transition:all 0.3s ease; z-index:1999;}
    .sidebar-overlay.active{opacity:1; visibility:visible;}
    /* Main Content */
    .main-content{margin-left:0; transition:margin-left 0.3s ease;}
    .main-content.sidebar-open{margin-left:300px;}
    .bmi-box{text-align:center; margin-bottom:20px;}
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
      <a href="bmi_calculator.php" class="active">📊 BMI Calculator</a>
      <a href="diet_plan.php">🥗 Diet Plan</a>
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

  <!-- Main Content -->
  <div class="main-content" id="mainContent">
    <div class="container">
      <h1>📊 BMI Calculator</h1>

      <?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>
      <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>

      <form method="POST" class="bmi-box">
        <label>Weight (kg):</label>
        <input type="number" step="0.1" name="weight" value="<?= htmlspecialchars($weight_kg) ?>" required>

        <label>Height:</label>
        <div style="display:flex; gap:10px; justify-content:center;">
          <input type="number" name="height_feet" placeholder="Feet" min="3" max="8" value="<?= $height_feet ?>" required>
          <input type="number" name="height_inches" placeholder="Inches" min="0" max="11" value="<?= $height_inches ?>" required>
        </div>

        <button type="submit">Calculate & Save BMI</button>
      </form>

      <h2>📜 BMI History</h2>
      <?php if (count($history) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Weight (kg)</th>
              <th>Height (ft+in)</th>
              <th>BMI</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $row): 
              $h_in = $row['height'] / 2.54;
              $h_ft = floor($h_in / 12);
              $h_rem = round($h_in % 12);
            ?>
              <tr>
                <td><?= $row['recorded_date'] ?></td>
                <td><?= $row['weight'] ?></td>
                <td><?= $h_ft ?> ft <?= $h_rem ?> in</td>
                <td><?= $row['bmi_value'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="text-align:center;">No BMI records yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <script>
    const hamburger=document.getElementById('hamburger');
    const sidebar=document.getElementById('sidebar');
    const overlay=document.getElementById('overlay');
    const mainContent=document.getElementById('mainContent');
    function toggleSidebar(){
      hamburger.classList.toggle('active');
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
      mainContent.classList.toggle('sidebar-open');
    }
    hamburger.addEventListener('click',toggleSidebar);
    overlay.addEventListener('click',toggleSidebar);
    window.addEventListener('resize',()=>{if(window.innerWidth<=768){
      hamburger.classList.remove('active'); sidebar.classList.remove('active');
      overlay.classList.remove('active'); mainContent.classList.remove('sidebar-open');}});
  </script>
</body>
</html>
