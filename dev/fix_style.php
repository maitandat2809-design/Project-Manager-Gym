<?php
$c = file_get_contents('assets/css/style.css');

// Remove both occurrences of .nav-links
$c = preg_replace('/\.nav-links\s*\{[^}]+\}/', '', $c);

// Add the correct one at the end of the file
$c .= "\n.nav-links {\n    display: flex;\n    gap: 2.5rem;\n    list-style: none;\n    margin: 0 auto;\n}\n";

file_put_contents('assets/css/style.css', $c);
echo "Style updated!";
?>
