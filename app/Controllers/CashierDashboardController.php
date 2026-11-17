<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class CashierDashboardController extends Controller {

    public function index(): void {
        $today = date('Y-m-d');

        // Tagihan belum lunas (unpaid)
        $unpaidBillings = Database::fetchAll("
            SELECT b.*, p.medical_record_number, p.full_name as patient_name, q.queue_number,
                   u.full_name as doctor_name
            FROM billings b
            JOIN patients p ON b.patient_id = p.id
            JOIN queues q ON b.queue_id = q.id
            JOIN doctors d ON q.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            WHERE b.payment_status = 'unpaid'
            ORDER BY b.id ASC
        ");

        // Riwayat tagihan lunas hari ini
        $paidToday = Database::fetchAll("
            SELECT b.*, p.medical_record_number, p.full_name as patient_name, q.queue_number,
                   u.full_name as cashier_name
            FROM billings b
            JOIN patients p ON b.patient_id = p.id
            JOIN queues q ON b.queue_id = q.id
            LEFT JOIN users u ON b.cashier_id = u.id
            WHERE b.payment_status = 'paid' AND DATE(b.paid_at) = :td
            ORDER BY b.paid_at DESC LIMIT 10
        ", [':td' => $today]);

        // Total penerimaan kasir hari ini
        $totalCollectedToday = Database::fetchOne("
            SELECT SUM(grand_total) as s FROM billings WHERE payment_status = 'paid' AND DATE(paid_at) = :td
        ", [':td' => $today])['s'] ?? 0;

        $this->render('cashier/dashboard', [
            'title'               => 'Kasir & Billing - Auto-Billing & Pembayaran',
            'unpaidBillings'      => $unpaidBillings,
            'paidToday'           => $paidToday,
            'totalCollectedToday' => $totalCollectedToday,
            'today'               => $today
        ], 'layouts/admin');
    }
}
