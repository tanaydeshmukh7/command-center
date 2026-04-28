<?php
require_once __DIR__ . '/api/config.php';
try {
    $db = getDB();
    ensureAdminTables($db);
    $db->exec("ALTER TABLE patients MODIFY severity VARCHAR(20)");

    if (!columnExists($db, 'patients', 'allocation')) {
        $db->exec("ALTER TABLE patients ADD COLUMN allocation VARCHAR(50) NULL");
    }

    if (!columnExists($db, 'patients', 'assigned_resource')) {
        $db->exec("ALTER TABLE patients ADD COLUMN assigned_resource VARCHAR(50) NULL");
    }

    echo "Success";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
