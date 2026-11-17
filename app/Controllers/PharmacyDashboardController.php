<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class PharmacyDashboardController extends Controller {

    public function index(): void {
        $today = date('Y-m-d');

        // Resep masuk hari ini (pending & processing)
        $pendingPrescriptions = Database::fetchAll("
            SELECT rx.*, p.medical_record_number, p.full_name as patient_name,
                   u.full_name as doctor_name, q.queue_number
            FROM prescriptions rx
            JOIN patients p ON rx.patient_id = p.id
            JOIN doctors d ON rx.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            JOIN queues q ON rx.queue_id = q.id
            WHERE rx.prescription_date = :td AND rx.status IN ('pending', 'processing')
            ORDER BY rx.id ASC
        ", [':td' => $today]);

        // Ringkasan stok obat hampir habis
        $lowStockMeds = Database::fetchAll("
            SELECT * FROM medicines 
            WHERE current_stock <= min_stock AND is_active = 1 
            ORDER BY current_stock ASC LIMIT 10
        ");

        $this->render('pharmacy/dashboard', [
            'title'                => 'Apotek & Farmasi - Pemrosesan Resep',
            'pendingPrescriptions' => $pendingPrescriptions,
            'lowStockMeds'         => $lowStockMeds,
            'today'                => $today
        ], 'layouts/admin');
    }
}
