<?php
session_start();
include("db_connect.php");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $weight = $_POST['weight'];
    $phone = $_POST['phone'];

    // ✅ Convert feet + inches to cm
    $height_feet = intval($_POST['height_feet']);
    $height_inches = intval($_POST['height_inches']);
    $total_inches = ($height_feet * 12) + $height_inches;
    $height = round($total_inches * 2.54, 2); // store in cm

    if ($password !== $confirm_password) {
        $error = "❌ Passwords do not match!";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM Users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "❌ Email already registered!";
            $stmt->close();
        } else {
            $stmt->close();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO Users (Name, Email, Password, Age, Gender, Height, Weight, Phone) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssisdss", $name, $email, $hashed_password, $age, $gender, $height, $weight, $phone);

            if ($stmt->execute()) {
                $success = "✅ Registration successful! <a href='login.php'>Login</a>";
            } else {
                $error = "❌ Error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register - FitnessHub</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Sidebar Navigation (same as other pages) */
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

    /* Hamburger */
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

    .main-content { margin-left: 0; transition: margin-left 0.3s ease; }
    .main-content.sidebar-open { margin-left: 300px; }

    /* Form styling */
    .container {
      max-width: 500px;
      margin: 60px auto 0 auto;
      background: #fff;
      padding: 30px 30px 20px 30px;
      border-radius: 12px;
      box-shadow: 0 4px 24px rgba(44,62,80,0.08);
    }
    h1 { text-align: center; margin-bottom: 20px; }
    label { display: block; margin-top: 15px; font-weight: 500; }
    input, select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid #b2bec3;
      font-size: 1rem;
      box-sizing: border-box;
    }
    button[type="submit"] {
      width: 100%;
      background: #3498db;
      color: #fff;
      border: none;
      padding: 12px;
      border-radius: 6px;
      font-size: 1.1rem;
      font-weight: 600;
      margin-top: 20px;
      cursor: pointer;
      transition: background 0.2s;
    }
    button[type="submit"]:hover { background: #217dbb; }
    .error { color: #e74c3c; background: #fdecea; padding: 10px; border-radius: 6px; margin-bottom: 10px; text-align: center;}
    .success { color: #27ae60; background: #eafaf1; padding: 10px; border-radius: 6px; margin-bottom: 10px; text-align: center;}
  </style>
</head>
<body>
  <!-- Sidebar Navigation -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header"><h3>💪 FitnessHub</h3></div>
    <nav class="sidebar-nav">
      <a href="index.php">🏠 Home</a>
      <a href="login.php">🔑 Login</a>
      <a href="register.php" class="active">📝 Register</a>
    </nav>
  </div>

  <!-- Hamburger -->
  <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
  <div class="sidebar-overlay" id="overlay"></div>

  <!-- Main Content -->
  <div class="main-content" id="mainContent">
    <div class="container">
      <h1>📝 Create Account</h1>

      <?php if ($error != ""): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
      <?php if ($success != ""): ?><p class="success"><?php echo $success; ?></p><?php endif; ?>

      <form method="POST" action="register.php">
        <label>Name:</label>
        <input type="text" name="name" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required>

        <label>Age:</label>
        <input type="number" name="age" min="10" max="100" required>

        <label>Gender:</label>
        <select name="gender" required>
          <option value="">Select</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
        </select>

        <label>Height:</label>
        <div style="display:flex; gap:10px;">
          <input type="number" name="height_feet" placeholder="Feet" min="3" max="8" required>
          <input type="number" name="height_inches" placeholder="Inches" min="0" max="11" required>
        </div>

        <label>Weight (kg):</label>
        <input type="number" step="0.01" name="weight" required>

        <label>Phone:</label>
        <input type="text" name="phone">

        <button type="submit">Register</button>
      </form>

      <p style="text-align:center; margin-top:10px;">
        Already have an account? <a href="login.php">Login</a>
      </p>
    </div>
  </div>

  <!-- Sidebar Script -->
  <script>
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
  </script>
</body>
</html>





