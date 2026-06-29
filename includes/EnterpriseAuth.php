<?php
/**
 * Enterprise Authentication & RBAC Middleware for ISNM ERP.
 * Single entry point for all auth-related operations across the system.
 */
if (class_exists('EnterpriseAuth', false)) return;

require_once __DIR__ . '/Response.php';

class EnterpriseAuth {
    private static ?EnterpriseAuth $instance = null;
    private ?AuthenticationService $authService = null;
    private array $roleHierarchy = [];
    private array $permissionCache = [];

    // Role hierarchy: higher index = more authority
    private const ROLE_HIERARCHY = [
        'student',
        'lecturer',
        'non-teaching-staff',
        'secretary',
        'hr-manager',
        'school-librarian',
        'head-of-department',
        'bursar',
        'deputy-principal',
        'principal',
        'academic-registrar',
        'director',
        'director-general',
        'ceo',
    ];

    // Permission matrix: role => allowed modules
    private const PERMISSIONS = [
        'student'              => ['dashboard', 'timetable', 'exam', 'progress', 'fees', 'library'],
        'lecturer'             => ['dashboard', 'attendance', 'marks', 'notes', 'timetable', 'leaves'],
        'non-teaching-staff'   => ['dashboard', 'attendance', 'leaves'],
        'secretary'            => ['dashboard', 'appointments', 'documents', 'communications'],
        'hr-manager'           => ['dashboard', 'staff', 'payroll', 'recruitment', 'appraisals', 'leaves'],
        'school-librarian'     => ['dashboard', 'library', 'inventory'],
        'head-of-department'   => ['dashboard', 'staff', 'budget', 'reports', 'curriculum'],
        'bursar'               => ['dashboard', 'fees', 'payments', 'ledger', 'payroll', 'reports', 'tax'],
        'deputy-principal'     => ['dashboard', 'students', 'staff', 'discipline', 'academics'],
        'principal'            => ['dashboard', 'students', 'staff', 'academics', 'discipline', 'reports'],
        'academic-registrar'   => ['dashboard', 'students', 'exams', 'transcripts', 'graduation', 'certificates'],
        'director'             => ['dashboard', 'budget', 'reports', 'staff', 'strategic'],
        'director-general'     => ['dashboard', 'all'],
        'ceo'                  => ['dashboard', 'all'],
    ];

