<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['kantin_id'] = '7ac82ed5-9564-491b-9cf5-640d72be7c0d';
$_POST['nama_menu'] = 'Nasi Goreng Test';
$_POST['deskripsi'] = 'Test Deskripsi';
$_POST['tanggal'] = date('Y-m-d');
include 'api/kantin/post_menu_harian.php';
?>
