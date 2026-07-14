-- ============================================================
-- ISNM ENTERPRISE CMS — COMPLETE DATABASE SCHEMA
-- Role-based content management for institutional website
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. CMS PAGES — Every public-facing page
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(150) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `page_type` enum('static','dynamic','system') NOT NULL DEFAULT 'static',
  `template` varchar(100) DEFAULT 'default',
  `hero_title` varchar(255) DEFAULT NULL,
  `hero_subtitle` varchar(500) DEFAULT NULL,
  `hero_image` varchar(500) DEFAULT NULL,
  `hero_overlay_color` varchar(20) DEFAULT 'rgba(26,35,126,0.7)',
  `content` longtext DEFAULT NULL,
  `sidebar_content` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `og_title` varchar(255) DEFAULT NULL,
  `og_description` text DEFAULT NULL,
  `og_image` varchar(500) DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL,
  `schema_type` varchar(50) DEFAULT NULL,
  `schema_data` longtext DEFAULT NULL,
  `og_type` varchar(50) DEFAULT 'website',
  `og_locale` varchar(10) DEFAULT 'en_US',
  `twitter_card` varchar(50) DEFAULT 'summary_large_image',
  `twitter_site` varchar(100) DEFAULT NULL,
  `twitter_creator` varchar(100) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `allow_comments` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `page_views` bigint(20) DEFAULT 0,
  `last_viewed_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_page_type` (`page_type`),
  KEY `idx_published` (`is_published`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. CMS CONTENT BLOCKS — Reusable content sections per page
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_content_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) DEFAULT NULL,
  `block_key` varchar(100) NOT NULL,
  `block_type` enum('text','html','image','gallery','video','stats','cards','timeline','testimonials','cta','faq','accordion','map','embed','custom') NOT NULL DEFAULT 'text',
  `title` varchar(255) DEFAULT NULL,
  `subtitle` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `settings` longtext DEFAULT NULL,
  `animation` varchar(50) DEFAULT 'fade-up',
  `background_style` varchar(100) DEFAULT NULL,
  `text_color` varchar(20) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_block_page` (`page_id`),
  KEY `idx_block_key` (`block_key`),
  KEY `idx_block_sort` (`sort_order`),
  CONSTRAINT `fk_block_page` FOREIGN KEY (`page_id`) REFERENCES `cms_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. CMS SETTINGS — Global site configuration
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `value_type` enum('text','textarea','json','image','boolean','integer','color') DEFAULT 'text',
  `label` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_setting_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. CMS HERO BANNERS — Homepage and page hero sliders
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_slug` varchar(150) DEFAULT 'home',
  `title` varchar(255) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `mobile_image_url` varchar(500) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `link_text` varchar(100) DEFAULT NULL,
  `overlay_color` varchar(30) DEFAULT 'rgba(26,35,126,0.7)',
  `text_color` varchar(20) DEFAULT '#ffffff',
  `text_position` enum('center','left','right','bottom-left') DEFAULT 'center',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_banner_page` (`page_slug`),
  KEY `idx_banner_active` (`is_active`),
  KEY `idx_banner_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. CMS GALLERY — Image gallery with categories
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_gallery_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cms_gallery_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gallery_cat` (`category_id`),
  KEY `idx_gallery_sort` (`sort_order`),
  CONSTRAINT `fk_gallery_cat` FOREIGN KEY (`category_id`) REFERENCES `cms_gallery_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. CMS EVENTS — Events calendar
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `event_type` enum('academic','ceremony','workshop','seminar','conference','sports','social','other') DEFAULT 'other',
  `image_url` varchar(500) DEFAULT NULL,
  `registration_url` varchar(500) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `current_participants` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_event_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 7. CMS TESTIMONIALS — Student/Alumni testimonials
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author_name` varchar(200) NOT NULL,
  `author_title` varchar(200) DEFAULT NULL,
  `author_image` varchar(500) DEFAULT NULL,
  `author_role` enum('student','alumni','staff','parent','partner') DEFAULT 'student',
  `content` text NOT NULL,
  `rating` tinyint(1) DEFAULT 5,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_test_featured` (`is_featured`),
  KEY `idx_test_published` (`is_published`),
  KEY `idx_test_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 8. CMS PARTNERS — Partner organizations
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `partner_type` enum('donor','academic','healthcare','government','ngo','corporate','other') DEFAULT 'other',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner_type` (`partner_type`),
  KEY `idx_partner_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 9. CMS FAQs — Frequently asked questions
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_slug` varchar(150) DEFAULT 'general',
  `question` varchar(500) NOT NULL,
  `answer` longtext NOT NULL,
  `category` varchar(100) DEFAULT 'general',
  `sort_order` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_faq_page` (`page_slug`),
  KEY `idx_faq_category` (`category`),
  KEY `idx_faq_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 10. CMS MEDIA LIBRARY — Centralized media management
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` bigint(20) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `folder` varchar(100) DEFAULT 'uploads',
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_media_type` (`file_type`),
  KEY `idx_media_folder` (`folder`),
  KEY `idx_media_uploaded` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 11. CMS CONTENT APPROVALS — Workflow tracking
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` varchar(50) NOT NULL,
  `content_id` int(11) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `submitted_by_name` varchar(200) DEFAULT NULL,
  `submitted_by_role` varchar(100) DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `reviewer_name` varchar(200) DEFAULT NULL,
  `status` enum('draft','pending_review','approved','rejected','revision_requested','published') NOT NULL DEFAULT 'draft',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `notes` text DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_approval_content` (`content_type`, `content_id`),
  KEY `idx_approval_status` (`status`),
  KEY `idx_approval_submitter` (`submitted_by`),
  KEY `idx_approval_reviewer` (`reviewer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 12. CMS REVISIONS — Content versioning
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_revisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` varchar(50) NOT NULL,
  `content_id` int(11) NOT NULL,
  `revision_number` int(11) DEFAULT 1,
  `title` varchar(255) DEFAULT NULL,
  `content_snapshot` longtext DEFAULT NULL,
  `changes_summary` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rev_content` (`content_type`, `content_id`),
  KEY `idx_rev_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 13. CMS AUDIT LOG — Complete change tracking
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_audit_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(200) DEFAULT NULL,
  `user_role` varchar(100) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `content_type` varchar(50) DEFAULT NULL,
  `content_id` int(11) DEFAULT NULL,
  `content_title` varchar(255) DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_content` (`content_type`, `content_id`),
  KEY `idx_audit_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 14. CMS ROLE PERMISSIONS — Who can edit what
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `page_slug` varchar(150) DEFAULT NULL,
  `content_type` varchar(50) DEFAULT NULL,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_publish` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(1) DEFAULT 0,
  `can_view` tinyint(1) DEFAULT 1,
  `requires_approval` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permission_page` (`role_name`, `permission`, `page_slug`),
  KEY `idx_perm_role` (`role_name`),
  KEY `idx_perm_page` (`page_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 15. CMS NEWS CATEGORIES — News classification
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_news_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'fas fa-newspaper',
  `color` varchar(20) DEFAULT '#1A237E',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 16. CMS PAGE VIEWS — Analytics tracking
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_page_views` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `page_slug` varchar(150) NOT NULL,
  `visitor_ip` varchar(45) DEFAULT NULL,
  `visitor_agent` text DEFAULT NULL,
  `referer_url` varchar(500) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pv_page` (`page_slug`),
  KEY `idx_pv_date` (`viewed_at`),
  KEY `idx_pv_device` (`device_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 17. CMS SOCIAL LINKS — Social media profiles
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_social_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 18. CMS STAFF DIRECTORY — Public-facing staff profiles
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_staff_directory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `full_name` varchar(200) NOT NULL,
  `position` varchar(200) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `qualification` varchar(300) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `office_location` varchar(200) DEFAULT NULL,
  `office_hours` varchar(200) DEFAULT NULL,
  `is_leadership` tinyint(1) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_staff_dept` (`department`),
  KEY `idx_staff_leader` (`is_leadership`),
  KEY `idx_staff_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA: Default Settings
-- ============================================================
INSERT IGNORE INTO `cms_settings` (`setting_group`, `setting_key`, `setting_value`, `value_type`, `label`, `sort_order`) VALUES
('general', 'school_name', 'Iganga School of Nursing and Midwifery', 'text', 'School Name', 1),
('general', 'school_motto', 'Quality Healthcare Education', 'text', 'School Motto', 2),
('general', 'school_tagline', 'Training Competent and Caring Healthcare Professionals', 'text', 'Tagline', 3),
('general', 'school_email', 'info@igangaschoolofnursing.ac.ug', 'text', 'Primary Email', 4),
('general', 'school_phone', '+256 700 123 456', 'text', 'Primary Phone', 5),
('general', 'school_address', 'Iganga, Uganda', 'text', 'Address', 6),
('general', 'school_pobox', 'P.O Box 123, Iganga', 'text', 'P.O. Box', 7),
('contact', 'admissions_email', 'admissions@igangaschoolofnursing.ac.ug', 'text', 'Admissions Email', 10),
('contact', 'bursar_email', 'bursar@igangaschoolofnursing.ac.ug', 'text', 'Bursar Email', 11),
('contact', 'principal_email', 'principal@igangaschoolofnursing.ac.ug', 'text', 'Principal Email', 12),
('contact', 'ict_email', 'ict@igangaschoolofnursing.ac.ug', 'text', 'ICT Email', 13),
('contact', 'emergency_phone', '+256 700 987 654', 'text', 'Emergency Phone', 14),
('seo', 'meta_title_suffix', '| Iganga School of Nursing and Midwifery', 'text', 'Title Suffix', 20),
('seo', 'default_meta_description', 'Iganga School of Nursing and Midwifery - Premier healthcare education institution in Uganda, training competent nurses and midwives.', 'textarea', 'Default Meta Description', 21),
('seo', 'google_analytics_id', '', 'text', 'Google Analytics ID', 22),
('seo', 'google_search_console', '', 'text', 'Search Console Verification', 23),
('social', 'facebook_url', 'https://facebook.com/igangaschoolofnursing', 'text', 'Facebook URL', 30),
('social', 'twitter_url', 'https://twitter.com/isnm_ug', 'text', 'Twitter URL', 31),
('social', 'instagram_url', 'https://instagram.com/isnm_ug', 'text', 'Instagram URL', 32),
('social', 'linkedin_url', 'https://linkedin.com/company/isnm', 'text', 'LinkedIn URL', 33),
('social', 'youtube_url', '', 'text', 'YouTube URL', 34),
('social', 'whatsapp_number', '+256700123456', 'text', 'WhatsApp Number', 35),
('homepage', 'hero_animation', 'fade', 'text', 'Hero Animation Style', 40),
('homepage', 'stats_counter_enabled', '1', 'boolean', 'Show Stats Counter', 41),
('homepage', 'testimonials_enabled', '1', 'boolean', 'Show Testimonials', 42),
('homepage', 'partners_enabled', '1', 'boolean', 'Show Partners', 43),
('homepage', 'gallery_preview_enabled', '1', 'boolean', 'Show Gallery Preview', 44),
('footer', 'developer_name', 'Reagan Otema', 'text', 'Developer Name', 50),
('footer', 'developer_url', 'https://reaganotema.com', 'text', 'Developer URL', 51),
('footer', 'copyright_text', 'Iganga School of Nursing and Midwifery. All Rights Reserved.', 'text', 'Copyright Text', 52);

-- ============================================================
-- SEED DATA: Default Social Links
-- ============================================================
INSERT IGNORE INTO `cms_social_links` (`platform`, `url`, `icon`, `is_active`, `sort_order`) VALUES
('facebook', 'https://facebook.com/igangaschoolofnursing', 'fab fa-facebook-f', 1, 1),
('twitter', 'https://twitter.com/isnm_ug', 'fab fa-twitter', 1, 2),
('instagram', 'https://instagram.com/isnm_ug', 'fab fa-instagram', 1, 3),
('linkedin', 'https://linkedin.com/company/isnm', 'fab fa-linkedin-in', 1, 4),
('youtube', '', 'fab fa-youtube', 0, 5),
('whatsapp', 'https://wa.me/256700123456', 'fab fa-whatsapp', 1, 6);

-- ============================================================
-- SEED DATA: Role Permissions
-- ============================================================
INSERT IGNORE INTO `cms_role_permissions` (`role_name`, `permission`, `page_slug`, `content_type`, `can_create`, `can_edit`, `can_delete`, `can_publish`, `can_approve`, `requires_approval`) VALUES
('Director General', 'manage_all', NULL, NULL, 1, 1, 1, 1, 1, 0),
('CEO', 'edit_homepage', 'about', 'page', 0, 1, 0, 1, 0, 0),
('CEO', 'edit_ceo_message', 'about', 'content_block', 0, 1, 0, 0, 0, 1),
('Director Academics', 'manage_programs', 'programs', 'page', 1, 1, 0, 1, 0, 1),
('Director Academics', 'manage_news', NULL, 'news', 1, 1, 0, 1, 0, 1),
('School Principal', 'edit_principal_message', 'about', 'content_block', 0, 1, 0, 0, 0, 1),
('School Principal', 'manage_announcements', NULL, 'announcement', 1, 1, 1, 1, 0, 0),
('Director Finance', 'edit_tuition', 'programs', 'content_block', 0, 1, 0, 0, 0, 1),
('Director Finance', 'manage_donations', 'donate', 'page', 0, 1, 0, 1, 0, 0),
('School Bursar', 'edit_payment_info', 'donate', 'content_block', 0, 1, 0, 0, 0, 1),
('Director Admissions', 'manage_admissions', 'application', 'page', 1, 1, 0, 1, 0, 1),
('Academic Registrar', 'edit_registration', 'programs', 'content_block', 0, 1, 0, 0, 0, 1),
('HR Manager', 'manage_careers', 'contact', 'content_block', 1, 1, 1, 1, 0, 0),
('School Secretary', 'manage_notices', NULL, 'announcement', 1, 1, 0, 1, 0, 0),
('School Librarian', 'edit_library', 'about', 'content_block', 0, 1, 0, 0, 0, 1),
('Events Coordinator', 'manage_events', NULL, 'event', 1, 1, 1, 1, 0, 0),
('Events Coordinator', 'manage_gallery', NULL, 'gallery', 1, 1, 1, 1, 0, 0),
('Director ICT', 'manage_website_settings', NULL, 'setting', 1, 1, 0, 1, 0, 0),
('Director ICT', 'manage_banners', NULL, 'banner', 1, 1, 1, 1, 0, 0),
('Director ICT', 'manage_media', NULL, 'media', 1, 1, 1, 1, 0, 0);

-- ============================================================
-- SEED DATA: Default CMS Pages
-- ============================================================
INSERT IGNORE INTO `cms_pages` (`slug`, `title`, `page_type`, `hero_title`, `hero_subtitle`, `meta_title`, `meta_description`, `is_published`, `sort_order`) VALUES
('home', 'Home', 'dynamic', 'Welcome to ISNM', 'Training Competent and Caring Healthcare Professionals', 'Iganga School of Nursing and Midwifery | Home', 'Premier healthcare education institution in Uganda', 1, 1),
('about', 'About Us', 'static', 'About ISNM', 'Excellence in Healthcare Education Since 1997', 'About Us | Iganga School of Nursing and Midwifery', 'Learn about our history, mission, vision, and values', 1, 2),
('history', 'Our History', 'static', 'Our History', 'A Legacy of Healthcare Excellence', 'Our History | Iganga School of Nursing and Midwifery', 'The rich history of ISNM since 1997', 1, 3),
('programs', 'Academic Programs', 'static', 'Academic Programs', 'Comprehensive Healthcare Education Programs', 'Academic Programs | Iganga School of Nursing and Midwifery', 'Explore our Certificate, Diploma, and Degree programs', 1, 4),
('news', 'News & Events', 'dynamic', 'News & Events', 'Stay Updated with ISNM', 'News & Events | Iganga School of Nursing and Midwifery', 'Latest news, events, and announcements from ISNM', 1, 5),
('contact', 'Contact Us', 'static', 'Contact Us', 'Get in Touch with ISNM', 'Contact Us | Iganga School of Nursing and Midwifery', 'Contact information, map, and inquiry form', 1, 6),
('donate', 'Donate', 'static', 'Support ISNM', 'Your Donation Transforms Healthcare Education', 'Donate | Iganga School of Nursing and Midwifery', 'Support nursing education in Uganda through donations', 1, 7),
('volunteer', 'Volunteer', 'static', 'Volunteer With Us', 'Make a Difference in Healthcare Education', 'Volunteer | Iganga School of Nursing and Midwifery', 'Volunteer opportunities at Iganga School of Nursing', 1, 8),
('application', 'Apply Now', 'static', 'Apply to ISNM', 'Start Your Healthcare Career Today', 'Apply Now | Iganga School of Nursing and Midwifery', 'Submit your application to Iganga School of Nursing', 1, 9),
('portal', 'Student Portal', 'dynamic', 'Student Portal', 'Access Your Academic Dashboard', 'Student Portal | Iganga School of Nursing and Midwifery', 'Student login portal for academic resources', 1, 10);

-- ============================================================
-- SEED DATA: Default Content Blocks for Home Page
-- ============================================================
SET @home_id = (SELECT id FROM cms_pages WHERE slug = 'home' LIMIT 1);

INSERT IGNORE INTO `cms_content_blocks` (`page_id`, `block_key`, `block_type`, `title`, `subtitle`, `content`, `sort_order`) VALUES
(@home_id, 'welcome', 'text', 'Welcome to Iganga School of Nursing and Midwifery', 'Established to provide quality nursing and midwifery education in Uganda and the region.', '<p>Iganga School of Nursing and Midwifery (ISNM) is a premier healthcare education institution dedicated to training competent, compassionate, and skilled nurses and midwives. Located in Iganga, Eastern Uganda, we have been at the forefront of healthcare education since 1997.</p><p>Our programs are designed to equip students with the knowledge, skills, and values needed to provide quality healthcare services in diverse settings.</p>', 1),
(@home_id, 'stats', 'stats', 'Our Impact in Numbers', 'Making a difference in healthcare education', NULL, 2),
(@home_id, 'why_choose', 'cards', 'Why Choose ISNM', 'Discover what makes us the preferred choice for healthcare education', NULL, 3),
(@home_id, 'cta', 'cta', 'Ready to Start Your Journey?', 'Join thousands of healthcare professionals trained at ISNM', NULL, 10);

-- ============================================================
-- SEED DATA: Default Gallery Categories
-- ============================================================
INSERT IGNORE INTO `cms_gallery_categories` (`name`, `slug`, `sort_order`) VALUES
('Campus Life', 'campus-life', 1),
('Graduation', 'graduation', 2),
('Clinical Training', 'clinical-training', 3),
('Sports & Activities', 'sports-activities', 4),
('Facilities', 'facilities', 5),
('Events', 'events', 6);

-- ============================================================
-- SEED DATA: Default News Categories
-- ============================================================
INSERT IGNORE INTO `cms_news_categories` (`name`, `slug`, `icon`, `color`, `sort_order`) VALUES
('General', 'general', 'fas fa-newspaper', '#1A237E', 1),
('Academic', 'academic', 'fas fa-graduation-cap', '#2E7D32', 2),
('Admissions', 'admissions', 'fas fa-user-plus', '#E65100', 3),
('Events', 'events', 'fas fa-calendar-alt', '#6A1B9A', 4),
('Announcements', 'announcements', 'fas fa-bullhorn', '#C62828', 5),
('Student Life', 'student-life', 'fas fa-users', '#00838F', 6),
('Sports', 'sports', 'fas fa-football-ball', '#F57F17', 7),
('Research', 'research', 'fas fa-flask', '#1565C0', 8);

-- ============================================================
-- SEED DATA: Default FAQs
-- ============================================================
INSERT IGNORE INTO `cms_faqs` (`page_slug`, `question`, `answer`, `category`, `sort_order`) VALUES
('general', 'What programs does ISNM offer?', 'ISNM offers Certificate in Nursing, Certificate in Midwifery, Diploma in Nursing (Extension), and Diploma in Midwifery (Extension) programs.', 'admissions', 1),
('general', 'How do I apply to ISNM?', 'Applications can be submitted online through our application portal or in person at the admissions office. Required documents include academic certificates, national ID, and passport photos.', 'admissions', 2),
('general', 'What are the admission requirements?', 'Requirements vary by program. Generally, candidates need O-Level certificates with at least 5 passes including English, Mathematics, Biology, and Chemistry.', 'admissions', 3),
('general', 'How can I pay tuition fees?', 'Fees can be paid via Mobile Money (MTN/Airtel), bank transfer, or cash at the bursar''s office. Online payment is also available through our payment portal.', 'finance', 4),
('general', 'Does ISNM offer accommodation?', 'Yes, ISNM has on-campus hostel facilities for both male and female students. Allocation is based on availability and distance from home.', 'student_life', 5),
('general', 'What career opportunities are available after graduation?', 'Graduates can work in hospitals, health centers, community health programs, NGOs, international organizations, and can pursue further education.', 'academic', 6),
('general', 'Is ISNM accredited?', 'Yes, ISNM is fully accredited by the Uganda Nurses and Midwives Council (UNMC) and the National Council for Higher Education (NCHE).', 'general', 7),
('general', 'How can I contact ISNM?', 'You can reach us by phone at +256 700 123 456, email at info@igangaschoolofnursing.ac.ug, or visit us at Iganga, Uganda.', 'general', 8);

-- ============================================================
-- SEED DATA: Default Testimonials
-- ============================================================
INSERT IGNORE INTO `cms_testimonials` (`author_name`, `author_title`, `author_role`, `content`, `rating`, `is_featured`, `sort_order`) VALUES
('Sarah Nambogo', 'Registered Nurse, Mulago Hospital', 'alumni', 'ISNM gave me the foundation I needed to become a competent nurse. The clinical training and dedicated faculty prepared me for the real healthcare challenges.', 5, 1, 1),
('James Ochieng', 'Midwife, Iganga Health Center IV', 'alumni', 'The Certificate in Midwifery program at ISNM was transformative. I now serve my community with confidence and professional expertise.', 5, 1, 2),
('Grace Nakamya', 'Student, Diploma in Nursing', 'student', 'Choosing ISNM was the best decision of my life. The modern facilities, experienced lecturers, and supportive learning environment make every day worthwhile.', 5, 1, 3),
('Dr. Moses Wambamba', 'Medical Director, Iganga Hospital', 'partner', 'ISNM graduates consistently demonstrate clinical excellence and compassionate care. We are proud to partner with this exceptional institution.', 5, 1, 4);

-- ============================================================
-- SEED DATA: Default Events
-- ============================================================
INSERT IGNORE INTO `cms_events` (`title`, `slug`, `description`, `short_description`, `event_date`, `event_type`, `location`, `is_published`) VALUES
('New Academic Year Orientation', 'new-academic-year-orientation-2026', 'Welcome ceremony and orientation for new and returning students for the 2026 academic year.', 'Welcome ceremony for all students', '2026-02-01', 'academic', 'ISNM Main Campus', 1),
('International Nurses Day Celebration', 'international-nurses-day-2026', 'Annual celebration of International Nurses Day with guest speakers, exhibitions, and awards.', 'Celebrating nursing excellence', '2026-05-12', 'ceremony', 'ISNM Auditorium', 1),
('Clinical Skills Workshop', 'clinical-skills-workshop-2026', 'Hands-on workshop for nursing students on advanced clinical skills and patient care techniques.', 'Advanced clinical skills training', '2026-06-15', 'workshop', 'ISNM Skills Laboratory', 1);

SET FOREIGN_KEY_CHECKS = 1;
