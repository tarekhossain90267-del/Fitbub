CREATE DATABASE IF NOT EXISTS fitnesshub;
USE fitnesshub;

-- Drop in correct order (children → parents)
DROP TABLE IF EXISTS Workout_Sets;
DROP TABLE IF EXISTS Workout_Sessions;
DROP TABLE IF EXISTS Workout_Exercises;
DROP TABLE IF EXISTS Workout_Plans;
DROP TABLE IF EXISTS Exercises;
DROP TABLE IF EXISTS User_Achievements;
DROP TABLE IF EXISTS Achievements;
DROP TABLE IF EXISTS BMI_History;
DROP TABLE IF EXISTS Meals;
DROP TABLE IF EXISTS Diet_Plans;
DROP TABLE IF EXISTS Goals;
DROP TABLE IF EXISTS Food_Items;
DROP TABLE IF EXISTS Locations;
DROP TABLE IF EXISTS Users;

-- Users
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(50) NOT NULL,
    Age INT NULL,
    Gender ENUM('male','female') NULL,
    Height DECIMAL(5,2) NULL,
    Weight DECIMAL(5,2) NULL,
    Phone VARCHAR(20) NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Goals
CREATE TABLE Goals (
    goal_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    goal_type ENUM('weight_loss','weight_gain','muscle_gain','bmi_target','maintain') NOT NULL,
    daily_calorie_target INT NOT NULL,
    current_bmi DECIMAL(5,2) NULL,
    target_bmi DECIMAL(5,2) NULL,
    target_weight DECIMAL(5,2) NULL,
    goal_description TEXT NULL,
    target_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Food Items
CREATE TABLE Food_Items (
    food_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    calories INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default food data
INSERT INTO Food_Items (name, calories) VALUES
('Plain Rice (1 cup)', 206),
('Boiled Egg (1 piece)', 78),
('Dal (1 cup)', 198),
('Fish Curry (100g)', 200),
('Chicken Curry (100g)', 240),
('Beef Curry (100g)', 250),
('Vegetable Curry (1 cup)', 150),
('Roti/Chapati (1 piece)', 120),
('Milk (1 glass)', 150),
('Banana (1 medium)', 105),
('Apple (1 medium)', 95);

-- Diet Plans
CREATE TABLE Diet_Plans (
    diet_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, 
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date)
);

-- Meals
CREATE TABLE Meals (
    meal_id INT AUTO_INCREMENT PRIMARY KEY,
    diet_id INT NOT NULL,
    food_id INT NOT NULL,
    meal_type ENUM('breakfast','lunch','dinner','snack') NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (diet_id) REFERENCES Diet_Plans(diet_id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES Food_Items(food_id) ON DELETE CASCADE,
    UNIQUE KEY unique_meal_per_day (diet_id, food_id, meal_type)
);

-- Locations
CREATE TABLE Locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    latitude DECIMAL(9,6) NOT NULL,
    longitude DECIMAL(9,6) NOT NULL
);

INSERT INTO Locations (name, latitude, longitude)
VALUES ('BRAC University', 23.772500, 90.425400);

-- BMI History
CREATE TABLE BMI_History (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bmi_value DECIMAL(5,2) NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    height DECIMAL(5,2) NOT NULL,
    recorded_date DATE NOT NULL,
    notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Achievements
CREATE TABLE Achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('meals','workouts','bmi','goals') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE User_Achievements (
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    progress_value INT DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    earned_date DATE NULL,
    PRIMARY KEY (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES Achievements(achievement_id) ON DELETE CASCADE
);

-- Exercises & Workouts
CREATE TABLE Exercises (
    exercise_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    muscle_group VARCHAR(50) NOT NULL,
    difficulty_level ENUM('beginner','intermediate','advanced') NOT NULL,
    equipment_needed VARCHAR(100) DEFAULT 'None',
    calories_per_minute DECIMAL(5,2) NOT NULL,
    instructions TEXT,
    safety_tips TEXT
);

CREATE TABLE Workout_Plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    description TEXT,
    difficulty_level ENUM('beginner','intermediate','advanced') NOT NULL,
    duration_minutes INT NOT NULL,
    frequency_per_week INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Workout_Exercises (
    workout_exercise_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    exercise_id INT NOT NULL,
    exercise_order INT NOT NULL,
    target_sets INT,
    target_reps INT,
    target_weight DECIMAL(5,2),
    rest_seconds INT,
    FOREIGN KEY (plan_id) REFERENCES Workout_Plans(plan_id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES Exercises(exercise_id) ON DELETE CASCADE
);

CREATE TABLE Workout_Sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    total_duration_minutes INT,
    calories_burned INT DEFAULT 0,
    difficulty_rating VARCHAR(50),
    completion_status ENUM('partial','completed') DEFAULT 'partial',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES Workout_Plans(plan_id) ON DELETE CASCADE
);

CREATE TABLE Workout_Sets (
    set_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    exercise_id INT NOT NULL,
    set_number INT,
    reps_completed INT,
    weight_used DECIMAL(5,2),
    duration_seconds INT,
    difficulty_rating VARCHAR(50),
    FOREIGN KEY (session_id) REFERENCES Workout_Sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES Exercises(exercise_id) ON DELETE CASCADE
);

-- Reminders
CREATE TABLE Reminders (
    reminder_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reminder_text VARCHAR(255) NOT NULL,
    reminder_time TIME NULL, -- optional, if user wants a time for the reminder
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX idx_dietplans_date ON Diet_Plans(date);
CREATE INDEX idx_meals_mealtype ON Meals(meal_type);
CREATE INDEX idx_goals_user_id ON Goals(user_id);
CREATE INDEX idx_dietplans_user_date ON Diet_Plans(user_id, date);
