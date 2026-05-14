<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Skor TEAP - Universitas Trunojoyo Madura</title>
    <meta name="description" content="Portal Pengecekan Skor TEAP (Test of English Academic Proficiency) Mahasiswa Universitas Trunojoyo Madura (UTM).">
    <link rel="stylesheet" href="style.css">
    <!-- Ionicons for icons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <!-- Chart.js for visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- AI Interpretation Module -->
    <script type="module" src="intrepetation.js"></script>
</head>
<body>
    <div id="loadingOverlay">
        <div class="panda-container">
            <img src="panda.jpg" alt="Panda Sakera">
        </div>
        <div class="loading-text">Sabar bro lagi proses<span class="loading-dots"></span></div>
    </div>

    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <!-- No Data / Error Card Overlay -->
    <div id="noDataOverlay" class="no-data-overlay" style="display: none;">
        <div class="no-data-card">
            <button class="close-card" onclick="closeNoData()">&times;</button>
            <img src="panda_nodata.jpg" alt="No Data Panda">
            <h3>Data Tidak Ditemukan</h3>
            <p id="noDataMsg">Coba periksa kembali NIM yang Anda masukkan bray.</p>
            <button class="retry-btn" onclick="closeNoData()">Oke, Gue Cek Lagi</button>
        </div>
    </div>

    <nav>
        <div class="logo">
            <div class="title">TEAP UTM</div>
            <div class="subtitle">Next-Gen Academic Interface x AI Interpretation</div>
            <div class="credit">Developed by Martin Harahap</div>
        </div>
    </nav>

    <main>
        <div class="hero-content">
            <h1>Check Your <span>TEAP Score</span></h1>
            <p class="subtitle">
                Akses hasil tes TEAP Anda secara instan dan dapatkan interpretasi berbasis AI 
                untuk memahami kemampuan bahasa Inggris Anda saat ini.
                <br>
                Masukkan NIM untuk melihat skor TEAP Anda.
            </p>
        </div>

        <div class="search-container">
            <form id="checkForm">
                <div class="input-group">
                    <label for="nim">NOMOR INDUK MAHASISWA (NIM)</label>
                    <input type="text" id="nim" name="nim" placeholder="Masukkan NIM Anda..." required autocomplete="off">
                    <button type="submit" id="submitBtn">
                        <span>Cek Skor Sekarang</span>
                        <ion-icon name="search-outline"></ion-icon>
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Dashboard (Hidden Initially) -->
        <div id="resultsDashboard" style="display: none; width: 100%; max-width: 1200px; animation: fadeInUp 0.6s ease;">
            <div class="dashboard-top">
                <button class="back-btn" onclick="backToSearch()">
                    <ion-icon name="arrow-back-outline"></ion-icon> Cari NIM Lain
                </button>
                <div class="student-profile">
                    <h2 id="displayNama">Mahasiswa</h2>
                    <p id="displayNim">NIM: -</p>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Left: Analytics & Chart -->
                <div class="grid-left">
                    <div class="summary-card">
                        <div class="chart-header">
                            <h3>Score Progression Analytics</h3>
                            <p>Tren pencapaian skor berdasarkan riwayat ujian</p>
                        </div>
                        <div class="chart-container">
                            <canvas id="scoreChart"></canvas>
                        </div>
                        <div class="summary-actions">
                            <button class="ai-interpret-btn global" onclick="interpretAllAI(window.currentResults)">
                                Interpretasikan Seluruh Pencapaian
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Best Score Highlight -->
                <div class="grid-right">
                    <div id="bestScorePlaceholder"></div>
                </div>
            </div>

            <div class="history-section">
                <div class="section-header">
                    <h3>Riwayat Tes Terdaftar</h3>
                    <span>Diurutkan berdasarkan waktu ujian terbaru</span>
                </div>
                <div id="historyArea" class="horizontal-scroll"></div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Pusat Bahasa - Universitas Trunojoyo Madura. <br>Designed with precision for students excellence.</p>
    </footer>

    <script>
        window.currentResults = []; // Global state for AI

        function backToSearch() {
            document.getElementById('resultsDashboard').style.display = 'none';
            document.querySelector('.hero-content').style.display = 'block';
            document.querySelector('.search-container').style.display = 'block';
        }

        document.getElementById('checkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const nimInput = document.getElementById('nim').value;
            const loadingOverlay = document.getElementById('loadingOverlay');
            const dashboard = document.getElementById('resultsDashboard');
            const hero = document.querySelector('.hero-content');
            const search = document.querySelector('.search-container');
            
            loadingOverlay.style.display = 'flex';
            
            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'nim=' + encodeURIComponent(nimInput)
            })
            .then(async response => {
                const isJson = response.headers.get('content-type')?.includes('application/json');
                const data = isJson ? await response.json() : null;
                if (!response.ok) throw { message: data?.message || 'Server Error', debug: data?.debug };
                return data;
            })
            .then(data => {
                loadingOverlay.style.display = 'none';
                
                if (data.success === 'yes' && data.num > 0) {
                    hero.style.display = 'none';
                    search.style.display = 'none';
                    dashboard.style.display = 'block';

                    document.getElementById('displayNama').innerText = data.datax[0].nama_peserta || 'Mahasiswa';
                    document.getElementById('displayNim').innerText = 'NIM: ' + nimInput;
                    window.currentResults = data.datax; // Store for AI

                    // Sorting data by date for chart (oldest to newest)
                    const chronData = [...data.datax].sort((a, b) => new Date(a.tgl_ujian) - new Date(b.tgl_ujian));
                    renderChart(chronData);

                    // Sorting data by score for best highlight
                    const bestItem = [...data.datax].sort((a, b) => parseInt(b.score) - parseInt(a.score))[0];
                    renderBestScore(bestItem);

                    // Sorting history by date (oldest to newest) for the list
                    const historyData = [...data.datax].sort((a, b) => new Date(a.tgl_ujian) - new Date(b.tgl_ujian));
                    renderHistory(historyData);

                } else {
                    showNoData(data.message || 'NIM tersebut tidak terdaftar di database kami.');
                }
            })
            .catch(err => {
                loadingOverlay.style.display = 'none';
                showNoData(err.message || 'Gagal menghubungi server UTM bray.');
                console.error(err);
            });
        });

        function showNoData(msg) {
            document.getElementById('noDataMsg').innerText = msg;
            document.getElementById('noDataOverlay').style.display = 'flex';
        }

        function closeNoData() {
            document.getElementById('noDataOverlay').style.display = 'none';
        }

        function renderBestScore(item) {
            const container = document.getElementById('bestScorePlaceholder');
            container.innerHTML = `
                <div class="result-card highest-score best-highlight">
                    <div class="high-score-tag">The Best Achievement</div>
                    <div class="score-badge">${item.score}<span>Total Skor</span></div>
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <h4 style="color: #fff; font-size: 1.2rem;">${item.nama_peserta}</h4>
                        <p style="color: var(--text-muted); font-size: 0.75rem;">No. Tes: ${item.no_tes}</p>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">Tanggal <span>${item.tgl_ujian}</span></div>
                        <div class="info-item">Ruang <span>${item.nama_ruang}</span></div>
                    </div>
                    <div class="viz-container">
                        <div class="viz-item"><div class="viz-label"><span>Listening</span> <span>${item.listening}</span></div><div class="viz-bar-bg"><div class="viz-bar" style="width: ${(item.listening/68)*100}%"></div></div></div>
                        <div class="viz-item"><div class="viz-label"><span>Structure</span> <span>${item.structure}</span></div><div class="viz-bar-bg"><div class="viz-bar" style="width: ${(item.structure/68)*100}%"></div></div></div>
                        <div class="viz-item"><div class="viz-label"><span>Reading</span> <span>${item.reading}</span></div><div class="viz-bar-bg"><div class="viz-bar" style="width: ${(item.reading/67)*100}%"></div></div></div>
                    </div>
                    <button class="ai-interpret-btn" onclick="interpretWithAI('${item.no_tes}', ${item.score}, ${item.listening}, ${item.structure}, ${item.reading}, '${item.nama_peserta}', '${item.nama_ruang}')">
                        Interpretasikan Hasil Sesi Ini
                    </button>
                </div>
            `;
        }

        function renderHistory(data) {
            const container = document.getElementById('historyArea');
            container.innerHTML = data.map(item => `
                <div class="result-card mini">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                        <span style="font-size: 0.7rem; color: var(--text-muted);">${item.tgl_ujian}</span>
                        <span style="font-weight: 800; color: var(--primary-light); font-size: 1.1rem;">${item.score}</span>
                    </div>
                    <p style="font-size: 0.75rem; color: #fff; margin-bottom: 0.2rem;">No: ${item.no_tes}</p>
                    <p style="font-size: 0.65rem; color: var(--text-muted); margin-bottom: 0.8rem;">Ruang: ${item.nama_ruang}</p>
                    
                    <div class="mini-stats" style="display: flex; justify-content: space-between; font-size: 0.65rem; color: #a0a0a0; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.05); margin-bottom: 1rem;">
                        <span>L: ${item.listening}</span>
                        <span>S: ${item.structure}</span>
                        <span>R: ${item.reading}</span>
                    </div>

                    <button class="ai-interpret-btn" onclick="interpretWithAI('${item.no_tes}', ${item.score}, ${item.listening}, ${item.structure}, ${item.reading}, '${item.nama_peserta}', '${item.nama_ruang}')" style="margin-top: auto; padding: 0.5rem; font-size: 0.7rem;">
                        Analisis
                    </button>
                </div>
            `).join('');
        }

        let myChart = null;
        function renderChart(data) {
            const ctx = document.getElementById('scoreChart').getContext('2d');
            if (myChart) myChart.destroy();
            myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(i => i.tgl_ujian),
                    datasets: [{
                        label: 'Total Skor',
                        data: data.map(i => i.score),
                        borderColor: '#E31B23',
                        backgroundColor: 'rgba(227, 27, 35, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointRadius: 6,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0a0a0' } },
                        x: { ticks: { color: '#a0a0a0' } }
                    }
                }
            });
        }
    </script>
</body>
</html>