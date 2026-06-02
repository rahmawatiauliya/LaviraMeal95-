<?php
include_once __DIR__ . '/../api/shared/config.php';
$school_id = 'd28bb762-cbca-4379-b55a-a2a11ddd7d64';
$stmt = $db->prepare("SELECT * FROM sekolah_poin_jadwal WHERE sekolah_id = ?");
$stmt->execute([$school_id]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
