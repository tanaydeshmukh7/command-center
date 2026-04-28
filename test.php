<?php
require_once 'config.php';

try {
    $db = getDB();
    echo "✅ Connected to Railway DB";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
