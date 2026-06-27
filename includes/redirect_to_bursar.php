<?php
/**
 * Redirect helper for consolidated bursar dashboard.
 * Call at the top of any standalone bursar file to redirect to school-bursar.php
 * with the appropriate section anchor.
 */
function bursarRedirect(string $section = 'home', bool $keepGetParams = false) {
    $url = 'school-bursar.php#' . $section;
    if ($keepGetParams && !empty($_GET)) {
        $params = $_GET;
        unset($params['section'], $params['view']);
        if (!empty($params)) {
            $url = 'school-bursar.php?' . http_build_query($params) . '#' . $section;
        }
    }
    header('Location: ' . $url);
    exit;
}
