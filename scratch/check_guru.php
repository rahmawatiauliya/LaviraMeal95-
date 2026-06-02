<?php
include 'api/shared/config.php';
$stmt = $db->query("SHOW TABLES LIKE 'guru'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $db->query("SELECT * FROM guru WHERE id = '61a3edbc0ef5c3f62b711aaea28667f9'");
if ($stmt2) {
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
}
?>
