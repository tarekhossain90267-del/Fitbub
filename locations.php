<?php
session_start();
include("db_connect.php");

// Fetch BRAC University coordinates from Locations table
$sql = "SELECT latitude, longitude FROM Locations WHERE name='BRAC University' LIMIT 1";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

// Use coordinates from database or fallback
if ($row) {
    $latitude = $row['latitude'];
    $longitude = $row['longitude'];
} else {
    $latitude = 23.772500;
    $longitude = 90.425400;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Gym & Jogging Locations - FitnessHub</title>
  <link rel="stylesheet" href="style.css">
  <style>
    #map { height: 500px; width: 100%; border-radius: 10px; margin-top: 20px; }
    .results-box {
      margin-top: 30px;
      padding: 20px;
      background: #f9f9f9;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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

    /* Sidebar */
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

    /* Main */
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
    .tagline { text-align: center; color: #3498db; font-weight: 500; margin-bottom: 20px; }
  </style>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
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
      <a href="meal_history.php">📅 Meal History</a>
      <a href="bmi_calculator.php">📊 BMI Calculator</a>
      <a href="diet_plan.php">🥗 Diet Plan</a>
      <a href="workout_manager.php">💪 Workouts</a>
      <a href="achievements.php">🏆 Achievements</a>
      <a href="performance_report.php">📈 Performance Report</a>
      <a href="profile.php">👤 My Profile</a>
      <a href="locations.php" class="active">📍 Gym & Jogging Locations</a>
      <a href="logout.php">🚪 Logout</a>
    </nav>
  </div>

  <!-- Hamburger -->
  <div class="hamburger" id="hamburger">
    <span></span><span></span><span></span>
  </div>
  <div class="sidebar-overlay" id="overlay"></div>

  <!-- Main -->
  <div class="main-content" id="mainContent">
    <div class="container">
      <h1>📍 Gym & Jogging Locations Near BRAC University</h1>
      <p class="tagline">Explore gyms and running tracks within 2 km</p>

      <div id="map"></div>

      <div class="results-box">
        <h2>📋 Nearby Spots</h2>
        <p>Below is the list of gyms and jogging locations fetched within 2km radius:</p>
        <table id="resultsTable">
          <thead>
            <tr>
              <th>Name</th>
              <th>Type</th>
              <th>Distance (approx)</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

<script>
// Sidebar JS
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

// Leaflet Map
var map = L.map('map').setView([<?php echo $latitude; ?>, <?php echo $longitude; ?>], 15);

// Tiles
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Marker for BRAC University
L.marker([<?php echo $latitude; ?>, <?php echo $longitude; ?>])
  .addTo(map)
  .bindPopup("🎓 BRAC University");

// Overpass Query
var query = `
[out:json][timeout:25];
(
  node["leisure"="fitness_centre"](around:2000,<?php echo $latitude; ?>,<?php echo $longitude; ?>);
  way["leisure"="fitness_centre"](around:2000,<?php echo $latitude; ?>,<?php echo $longitude; ?>);

  node["leisure"="track"](around:2000,<?php echo $latitude; ?>,<?php echo $longitude; ?>);
  way["leisure"="track"](around:2000,<?php echo $latitude; ?>,<?php echo $longitude; ?>);

  way["highway"="footway"]["sport"="running"](around:2000,<?php echo $latitude; ?>,<?php echo $longitude; ?>);
  relation["route"="running"](around:2000,<?php echo $latitude; ?>,<?php echo $longitude; ?>);
);
out center;
`;

function haversine(lat1, lon1, lat2, lon2) {
  function toRad(x) { return x * Math.PI / 180; }
  var R = 6371; // km
  var dLat = toRad(lat2-lat1);
  var dLon = toRad(lon2-lon1);
  var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
          Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
          Math.sin(dLon/2) * Math.sin(dLon/2);
  var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return (R * c * 1000).toFixed(0); // meters
}

fetch("https://overpass-api.de/api/interpreter", {
  method: "POST",
  body: query
})
.then(res => res.json())
.then(data => {
  const tableBody = document.querySelector("#resultsTable tbody");

  data.elements.forEach(el => {
    var lat = el.lat || (el.center && el.center.lat);
    var lon = el.lon || (el.center && el.center.lon);
    if (!lat || !lon) return;

    let name = el.tags && el.tags.name ? el.tags.name : "Unnamed";
    let type = (el.tags && el.tags.leisure === "fitness_centre") ? "Gym" : "Jogging";

    // Marker
    let iconUrl = (type === "Gym")
      ? 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
      : 'https://maps.google.com/mapfiles/ms/icons/green-dot.png';

    L.marker([lat, lon], { icon: L.icon({
      iconUrl: iconUrl, iconSize: [32, 32]
    })})
    .addTo(map)
    .bindPopup((type === "Gym" ? "🏋 " : "🏃 ") + name);

    // Distance
    let distance = haversine(<?php echo $latitude; ?>, <?php echo $longitude; ?>, lat, lon);

    // Add to table
    let row = `<tr>
      <td>${name}</td>
      <td>${type}</td>
      <td>~${distance} m</td>
    </tr>`;
    tableBody.innerHTML += row;
  });
});
</script>
</body>
</html>




