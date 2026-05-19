<?php
/**
 * Cross-database statistics for executive dashboards.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../views/student_data_loader.php';

if (!function_exists('getInstitutionOverviewStats')) {
    function getInstitutionOverviewStats() {
        $stats = [
            'total_staff' => 0,
            'total_students_db' => 0,
            'total_students_files' => 0,
            'total_students' => 0,
            'website_pages' => 0,
            'website_posts' => 0,
            'website_applications' => 0,
            'pending_applications' => 0,
            'data_files' => 0,
        ];

        try {
            $staffConn = getStaffConnection();
            $r = $staffConn->query("SELECT COUNT(*) AS c FROM staff WHERE status = 'Active'");
            if ($r) {
                $stats['total_staff'] = (int) ($r->fetch_assoc()['c'] ?? 0);
            }
        } catch (Exception $e) {
            error_log('institution_stats staff: ' . $e->getMessage());
        }

        try {
            $studentsConn = getStudentsConnection();
            $tables = ['students', 'users'];
            foreach ($tables as $table) {
                $check = $studentsConn->query("SHOW TABLES LIKE '{$table}'");
                if ($check && $check->num_rows > 0) {
                    $r = $studentsConn->query("SELECT COUNT(*) AS c FROM `{$table}`");
                    if ($r) {
                        $stats['total_students_db'] += (int) ($r->fetch_assoc()['c'] ?? 0);
                    }
                }
            }
        } catch (Exception $e) {
            error_log('institution_stats students: ' . $e->getMessage());
        }

        try {
            $loader = new StudentDataLoader();
            $stats['data_files'] = count(glob(__DIR__ . '/../students_data/*.xlsx') ?: []);
            $fileStudents = $loader->loadAllStudents();
            $stats['total_students_files'] = count($fileStudents);
        } catch (Exception $e) {
            error_log('institution_stats files: ' . $e->getMessage());
        }

        $stats['total_students'] = max($stats['total_students_db'], $stats['total_students_files']);

        try {
            $websiteConn = getWebsiteConnection();
            foreach (
                [
                    'website_pages' => 'pages',
                    'website_posts' => 'posts',
                    'website_applications' => 'applications',
                ] as $key => $table
            ) {
                $check = $websiteConn->query("SHOW TABLES LIKE '{$table}'");
                if ($check && $check->num_rows > 0) {
                    $r = $websiteConn->query("SELECT COUNT(*) AS c FROM `{$table}`");
                    if ($r) {
                        $stats[$key] = (int) ($r->fetch_assoc()['c'] ?? 0);
                    }
                }
            }
            $check = $websiteConn->query("SHOW TABLES LIKE 'applications'");
            if ($check && $check->num_rows > 0) {
                $r = $websiteConn->query("SELECT COUNT(*) AS c FROM applications WHERE status IN ('Pending','Submitted','Under Review')");
                if ($r) {
                    $stats['pending_applications'] = (int) ($r->fetch_assoc()['c'] ?? 0);
                }
            }
        } catch (Exception $e) {
            error_log('institution_stats website: ' . $e->getMessage());
        }

        return $stats;
    }
}
