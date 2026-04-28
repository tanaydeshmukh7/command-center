<?php
// Try MySQL 8 with native_password workaround
$attempts = [
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'opts' => [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'password', 'opts' => []],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'admin', 'opts' => []],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '123456', 'opts' => []],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'Password123', 'opts' => []],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'tanay', 'opts' => []],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '1234', 'opts' => []],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'admin123', 'opts' => []],
    // Try with mysqli
];

foreach ($attempts as $a) {
    try {
        $dsn = "mysql:host={$a['host']};port=3306";
        $pdo = new PDO($dsn, $a['user'], $a['pass'], $a['opts']);
        echo "SUCCESS: pass=" . ($a['pass'] ?: '(empty)') . "\n";
        $stmt = $pdo->query('SHOW DATABASES');
        while ($row = $stmt->fetch()) echo "  " . $row[0] . "\n";
        exit(0);
    } catch (Exception $e) {
        echo "FAIL pass=" . ($a['pass'] ?: '(empty)') . "\n";
    }
}

// Also try mysqli
echo "\n--- Trying mysqli ---\n";
$passwords = ['', 'root', 'password', 'admin', '123456', 'tanay', '1234'];
foreach ($passwords as $pass) {
    $conn = @mysqli_connect('127.0.0.1', 'root', $pass, '', 3306);
    if ($conn) {
        echo "mysqli SUCCESS with pass=" . ($pass ?: '(empty)') . "\n";
        $result = mysqli_query($conn, 'SHOW DATABASES');
        while ($row = mysqli_fetch_row($result)) echo "  " . $row[0] . "\n";
        exit(0);
    } else {
        echo "mysqli FAIL pass=" . ($pass ?: '(empty)') . ": " . mysqli_connect_error() . "\n";
    }
}
