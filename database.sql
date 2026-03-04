CREATE TABLE IF NOT EXISTS calendars (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_time TIME NOT NULL DEFAULT '08:00:00',
    end_time TIME NOT NULL DEFAULT '20:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS calendar_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calendar_id VARCHAR(36) NOT NULL,
    date_value DATE NOT NULL,
    FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255),
    cookie_hash VARCHAR(64) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS availabilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calendar_day_id INT NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    time_slot TIME NOT NULL,
    status ENUM('available', 'maybe') DEFAULT 'available',
    FOREIGN KEY (calendar_day_id) REFERENCES calendar_days(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_slot (calendar_day_id, user_id, time_slot)
);
