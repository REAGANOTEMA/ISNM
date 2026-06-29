<?php
/**
 * Auth API Module — authentication endpoints
 * Routes: /api/auth/login, /api/auth/logout, /api/auth/me, /api/auth/csrf
 */
class Api_Auth {
    private EnterpriseAuth $auth;

    public function __construct(EnterpriseAuth $auth) {
        $this->auth = $auth;
    }

    public function login(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $email = Validator::sanitize($data['email'] ?? '', 'email');
        $password = $data['password'] ?? '';
        $type = Validator::sanitize($data['type'] ?? 'staff', 'string');

        if (!$email || !$password) {
            Response::error('Email and password are required');
        }

        require_once __DIR__ . '/../../auth-service.php';
        $authService = new AuthenticationService();
        $result = $authService->authenticate($email, $password, $type);

        if (!$result['success']) {
            Response::error($result['message'] ?? 'Authentication failed', 401);
        }

        $this->auth->login($result['user'], $type);
        Response::success([
            'user' => $this->auth->getUser(),
            'token' => $this->auth->generateCsrfToken(),
        ], 'Login successful');
    }

    public function logout(): void {
        $this->auth->logout();
        Response::success(null, 'Logged out');
    }

    public function me(): void {
        $this->auth->requireAuth();
        Response::success([
            'user' => $this->auth->getUser(),
            'role' => $this->auth->getRole(),
            'permissions' => $this->getUserPermissions(),
        ]);
    }

    public function csrf(): void {
        $token = $this->auth->generateCsrfToken();
        Response::success(['csrf_token' => $token]);
    }

    private function getUserPermissions(): array {
        $allModules = ['dashboard', 'students', 'staff', 'fees', 'payments', 'ledger',
                       'payroll', 'reports', 'tax', 'library', 'attendance', 'marks',
                       'exams', 'transcripts', 'graduation', 'discipline', 'communications',
                       'inventory', 'budget', 'recruitment', 'appraisals'];
        $result = [];
        foreach ($allModules as $module) {
            $result[$module] = $this->auth->hasPermission($module);
        }
        return $result;
    }
}
