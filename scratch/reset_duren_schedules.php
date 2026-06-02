<?php
include_once __DIR__ . '/../api/shared/config.php';
$school_id = 'd28bb762-cbca-4379-b55a-a2a11ddd7d64';

try {
    $db->beginTransaction();

    $stmt = $db->prepare("UPDATE sekolah_poin_jadwal SET last_distributed = NULL WHERE sekolah_id = ? AND target_identifier IN ('1-A', '1-B')");
    $stmt->execute([$school_id]);
    echo "Successfully updated schedules: last_distributed set to NULL.\n";

    $db->commit();
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
