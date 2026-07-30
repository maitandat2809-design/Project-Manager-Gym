CREATE DATABASE IF NOT EXISTS gym_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gym_manager;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin', 'coach') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    specialty VARCHAR(100),
    bio TEXT,
    hourly_rate DECIMAL(10, 2),
    image VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    coach_id INT,
    booking_date DATETIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed some initial data
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Coach John', 'john@gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coach');

INSERT INTO coaches (user_id, specialty, bio, hourly_rate, image) VALUES 
(2, 'Yoga & Pilates', 'Certified instructor with 10 years of experience.', 50.00, 'coach1.jpg');

INSERT INTO products (name, description, price, stock, image) VALUES 
('Whey Protein', 'High quality whey protein for muscle recovery.', 45.99, 50, 'product1.jpg'),
('Yoga Mat', 'Non-slip yoga mat for all types of exercises.', 19.99, 100, 'product2.jpg');
