<?php
/**
 * Standardized Response handler for ISNM ERP.
 * Provides consistent JSON and HTML responses across all endpoints.
 */
if (class_exists('Response', false)) return;

class Response {
    private static string $charset = 'UTF-8';

    public static function json(mixed $data, int $status = 200, string $message = ''): never {
        self::setContentType('application/json', $status);
        $payload = ['success' => $status >= 200 && $status < 300, 'data' => $data];
        if ($message) $payload['message'] = $message;
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Operation completed'): never {
        self::json($data, 200, $message);
    }

    public static function created(mixed $data = null, string $message = 'Resource created'): never {
        self::json($data, 201, $message);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): never {
        self::setContentType('application/json', $status);
        $payload = ['success' => false, 'message' => $message];
        if ($errors !== null) $payload['errors'] = $errors;
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function notFound(string $message = 'Resource not found'): never {
        self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): never {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Access denied'): never {
        self::error($message, 403);
    }

    public static function methodNotAllowed(string $allowed = 'GET, POST'): never {
        self::setContentType('application/json', 405);
        header("Allow: $allowed");
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    public static function html(string $content, int $status = 200): never {
        self::setContentType('text/html', $status);
        echo $content;
        exit;
    }

    public static function redirect(string $url, int $status = 302): never {
        if ($status === 301) header('HTTP/1.1 301 Moved Permanently');
        header("Location: $url");
        exit;
    }

    public static function setContentType(string $mime, int $status = 200): void {
        if (!headers_sent()) {
            header_remove('Content-Type');
            header("Content-Type: $mime; charset=" . self::$charset);
            http_response_code($status);
        }
    }

    public static function download(string $filePath, string $filename = ''): never {
        if (!file_exists($filePath)) self::notFound('File not found');
        $filename = $filename ?: basename($filePath);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache');
        readfile($filePath);
        exit;
    }
}
