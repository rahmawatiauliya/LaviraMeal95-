<?php
// Simulate file upload and POST request to register_kantin.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['nama_pemilik'] = 'Test Owner';
$_POST['email_pemilik'] = 'testowner@gmail.com';
$_POST['nama_kantin'] = 'Test Canteen';
$_POST['wilayah_id'] = '20219803'; // SMPN 1 Klari
$_POST['username'] = 'testcanteen1';
$_POST['password'] = 'password123';

// Mock $_FILES
$_FILES['foto_kantin'] = [
    'name' => 'test_kantin.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => tempnam(sys_get_temp_dir(), 'php'),
    'error' => 0,
    'size' => 1234
];
file_put_contents($_FILES['foto_kantin']['tmp_name'], 'dummy content');

$_FILES['foto_menu'] = [
    'name' => 'test_menu.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => tempnam(sys_get_temp_dir(), 'php'),
    'error' => 0,
    'size' => 1234
];
file_put_contents($_FILES['foto_menu']['tmp_name'], 'dummy content');

ob_start();
include 'api/auth/register_kantin.php';
$output = ob_get_clean();

echo "Output:\n" . $output . "\n";
?>
