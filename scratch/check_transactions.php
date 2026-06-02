<?php
include 'api/shared/config.php';
$stmt = $db->query("SELECT * FROM kantin");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $db->query("SELECT * FROM transaksi_siswa");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
?>