    private function __construct() {
        $this->initSession();
        $this->loadAuthService();
        $this->buildRoleHierarchy();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', 1);
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                ini_set('session.cookie_secure', 1);
            }
            session_start();
        }
    }

    private function loadAuthService(): void {
        $authFile = __DIR__ . '/../auth-service.php';
        if (file_exists($authFile)) {
            require_once $authFile;
            if (class_exists('AuthenticationService')) {
                $this->authService = new AuthenticationService();
            }
        }
    }

    private function buildRoleHierarchy(): void {
        foreach (self::ROLE_HIERARCHY as $i => $role) {
            $this->roleHierarchy[$role] = $i;
        }
    }

    private function normalizeRole(string $role): string {
        $role = strtolower(trim($role));
        // Map legacy role names to canonical form
        $map = [
            'admin' => 'director',
            'owner' => 'director-general',
            'teachers' => 'lecturer',
            'teacher' => 'lecturer',
            'lecturers' => 'lecturer',
            'students' => 'student',
            'school principal' => 'principal',
            'school bursar' => 'bursar',
            'school secretary' => 'secretary',
            'school librarian' => 'school-librarian',
            'hr manager' => 'hr-manager',
            'academic registrar' => 'academic-registrar',
            'director general' => 'director-general',
            'deputy principal' => 'deputy-principal',
            'head of department' => 'head-of-department',
        ];
        return $map[$role] ?? $role;
    }

    // ── Authentication ──

    public function isAuthenticated(): bool {
        return !empty($_SESSION['user_id']);
    }

    public function getUserType(): string {
        return $_SESSION['type'] ?? '';
    }

    public function getUserId(): ?int {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public function getRole(): string {
        return $this->normalizeRole($_SESSION['role'] ?? '');
    }

    public function getUser(): array {
        if ($this->authService) {
            return $this->authService->getCurrentUser();
        }
        return [
            'id' => $_SESSION['user_id'] ?? 0,
            'full_name' => $_SESSION['full_name'] ?? 'User',
            'email' => $_SESSION['email'] ?? '',
            'role' => $this->getRole(),
            'type' => $this->getUserType(),
        ];
    }

    public function requireAuth(): void {
        if (!$this->isAuthenticated()) {
            $loginUrl = $this->getUserType() === 'student' ? '../student-login.php' : '../organogram.php';
            Response::redirect($loginUrl);
        }
    }

    public function requireStaff(): void {
        $this->requireAuth();
        if ($this->getUserType() !== 'staff') {
            Response::forbidden('Staff access required');
        }
    }

    public function requireStudent(): void {
        $this->requireAuth();
        if ($this->getUserType() !== 'student') {
            Response::forbidden('Student access required');
        }
    }

    // ── RBAC ──

    public function hasRole(string $role): bool {
        return $this->getRole() === $this->normalizeRole($role);
    }

    public function hasAnyRole(array $roles): bool {
        $normalized = $this->getRole();
        foreach ($roles as $role) {
            if ($normalized === $this->normalizeRole($role)) return true;
        }
        return false;
    }

    public function hasMinRole(string $minRole): bool {
        $userLevel = $this->roleHierarchy[$this->getRole()] ?? -1;
        $minLevel = $this->roleHierarchy[$this->normalizeRole($minRole)] ?? PHP_INT_MAX;
        return $userLevel >= $minLevel;
    }

    public function hasPermission(string $module): bool {
        $role = $this->getRole();
        $cacheKey = "$role:$module";
        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }
        $perms = self::PERMISSIONS[$role] ?? [];
        $result = in_array('all', $perms, true) || in_array($module, $perms, true);
        $this->permissionCache[$cacheKey] = $result;
        return $result;
    }

    public function requirePermission(string $module): void {
        $this->requireStaff();
        if (!$this->hasPermission($module)) {
            $dashboard = $this->getDashboardRoute();
            if ($dashboard) {
                Response::redirect($dashboard);
            }
            Response::forbidden("Access denied: $module module requires higher privileges");
        }
    }

    public function requireRole(string $requiredRole): void {
        $this->requireStaff();
        if (!$this->hasRole($requiredRole)) {
            $dashboard = $this->getDashboardRoute();
            Response::redirect($dashboard ?: '../index.php');
        }
    }

    public function requireMinRole(string $minRole): void {
        $this->requireStaff();
        if (!$this->hasMinRole($minRole)) {
            $dashboard = $this->getDashboardRoute();
            Response::redirect($dashboard ?: '../index.php');
        }
    }

    public function getDashboardRoute(?string $role = null): string {
        $role = $this->normalizeRole($role ?? $this->getRole());
        $map = [
            'ceo' => 'dashboards/ceo.php',
            'director-general' => 'dashboards/director-general.php',
            'director' => 'dashboards/director-academics.php',
            'academic-registrar' => 'dashboards/academic-registrar.php',
            'principal' => 'dashboards/school-principal.php',
            'deputy-principal' => 'dashboards/deputy-principal.php',
            'secretary' => 'dashboards/school-secretary.php',
            'bursar' => 'dashboards/school-bursar.php',
            'hr-manager' => 'dashboards/hr-manager.php',
            'school-librarian' => 'dashboards/school-librarian.php',
            'lecturer' => 'dashboards/lecturers.php',
            'head-of-department' => 'dashboards/head-nursing.php',
            'student' => 'student_panel/index.php',
            'non-teaching-staff' => 'dashboards/non-teaching-staff.php',
        ];
        return $map[$role] ?? 'index.php';
    }

    // ── Session Management ──

    public function login(array $user, string $type): void {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['type'] = $type;
        $_SESSION['role'] = $user['role_name'] ?? $user['role'] ?? 'staff';
        $_SESSION['full_name'] = $user['full_name'] ?? $user['name'] ?? 'User';
        $_SESSION['email'] = $user['email'] ?? '';
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        // Session security
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public function checkSessionValidity(): bool {
        if (!$this->isAuthenticated()) return false;

        // Check user agent consistency
        if (($_SESSION['user_agent'] ?? '') !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            $this->logout();
            return false;
        }

        // Session timeout (1 hour default)
        $timeout = $_SESSION['session_timeout'] ?? 3600;
        if ((time() - ($_SESSION['last_activity'] ?? 0)) > $timeout) {
            $this->logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    // ── CSRF Protection ──

    public function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken(?string $token): bool {
        if (empty($token) || empty($_SESSION['csrf_token'])) return false;
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function requireCsrfToken(): void {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$this->validateCsrfToken($token)) {
            Response::error('Invalid or missing CSRF token', 403);
        }
    }

    // ── Rate Limiting ──

    public function checkRateLimit(string $action, int $maxAttempts = 5, int $windowSec = 300): bool {
        $key = "rate_limit:{$action}:" . ($this->getUserId() ?? $_SERVER['REMOTE_ADDR']);
        $attempts = $_SESSION[$key]['attempts'] ?? 0;
        $firstAttempt = $_SESSION[$key]['time'] ?? 0;

        if (time() - $firstAttempt > $windowSec) {
            $_SESSION[$key] = ['attempts' => 1, 'time' => time()];
            return true;
        }

        $_SESSION[$key]['attempts'] = $attempts + 1;
        return $_SESSION[$key]['attempts'] <= $maxAttempts;
    }

    public function requireRateLimit(string $action, int $max = 5, int $window = 300): void {
        if (!$this->checkRateLimit($action, $max, $window)) {
            Response::error("Too many attempts. Try again in " . ceil($window / 60) . " minutes.", 429);
        }
    }
}
