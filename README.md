
🏋️‍♀️ FitnessHub

FitnessHub is a web application that helps people stay healthy by tracking their meals, workouts, BMI, goals, and achievements.
It is built using PHP, MySQL, HTML, and CSS.

✨ Main Features

 👤 User Accounts – Register, log in, and edit your profile.
 🍽️ Meals – Add what you eat daily, see today’s meals, and check meal history.
 🥗 Diet Plans – Create daily diet plans linked with meals.
 ⚖️ BMI & Goals – Calculate BMI, save BMI history, and set fitness goals.
 🏋️ Workouts – Add exercises, create workout plans, log sessions and sets.
 🏆 Achievements – Unlock achievements as you make progress.
 📍 Locations – View gyms or fitness places near BRAC University.
 ⏰ Reminders – Set reminders for workouts or meals.

🛠️ Tools Used
 Frontend: HTML, CSS
 Backend: PHP
 Database: MySQL

📂 Project Files
* `index.php` → Homepage
 *`register.php`, `login.php`, `logout.php` → User authentication
* `profile.php`, `edit_profile.php` → Profile management
* `add_meal.php`, `daily_meals.php`, `meal_history.php` → Meal tracking
* `diet_plan.php` → Diet planning
* `bmi_calculator.php` → BMI calculation and history
* `workout_manager.php` → Workout tracking
* `achievements.php` → Achievements
* `performance_report.php` → Reports
* `locations.php` → Fitness locations
* `db_connect.php` → Database connection
* `fitnesshub.sql` → Database file
* `style.css` → Styling

🗄️ Database (Main Tables)
* Users → user info
* Goals → fitness goals
* Food_Items → food with calories
* Diet_Plans → daily diet plans
* Meals → meals eaten
* BMI_History → BMI records
* **Achievements & User\_Achievements** → progress milestones
* **Exercises & Workouts** → exercise tracking
* **Locations** → gyms and fitness places
* **Reminders** → user reminders

⚙️ How to Run
1. Copy project folder into your `htdocs` (XAMPP) or server.
2. Import `fitnesshub.sql` into MySQL (via phpMyAdmin).
3. Edit `db_connect.php` with your MySQL username and password.
4. Open in browser

   http://localhost/fitnesshub
  

✅ This project is a complete fitness tracker that combines diet, workouts, and goals into one simple system.

