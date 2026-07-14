﻿<?php
/**
 * ISNM database configuration.
 * Production credentials are loaded from .env and should not be committed.
 */

if (!function_exists('isnm_env')) {
    function isnm_env(string $key, $default = null) {
        $value = getenv($key);
        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }
        if ($value === false && isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        }
        return $value === false ? $default : $value;
    }
}

if (!function_exists('isnm_load_env')) {
    function isnm_load_env(string $path): void {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);

            if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                $value = substr($value, 1, -1);
            }

            if ($key !== '') {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv($key . '=' . $value);
            }
        }
    }
}

isnm_load_env(__DIR__ . '/../.env');
// If no .env found, try .env.production as fallback (deployed on hosting)
if (empty($_ENV['DB_HOST']) && is_file(__DIR__ . '/../.env.production')) {
    isnm_load_env(__DIR__ . '/../.env.production');
}
// Load .env.local if it exists (local dev override)
if (is_file(__DIR__ . '/../.env.local')) {
    isnm_load_env(__DIR__ . '/../.env.local');
}

// ── Auto-detect session cookie path from actual deployment location ──
if (!defined('APP_BASE_PATH')) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
    $scriptDir  = dirname($scriptName);
    $upDirs     = ['dashboards','includes','config','sql','views','logs','students_data'];
    while (in_array(basename($scriptDir), $upDirs, true) && $scriptDir !== '/') {
        $scriptDir = dirname($scriptDir);
    }
    define('APP_BASE_PATH', rtrim($scriptDir, '/') ?: '/');
}
if (!defined('SESSION_COOKIE_PATH')) {
    define('SESSION_COOKIE_PATH', APP_BASE_PATH);
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', in_array(isnm_env('APP_DEBUG', 'false'), ['true', '1', 'yes'], true));
}
if (!defined('APP_ENV')) {
    define('APP_ENV', isnm_env('APP_ENV', 'production'));
}

if (!defined('DB_HOST')) {
    define('DB_HOST', isnm_env('DB_HOST', isnm_env('STUDENTS_DB_HOST', 'localhost')));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', (int) isnm_env('DB_PORT', isnm_env('STUDENTS_DB_PORT', 3306)));
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', isnm_env('DB_CHARSET', 'utf8mb4'));
}
if (!defined('STUDENTS_DB_HOST')) {
    define('STUDENTS_DB_HOST', isnm_env('STUDENTS_DB_HOST', DB_HOST));
}
if (!defined('STUDENTS_DB_PORT')) {
    define('STUDENTS_DB_PORT', (int) isnm_env('STUDENTS_DB_PORT', DB_PORT));
}
if (!defined('STUDENTS_DB_NAME')) {
    define('STUDENTS_DB_NAME', isnm_env('STUDENTS_DB_NAME', 'igangaschool_students'));
}
if (!defined('STUDENTS_DB_USER')) {
    define('STUDENTS_DB_USER', isnm_env('STUDENTS_DB_USER', STUDENTS_DB_NAME));
}
if (!defined('STUDENTS_DB_PASS')) {
    define('STUDENTS_DB_PASS', isnm_env('STUDENTS_DB_PASS', ''));
}
if (!defined('STUDENTS_DB_CHARSET')) {
    define('STUDENTS_DB_CHARSET', isnm_env('STUDENTS_DB_CHARSET', DB_CHARSET));
}

if (!defined('STAFF_DB_HOST')) {
    define('STAFF_DB_HOST', isnm_env('STAFF_DB_HOST', DB_HOST));
}
if (!defined('STAFF_DB_PORT')) {
    define('STAFF_DB_PORT', (int) isnm_env('STAFF_DB_PORT', DB_PORT));
}
if (!defined('STAFF_DB_NAME')) {
    define('STAFF_DB_NAME', isnm_env('STAFF_DB_NAME', 'igangaschool_staffs'));
}
if (!defined('STAFF_DB_USER')) {
    define('STAFF_DB_USER', isnm_env('STAFF_DB_USER', STAFF_DB_NAME));
}
if (!defined('STAFF_DB_PASS')) {
    define('STAFF_DB_PASS', isnm_env('STAFF_DB_PASS', ''));
}
if (!defined('STAFF_DB_CHARSET')) {
    define('STAFF_DB_CHARSET', isnm_env('STAFF_DB_CHARSET', DB_CHARSET));
}

