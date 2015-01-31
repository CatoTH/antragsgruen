<?php
// find -name "*php" -exec php tests/tokenize.php {} \;

echo $argv[1] . "\n";
error_reporting(E_ALL);
ini_set("display_errors", 1);
$x = file_get_contents($argv[1]);
token_get_all($x);
