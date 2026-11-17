<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class QueueTrackerController extends Controller {

    public function index(): void {
        $today = date('Y-m-d');

        // Ambil dokter yang praktik hari ini
        $doctors = Database::fetchAll("
            SELECT d.id, d.room_name, d.specialization, u.full_name as doctor_name
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.is_active = 1
        ");

        $this->render('public/queue', [
            'title'   => 'Live Queue Tracker - Pantau Antrean Pasien',
            'today'   => $today,
            'doctors' => $doctors
        ]);
    }

    public function apiLiveStatus(): void {
        $today = date('Y-m-d');

        // Query dokter & status antrean terkini
        $doctors = Database::fetchAll("
            SELECT d.id as doctor_id, d.room_name, d.specialization, u.full_name as doctor_name
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.is_active = 1
        ");

        $result = [];

        foreach ($doctors as $doc) {
            $docId = $doc['doctor_id'];

            // Antrean yang sedang dipanggil / di dalam ruangan
            $currentCalling = Database::fetchOne("
                SELECT queue_number, status, called_at
                FROM queues
                WHERE queue_date = :td AND doctor_id = :did AND status IN ('calling', 'in_consultation')
                ORDER BY FIELD(status, 'calling', 'in_consultation'), called_at DESC
                LIMIT 1
            ", [':td' => $today, ':did' => $docId]);

            // Antrean berikutnya (waiting)
            $nextQueue = Database::fetchOne("
                SELECT queue_number
                FROM queues
                WHERE queue_date = :td AND doctor_id = :did AND status = 'waiting'
                ORDER BY id ASC
                LIMIT 1
            ", [':td' => $today, ':did' => $docId]);

            // Total antrean hari ini
            $totalToday = Database::fetchOne("
                SELECT COUNT(*) as cnt FROM queues WHERE queue_date = :td AND doctor_id = :did AND status != 'cancelled'
            ", [':td' => $today, ':did' => $docId])['cnt'] ?? 0;

            // Total antrean menunggu
            $waitingCount = Database::fetchOne("
                SELECT COUNT(*) as cnt FROM queues WHERE queue_date = :td AND doctor_id = :did AND status = 'waiting'
            ", [':td' => $today, ':did' => $docId])['cnt'] ?? 0;

            // Total selesai
            $completedCount = Database::fetchOne("
                SELECT COUNT(*) as cnt FROM queues WHERE queue_date = :td AND doctor_id = :did AND status IN ('completed', 'cashier', 'pharmacy')
            ", [':td' => $today, ':did' => $docId])['cnt'] ?? 0;

            $result[] = [
                'doctor_id'      => $docId,
                'doctor_name'    => $doc['doctor_name'],
                'room_name'      => $doc['room_name'],
                'specialization' => $doc['specialization'],
                'current_number' => $currentCalling['queue_number'] ?? '-',
                'current_status' => $currentCalling['status'] ?? 'Standby',
                'next_number'    => $nextQueue['queue_number'] ?? '-',
                'total_today'    => (int)$totalToday,
                'waiting_count'  => (int)$waitingCount,
                'completed_count'=> (int)$completedCount,
            ];
        }

        $this->json([
            'status'     => 'success',
            'server_time'=> date('H:i:s') . ' WIB',
            'date_formatted' => formatDateIndo($today),
            'data'       => $result
        ]);
    }

    public function apiSearchQueue(): void {
        $today = date('Y-m-d');
        $query = trim($_GET['q'] ?? '');

        if (empty($query)) {
            $this->json(['status' => 'error', 'message' => 'Masukkan nomor antrean atau kode booking.'], 400);
        }

        // Cari berdasarkan kode booking atau nomor antrean
        $sql = "
            SELECT q.queue_number, q.status, q.queue_date, d.room_name, u.full_name as doctor_name,
                   p.full_name as patient_name, a.booking_code
            FROM queues q
            JOIN doctors d ON q.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            JOIN patients p ON q.patient_id = p.id
            LEFT JOIN appointments a ON q.appointment_id = a.id
            WHERE (q.queue_number = :q OR a.booking_code = :q)
            ORDER BY q.id DESC
            LIMIT 1
        ";
        $data = Database::fetchOne($sql, [':q' => $query]);

        if (!$data) {
            $this->json(['status' => 'not_found', 'message' => 'Nomor antrean atau kode booking tidak ditemukan.'], 404);
        }

        // Hitung berapa orang lagi di depannya jika masih 'waiting'
        $peopleAhead = 0;
        if ($data['status'] === 'waiting') {
            $aheadCount = Database::fetchOne("
                SELECT COUNT(*) as cnt FROM queues 
                WHERE queue_date = :qd AND doctor_id = (SELECT doctor_id FROM queues WHERE queue_number = :qn AND queue_date = :qd LIMIT 1)
                  AND status = 'waiting' AND id < (SELECT id FROM queues WHERE queue_number = :qn AND queue_date = :qd LIMIT 1)
            ", [':qd' => $data['queue_date'], ':qn' => $data['queue_number']])['cnt'] ?? 0;
            $peopleAhead = (int)$aheadCount;
        }

        $this->json([
            'status'       => 'found',
            'data'         => $data,
            'people_ahead' => $peopleAhead,
            'badge'        => getQueueBadge($data['status'])
        ]);
    }
}
