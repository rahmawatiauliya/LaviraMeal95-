<?php
include_once __DIR__ . '/../api/shared/config.php';

$school_id = 'd28bb762-cbca-4379-b55a-a2a11ddd7d64';

echo "=== SCHOOL ===\n";
$stmt = $db->prepare("SELECT id, nama_sekolah, saldo FROM sekolah WHERE id = ?");
$stmt->execute([$school_id]);
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "=== STUDENTS WITH NON-ZERO BALANCE OR IN KELAS 1 ===\n";
$stmt = $db->prepare("SELECT id, nama, kelas, saldo FROM siswa WHERE sekolah_id = ? AND (saldo > 0 OR kelas LIKE '1-%')");
$stmt->execute([$school_id]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "=== ACTIVITY LOGS ===\n";
$stmt = $db->prepare("SELECT id, type, message, detail, created_at FROM activity_logs WHERE sekolah_id = ? ORDER BY created_at DESC");
$stmt->execute([$school_id]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "=== ALL STUDENTS COUNT BY CLASS ===\n";
$stmt = $db->prepare("SELECT kelas, COUNT(*) as count, SUM(saldo) as total_saldo FROM siswa WHERE sekolah_id = ? GROUP BY kelas");
$stmt->execute([$school_id]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
