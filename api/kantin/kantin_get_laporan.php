<?php
header("Content-Type: application/json");
include_once __DIR__ . '/../shared/config.php';

$kantin_id_input = $_GET['kantin_id'] ?? '';

if (empty($kantin_id_input)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Kantin ID diperlukan."]);
    exit;
}

try {
    // 1. Cari data Kantin berdasarkan user_id (tabel users) atau id (tabel kantin)
    $stmtKantin = $db->prepare("SELECT id, nama_kantin FROM kantin WHERE user_id = ? OR id = ? LIMIT 1");
    $stmtKantin->execute([$kantin_id_input, $kantin_id_input]);
    $kantin = $stmtKantin->fetch(PDO::FETCH_ASSOC);

    if (!$kantin) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Kantin tidak ditemukan."]);
        exit;
    }
    $kantin_id = $kantin['id'];
    
    // Get date range parameters
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    // 2. Hitung total PTS didapat
    $stmtTotalPts = $db->prepare("
        SELECT COALESCE(SUM(nominal), 0) as total FROM (
            SELECT nominal FROM transaksi_siswa WHERE kantin_id = ? AND type = 'keluar' AND DATE(created_at) BETWEEN ? AND ?
            UNION ALL
            SELECT nominal FROM transaksi_guru WHERE kantin_id = ? AND type = 'keluar' AND DATE(created_at) BETWEEN ? AND ?
        ) combined
    ");
    $stmtTotalPts->execute([$kantin_id, $start_date, $end_date, $kantin_id, $start_date, $end_date]);
    $total_pts = (float)$stmtTotalPts->fetch(PDO::FETCH_ASSOC)['total'];

    // 3. Hitung total transaksi
    $stmtTotalTrans = $db->prepare("
        SELECT COUNT(*) as total FROM (
            SELECT id FROM transaksi_siswa WHERE kantin_id = ? AND type = 'keluar' AND DATE(created_at) BETWEEN ? AND ?
            UNION ALL
            SELECT id FROM transaksi_guru WHERE kantin_id = ? AND type = 'keluar' AND DATE(created_at) BETWEEN ? AND ?
        ) combined
    ");
    $stmtTotalTrans->execute([$kantin_id, $start_date, $end_date, $kantin_id, $start_date, $end_date]);
    $total_transaksi = (int)$stmtTotalTrans->fetch(PDO::FETCH_ASSOC)['total'];

    // 4. Hitung rata-rata PTS/Siswa
    $stmtAvg = $db->prepare("
        SELECT COALESCE(AVG(nominal), 0) as total FROM (
            SELECT nominal FROM transaksi_siswa WHERE kantin_id = ? AND type = 'keluar' AND DATE(created_at) BETWEEN ? AND ?
            UNION ALL
            SELECT nominal FROM transaksi_guru WHERE kantin_id = ? AND type = 'keluar' AND DATE(created_at) BETWEEN ? AND ?
        ) combined
    ");
    $stmtAvg->execute([$kantin_id, $start_date, $end_date, $kantin_id, $start_date, $end_date]);
    $avg = round((float)$stmtAvg->fetch(PDO::FETCH_ASSOC)['total'], 1);

    // 5. Hitung transaksi minggu ini (7 hari terakhir dari end_date)
    $stmtWeek = $db->prepare("
        SELECT COUNT(*) as total FROM (
            SELECT id FROM transaksi_siswa WHERE kantin_id = ? AND type = 'keluar' AND DATE(created_at) BETWEEN DATE_SUB(?, INTERVAL 7 DAY) AND ?
            UNION ALL
            SELECT id FROM transaksi_guru WHERE kantin_id = ? AND type = 'keluar' AND DATE(created_at) BETWEEN DATE_SUB(?, INTERVAL 7 DAY) AND ?
        ) combined
    ");
    $stmtWeek->execute([$kantin_id, $end_date, $end_date, $kantin_id, $end_date, $end_date]);
    $transaksi_minggu_ini = (int)$stmtWeek->fetch(PDO::FETCH_ASSOC)['total'];

    // 6. Ambil riwayat transaksi terkini (30 transaksi terakhir dalam rentang tanggal)
    $stmtHistory = $db->prepare("
        SELECT * FROM (
            SELECT ts.id, ts.nominal as amount, ts.created_at, s.nama as student_name, s.kelas as sub_text, 'Siswa' as role
            FROM transaksi_siswa ts
            JOIN siswa s ON ts.siswa_id = s.id
            WHERE ts.kantin_id = ? AND ts.type = 'keluar' AND DATE(ts.created_at) BETWEEN ? AND ?
            UNION ALL
            SELECT tg.id, tg.nominal as amount, tg.created_at, g.nama as student_name, 'Guru / Staf' as sub_text, 'Guru' as role
            FROM transaksi_guru tg
            JOIN guru g ON tg.guru_id = g.id
            WHERE tg.kantin_id = ? AND tg.type = 'keluar' AND DATE(tg.created_at) BETWEEN ? AND ?
        ) combined
        ORDER BY created_at DESC
        LIMIT 30
    ");
    $stmtHistory->execute([$kantin_id, $start_date, $end_date, $kantin_id, $start_date, $end_date]);
    $rows = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    $riwayat = [];
    foreach ($rows as $row) {
        $prefix = $row['role'] === 'Guru' ? 'Guru: ' : 'Siswa: ';
        $riwayat[] = [
            "id" => $row['id'],
            "nama" => $prefix . ($row['student_name'] ?? 'Anonim'),
            "sub" => ($row['sub_text'] ?? '-') . " · " . date('d M Y H:i', strtotime($row['created_at'])),
            "amount" => (int)$row['amount']
        ];
    }

    echo json_encode([
        "status" => "success",
        "stats" => [
            "total_pts" => $total_pts,
            "total_transaksi" => $total_transaksi,
            "rata_rata" => $avg,
            "transaksi_minggu_ini" => $transaksi_minggu_ini
        ],
        "riwayat" => $riwayat
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