if (!defined('WEBSITE_DB_HOST')) {
    define('WEBSITE_DB_HOST', isnm_env('WEBSITE_DB_HOST', DB_HOST));
}
if (!defined('WEBSITE_DB_PORT')) {
    define('WEBSITE_DB_PORT', (int) isnm_env('WEBSITE_DB_PORT', DB_PORT));
}
if (!defined('WEBSITE_DB_NAME')) {
    define('WEBSITE_DB_NAME', isnm_env('WEBSITE_DB_NAME', 'igangaschool_website'));
}
if (!defined('WEBSITE_DB_USER')) {
    define('WEBSITE_DB_USER', isnm_env('WEBSITE_DB_USER', WEBSITE_DB_NAME));
}
if (!defined('WEBSITE_DB_PASS')) {
    define('WEBSITE_DB_PASS', isnm_env('WEBSITE_DB_PASS', ''));
}
if (!defined('WEBSITE_DB_CHARSET')) {
    define('WEBSITE_DB_CHARSET', isnm_env('WEBSITE_DB_CHARSET', DB_CHARSET));
}

if (!defined('ICT_DB_HOST')) {
    define('ICT_DB_HOST', isnm_env('ICT_DB_HOST', DB_HOST));
}
if (!defined('ICT_DB_PORT')) {
    define('ICT_DB_PORT', (int) isnm_env('ICT_DB_PORT', DB_PORT));
}
if (!defined('ICT_DB_NAME')) {
    define('ICT_DB_NAME', isnm_env('ICT_DB_NAME', 'igangaschool_ict'));
}
if (!defined('ICT_DB_USER')) {
    define('ICT_DB_USER', isnm_env('ICT_DB_USER', ICT_DB_NAME));
}
if (!defined('ICT_DB_PASS')) {
    define('ICT_DB_PASS', isnm_env('ICT_DB_PASS', ''));
}
if (!defined('ICT_DB_CHARSET')) {
    define('ICT_DB_CHARSET', isnm_env('ICT_DB_CHARSET', DB_CHARSET));
}

