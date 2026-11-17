<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class AdminDashboardController extends Controller {

    public function index(): void {
        $today = date('Y-m-d');

        // Statistik Utama
        $totalPatients = Database::fetchOne("SELECT COUNT(*) as c FROM patients")['c'] ?? 0;
        $todayQueues = Database::fetchOne("SELECT COUNT(*) as c FROM queues WHERE queue_date = :td", [':td' => $today])['c'] ?? 0;
        $totalRevenueToday = Database::fetchOne("
            SELECT SUM(grand_total) as s FROM billings WHERE payment_status = 'paid' AND DATE(paid_at) = :td
        ", [':td' => $today])['s'] ?? 0;
        $pendingDoctorFees = Database::fetchOne("
            SELECT SUM(net_fee_amount) as s FROM doctor_fees WHERE status = 'unpaid'
        ")['s'] ?? 0;

        // Obat dengan stok kritis / di bawah min_stock
        $lowStockMeds = Database::fetchAll("
            SELECT name, unit, current_stock, min_stock 
            FROM medicines 
            WHERE current_stock <= min_stock AND is_active = 1
            ORDER BY current_stock ASC LIMIT 5
        ");

        // Riwayat Transaksi Kasir Terakhir
        $recentBillings = Database::fetchAll("
            SELECT b.*, p.full_name as patient_name, u.full_name as cashier_name
            FROM billings b
            JOIN patients p ON b.patient_id = p.id
            LEFT JOIN users u ON b.cashier_id = u.id
            ORDER BY b.id DESC LIMIT 5
        ");

        $this->render('admin/dashboard', [
            'title'             => 'Ringkasan Eksekutif & Manajemen',
            'totalPatients'     => $totalPatients,
            'todayQueues'       => $todayQueues,
            'totalRevenueToday' => $totalRevenueToday,
            'pendingDoctorFees' => $pendingDoctorFees,
            'lowStockMeds'      => $lowStockMeds,
            'recentBillings'    => $recentBillings
        ], 'layouts/admin');
    }
}
