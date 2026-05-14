<?php
// api.php - Final Fixed Version

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nim'])) {
    $nim = $_POST['nim'];

    $ch = curl_init();

    // Endpoint yang benar berdasarkan investigasi
    curl_setopt($ch, CURLOPT_URL, "https://pusatbahasa.trunojoyo.ac.id/ss");
    curl_setopt($ch, CURLOPT_POST, true);
    
    // PERBAIKAN: Menggunakan parameter 'idpeserta' bukan 'nim'
    curl_setopt($ch, CURLOPT_POSTFIELDS, "idpeserta=" . $nim);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Requested-With: XMLHttpRequest",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Content-Type: application/x-www-form-urlencoded"
    ]);

    // SSL Verification Bypass (Penting untuk localhost XAMPP)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error_msg = curl_error($ch);
    curl_close($ch);

    if ($error_msg) {
        http_response_code(500);
        echo json_encode([
            'success' => 'no', 
            'message' => 'Koneksi ke server UTM gagal', 
            'debug' => 'CURL Error: ' . $error_msg
        ]);
        exit;
    }

    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => 'no', 
            'message' => 'Server UTM tidak merespon dengan benar',
            'debug' => 'HTTP Code: ' . $httpCode
        ]);
        exit;
    }

    // Bersihkan response jika ada whitespace yang merusak JSON
    $clean_response = trim($response);

    // Cek apakah response valid JSON
    $json_test = json_decode($clean_response);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo json_encode([
            'success' => 'no', 
            'message' => 'Format data tidak dikenali',
            'debug' => 'Raw Body: ' . substr($clean_response, 0, 200)
        ]);
        exit;
    }

    // Kembalikan response asli dari UTM
    echo $clean_response;

} else {
    http_response_code(400);
    echo json_encode(['success' => 'no', 'message' => 'NIM/ID Peserta tidak ditemukan']);
}
