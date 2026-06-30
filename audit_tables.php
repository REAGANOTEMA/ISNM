<?php
/**
 * DATABASE TABLE AUDIT
 * Groups all tables by prefix, identifies duplicates, maps to potential modules
 */
require_once __DIR__ . '/config/database.php';

echo "=== DATABASE TABLE AUDIT ===\n\n";

// Staff database
$staffConn = getStaffConnection();
$studentsConn = getStudentsConnection();

function auditDatabase($conn, $dbName) {
    echo "─── $dbName Database ───\n";
    
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    echo "Total tables: " . count($tables) . "\n\n";
    
    // Group by prefix
    $prefixes = [];
    $duplicates = [];
    
    foreach ($tables as $table) {
        // Extract prefix (first word before underscore, or first two words)
        $parts = explode('_', $table);
        $prefix = $parts[0];
        
        // Special groupings
        if (in_array($prefix, ['academic', 'exam', 'grade', 'assessment', 'course', 'curriculum', 'timetable', 'lecture'])) {
            $prefix = 'academic';
        } elseif (in_array($prefix, ['fee', 'payment', 'invoice', 'bursar', 'finance', 'budget', 'payroll', 'expenditure', 'revenue', 'account'])) {
            $prefix = 'finance';
        } elseif (in_array($prefix, ['hr', 'staff', 'employee', 'leave', 'attendance', 'recruit', 'appraisal', 'training', 'disciplinary'])) {
            $prefix = 'hr';
        } elseif (in_array($prefix, ['admission', 'applicant', 'intake', 'enrollment', 'registration'])) {
            $prefix = 'admissions';
        } elseif (in_array($prefix, ['student', 'learner'])) {
            $prefix = 'student';
        } elseif (in_array($prefix, ['lab', 'equipment', 'inventory', 'asset', 'stock', 'store'])) {
            $prefix = 'inventory';
        } elseif (in_array($prefix, ['library', 'book', 'borrow'])) {
            $prefix = 'library';
        } elseif (in_array($prefix, ['hostel', 'accommodation', 'meal'])) {
            $prefix = 'accommodation';
        } elseif (in_array($prefix, ['clinical', 'patient', 'medical', 'health', 'sickbay', 'ward'])) {
            $prefix = 'clinical';
        } elseif (in_array($prefix, ['vehicle', 'driver', 'fuel', 'trip'])) {
            $prefix = 'transport';
        } elseif (in_array($prefix, ['security', 'visitor', 'access'])) {
            $prefix = 'security';
        } elseif (in_array($prefix, ['notification', 'message', 'alert', 'email', 'sms', 'communication'])) {
            $prefix = 'communication';
        } elseif (in_array($prefix, ['document', 'file', 'upload', 'certificate', 'template'])) {
            $prefix = 'documents';
        } elseif (in_array($prefix, ['website', 'page', 'news', 'gallery', 'event'])) {
            $prefix = 'website';
        } elseif (in_array($prefix, ['ict', 'cyber', 'network', 'system', 'backup', 'audit', 'log'])) {
            $prefix = 'system';
        } elseif (in_array($prefix, ['guild', 'student_union', 'volunteer'])) {
            $prefix = 'student_activities';
        } elseif (in_array($prefix, ['quality', 'accreditation', 'compliance'])) {
            $prefix = 'quality';
        } elseif (in_array($prefix, ['research', 'publication', 'partnership'])) {
            $prefix = 'research';
        } elseif (in_array($prefix, ['graduation', 'certificate', 'transcript'])) {
            $prefix = 'graduation';
        } elseif (in_array($prefix, ['scholarship', 'sponsorship', 'donation'])) {
            $prefix = 'scholarships';
        } elseif (in_array($prefix, ['penalty', 'fine', 'compliance'])) {
            $prefix = 'compliance';
        } elseif (in_array($prefix, ['procurement', 'vendor', 'contract'])) {
            $prefix = 'procurement';
        } elseif (in_array($prefix, ['task', 'project', 'workflow'])) {
            $prefix = 'workflow';
        } elseif (in_array($prefix, ['calendar', 'event'])) {
            $prefix = 'calendar';
        } elseif (in_array($prefix, ['setting', 'config', 'preference'])) {
            $prefix = 'settings';
        }
        
        if (!isset($prefixes[$prefix])) {
            $prefixes[$prefix] = [];
        }
        $prefixes[$prefix][] = $table;
    }
    
    // Sort prefixes
    ksort($prefixes);
    
    // Output grouped tables
    foreach ($prefixes as $prefix => $tbls) {
        echo strtoupper($prefix) . " (" . count($tbls) . " tables)\n";
        foreach ($tbls as $t) {
            echo "  - $t\n";
        }
        echo "\n";
    }
    
    // Check for potential duplicates
    echo "─── POTENTIAL DUPLICATES ───\n";
    $normalized = [];
    foreach ($tables as $table) {
        $norm = strtolower(preg_replace('/s$/', '', str_replace('_', '', $table)));
        if (!isset($normalized[$norm])) {
            $normalized[$norm] = [];
        }
        $normalized[$norm][] = $table;
    }
    
    $dupCount = 0;
    foreach ($normalized as $norm => $tbls) {
        if (count($tbls) > 1) {
            echo "  DUPLICATE: " . implode(', ', $tbls) . "\n";
            $dupCount++;
        }
    }
    if ($dupCount === 0) echo "  None found.\n";
    
    echo "\n";
    return $prefixes;
}

$staffPrefixes = auditDatabase($staffConn, 'staffs_db');
$studentPrefixes = auditDatabase($studentsConn, 'students_db');

// Summary
echo "=== MODULE MAPPING SUMMARY ===\n\n";
echo "Staff DB prefixes: " . count($staffPrefixes) . "\n";
echo "Student DB prefixes: " . count($studentPrefixes) . "\n";
