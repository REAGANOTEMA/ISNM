<?php
/**
 * Database Configuration Helper for ISNM
 * Run ONCE after uploading to hosting to test and fix database credentials.
 * DELETE this file after successful configuration.
 */
$step = $_GET['step'] ?? 'start';
$envPath = __DIR__ . '/.env';

function testConn($host, $user, $pass, $name, $port = 3306) {
    try {
        $c = @new mysqli($host, $user, $pass, $name, $port);
        if ($c->connect_error) return false;
        $c->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function writeEnv($path, $updates) {
    $content = file_get_contents($path);
    foreach ($updates as $key => $val) {
        $pattern = "/^{$key}=.*/m";
        $replacement = "{$key}={$val}";
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            $content .= "\n{$key}={$val}";
        }
    }
    return file_put_contents($path, $content);
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>ISNM DB Setup</title>
<style>body{font-family:Arial,sans-serif;max-width:700px;margin:40px auto;padding:20px;line-height:1.6}h1{color:#1a237e}.ok{color:#16a34a;font-weight:700}.fail{color:#dc2626;font-weight:700}.box{border:1px solid #ddd;border-radius:8px;padding:16px;margin:12px 0;background:#f9fafb}input,select{width:100%;padding:8px;margin:4px 0 12px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box}button{background:#1a237e;color:#fff;border:none;padding:10px 24px;border-radius:6px;cursor:pointer}button:hover{background:#0d1442}.info{background:#e8eaf6;padding:12px;border-radius:6px;margin:12px 0}
</style></head><body>

<?php if ($step === 'start'): ?>
<h1>ISNM Database Setup</h1>
<p class="info">This tool helps configure your hosting database credentials. cPanel hosting typically <strong>prefixes</strong> database names and users with your cPanel username (e.g., <code>youruser_igangaschoolofl_students_db</code>).</p>

<div class="box">
<h3>Step 1: Find your cPanel MySQL details</h3>
<ol>
<li>Log into your hosting cPanel</li>
<li>Click <strong>MySQL Databases</strong> or <strong>Databases → MySQL</strong></li>
<li>You'll see a list of databases like: <code>youruser_igangaschoolofl_students_db</code></li>
<li>You'll see database users like: <code>youruser_someuser</code></li>
<li>Make sure each user is <strong>assigned</strong> to its database with <strong>ALL PRIVILEGES</strong></li>
</ol>
<a href="?step=test"><button>I have my details → Test Connection</button></a>
</div>

<?php elseif ($step === 'test'): ?>
<h1>Test Database Connection</h1>
<p>Enter your hosting database details below. Leave a field blank to skip that database.</p>

<form method="POST">
<div class="box">
<h3>Students Database</h3>
<p class="info"><small>cPanel name usually: <code>youruser_igangaschoolofl_students_db</code></small></p>
<input type="text" name="stu_host" placeholder="Host (usually localhost)" value="localhost">
<input type="text" name="stu_name" placeholder="Database name" value="igangaschoolofl_students_db">
<input type="text" name="stu_user" placeholder="Username" value="igangaschoolofl_students_db">
<input type="password" name="stu_pass" placeholder="Password">
</div>

<div class="box">
<h3>Staff Database</h3>
<p class="info"><small>cPanel name usually: <code>youruser_igangaschoolofl_staffs_db</code></small></p>
<input type="text" name="staff_host" placeholder="Host (usually localhost)" value="localhost">
<input type="text" name="staff_name" placeholder="Database name" value="igangaschoolofl_staffs_db">
<input type="text" name="staff_user" placeholder="Username" value="igangaschoolofl_staffs_db">
<input type="password" name="staff_pass" placeholder="Password">
</div>

<div class="box">
<h3>Website Database</h3>
<p class="info"><small>cPanel name usually: <code>youruser_igangaschoolofl_website_db</code></small></p>
<input type="text" name="web_host" placeholder="Host (usually localhost)" value="localhost">
<input type="text" name="web_name" placeholder="Database name" value="igangaschoolofl_website_db">
<input type="text" name="web_user" placeholder="Username" value="igangaschoolofl_website_db">
<input type="password" name="web_pass" placeholder="Password">
</div>

<div class="box">
<h3>ICT Database</h3>
<p class="info"><small>cPanel name usually: <code>youruser_igangaschoolofl_ict</code></small></p>
<input type="text" name="ict_host" placeholder="Host (usually localhost)" value="localhost">
<input type="text" name="ict_name" placeholder="Database name" value="igangaschoolofl_ict">
<input type="text" name="ict_user" placeholder="Username" value="igangaschoolofl_ict">
<input type="password" name="ict_pass" placeholder="Password">
</div>

<button type="submit" name="save" value="1">Test & Save Configuration</button>
</form>

<?php endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    echo '<h2>Results</h2>';
    $results = [];
    $databases = [
        'STUDENTS' => ['host' => 'stu_host', 'name' => 'stu_name', 'user' => 'stu_user', 'pass' => 'stu_pass'],
        'STAFF' => ['host' => 'staff_host', 'name' => 'staff_name', 'user' => 'staff_user', 'pass' => 'staff_pass'],
        'WEBSITE' => ['host' => 'web_host', 'name' => 'web_name', 'user' => 'web_user', 'pass' => 'web_pass'],
        'ICT' => ['host' => 'ict_host', 'name' => 'ict_name', 'user' => 'ict_user', 'pass' => 'ict_pass'],
    ];
    $updates = [];
    $allOk = true;

    foreach ($databases as $prefix => $fields) {
        $host = trim($_POST[$fields['host']] ?? 'localhost');
        $name = trim($_POST[$fields['name']] ?? '');
        $user = trim($_POST[$fields['user']] ?? '');
        $pass = trim($_POST[$fields['pass']] ?? '');

        if (empty($name) || empty($user)) {
            echo "<p><strong>{$prefix}:</strong> Skipped (empty fields)</p>";
            continue;
        }

        $ok = testConn($host, $user, $pass, $name, 3306);
        $status = $ok ? '<span class="ok">✓ CONNECTED</span>' : '<span class="fail">✗ FAILED</span>';
        echo "<p><strong>{$prefix} DB:</strong> {$status} — Host: {$host}, DB: {$name}, User: {$user}</p>";

        if ($ok) {
            $updates["{$prefix}_DB_HOST"] = $host;
            $updates["{$prefix}_DB_NAME"] = $name;
            $updates["{$prefix}_DB_USER"] = $user;
            $updates["{$prefix}_DB_PASS"] = $pass;
        } else {
            $allOk = false;
        }
    }

    if (count($updates) > 0 && file_exists($envPath) && is_writable($envPath)) {
        writeEnv($envPath, $updates);
        echo '<p class="ok">✓ .env file updated successfully!</p>';
    } elseif (count($updates) > 0) {
        echo '<p>Could not write to .env. Copy these into your .env file manually:</p>';
        echo '<div class="box"><pre>';
        foreach ($updates as $k => $v) echo "{$k}={$v}\n";
        echo '</pre></div>';
    }

    if ($allOk) {
        echo '<p class="ok" style="font-size:1.2em">✓ All databases connected! Your system should work now.</p>';
        echo '<p><strong>Important:</strong> Delete this <code>db-setup.php</code> file after setup for security.</p>';
    } else {
        echo '<p class="fail">✗ Some connections failed. Check your hosting cPanel MySQL settings and try again.</p>';
        echo '<p>Common issues:<br>
        • Database names on cPanel are prefixed: <code>yourcpaneluser_igangaschoolofl_students_db</code><br>
        • Database users are separate from database names<br>
        • Users must be assigned to databases with ALL PRIVILEGES<br>
        • Host is usually <code>localhost</code> on shared hosting</p>';
    }
}
?>
</body></html>
