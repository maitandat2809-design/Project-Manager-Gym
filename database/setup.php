<?php
$host = 'sql202.infinityfree.com';
$dbname = 'if0_42164663_gym';
$username = 'if0_42164663';
$password = 'Dat2003==';

echo "Starting setup...<br>";

// 1. Unzip Source Code
$zip = new ZipArchive;
$res = $zip->open('GymManager_SourceCode.zip');
if ($res === TRUE) {
    $zip->extractTo(__DIR__);
    $zip->close();
    echo "Files extracted successfully.<br>";
} else {
    echo "Failed to extract zip file.<br>";
}

// 2. Overwrite config/db.php
$dbConfig = "<?php
\$host = '$host';
\$dbname = '$dbname';
\$username = '$username';
\$password = '$password';

try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\", \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    die(\"Connection failed: \" . \$e->getMessage());
}
?>";

if (!file_exists(__DIR__ . '/config')) {
    mkdir(__DIR__ . '/config', 0755, true);
}
file_put_contents(__DIR__ . '/config/db.php', $dbConfig);
echo "Database config updated.<br>";

// 3. Import Database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents('gym_manager_export.sql');
    if ($sql) {
        $pdo->exec($sql);
        echo "Database imported successfully.<br>";
    } else {
        echo "SQL file not found or empty.<br>";
    }
} catch(PDOException $e) {
    echo "Database Import Error: " . $e->getMessage() . "<br>";
}

// 4. Cleanup
@unlink('GymManager_SourceCode.zip');
@unlink('gym_manager_export.sql');
echo "Setup complete! Please delete setup.php manually or wait for it to be deleted.";
@unlink(__FILE__);
?>
