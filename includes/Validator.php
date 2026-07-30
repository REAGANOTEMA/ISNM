<?php
/**
 * Input validation and sanitization utilities for ISNM ERP.
 */
if (class_exists('Validator', false)) return;

class Validator {
    private array $errors = [];
    private array $data = [];
    private array $rules = [];

    public function __construct(array $data, array $rules) {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }

    public static function make(array $data, array $rules): self {
        return new self($data, $rules);
    }

    public function passes(): bool {
        return empty($this->errors);
    }

    public function fails(): bool {
        return !$this->passes();
    }

    public function errors(): array {
        return $this->errors;
    }

    public function firstError(): string {
        return $this->errors ? reset($this->errors)[0] : '';
    }

    public function validated(): array {
        $result = [];
        foreach ($this->rules as $field => $fieldRules) {
            if (array_key_exists($field, $this->data)) {
                $result[$field] = $this->data[$field];
            }
        }
        return $result;
    }

    private function validate(): void {
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $method = 'rule' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    $this->$method($field, $value, $params);
                }
            }
        }
    }

    private function addError(string $field, string $message): void {
        $this->errors[$field][] = $message;
    }

    // ── Built-in Rules ──

    private function ruleRequired(string $field, mixed $value): void {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }

    private function ruleEmail(string $field, mixed $value): void {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Invalid email address');
        }
    }

    private function ruleNumeric(string $field, mixed $value): void {
        if ($value && !is_numeric($value)) {
            $this->addError($field, ucfirst($field) . ' must be a number');
        }
    }

    private function ruleInteger(string $field, mixed $value): void {
        if ($value && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, ucfirst($field) . ' must be an integer');
        }
    }

    private function ruleMin(string $field, mixed $value, array $params): void {
        $min = (int)($params[0] ?? 0);
        if (is_string($value) && strlen($value) < $min) {
            $this->addError($field, ucfirst($field) . " must be at least $min characters");
        }
        if (is_numeric($value) && $value < $min) {
            $this->addError($field, ucfirst($field) . " must be at least $min");
        }
    }

    private function ruleMax(string $field, mixed $value, array $params): void {
        $max = (int)($params[0] ?? 0);
        if (is_string($value) && strlen($value) > $max) {
            $this->addError($field, ucfirst($field) . " must not exceed $max characters");
        }
        if (is_numeric($value) && $value > $max) {
            $this->addError($field, ucfirst($field) . " must not exceed $max");
        }
    }

    private function rulePhone(string $field, mixed $value): void {
        if ($value && !preg_match('/^\+?256\d{9}$/', preg_replace('/\D/', '', $value))) {
            $this->addError($field, 'Invalid phone number. Use Uganda format (256...)');
        }
    }

    private function ruleDate(string $field, mixed $value): void {
        if ($value && !strtotime($value)) {
            $this->addError($field, 'Invalid date format');
        }
    }

    private function ruleUrl(string $field, mixed $value): void {
        if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, 'Invalid URL');
        }
    }

    private function ruleIn(string $field, mixed $value, array $params): void {
        if ($value && !in_array((string)$value, $params, true)) {
            $this->addError($field, ucfirst($field) . ' must be one of: ' . implode(', ', $params));
        }
    }

    private function ruleBoolean(string $field, mixed $value): void {
        if ($value !== null && !in_array((string)$value, ['0', '1', 'true', 'false', 0, 1, true, false], true)) {
            $this->addError($field, ucfirst($field) . ' must be true or false');
        }
    }

    private function ruleConfirmed(string $field, mixed $value): void {
        $confirmationField = $field . '_confirmation';
        $confirmation = $this->data[$confirmationField] ?? null;
        if ($value !== $confirmation) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' confirmation does not match');
        }
    }

    // ── Static Sanitizers ──

    public static function sanitize(mixed $value, string $type = 'string'): mixed {
        if ($type === 'string') {
            return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
        } elseif ($type === 'int') {
            return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : 0;
        } elseif ($type === 'float') {
            return filter_var($value, FILTER_VALIDATE_FLOAT) !== false ? (float)$value : 0.0;
        } elseif ($type === 'email') {
            return strtolower(trim(filter_var($value, FILTER_SANITIZE_EMAIL)));
        } elseif ($type === 'url') {
            return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
        } elseif ($type === 'phone') {
            return preg_replace('/[^\d+]/', '', trim($value));
        } elseif ($type === 'alphanumeric') {
            return preg_replace('/[^a-zA-Z0-9]/', '', trim($value));
        } elseif ($type === 'bool') {
            return in_array((string)$value, ['1', 'true', 'yes', 'on'], true);
        }
        return trim((string)$value);
    }

    public static function sanitizeArray(array $data, array $types): array {
        $result = [];
        foreach ($types as $field => $type) {
            if (array_key_exists($field, $data)) {
                $result[$field] = self::sanitize($data[$field], $type);
            }
        }
        return $result;
    }

    public static function escapeHtml(mixed $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function escapeJs(string $value): string {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public static function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }

    public static function generateOtp(int $digits = 6): string {
        $min = 10 ** ($digits - 1);
        $max = 10 ** $digits - 1;
        return (string) random_int($min, $max);
    }

    public static function isValidNsin(string $nsin): bool {
        return (bool) preg_match('/^CM\d{13}$/', strtoupper(trim($nsin)));
    }
}
