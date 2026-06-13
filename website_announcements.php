<?php
require_once __DIR__ . '/config/database.php';

// Use website DB connection
$conn = getWebsiteConnection();

$announcements = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, title, content, announcement_type, target_audience, priority, posted_by_name, posted_by_role, posted_date, expiry_date, attachment_path FROM announcements WHERE status = 'published' AND (expiry_date IS NULL OR expiry_date >= CURDATE()) ORDER BY priority DESC, posted_date DESC");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $announcements = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log('Announcements fetch error: ' . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Announcements - ISNM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4">Latest Announcements</h1>

    <?php if (empty($announcements)): ?>
        <div class="alert alert-info">No announcements at the moment. Please check back later.</div>
    <?php else: ?>
        <?php foreach ($announcements as $a): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title"><?php echo htmlspecialchars($a['title']); ?></h5>
                        <small class="text-muted"><?php echo date('M j, Y', strtotime($a['posted_date'])); ?></small>
                    </div>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($a['content'])); ?></p>
                    <p class="text-muted small mb-0">Posted by <?php echo htmlspecialchars($a['posted_by_name'] ?? $a['posted_by_role'] ?? 'Staff'); ?> — <?php echo htmlspecialchars($a['posted_by_role'] ?? ''); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
