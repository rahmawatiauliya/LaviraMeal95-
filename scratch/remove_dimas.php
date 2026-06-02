<?php
include 'api/shared/config.php';

try {
    $db->beginTransaction();

    // 1. Cari data Dimas Saputra
    $stmtSiswa = $db->query("SELECT id, saldo FROM siswa WHERE nama = 'Dimas Saputra' LIMIT 1");
    $siswa = $stmtSiswa->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        throw new Exception("Siswa Dimas Saputra tidak ditemukan.");
    }

    // 2. Cari transaksi terakhir Dimas Saputra
    $stmtTrans = $db->prepare("SELECT id, kantin_id, nominal FROM transaksi_siswa WHERE siswa_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmtTrans->execute([$siswa['id']]);
    $transaksi = $stmtTrans->fetch(PDO::FETCH_ASSOC);

    if (!$transaksi) {
        throw new Exception("Transaksi untuk Dimas Saputra tidak ditemukan.");
    }

    $kantin_id = $transaksi['kantin_id'];
    $nominal = $transaksi['nominal'];

    // 3. Hapus Transaksi
    $db->prepare("DELETE FROM transaksi_siswa WHERE id = ?")->execute([$transaksi['id']]);

    // 4. Hapus juga log pengambilan makan (biar bersih)
    $db->prepare("DELETE FROM siswa_pengambilan_mbg WHERE siswa_id = ? ORDER BY id DESC LIMIT 1")->execute([$siswa['id']]);

    // 5. Kurangi Poin Kantin
    $db->prepare("UPDATE kantin SET saldo = saldo - ? WHERE id = ?")->execute([$nominal, $kantin_id]);

    // 6. Kembalikan Poin Dimas
    $db->prepare("UPDATE siswa SET saldo = saldo + ? WHERE id = ?")->execute([$nominal, $siswa['id']]);

    $db->commit();
    echo "Berhasil menghapus riwayat transaksi test atas nama Dimas Saputra, memotong $nominal PTS dari Kantin, dan mengembalikan saldo Dimas.";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
