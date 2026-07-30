<?php
/**
 * Centralized Error Handler for ISNM ERP.
 * Replaces scattered error_reporting(0) with unified logging.
 */
if (class_exists('ErrorHandler', false)) return;

class ErrorHandler {
    private static bool $registered = false;
    private static string $logDir = '';
    private static bool $displayErrors = false;

    public static function register(bool $displayErrors = false): void {
        if (self::$registered) return;
        self::$registered = true;
        self::$displayErrors = $displayErrors;
        self::$logDir = __DIR__ . '/../logs';

        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0775, true);
        }

        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        ini_set('display_errors', $displayErrors ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', self::$logDir . '/php_errors.log');
        ini_set('ignore_repeated_errors', '1');

        set_error_handler([self::class, 'handleError'], E_ALL & ~E_DEPRECATED & ~E_STRICT);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) return false;

        $type = self::getErrorType($severity);
        $logEntry = sprintf("[%s] %s: %s in %s:%d", date('Y-m-d H:i:s'), $type, $message, $file, $line);
        error_log($logEntry);

        if ($display ?? self::$displayErrors) {
            printf("<pre style='color:#dc2626;background:#fef2f2;padding:8px;margin:4px;border-radius:4px;font-size:12px'><strong>%s:</strong> %s in <code>%s:%d</code></pre>", $type, htmlspecialchars($message), htmlspecialchars($file), $line);
        }
        return true;
    }

    public static function handleException(\Throwable $e): void {
        $logEntry = sprintf(
            "[%s] Uncaught %s: %s in %s:%d\n%s",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        error_log($logEntry);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $logEntry . "\n");
            exit(1);
        }

        if (self::$displayErrors) {
            echo '<div style="background:#fef2f2;color:#991b1b;padding:20px;border-radius:8px;max-width:700px;margin:40px auto;font-family:sans-serif">';
            echo '<h2 style="margin:0 0 10px;color:#dc2626">System Error</h2>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p style="font-size:12px;color:#666">File: ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
            echo '</div>';
        } else {
            http_response_code(500);
            if (ob_get_level()) ob_clean();
            echo '<!DOCTYPE html><html><head><title>Error</title><style>body{font-family:sans-serif;background:#fef2f2;padding:40px}.card{background:#fff;border-radius:12px;padding:24px;border:1px solid #fecaca;max-width:600px;margin:auto}h2{color:#dc2626;margin:0 0 8px}p{color:#666}</style></head>';
            echo '<body><div class="card"><h2>Internal Server Error</h2><p>An unexpected error occurred. The system administrator has been notified.</p></div></body></html>';
        }
        exit(1);
    }

    public static function handleShutdown(): void {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleError($err['type'], $err['message'], $err['file'], $err['line']);
        }
    }

    public static function log(string $message, string $level = 'INFO'): void {
        $logEntry = sprintf("[%s] [%s] %s", date('Y-m-d H:i:s'), strtoupper($level), $message);
        error_log($logEntry);
    }

    private static function getErrorType(int $severity): string {
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            return 'Fatal Error';
        } elseif (in_array($severity, [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING], true)) {
            return 'Warning';
        } elseif ($severity === E_PARSE) {
            return 'Parse Error';
        } elseif (in_array($severity, [E_NOTICE, E_USER_NOTICE], true)) {
            return 'Notice';
        } elseif ($severity === E_STRICT) {
            return 'Strict Standards';
        } elseif (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
            return 'Deprecated';
        }
        return 'Unknown';
    }
}
