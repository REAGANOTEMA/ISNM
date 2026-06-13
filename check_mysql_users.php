<?php
$c = @new mysqli('localhost', 'root', '', 'mysql', 3306);
if ($c->connect_error) {
    echo 'MySQL connect failed: ' . $c->connect_error . "\n";
    exit;
}
$r = $c->query("SELECT User,Host FROM user WHERE User LIKE 'igangaschoolofl_%'");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        echo $row['User'] . '@' . $row['Host'] . "\n";
    }
} else {
    echo 'Query failed: ' . $c->error . "\n";
}
$c->close();
