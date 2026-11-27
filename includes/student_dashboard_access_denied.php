<?php
if (!headers_sent()) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied | ISNM</title>
    <style>
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f4f7fb;font-family:Segoe UI,Roboto,Arial,sans-serif;color:#0f172a}
        .card{max-width:520px;background:#fff;padding:36px;border-radius:16px;box-shadow:0 12px 40px rgba(15,23,42,.12);text-align:center}
        .card h1{font-size:22px;color:#b91c1c;margin:0 0 12px}
        .card p{color:#475569;line-height:1.6;margin:0 0 20px}
        a{display:inline-block;background:#0f3d6e;color:#fff;text-decoration:none;padding:10px 22px;border-radius:8px;font-weight:600}
    </style>
</head>
<body>
    <div class="card">
        <h1>Access Denied</h1>
        <p>Your role is not authorised to perform this action. If you believe this is an error, contact the ICT administrator.</p>
        <a href="<?= htmlspecialchars(rtrim(defined('APP_BASE_PATH') ? APP_BASE_PATH : '/', '/') . '/staff-portal.php', ENT_QUOTES, 'UTF-8') ?>">Return to Staff Portal</a>
    </div>
</body>
</html>
