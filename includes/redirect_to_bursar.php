<?php
function bursarRedirect(string $section = 'overview', bool $keepGetParams = false) {
    $url = 'school-bursar.php?page=' . urlencode($section);
    if ($keepGetParams && !empty($_GET)) {
        $params = $_GET;
        unset($params['page'], $params['section'], $params['view']);
        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }
    }
    header('Location: ' . $url);
    exit;
}