if (!function_exists('isnm_mysqli_connect')) {
    function isnm_mysqli_connect(string $label, string $host, string $user, string $pass, string $db, int $port, string $charset) {
        // Connection cache â€” singleton per database
        static $connections = [];
        $cacheKey = $db;
        if (isset($connections[$cacheKey]) && $connections[$cacheKey] instanceof mysqli) {
            try {
                if (@$connections[$cacheKey]->ping()) {
                    return $connections[$cacheKey];
                }
            } catch (\Throwable $e) { }
            unset($connections[$cacheKey]);
        }

        mysqli_report(MYSQLI_REPORT_OFF);
        $oldLevel = error_reporting(0);

        // Hosting credentials loaded from environment / .env, with inline fallback defaults
        $hostingCreds = [
            'igangaschool_students'       => ['user' => isnm_env('DB_IGANGA_STUDENTS_USER',       'igangaschool_students'),       'pass' => isnm_env('DB_IGANGA_STUDENTS_PASS',       '3i%yHc00=cP^ZXwF')],
            'igangaschool_staffs'         => ['user' => isnm_env('DB_IGANGA_STAFFS_USER',          'igangaschool_staffs'),         'pass' => isnm_env('DB_IGANGA_STAFFS_PASS',         '?e=8Dc^D_1Aq9UQd')],
            'igangaschool_website'        => ['user' => isnm_env('DB_IGANGA_WEBSITE_USER',         'igangaschool_website'),        'pass' => isnm_env('DB_IGANGA_WEBSITE_PASS',         'tCB0WPn+5l)4!_rY')],
            'igangaschool_ict'            => ['user' => isnm_env('DB_IGANGA_ICT_USER',             'igangaschool_ict'),            'pass' => isnm_env('DB_IGANGA_ICT_PASS',             'R_@CPx%OifDKqGSy')],
            'igangaschoolofl_students_db' => ['user' => isnm_env('DB_IGANGAOFL_STUDENTS_USER',     'igangaschoolofl_students_db'), 'pass' => isnm_env('DB_IGANGAOFL_STUDENTS_PASS',     '3i%yHc00=cP^ZXwF')],
            'igangaschoolofl_staffs_db'   => ['user' => isnm_env('DB_IGANGAOFL_STAFFS_USER',       'igangaschoolofl_staffs_db'),   'pass' => isnm_env('DB_IGANGAOFL_STAFFS_PASS',       '?e=8Dc^D_1Aq9UQd')],
            'igangaschoolofl_website_db'  => ['user' => isnm_env('DB_IGANGAOFL_WEBSITE_USER',      'igangaschoolofl_website_db'),  'pass' => isnm_env('DB_IGANGAOFL_WEBSITE_PASS',      'tCB0WPn+5l)4!_rY')],
            'igangaschoolofl_ict'         => ['user' => isnm_env('DB_IGANGAOFL_ICT_USER',          'igangaschoolofl_ict'),         'pass' => isnm_env('DB_IGANGAOFL_ICT_PASS',          'R_@CPx%OifDKqGSy')],
        ];

        $credSet = [];
        // 1. Hosting creds first (if we know them)
        if (isset($hostingCreds[$db])) {
            $credSet[] = $hostingCreds[$db] + ['db' => $db];
        }
        // 2. Provided credentials
        $credSet[] = ['user' => $user, 'pass' => $pass, 'db' => $db];
        // 3. DB-name-as-user (cPanel pattern) if different
        if ($user !== $db) {
            $credSet[] = ['user' => $db, 'pass' => $pass, 'db' => $db];
        }
        // 4. Root fallbacks (local dev)
        if ($user === 'root' || $pass !== ($hostingCreds[$db]['pass'] ?? '')) {
            $rootPass = isnm_env('STUDENTS_DB_PASS', isnm_env('DB_PASS', ''));
            if (!empty($rootPass) && $rootPass !== $pass) {
                $credSet[] = ['user' => 'root', 'pass' => $rootPass, 'db' => $db];
            }
            $credSet[] = ['user' => 'root', 'pass' => '', 'db' => $db];
            $credSet[] = ['user' => 'root', 'pass' => 'root', 'db' => $db];
        }

        // Deduplicate
        $seen = [];
        $credSet = array_values(array_filter($credSet, function($c) use (&$seen) {
            $key = $c['user'] . '|' . $c['pass'];
            if (isset($seen[$key])) return false;
            $seen[$key] = true;
            return true;
        }));

        $hosts = array_values(array_unique(array_filter([$host, 'localhost', '127.0.0.1'])));
        $ports = array_values(array_unique(array_filter([$port, 3306, 3307])));

        foreach ($credSet as $cred) {
            $u = $cred['user'];
            $p = $cred['pass'];
            $d = $cred['db'];
            foreach ($hosts as $h) {
                $accessDenied = false;
                foreach ($ports as $pt) {
                    $pt = (int)$pt;
                    $conn = @new mysqli($h, $u, $p, $d, $pt);
                    if ($conn && !$conn->connect_error) {
                        $conn->set_charset($charset);
                        $connections[$cacheKey] = $conn;
                        error_reporting($oldLevel);
                        return $conn;
                    }
                    if ($conn) {
                        if (stripos($conn->connect_error, 'access denied') !== false) $accessDenied = true;
                        try { $conn->close(); } catch (\Throwable $_) {}
                    }
                    $conn = null;
                }
                if ($accessDenied) break;
            }
        }

        error_reporting($oldLevel);
        $msg = $label . ' DB Error: no connection could be established';
        error_log($msg);
        $GLOBALS['isnm_last_db_error'] = $msg;
        return null;
    }
}

if (!defined('PAYROLL_DB_HOST')) {
    define('PAYROLL_DB_HOST', isnm_env('PAYROLL_DB_HOST', STAFF_DB_HOST));
}
if (!defined('PAYROLL_DB_PORT')) {
    define('PAYROLL_DB_PORT', (int) isnm_env('PAYROLL_DB_PORT', STAFF_DB_PORT));
}
if (!defined('PAYROLL_DB_NAME')) {
    define('PAYROLL_DB_NAME', isnm_env('PAYROLL_DB_NAME', STAFF_DB_NAME));
}
if (!defined('PAYROLL_DB_USER')) {
    define('PAYROLL_DB_USER', isnm_env('PAYROLL_DB_USER', STAFF_DB_USER));
}
if (!defined('PAYROLL_DB_PASS')) {
    define('PAYROLL_DB_PASS', isnm_env('PAYROLL_DB_PASS', STAFF_DB_PASS));
}
if (!defined('PAYROLL_DB_CHARSET')) {
    define('PAYROLL_DB_CHARSET', isnm_env('PAYROLL_DB_CHARSET', STAFF_DB_CHARSET));
}

