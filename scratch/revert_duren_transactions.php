<?php
include_once __DIR__ . '/../api/shared/config.php';

$school_id = 'd28bb762-cbca-4379-b55a-a2a11ddd7d64';

try {
    $db->beginTransaction();

    echo "=== BEFORE REVERSION ===\n";
    // Get School Balance
    $stmt = $db->prepare("SELECT id, nama_sekolah, saldo FROM sekolah WHERE id = ?");
    $stmt->execute([$school_id]);
    print_r($stmt->fetch(PDO::FETCH_ASSOC));

    // Get Students with non-zero balance
    $stmt = $db->prepare("SELECT id, nama, kelas, saldo FROM siswa WHERE sekolah_id = ? AND saldo > 0");
    $stmt->execute([$school_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($students);

    // Get Logs to delete
    $stmt = $db->prepare("SELECT id, type, message, detail FROM activity_logs WHERE sekolah_id = ? AND type = 'DISTRIBUSI_POIN'");
    $stmt->execute([$school_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($logs);

    echo "\n=== PERFORMING REVERSION ===\n";
    
    // 1. Return 30.00 PTS to school balance
    $stmt_update_school = $db->prepare("UPDATE sekolah SET saldo = saldo + 30.00 WHERE id = ?");
    $stmt_update_school->execute([$school_id]);
    echo "1. Added 30.00 PTS back to school balance.\n";

    // 2. Set students' balances in Kelas 1-A and 1-B back to 0.00
    $stmt_update_siswa = $db->prepare("UPDATE siswa SET saldo = 0.00 WHERE sekolah_id = ? AND kelas IN ('1-A', '1-B')");
    $stmt_update_siswa->execute([$school_id]);
    echo "2. Reset students in Kelas 1-A and 1-B balances to 0.00.\n";

    // 3. Delete the activity logs for the distribution
    $stmt_delete_logs = $db->prepare("DELETE FROM activity_logs WHERE sekolah_id = ? AND type = 'DISTRIBUSI_POIN'");
    $stmt_delete_logs->execute([$school_id]);
    echo "3. Cleaned up activity logs for the point distribution.\n";

    $db->commit();
    echo "Transaction committed successfully!\n\n";

    echo "=== AFTER REVERSION ===\n";
    // Get School Balance
    $stmt = $db->prepare("SELECT id, nama_sekolah, saldo FROM sekolah WHERE id = ?");
    $stmt->execute([$school_id]);
    print_r($stmt->fetch(PDO::FETCH_ASSOC));

    // Get Students with non-zero balance
    $stmt = $db->prepare("SELECT id, nama, kelas, saldo FROM siswa WHERE sekolah_id = ? AND saldo > 0");
    $stmt->execute([$school_id]);
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
