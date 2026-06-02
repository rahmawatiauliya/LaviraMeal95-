<?php
header("Content-Type: application/json");
include_once __DIR__ . '/../shared/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    // Ambil data JSON dari body request
    $data = json_decode(file_get_contents("php://input"));
    
    $student_token = $data->student_token ?? '';
    $kantin_user_id = $data->kantin_id ?? ''; // Ini adalah user_id kantin dari user_data (tabel users)

    if (empty($student_token) || empty($kantin_user_id)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Token Siswa dan ID Kantin wajib diisi."]);
        exit;
    }

    try {
        $db->beginTransaction();

        // 1. Cari data Kantin berdasarkan user_id (dari tabel users) atau id (dari tabel kantin)
        $stmtKantin = $db->prepare("SELECT id, nama_kantin, sekolah_id FROM kantin WHERE user_id = ? OR id = ? LIMIT 1");
        $stmtKantin->execute([$kantin_user_id, $kantin_user_id]);
        $kantin = $stmtKantin->fetch(PDO::FETCH_ASSOC);

        if (!$kantin) {
            throw new Exception("Data Merchant/Kantin tidak ditemukan.");
        }
        $kantin_id = $kantin['id'];
        $nama_kantin = $kantin['nama_kantin'];

        // 2. Cari data Pelanggan berdasarkan qr_code_token atau username atau nis
        $stmtSiswa = $db->prepare("
            SELECT s.id, s.user_id, s.saldo, s.nama, s.nis, s.sekolah_id, 'siswa' as role 
            FROM siswa s 
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.qr_code_token = ? OR s.nis = ? OR u.username = ?
            LIMIT 1
        ");
        $stmtSiswa->execute([$student_token, $student_token, $student_token]);
        $pelanggan = $stmtSiswa->fetch(PDO::FETCH_ASSOC);

        if (!$pelanggan) {
            $stmtGuru = $db->prepare("
                SELECT g.id, g.user_id, g.saldo, g.nama, g.nip as nis, g.sekolah_id, 'guru' as role 
                FROM guru g 
                LEFT JOIN users u ON g.user_id = u.id
                WHERE u.username = ? OR g.nip = ?
                LIMIT 1
            ");
            $stmtGuru->execute([$student_token, $student_token]);
            $pelanggan = $stmtGuru->fetch(PDO::FETCH_ASSOC);
        }

        if (!$pelanggan) {
            throw new Exception("Siswa/Guru dengan token tersebut tidak terdaftar.");
        }

        $pelanggan_id = $pelanggan['id'];
        $nama_siswa = $pelanggan['nama']; // Keep variable name same for response backward compatibility
        $saldo_pelanggan = (float)$pelanggan['saldo'];
        $role_pelanggan = $pelanggan['role'];
        
        // 3. Validasi Saldo Pelanggan (Minimal 1 PTS)
        $nominal_transaksi = 1; // 1 PTS per porsi (atau 1 Kuota Makan)
        if ($saldo_pelanggan < $nominal_transaksi) {
            throw new Exception("Poin tidak mencukupi. (Saldo: $saldo_pelanggan PTS, Butuh: $nominal_transaksi PTS)");
        }

        // 3.5 Ambil Detail Menu Makanan Kantin Terkini
        $stmtMenu = $db->prepare("SELECT id, nama_menu, foto_menu FROM menu_harian WHERE kantin_id = ? ORDER BY tanggal DESC LIMIT 1");
        $stmtMenu->execute([$kantin_id]);
        $menu_makanan = $stmtMenu->fetch(PDO::FETCH_ASSOC);
        $nama_menu = $menu_makanan ? $menu_makanan['nama_menu'] : 'Paket Makan LaviraMeal';

        // 4. Potong Saldo Pelanggan
        if ($role_pelanggan == 'siswa') {
            $stmtPotong = $db->prepare("UPDATE siswa SET saldo = saldo - ? WHERE id = ?");
        } else {
            $stmtPotong = $db->prepare("UPDATE guru SET saldo = saldo - ? WHERE id = ?");
        }
        $stmtPotong->execute([$nominal_transaksi, $pelanggan_id]);

        // 4.5 Tambahkan Saldo ke Kantin yang di-scan (Patch kolom saldo jika belum ada)
        try { 
            $db->query("SELECT saldo FROM kantin LIMIT 1"); 
        } catch (Exception $e) { 
            $db->exec("ALTER TABLE kantin ADD COLUMN saldo DECIMAL(15,2) DEFAULT 0"); 
        }
        $stmtTambah = $db->prepare("UPDATE kantin SET saldo = saldo + ? WHERE id = ?");
        $stmtTambah->execute([$nominal_transaksi, $kantin_id]);

        // 5. Catat transaksi di tabel transaksi
        $message = "Makan di Kantin $nama_kantin ($nama_menu)";
        if ($role_pelanggan == 'siswa') {
            $stmtTrans = $db->prepare("
                INSERT INTO transaksi_siswa (siswa_id, kantin_id, type, category, nominal, message) 
                VALUES (?, ?, 'keluar', 'Makan', ?, ?)
            ");
        } else {
            $stmtTrans = $db->prepare("
                INSERT INTO transaksi_guru (guru_id, kantin_id, type, category, nominal, message) 
                VALUES (?, ?, 'keluar', 'Makan', ?, ?)
            ");
        }
        $stmtTrans->execute([$pelanggan_id, $kantin_id, $nominal_transaksi, $message]);

        // 6. Catat pengambilan ke tabel pengambilan_mbg (Untuk statistik konsumsi harian)
        if ($role_pelanggan == 'siswa') {
            try {
                $stmtMbg = $db->prepare("
                    INSERT INTO siswa_pengambilan_mbg (siswa_id, sekolah_id, tanggal) 
                    VALUES (?, ?, NOW())
                ");
                $stmtMbg->execute([$pelanggan_id, $pelanggan['sekolah_id']]);
            } catch (Exception $e_mbg) {
                error_log("Error inserting to siswa_pengambilan_mbg: " . $e_mbg->getMessage());
            }
        } else {
            try {
                $stmtMbg = $db->prepare("
                    INSERT INTO guru_pengambilan_mbg (guru_id, sekolah_id, tanggal) 
                    VALUES (?, ?, NOW())
                ");
                $stmtMbg->execute([$pelanggan_id, $pelanggan['sekolah_id']]);
            } catch (Exception $e_mbg) {
                error_log("Error inserting to guru_pengambilan_mbg: " . $e_mbg->getMessage());
            }
        }

        $db->commit();

        $jadwal_id = $db->lastInsertId(); // Dapatkan ID jadwal/mbg (kalau dibutuhkan)

        echo json_encode([
            "status" => "success",
            "message" => "Transaksi berhasil diproses.",
            "student_name" => $nama_siswa,
            "deducted_points" => $nominal_transaksi,
            "menu_detail" => $menu_makanan,
            "jadwal_id" => $jadwal_id
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