if (!function_exists('getICTConnection')) {
    function getICTConnection() {
        return isnm_mysqli_connect('ICT', ICT_DB_HOST, ICT_DB_USER, ICT_DB_PASS, ICT_DB_NAME, ICT_DB_PORT, ICT_DB_CHARSET);
    }
}

if (!function_exists('getPayrollDBName')) {
    function getPayrollDBName(): string {
        return PAYROLL_DB_NAME;
    }
}

if (!function_exists('getPayrollConnection')) {
    function getPayrollConnection() {
        return isnm_mysqli_connect('Payroll', PAYROLL_DB_HOST, PAYROLL_DB_USER, PAYROLL_DB_PASS, PAYROLL_DB_NAME, PAYROLL_DB_PORT, PAYROLL_DB_CHARSET);
    }
}

if (!function_exists('getStudentsConnection')) {
    function getStudentsConnection() {
        return isnm_mysqli_connect('Students', STUDENTS_DB_HOST, STUDENTS_DB_USER, STUDENTS_DB_PASS, STUDENTS_DB_NAME, STUDENTS_DB_PORT, STUDENTS_DB_CHARSET);
    }
}

if (!function_exists('getStaffConnection')) {
    function getStaffConnection() {
        return isnm_mysqli_connect('Staff', STAFF_DB_HOST, STAFF_DB_USER, STAFF_DB_PASS, STAFF_DB_NAME, STAFF_DB_PORT, STAFF_DB_CHARSET);
    }
}

if (!function_exists('getWebsiteConnection')) {
    function getWebsiteConnection() {
        return isnm_mysqli_connect('Website', WEBSITE_DB_HOST, WEBSITE_DB_USER, WEBSITE_DB_PASS, WEBSITE_DB_NAME, WEBSITE_DB_PORT, WEBSITE_DB_CHARSET);
    }
}

if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection($database) {
        $map = [
            'staffs'   => 'getStaffConnection',
            'students' => 'getStudentsConnection',
            'website'  => 'getWebsiteConnection',
            'ict'      => 'getICTConnection',
        ];
        $func = $map[$database] ?? 'getStaffConnection';
        return function_exists($func) ? $func() : null;
    }
}

if (!function_exists('getConnection')) {
    function getConnection() {
        return getStudentsConnection();
    }
}

if (!function_exists('closeConnection')) {
    function closeConnection($conn) {
        if ($conn) {
            $conn->close();
        }
    }
}

if (!function_exists('executePrepared')) {
    function executePrepared($conn, $query, $types, $params) {
        try {
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }

            return $stmt;
        } catch (\Throwable $e) {
            error_log('Query Error: ' . $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('validateIndexNumber')) {
    function validateIndexNumber($index_number) {
        if (empty($index_number)) return false;
        return strlen($index_number) >= 5;
    }
}

if (!function_exists('studentExistsByIndexNumber')) {
    function studentExistsByIndexNumber($indexNumber) {
        $conn = getStudentsConnection();
        if (!$conn) return false;

        $stmt = $conn->prepare('SELECT id FROM students WHERE student_number = ? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('s', $indexNumber);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        $exists = $result && $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }
}

if (!function_exists('userExistsByEmail')) {
    function userExistsByEmail($email) {
        $conn = getStaffConnection();
        if (!$conn) return false;

        $stmt = $conn->prepare('SELECT id FROM staff WHERE email = ? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result();
        $exists = $result && $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim((string) $input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validatePhone')) {
    function validatePhone($phone) {
        $clean_phone = preg_replace('/[^0-9]/', '', (string) $phone);

        if (strlen($clean_phone) === 10 && preg_match('/^0[7]\d{8}$/', $clean_phone)) {
            return true;
        } elseif (strlen($clean_phone) === 12 && preg_match('/^256[7]\d{8}$/', $clean_phone)) {
            return true;
        } elseif (strlen($clean_phone) === 9 && preg_match('/^7\d{8}$/', $clean_phone)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('validatePhoneLenient')) {
    function validatePhoneLenient($phone) {
        $clean = preg_replace('/[^0-9]/', '', (string) $phone);
        return strlen($clean) >= 9;
    }
}
?>
