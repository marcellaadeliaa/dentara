<div class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between pb-8 border-b border-slate-200 gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-100 text-teal-800 text-xs font-bold mb-2">
                    <span class="w-2 h-2 rounded-full bg-teal-600 animate-ping"></span>
                    Live AJAX Polling Aktif
                </div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Queue Tracker (Pemantau Antrean)</h1>
                <p class="text-xs text-slate-500 mt-1">Pantau nomor panggilan dokter secara langsung dari ponsel atau ruang tunggu</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="bg-white px-4 py-2.5 rounded-2xl border border-slate-200 text-right shadow-sm">
                    <span class="text-[10px] text-slate-400 font-semibold block uppercase tracking-wider">Terakhir Diperbarui</span>
                    <span id="last-updated" class="text-xs font-bold text-slate-800 font-mono"><?= date('H:i:s') ?> WIB</span>
                </div>
                <button onclick="fetchQueueStatus()" class="p-2.5 bg-white hover:bg-slate-100 border border-slate-200 rounded-2xl text-slate-600 shadow-sm transition" title="Segarkan Manual">
                    <svg id="refresh-icon" class="w-5 h-5 text-teal-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>

        <!-- Search Your Queue -->
        <div class="my-8 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 mb-1">Cek Status Antrean Anda</h3>
            <p class="text-xs text-slate-500 mb-4">Masukkan Nomor Antrean Anda (misal: A-001) atau Kode Booking (misal: BK-20261117-001)</p>
            
            <form id="search-queue-form" onsubmit="searchMyQueue(event)" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" id="queue-input" required placeholder="Contoh: A-001 atau BK-20261117-001"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm uppercase font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                </div>
                <button type="submit" class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl text-sm shadow-sm transition">
                    Cek Posisi
                </button>
            </form>

            <!-- Result Box -->
            <div id="search-result" class="hidden mt-4 p-4 rounded-2xl border text-xs"></div>
        </div>

        <!-- Realtime Doctor Queue Grid -->
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900">Antrean Per Ruangan Poli Hari Ini</h2>
            <span class="text-xs text-teal-600 font-semibold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Auto-sync setiap 6 detik
            </span>
        </div>

        <div id="queue-grid" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Will be dynamically populated by JS via AJAX polling -->
            <div class="p-8 text-center bg-white rounded-3xl border border-slate-200 text-slate-400 col-span-2">
                Memuat data antrean terkini...
            </div>
        </div>

    </div>
</div>

<script>
const API_STATUS_URL = '<?= url("api/queue/status") ?>';
const API_SEARCH_URL = '<?= url("api/queue/search") ?>';

async function fetchQueueStatus() {
    const refreshIcon = document.getElementById('refresh-icon');
    if (refreshIcon) refreshIcon.classList.add('animate-spin');

    try {
        const res = await fetch(API_STATUS_URL);
        const json = await res.json();

        if (json.status === 'success') {
            document.getElementById('last-updated').innerText = json.server_time;
            renderQueueCards(json.data);
        }
    } catch (err) {
        console.error('Error polling queue status:', err);
    } finally {
        if (refreshIcon) {
            setTimeout(() => refreshIcon.classList.remove('animate-spin'), 600);
        }
    }
}

