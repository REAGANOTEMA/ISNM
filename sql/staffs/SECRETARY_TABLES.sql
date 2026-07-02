CREATE TABLE IF NOT EXISTS `igangaschoolofl_staffs_db`.`secretary_appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `appointment_date` DATE NOT NULL,
    `appointment_time` TIME NOT NULL,
    `location` VARCHAR(255),
    `status` ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `igangaschoolofl_staffs_db`.`secretary_meetings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `meeting_date` DATE NOT NULL,
    `meeting_time` TIME NOT NULL,
    `duration_minutes` INT DEFAULT 60,
    `location` VARCHAR(255),
    `attendees` TEXT,
    `status` ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `igangaschoolofl_staffs_db`.`secretary_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `file_path` VARCHAR(500),
    `file_type` VARCHAR(50),
    `category` VARCHAR(100),
    `status` ENUM('active','archived') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `igangaschoolofl_staffs_db`.`secretary_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `recipient_id` INT NOT NULL,
    `subject` VARCHAR(255),
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `igangaschoolofl_staffs_db`.`secretary_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `request_type` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `priority` ENUM('low','medium','high') DEFAULT 'medium',
    `status` ENUM('pending','in_progress','resolved','rejected') DEFAULT 'pending',
    `assigned_to` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `igangaschoolofl_staffs_db`.`secretary_announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `target_audience` VARCHAR(100) DEFAULT 'all',
    `is_active` TINYINT(1) DEFAULT 1,
    `publish_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `igangaschoolofl_staffs_db`.`secretary_contacts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `contact_name` VARCHAR(255) NOT NULL,
    `contact_email` VARCHAR(255),
    `contact_phone` VARCHAR(50),
    `organization` VARCHAR(255),
    `category` VARCHAR(100),
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
