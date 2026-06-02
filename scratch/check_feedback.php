<?php
include 'api/shared/config.php';
$stmt = $db->query("SELECT * FROM feedback_kantin");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $db->query("SHOW TABLES LIKE 'transaksi_%'");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

try {
    $stmt3 = $db->query("SELECT * FROM transaksi_guru");
    if($stmt3) print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {}
?>
