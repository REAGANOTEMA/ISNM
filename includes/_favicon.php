<?php
// Auto-detect path depth so favicon works from any directory
$_depth = substr_count(str_replace('\\','/',$_SERVER['PHP_SELF']), '/') - 2;
$_rp = $_depth > 0 ? str_repeat('../', $_depth) : './';
?>
<link rel="icon"             type="image/png" href="<?= $_rp ?>images/school-logo.png">
<link rel="shortcut icon"    type="image/png" href="<?= $_rp ?>images/school-logo.png">
<link rel="apple-touch-icon"                  href="<?= $_rp ?>images/school-logo.png">
<link rel="apple-touch-icon" sizes="180x180"  href="<?= $_rp ?>images/school-logo.png">
<link rel="apple-touch-icon" sizes="152x152"  href="<?= $_rp ?>images/school-logo.png">
<link rel="apple-touch-icon" sizes="144x144"  href="<?= $_rp ?>images/school-logo.png">
<link rel="apple-touch-icon" sizes="120x120"  href="<?= $_rp ?>images/school-logo.png">
<link rel="apple-touch-icon" sizes="76x76"    href="<?= $_rp ?>images/school-logo.png">
<link rel="manifest"                          href="/ISNM/manifest.json">
<meta name="theme-color"              content="#1a237e">
<meta name="msapplication-TileColor" content="#1a237e">
<meta name="msapplication-TileImage" content="<?= $_rp ?>images/school-logo.png">
<meta name="apple-mobile-web-app-capable"            content="yes">
<meta name="apple-mobile-web-app-status-bar-style"   content="black-translucent">
<meta name="apple-mobile-web-app-title"              content="ISNM">
<meta name="mobile-web-app-capable"                  content="yes">
