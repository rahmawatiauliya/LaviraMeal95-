<?php
include 'api/shared/config.php';
try {
    $db->exec("UPDATE siswa SET saldo = saldo + 11");
    echo "Successfully added 11 PTS to all students.";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
