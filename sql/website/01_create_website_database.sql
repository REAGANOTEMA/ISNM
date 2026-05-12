-- ISNM Website Database Schema
-- Database: website_db

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS website_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE website_db;

-- Drop existing tables if they exist (for fresh installation)
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS galleries;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS contact_submissions;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS settings;

-- 1. Pages Table
CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    meta_title VARCHAR(200),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    status ENUM('Published', 'Draft', 'Archived') DEFAULT 'Draft',
    featured_image VARCHAR(500),
    page_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_page_order (page_order)
);

-- 2. Posts Table (Blog/News)
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(500),
    category_id INT,
    author VARCHAR(100),
    status ENUM('Published', 'Draft', 'Archived') DEFAULT 'Draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_category_id (category_id),
    INDEX idx_published_at (published_at)
);

-- 3. Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_parent_id (parent_id)
);

-- 4. Galleries Table
CREATE TABLE galleries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    folder_name VARCHAR(100) NOT NULL,
    cover_image VARCHAR(500),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- 5. Applications Table
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    course VARCHAR(100),
    year INT,
    previous_school VARCHAR(200),
    guardian_name VARCHAR(100),
    guardian_phone VARCHAR(20),
    message TEXT,
    status ENUM('New', 'Under Review', 'Accepted', 'Rejected', 'Waitlisted') DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_course (course),
    INDEX idx_created_at (created_at)
);

-- 6. Contact Submissions Table
CREATE TABLE contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message LONGTEXT NOT NULL,
    status ENUM('New', 'Read', 'Responded') DEFAULT 'New',
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- 7. News Table
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(500),
    status ENUM('Published', 'Draft', 'Archived') DEFAULT 'Draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at)
);

-- 8. Settings Table (Enhanced)
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_type ENUM('text', 'number', 'boolean', 'file', 'json', 'array', 'email', 'url', 'color', 'date') DEFAULT 'text',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    is_public BOOLEAN DEFAULT FALSE,
    is_editable BOOLEAN DEFAULT TRUE,
    validation_rules JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_setting_type (setting_type),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public),
    INDEX idx_is_editable (is_editable)
);

-- Insert sample data for testing

-- Sample pages
INSERT INTO pages (title, slug, content, meta_title, meta_description, meta_keywords, status, page_order) VALUES
('Home', 'home', '<h1>Welcome to ISNM</h1><p>Professional nursing and midwifery education.</p>', 'Home - ISNM', 'Welcome to Iganga School of Nursing and Midwifery', 'nursing, midwifery, education, uganda', 'Published', 1),
('About Us', 'about-us', '<h1>About ISNM</h1><p>Leading nursing and midwifery education in Uganda.</p>', 'About ISNM', 'Learn about our mission and values', 'about, mission, values', 'Published', 2),
('Programs', 'programs', '<h1>Our Programs</h1><p>Comprehensive nursing and midwifery programs.</p>', 'Programs - ISNM', 'Explore our academic programs', 'programs, courses, academics', 'Published', 3),
('Contact', 'contact', '<h1>Contact Us</h1><p>Get in touch with our team.</p>', 'Contact ISNM', 'Contact information and location', 'contact, location, address', 'Published', 4);

-- Sample categories
INSERT INTO categories (name, slug, description) VALUES
('News', 'news', 'Latest news and announcements'),
('Events', 'events', 'Upcoming events and activities'),
('Academics', 'academics', 'Academic information and updates'),
('Admissions', 'admissions', 'Admission information and requirements');

-- Sample posts
INSERT INTO posts (title, slug, content, excerpt, category_id, author, status, published_at) VALUES
('Welcome to ISNM', 'welcome-to-isnm', '<h1>Welcome to ISNM</h1><p>We are pleased to welcome you to our institution.</p>', 'Welcome message for new students and visitors.', 1, 'Administrator', 'Published', NOW()),
('New Academic Year 2025/2026', 'new-academic-year-2025-2026', '<h1>New Academic Year</h1><p>Applications are now open for the 2025/2026 academic year.</p>', 'Applications are open for the new academic year.', 1, 'Administrator', 'Published', NOW());

