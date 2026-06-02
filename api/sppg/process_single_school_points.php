<?php
include_once __DIR__ . '/../shared/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['sekolah_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'sekolah_id wajib diisi.']);
    exit();
}

$sekolahId = $data['sekolah_id'];
$currentMonth = date('m-Y');
$today = (int)date('d');

try {
    $db->beginTransaction();

    // Ambil data jadwal untuk sekolah ini saja
    $query = "SELECT s.id, s.nama_sekolah, sp.monthly_amount, sp.distribution_day, sp.last_distributed
              FROM sekolah s
              JOIN scheduled_points sp ON s.id = sp.sekolah_id
              WHERE s.id = :sekolah_id
              AND sp.status = 'active'";

    $stmt = $db->prepare($query);
    $stmt->execute([':sekolah_id' => $sekolahId]);
    $school = $stmt->fetch();

    if (!$school) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Jadwal poin untuk sekolah ini tidak ditemukan.']);
        exit();
    }

    // Cek apakah bulan ini sudah terdistribusi
    if ($school['last_distributed'] && date('m-Y', strtotime($school['last_distributed'])) === $currentMonth) {
        $db->rollBack();
        echo json_encode([
            'status' => 'already_distributed',
            'message' => 'Sekolah ' . $school['nama_sekolah'] . ' sudah menerima distribusi poin bulan ini.'
        ]);
        exit();
    }

    // Cek apakah tanggal distribusi sudah tiba
    if ((int)$school['distribution_day'] > $today) {
        $db->rollBack();
        echo json_encode([
            'status' => 'not_yet',
            'message' => 'Tanggal distribusi (Tgl ' . $school['distribution_day'] . ') belum tiba.'
        ]);
        exit();
    }

    $amount = $school['monthly_amount'];

    // 1. Tambah saldo sekolah
    $updateSekolah = "UPDATE sekolah SET saldo = saldo + ? WHERE id = ?";
    $db->prepare($updateSekolah)->execute([$amount, $sekolahId]);

    // 2. Catat transaksi
    $trx_id = 'AUTO-PTS-' . date('Ymd') . '-' . bin2hex(random_bytes(3));
    $insertTrx = "INSERT INTO transaksi_dana (id, sppg_id, sekolah_id, nominal, metode, status) 
                  VALUES (?, (SELECT id FROM sppg LIMIT 1), ?, ?, 'Auto-Monthly', 'Berhasil')";
    $db->prepare($insertTrx)->execute([$trx_id, $sekolahId, $amount]);

    // 3. Update tanggal terakhir distribusi
    $updateSchedule = "UPDATE scheduled_points SET last_distributed = CURDATE() WHERE sekolah_id = ?";
    $db->prepare($updateSchedule)->execute([$sekolahId]);

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Distribusi poin berhasil dikirim ke ' . $school['nama_sekolah'] . '.',
        'details' => [
            'sekolah' => $school['nama_sekolah'],
            'amount' => $amount,
            'month' => $currentMonth
        ]
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(200);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses distribusi: ' . $e->getMessage()]);
}
?>
