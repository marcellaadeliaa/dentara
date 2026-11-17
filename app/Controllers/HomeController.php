<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class HomeController extends Controller {

    public function index(): void {
        // Ambil dokter aktif
        $doctors = Database::fetchAll("
            SELECT d.*, u.full_name, u.email, u.phone 
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.is_active = 1
        ");

        // Ambil promo aktif
        $promotions = Database::fetchAll("
            SELECT * FROM promotions 
            WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date 
            ORDER BY id DESC LIMIT 3
        ");

        // Ambil kategori tindakan favorit
        $treatments = Database::fetchAll("
            SELECT * FROM treatments 
            WHERE is_active = 1 
            ORDER BY price ASC LIMIT 6
        ");

        // Antrean hari ini yang sedang dilayani
        $todayCalling = Database::fetchAll("
            SELECT q.*, d.room_name, u.full_name as doctor_name
            FROM queues q
            JOIN doctors d ON q.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            WHERE q.queue_date = CURDATE() AND q.status = 'calling'
            ORDER BY q.called_at DESC
        ");

        $this->render('public/index', [
            'title'        => 'Klinik Gigi Modern & Terpercaya',
            'doctors'      => $doctors,
            'promotions'   => $promotions,
            'treatments'   => $treatments,
            'todayCalling' => $todayCalling
        ]);
    }

    public function services(): void {
        $treatments = Database::fetchAll("
            SELECT * FROM treatments 
            WHERE is_active = 1 
            ORDER BY category ASC, price ASC
        ");

        // Group by category
        $categorized = [];
        foreach ($treatments as $t) {
            $categorized[$t['category']][] = $t;
        }

        $this->render('public/services', [
            'title'       => 'Daftar Layanan & Tarif Gigi Transparan',
            'categorized' => $categorized
        ]);
    }

    public function doctors(): void {
        $doctors = Database::fetchAll("
            SELECT d.*, u.full_name, u.email, u.phone 
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.is_active = 1
        ");

        // Jadwal dokter
        $schedules = Database::fetchAll("
            SELECT s.*, d.user_id, u.full_name as doctor_name
            FROM doctor_schedules s
            JOIN doctors d ON s.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            WHERE s.is_active = 1
            ORDER BY FIELD(s.day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), s.start_time ASC
        ");

        $this->render('public/doctors', [
            'title'     => 'Profil & Jadwal Dokter Gigi',
            'doctors'   => $doctors,
            'schedules' => $schedules
        ]);
    }

    public function faq(): void {
        $faqs = Database::fetchAll("
            SELECT * FROM faqs 
            WHERE is_active = 1 
            ORDER BY sort_order ASC
        ");

        $this->render('public/faq', [
            'title' => 'Tanya Jawab (FAQ)',
            'faqs'  => $faqs
        ]);
    }
}
