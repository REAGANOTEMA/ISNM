<?php
function renderComingSoon($title, $icon = 'fas fa-tools', $features = [], $badgeText = 'Coming Soon', $badgeColor = 'warning') {
    ?>
    <div class="card-section text-center py-5 mb-4">
        <div class="mb-3" style="font-size:52px;color:#0d6efd;opacity:.5;"><i class="<?= htmlspecialchars($icon) ?>"></i></div>
        <h5 class="fw-bold mb-2"><?= htmlspecialchars($title) ?></h5>
        <p class="text-muted mb-3" style="max-width:500px;margin:auto;">This module is under development. Planned features and functionality are listed below.</p>
        <span class="badge bg-<?= htmlspecialchars($badgeColor) ?> text-dark px-3 py-2 mb-4 d-inline-block">
            <i class="fas fa-clock me-1"></i><?= htmlspecialchars($badgeText) ?>
        </span>
        <?php if (!empty($features)): ?>
        <hr class="my-4" style="max-width:500px;margin:auto;">
        <div class="row g-3 mt-2" style="max-width:700px;margin:auto;">
            <?php foreach ($features as $f): ?>
            <div class="col-4 col-md-3">
                <div class="border rounded p-3 bg-light h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="<?= htmlspecialchars($f['icon'] ?? 'fas fa-star') ?> fa-2x text-primary mb-2"></i>
                    <small class="fw-medium"><?= htmlspecialchars($f['label']) ?></small>
                    <?php if (!empty($f['note'])): ?>
                    <small class="text-muted mt-1"><?= htmlspecialchars($f['note']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
