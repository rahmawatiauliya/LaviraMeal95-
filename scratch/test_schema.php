<?php
include 'api/shared/config.php';

// Show test kantin entries
$stmt = $db->query("
    SELECT u.id as user_id, u.username, u.email, k.id as kantin_id, k.nama_kantin, k.created_at
    FROM users u
    JOIN kantin k ON u.id = k.user_id
    WHERE u.username LIKE 'testkantin%'
       OR u.email LIKE 'testpemilik%'
       OR k.nama_kantin LIKE 'Test Kantin%'
    ORDER BY k.created_at DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($rows) . " test canteen(s):\n";
foreach ($rows as $row) {
    echo " - [{$row['kantin_id']}] {$row['nama_kantin']} | user: {$row['username']} | {$row['created_at']}\n";
}

if (count($rows) > 0) {
    // Delete via users table (CASCADE will remove kantin records)
    $userIds = array_column($rows, 'user_id');
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $del = $db->prepare("DELETE FROM users WHERE id IN ($placeholders)");
    $del->execute($userIds);
    echo "\nDeleted " . $del->rowCount() . " user(s) and their associated kantin records.\n";
} else {
    echo "No test kantin data found.\n";
}
?>
