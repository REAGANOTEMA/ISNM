<?php
/**
 * ISNM Document Settings Loader
 * Loads transcript/certificate settings from document_settings table
 * Provides defaults if table doesn't exist yet
 */

if (!function_exists('loadDocumentSettings')):

function loadDocumentSettings() {
    static $cache = null;
    if ($cache !== null) return $cache;

    $defaults = [
        'institution_name' => 'Iganga School of Nursing & Midwifery',
        'institution_short_name' => 'ISNM',
        'institution_address' => 'P.O. Box 1234, Kampala, Uganda',
        'institution_phone' => '+256 700 000 000',
        'institution_email' => 'registrar@isnm.ac.ug',
        'institution_motto' => '"Chosen to Serve, Based on a Disciplined Mind for Health Action"',
        'principal_name' => '_______________________',
        'director_name' => '_______________________',
        'registrar_name' => '_______________________',
        'transcript_fee' => '50000',
        'transcript_purposes' => 'Academic,Employment,Transfer,Further Studies,Other',
        'transcript_default_type' => 'transcript',
        'transcript_footer' => 'This is a computer-generated document. No signature required if digitally verified.',
        'transcript_verify_url' => 'https://isnm.ac.ug/verify',
        'logo_path' => 'images/school-logo.png',
        'background_color' => '#0f4c3a',
        'accent_color' => '#d4a843',
        'font_family' => 'Georgia, Times New Roman, serif',
    ];

    try {
        if (function_exists('getStaffConnection')) {
            $db = getStaffConnection();
        } else {
            require_once __DIR__ . '/../config/database.php';
            if (defined('STAFF_DB_HOST')) {
                $db = @new mysqli(STAFF_DB_HOST, STAFF_DB_USER, STAFF_DB_PASS, STAFF_DB_NAME, STAFF_DB_PORT);
            } else {
                $cache = $defaults;
                return $cache;
            }
        }

        if (!$db || $db->connect_error) {
            $cache = $defaults;
            return $cache;
        }

        $r = $db->query("SELECT setting_key, setting_value FROM document_settings");
        if ($r && $r->num_rows > 0) {
            while ($row = $r->fetch_assoc()) {
                $defaults[$row['setting_key']] = $row['setting_value'];
            }
        }

        $db->close();
    } catch (Exception $e) {
        // Silently use defaults
    }

    $cache = $defaults;
    return $cache;
}

function getDocumentSetting($key, $default = '') {
    $settings = loadDocumentSettings();
    return $settings[$key] ?? $default;
}

function saveDocumentSetting($key, $value) {
    try {
        if (function_exists('getStaffConnection')) {
            $db = getStaffConnection();
        } else {
            require_once __DIR__ . '/../config/database.php';
            if (defined('STAFF_DB_HOST')) {
                $db = @new mysqli(STAFF_DB_HOST, STAFF_DB_USER, STAFF_DB_PASS, STAFF_DB_NAME, STAFF_DB_PORT);
            } else {
                return false;
            }
        }

        if (!$db || $db->connect_error) return false;

        $k = $db->real_escape_string($key);
        $v = $db->real_escape_string($value);
        $db->query("INSERT INTO document_settings (setting_key, setting_value, updated_at) VALUES ('$k', '$v', NOW()) ON DUPLICATE KEY UPDATE setting_value='$v', updated_at=NOW()");
        $db->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function saveDocumentSettings($settings) {
    $success = true;
    foreach ($settings as $key => $value) {
        if (!saveDocumentSetting($key, $value)) $success = false;
    }
    return $success;
}

endif;
