<?php
session_start();
include("db_connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Get food items for dropdown
$stmt = $conn->prepare("SELECT food_id, name, calories FROM Food_Items ORDER BY name");
$stmt->execute();
$food_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $meal_type = $_POST['meal_type'];
    $food_id = $_POST['food_id'];
    $quantity = $_POST['quantity'];

    // Validate inputs
    if (empty($meal_type) || empty($food_id) || empty($quantity) || $quantity <= 0) {
        $error = "❌ Please fill all fields correctly.";
    } else {
        // Ensure diet plan exists for today
        $stmt = $conn->prepare("SELECT diet_id FROM Diet_Plans WHERE user_id=? AND date=CURDATE()");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $diet = $result->fetch_assoc();
            $diet_id = $diet['diet_id'];
        } else {
            $stmt2 = $conn->prepare("INSERT INTO Diet_Plans (user_id, date) VALUES (?, CURDATE())");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            $diet_id = $stmt2->insert_id;
            $stmt2->close();
        }
        $stmt->close();

        // Insert meal
        $stmt = $conn->prepare("INSERT INTO Meals (diet_id, food_id, meal_type, quantity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $diet_id, $food_id, $meal_type, $quantity);

        if ($stmt->execute()) {
            // Get food details for success message
            $stmt2 = $conn->prepare("SELECT name, calories FROM Food_Items WHERE food_id = ?");
            $stmt2->bind_param("i", $food_id);
            $stmt2->execute();
            $food = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            
            $total_calories = $food['calories'] * $quantity;
            $message = "✅ Meal added successfully!<br>
                       🍽️ {$food['name']} ({$quantity}x)<br>
                       🔥 Total Calories: {$total_calories}";
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get today's calorie summary
$stmt = $conn->prepare("
    SELECT 
        SUM(f.calories * m.quantity) as total_calories,
        COUNT(*) as meal_count
    FROM Meals m 
    JOIN Food_Items f ON m.food_id = f.food_id
    JOIN Diet_Plans d ON m.diet_id = d.diet_id
    WHERE d.user_id = ? AND d.date = CURDATE()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$today_summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_calories = $today_summary['total_calories'] ?? 0;
$meal_count = $today_summary['meal_count'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
  <title>Add Meal - FitnessHub</title>
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
    .hamburger:hover { background: rgba(255,255,255,0.2); transform: scale(1.05); }
    .hamburger.active { left: 320px; }
    .hamburger span { width: 20px; height: 2px; background: white; transition: all 0.3s ease; border-radius: 1px; }
    .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
    .hamburger.active span:nth-child(2) { opacity: 0; }
    .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(7px, -6px); }

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
    .sidebar-overlay.active { opacity: 1; visibility: visible; }

    /* Main Content */
    .main-content { margin-left: 0; transition: margin-left 0.3s ease; }
    .main-content.sidebar-open { margin-left: 300px; }

    .summary-box {
      background: #e8f5e8;
      border: 1px solid #2ecc71;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
      text-align: center;
    }
    .form-row { display: flex; gap: 15px; align-items: end; }
    .form-row > div { flex: 1; }
    .calorie-preview {
      background: #fff3cd;
      border: 1px solid #ffeaa7;
      border-radius: 6px;
      padding: 10px;
      margin-top: 10px;
      font-size: 14px;
      display: none;
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
      <a href="add_meal.php" class="active">➕ Add Meal</a>
      <a href="daily_meals.php">📊 View Meals</a>
      <a href="meal_history.php">📅 Meal History</a>
      <a href="bmi_calculator.php">📊 BMI Calculator</a>
      <a href="diet_plan.php">🥗 Diet Plan</a>
      <a href="workout_manager.php">💪 Workouts</a>
      <a href="achievements.php">🏆 Achievements</a>
      <a href="performance_report.php">📈 Performance Report</a>
      <a href="profile.php">👤 My Profile</a>
      <a href="locations.php">📍 Locations</a>
      <a href="logout.php">🚪 Logout</a>
    </nav>
  </div>

  <!-- Hamburger Menu -->
  <div class="hamburger" id="hamburger">
    <span></span><span></span><span></span>
  </div>

  <!-- Overlay -->
  <div class="sidebar-overlay" id="overlay"></div>

  <div class="main-content" id="mainContent">
    <div class="container">
      <h1>🍽️ Add Meal</h1>
      
      <!-- Today's Summary -->
      <div class="summary-box">
        <h3>📊 Today's Summary</h3>
        <p>🔥 Total Calories: <strong><?php echo $total_calories; ?></strong></p>
        <p>🍽️ Meals Added: <strong><?php echo $meal_count; ?></strong></p>
      </div>

      <?php if ($error != ""): ?>
        <p class="error"><?php echo $error; ?></p>
      <?php endif; ?>

      <?php if ($message != ""): ?>
        <p class="success"><?php echo $message; ?></p>
      <?php endif; ?>

      <form method="POST" action="add_meal.php">
        <div class="form-row">
          <div>
            <label>Meal Type:</label>
            <select name="meal_type" required>
              <option value="">Select meal type</option>
              <option value="breakfast">🌅 Breakfast</option>
              <option value="lunch">☀️ Lunch</option>
              <option value="dinner">🌙 Dinner</option>
              <option value="snack">🍎 Snack</option>
            </select>
          </div>
          
          <div>
            <label>Food Item:</label>
            <select name="food_id" id="food_select" required>
              <option value="">Select food item</option>
              <?php foreach ($food_items as $food): ?>
                <option value="<?php echo $food['food_id']; ?>" data-calories="<?php echo $food['calories']; ?>">
                  <?php echo $food['name']; ?> (<?php echo $food['calories']; ?> cal)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label>Quantity:</label>
            <input type="number" name="quantity" id="quantity" min="1" max="10" value="1" required>
          </div>
        </div>

        <div class="calorie-preview" id="calorie_preview">
          🔥 Estimated Calories: <span id="estimated_calories">0</span>
        </div>

        <button type="submit">➕ Add Meal</button>
      </form>

      <div style="margin-top: 20px; text-align: center;">
        <a href="daily_meals.php" class="btn-edit">📊 View Today's Meals</a>
        <a href="home.php" class="btn-edit">🏠 Back to Home</a>
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

    window.addEventListener('resize', () => {
      if (window.innerWidth <= 768) {
        hamburger.classList.remove('active');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        mainContent.classList.remove('sidebar-open');
      }
    });

    // Real-time calorie calculation
    const foodSelect = document.getElementById('food_select');
    const quantityInput = document.getElementById('quantity');
    const caloriePreview = document.getElementById('calorie_preview');
    const estimatedCalories = document.getElementById('estimated_calories');

    function updateCaloriePreview() {
      const selectedOption = foodSelect.options[foodSelect.selectedIndex];
      const quantity = parseInt(quantityInput.value) || 0;
      
      if (selectedOption && selectedOption.dataset.calories) {
        const caloriesPerUnit = parseInt(selectedOption.dataset.calories);
        const totalCalories = caloriesPerUnit * quantity;
        estimatedCalories.textContent = totalCalories;
        caloriePreview.style.display = 'block';
      } else {
        caloriePreview.style.display = 'none';
      }
    }

    foodSelect.addEventListener('change', updateCaloriePreview);
    quantityInput.addEventListener('input', updateCaloriePreview);
  </script>
</body>
</html>


