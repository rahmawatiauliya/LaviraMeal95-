<?php
include 'api/shared/config.php';
try {
    $stmt = $db->query("DESCRIBE menu_harian");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