function renderQueueCards(doctors) {
    const container = document.getElementById('queue-grid');
    if (!doctors || doctors.length === 0) {
        container.innerHTML = `
            <div class="col-span-2 bg-white rounded-3xl p-12 text-center border border-slate-200">
                <p class="text-slate-500 text-sm">Tidak ada dokter yang aktif praktik hari ini.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = doctors.map(doc => `
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm hover:shadow-md transition">
            <!-- Header Room & Doctor -->
            <div class="flex justify-between items-start pb-4 border-b border-slate-100">
                <div>
                    <span class="inline-block text-[11px] font-bold text-teal-700 bg-teal-50 px-2.5 py-0.5 rounded-full border border-teal-200">
                        ${escapeHtml(doc.room_name)}
                    </span>
                    <h3 class="text-base font-bold text-slate-900 mt-1.5">${escapeHtml(doc.doctor_name)}</h3>
                    <p class="text-xs text-slate-400 font-medium">${escapeHtml(doc.specialization)}</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Status Poli</span>
                    <span class="inline-flex items-center gap-1 text-xs font-bold ${doc.current_number !== '-' ? 'text-emerald-600' : 'text-slate-400'}">
                        <span class="w-2 h-2 rounded-full ${doc.current_number !== '-' ? 'bg-emerald-500 animate-ping' : 'bg-slate-300'}"></span>
                        ${doc.current_number !== '-' ? 'Melayani' : 'Standby'}
                    </span>
                </div>
            </div>

            <!-- Numbers Display -->
            <div class="grid grid-cols-2 gap-4 my-6">
                <!-- Now Serving -->
                <div class="bg-gradient-to-br from-teal-50 to-cyan-50 p-5 rounded-2xl border border-teal-200 text-center">
                    <span class="text-[10px] font-bold text-teal-800 uppercase tracking-wider block">Sedang Dilayani</span>
                    <span class="text-4xl sm:text-5xl font-black text-teal-700 tracking-tight my-1 block">
                        ${doc.current_number}
                    </span>
                    <span class="text-[10px] text-teal-600 font-semibold">${escapeHtml(doc.current_status)}</span>
                </div>

                <!-- Next in line -->
                <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 text-center">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Nomor Selanjutnya</span>
                    <span class="text-4xl sm:text-5xl font-black text-slate-700 tracking-tight my-1 block">
                        ${doc.next_number}
                    </span>
                    <span class="text-[10px] text-slate-400 font-semibold">Siap-siap</span>
                </div>
            </div>

            <!-- Mini Summary Footer -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Menunggu: <strong class="text-amber-600 font-bold">${doc.waiting_count}</strong></span>
                <span>Selesai: <strong class="text-emerald-600 font-bold">${doc.completed_count}</strong></span>
                <span>Total Hari Ini: <strong class="text-slate-800 font-bold">${doc.total_today}</strong></span>
            </div>
        </div>
    `).join('');
}

async function searchMyQueue(e) {
    e.preventDefault();
    const query = document.getElementById('queue-input').value.trim();
    const resultBox = document.getElementById('search-result');
    if (!query) return;

    resultBox.classList.remove('hidden');
    resultBox.className = 'mt-4 p-4 rounded-2xl border bg-slate-50 text-slate-600 text-xs';
    resultBox.innerHTML = 'Mencari informasi antrean...';

    try {
        const res = await fetch(`${API_SEARCH_URL}?q=${encodeURIComponent(query)}`);
        const json = await res.json();

        if (json.status === 'found') {
            const data = json.data;
            resultBox.className = 'mt-4 p-5 rounded-2xl border bg-teal-50/80 border-teal-200 text-slate-800 text-xs';
            resultBox.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <span class="font-bold text-sm text-teal-900">Hasil Pencarian Antrean</span>
                    ${json.badge}
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div><span class="text-slate-500">Nomor Antrean:</span> <strong class="text-teal-800 font-bold text-base block">${escapeHtml(data.queue_number)}</strong></div>
                    <div><span class="text-slate-500">Nama Pasien:</span> <strong class="text-slate-800 block">${escapeHtml(data.patient_name)}</strong></div>
                    <div><span class="text-slate-500">Dokter:</span> <span class="text-slate-700 block">${escapeHtml(data.doctor_name)}</span></div>
                    <div><span class="text-slate-500">Ruangan:</span> <span class="text-slate-700 block">${escapeHtml(data.room_name)}</span></div>
                </div>
                ${json.people_ahead > 0 ? `
                    <div class="mt-3 p-2.5 bg-amber-100 text-amber-900 rounded-xl font-medium">
                        Ada <strong>${json.people_ahead} pasien</strong> lagi di depan Anda. Harap bersiap di dekat ruang tunggu.
                    </div>
                ` : (data.status === 'calling' || data.status === 'in_consultation') ? `
                    <div class="mt-3 p-2.5 bg-emerald-100 text-emerald-900 rounded-xl font-bold animate-pulse">
                        Nomor Anda sedang dipanggil / berada di ruangan konsultasi!
                    </div>
                ` : ''}
            `;
        } else {
            resultBox.className = 'mt-4 p-4 rounded-2xl border bg-rose-50 border-rose-200 text-rose-800 text-xs';
            resultBox.innerHTML = json.message || 'Data antrean tidak ditemukan.';
        }
    } catch (err) {
        resultBox.className = 'mt-4 p-4 rounded-2xl border bg-rose-50 border-rose-200 text-rose-800 text-xs';
        resultBox.innerHTML = 'Terjadi kesalahan saat memproses pencarian.';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

// Initial fetch on page load
fetchQueueStatus();

// Polling interval: every 6 seconds
setInterval(fetchQueueStatus, 6000);
</script>
