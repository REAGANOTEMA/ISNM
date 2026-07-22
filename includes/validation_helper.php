<?php
/**
 * ISNM System Validation Helper
 * Provides consistent validation functions used across all dashboards.
 */

if (!function_exists('validateStudentData')) {
    /**
     * Validate student data array. Returns [valid => bool, errors => array].
     */
    function validateStudentData(array $data, string $mode = 'add'): array {
        $errors = [];

        if ($mode === 'add') {
            if (empty($data['first_name'])) $errors[] = 'First name is required.';
            if (empty($data['surname'])) $errors[] = 'Surname is required.';
        } else {
            if (empty($data['id']) || intval($data['id']) < 1) $errors[] = 'Valid student ID is required.';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address format.';
        }

        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]{7,20}$/', $data['phone'])) {
            $errors[] = 'Invalid phone number format.';
        }

        if (!empty($data['mobile_number']) && !preg_match('/^[0-9+\-\s()]{7,20}$/', $data['mobile_number'])) {
            $errors[] = 'Invalid mobile number format.';
        }

        $validGenders = ['Male', 'Female', 'Other', ''];
        if (!empty($data['gender']) && !in_array($data['gender'], $validGenders)) {
            $errors[] = 'Invalid gender value.';
        }

        $validStatuses = ['Active', 'Inactive', 'Suspended', 'Graduated', 'Withdrawn', ''];
        if (!empty($data['status']) && !in_array($data['status'], $validStatuses)) {
            $errors[] = 'Invalid status value.';
        }

        if (!empty($data['date_of_birth'])) {
            $dob = DateTime::createFromFormat('Y-m-d', $data['date_of_birth']);
            if (!$dob || $dob > new DateTime()) {
                $errors[] = 'Invalid date of birth.';
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }
}

if (!function_exists('validatePaymentData')) {
    /**
     * Validate payment data. Returns [valid => bool, errors => array].
     */
    function validatePaymentData(array $data): array {
        $errors = [];

        if (empty($data['student_id']) || intval($data['student_id']) < 1) {
            $errors[] = 'Valid student ID is required.';
        }
        if (!isset($data['amount']) || floatval($data['amount']) <= 0) {
            $errors[] = 'Payment amount must be greater than zero.';
        }

        $validMethods = ['Cash', 'Mobile Money', 'Bank Transfer', 'Cheque', 'Card', 'Online', ''];
        if (!empty($data['payment_method']) && !in_array($data['payment_method'], $validMethods)) {
            $errors[] = 'Invalid payment method.';
        }

        if (!empty($data['payment_date'])) {
            $d = DateTime::createFromFormat('Y-m-d', $data['payment_date']);
            if (!$d) {
                $errors[] = 'Invalid payment date format (use YYYY-MM-DD).';
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }
}

if (!function_exists('sanitizeInput')) {
    /**
     * Sanitize a string input value.
     */
    function sanitizeInput(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitizeArray')) {
    /**
     * Sanitize all string values in an array recursively.
     */
    function sanitizeArray(array $data): array {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $clean[$key] = sanitizeInput($value);
            } elseif (is_array($value)) {
                $clean[$key] = sanitizeArray($value);
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }
}

if (!function_exists('generateCSRFToken')) {
    /**
     * Generate a CSRF token and store it in the session.
     */
    function generateCSRFToken(): string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCSRFToken')) {
    /**
     * Verify a CSRF token against the session token.
     */
    function verifyCSRFToken(string $token): bool {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}

if (!function_exists('validateGender')) {
    function validateGender(string $gender): bool {
        return in_array($gender, ['Male', 'Female', 'Other']);
    }
}

if (!function_exists('validateMaritalStatus')) {
    function validateMaritalStatus(string $status): bool {
        return in_array($status, ['Single', 'Maried', 'Divorced', 'Widowed', 'Separated', '']);
    }
}

if (!function_exists('validateReligion')) {
    function validateReligion(string $religion): bool {
        return in_array($religion, ['Christianity', 'Islam', 'Hinduism', 'Buddhism', 'Judaism', 'Atheist', 'Agnostic', 'Other', '']);
    }
}

if (!function_exists('validateStudentCategory')) {
    function validateStudentCategory(string $cat): bool {
        return in_array($cat, ['UG', 'PG', 'Diploma', 'Certificate', 'Short Course', '']);
    }
}

if (!function_exists('validateStudentStatus')) {
    function validateStudentStatus(string $status): bool {
        return in_array($status, ['Active', 'Inactive', 'Suspended', 'Graduated', 'Withdrawn', 'Completed']);
    }
}

if (!function_exists('validatePaymentStatus')) {
    function validatePaymentStatus(string $status): bool {
        return in_array($status, ['completed', 'pending', 'cancelled', 'refunded']);
    }
}

if (!function_exists('validateRequirementStatus')) {
    function validateRequirementStatus(string $status): bool {
        return in_array($status, ['Not Submitted', 'Pending', 'Submitted', 'Verified', 'Rejected', 'Missing', 'Received', 'Not Yet Given']);
    }
}

if (!function_exists('isValidDate')) {
    function isValidDate(string $date, string $format = 'Y-m-d'): bool {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}

if (!function_exists('isValidEmail')) {
    function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('isValidPhone')) {
    function isValidPhone(string $phone): bool {
        return preg_match('/^[0-9+\-\s()]{7,20}$/', $phone) === 1;
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency(float $amount): string {
        return number_format($amount, 2, '.', ',');
    }
}

if (!function_exists('formatDate')) {
    function formatDate(string $date, string $outputFormat = 'd M Y'): string {
        if (empty($date)) return '';
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d) $d = new DateTime($date);
        return $d->format($outputFormat);
    }
}