-- Sample galleries
INSERT INTO galleries (title, description, folder_name, cover_image, status) VALUES
('Campus Life', 'Photos of campus activities and facilities', 'campus-life', 'images/gallery/campus1.jpg', 'Active'),
('Graduation Ceremony', 'Recent graduation ceremony photos', 'graduation-2025', 'images/gallery/graduation1.jpg', 'Active'),
('Clinical Training', 'Clinical practice and training sessions', 'clinical-training', 'images/gallery/clinical1.jpg', 'Active');

-- Sample settings
INSERT INTO settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES
('school_name', 'Institute of Strategic Nursing and Midwifery', 'text', 'School name for display', 'general', TRUE),
('school_address', 'P.O. Box 418, Iganga, Uganda', 'text', 'School address', 'general', TRUE),
('school_phone', '+256 123 456 789', 'text', 'School phone number', 'general', TRUE),
('school_email', 'info@isnm.ac.ug', 'email', 'School email address', 'general', TRUE),
('school_website', 'www.isnm.ac.ug', 'url', 'School website URL', 'general', TRUE),
('mission_statement', 'To provide quality nursing and midwifery education for healthcare excellence', 'text', 'School mission statement', 'general', TRUE),
('vision_statement', 'To be a leading institution in nursing and midwifery education', 'text', 'School vision statement', 'general', TRUE),
('admissions_open', 'true', 'boolean', 'Admissions status', 'admissions', TRUE),
('current_academic_year', '2025/2026', 'text', 'Current academic year', 'academic', TRUE),
('current_semester', 'Semester 1', 'text', 'Current semester', 'academic', TRUE),
('contact_email', 'info@isnm.ac.ug', 'email', 'Contact email', 'contact', TRUE),
('contact_phone', '+256 123 456 789', 'text', 'Contact phone', 'contact', TRUE),
('social_media_facebook', 'https://facebook.com/ISNMUganda', 'url', 'Facebook page URL', 'social', TRUE),
('social_media_twitter', 'https://twitter.com/ISNMUganda', 'url', 'Twitter profile URL', 'social', TRUE),
('site_maintenance', 'false', 'boolean', 'Site maintenance mode', 'system', FALSE),
('enable_notifications', 'true', 'boolean', 'Enable email notifications', 'notifications', FALSE),
('max_upload_size', '10485760', 'number', 'Maximum upload file size in bytes', 'system', FALSE),
('default_language', 'en', 'text', 'Default site language', 'ui', FALSE),
('timezone', 'Africa/Kampala', 'text', 'System timezone', 'system', FALSE),
('backup_frequency', 'daily', 'text', 'Backup frequency', 'system', FALSE),
('enable_analytics', 'true', 'boolean', 'Enable analytics tracking', 'analytics', FALSE),
('seo_meta_description', 'ISNM - Leading nursing and midwifery education in Uganda', 'text', 'Default SEO meta description', 'seo', TRUE),
('seo_meta_keywords', 'nursing, midwifery, education, Uganda, ISNM, healthcare', 'text', 'Default SEO meta keywords', 'seo', TRUE),
('google_analytics_code', '', 'text', 'Google Analytics tracking code', 'analytics', FALSE),
('facebook_pixel_code', '', 'text', 'Facebook Pixel tracking code', 'analytics', FALSE),
('cookie_policy', 'This site uses cookies to improve your experience.', 'text', 'Cookie policy text', 'privacy', TRUE),
('privacy_policy', 'Your privacy is important to us...', 'text', 'Privacy policy content', 'privacy', TRUE),
('terms_of_service', 'By using this site, you agree to...', 'text', 'Terms of service content', 'legal', TRUE);

-- End of Website Database Schema
