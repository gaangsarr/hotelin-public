<?php
session_start();

// Include database & constants
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// Clean input
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Format Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php?redirect=login');
        exit();
    }
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'Admin';
}

// Require admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

// Check if user is regular user
function isUser() {
    return isLoggedIn() && $_SESSION['role'] === 'User';
}

// ============================================
// HOTEL FUNCTIONS - URL PARAMETER BASED
// ============================================

/**
 * Get hotel ID from URL parameter
 * @return int Hotel ID or 0 if not set
 */
function getHotelIdFromURL() {
    return isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
}

/**
 * Get hotel details by ID
 * @param int $hotel_id
 * @return array|null Hotel data or null if not found
 */
function getHotelById($hotel_id) {
    if ($hotel_id <= 0) return null;
    
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT h.*, k.nama_kota, k.id_kota, p.nama_provinsi 
        FROM hotel h 
        JOIN kota k ON h.id_kota = k.id_kota 
        JOIN provinsi p ON k.id_provinsi = p.id_provinsi 
        WHERE h.id_hotel = ?
    ");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $conn->close();
    
    return $result;
}

/**
 * Get all hotels for super admin
 * @return array List of all hotels
 */
function getAllHotels() {
    $conn = getConnection();
    $result = $conn->query("
        SELECT h.*, k.nama_kota, p.nama_provinsi 
        FROM hotel h 
        JOIN kota k ON h.id_kota = k.id_kota 
        JOIN provinsi p ON k.id_provinsi = p.id_provinsi 
        ORDER BY h.nama_hotel
    ");
    $hotels = [];
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
    $conn->close();
    return $hotels;
}

/**
 * Require hotel_id parameter in URL (for admin pages)
 * Redirects to hotel selection if missing
 */
function requireHotelParam() {
    $hotel_id = getHotelIdFromURL();
    if ($hotel_id <= 0) {
        $_SESSION['error_message'] = 'Silakan pilih hotel terlebih dahulu!';
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit();
    }
    
    // Verify hotel exists
    $hotel = getHotelById($hotel_id);
    if (!$hotel) {
        $_SESSION['error_message'] = 'Hotel tidak ditemukan!';
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit();
    }
    
    return $hotel;
}

/**
 * Build admin URL with hotel_id parameter
 * @param string $page Page name (e.g., 'rooms.php')
 * @param int $hotel_id Hotel ID
 * @param array $extra_params Additional query parameters
 * @return string Full URL
 */
function adminURL($page, $hotel_id, $extra_params = []) {
    $params = array_merge(['hotel_id' => $hotel_id], $extra_params);
    $query = http_build_query($params);
    return BASE_URL . '/admin/' . $page . '?' . $query;
}

// Upload file helper
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    $targetDir = __DIR__ . '/../' . $targetDir;
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if file is actual image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not an image.'];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5000000) {
        return ['success' => false, 'message' => 'File is too large. Max 5MB.'];
    }
    
    // Check file extension
    if (!in_array($imageFileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type.'];
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'filename' => str_replace(__DIR__ . '/../', '', $targetFile)];
    } else {
        return ['success' => false, 'message' => 'Upload failed.'];
    }
}

// Delete file helper
function deleteFile($filePath) {
    $fullPath = __DIR__ . '/../' . $filePath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
        return true;
    }
    return false;
}

// Check room availability
function isRoomAvailable($kamar_id, $checkin, $checkout, $exclude_booking_id = null) {
    $conn = getConnection();
    
    $sql = "SELECT COUNT(*) as count FROM pemesanan 
            WHERE id_kamar = ? 
            AND status_pesanan IN ('Pending', 'Confirmed')
            AND (
                (tanggal_checkin <= ? AND tanggal_checkout > ?) OR
                (tanggal_checkin < ? AND tanggal_checkout >= ?) OR
                (tanggal_checkin >= ? AND tanggal_checkout <= ?)
            )";
    
    if ($exclude_booking_id) {
        $sql .= " AND id_pemesanan != ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($exclude_booking_id) {
        $stmt->bind_param("isssssssi", $kamar_id, $checkin, $checkin, $checkout, $checkout, $checkin, $checkout, $exclude_booking_id);
    } else {
        $stmt->bind_param("issssss", $kamar_id, $checkin, $checkin, $checkout, $checkout, $checkin, $checkout);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $conn->close();
    
    return $result['count'] == 0;
}

// Get user info
function getUserInfo($user_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $conn->close();
    return $result;
}

// ========================================
// FASILITAS FUNCTIONS (Normalized Version)
// ========================================

/**
 * Get all available facilities from master table
 * @return array List of all facilities
 */
function getAllFasilitas() {
    $conn = getConnection();
    $result = $conn->query("
        SELECT * FROM fasilitas 
        ORDER BY 
            CASE kategori
                WHEN 'Essential' THEN 1
                WHEN 'Premium' THEN 2
                WHEN 'Luxury' THEN 3
            END,
            nama_fasilitas
    ");
    
    $fasilitas = [];
    while ($row = $result->fetch_assoc()) {
        $fasilitas[] = $row;
    }
    $conn->close();
    return $fasilitas;
}

/**
 * Get facilities for specific hotel
 * @param int $hotel_id Hotel ID
 * @return array List of facilities with details
 */
function getHotelFasilitas($hotel_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT f.id_fasilitas, f.nama_fasilitas, f.icon, f.kategori
        FROM fasilitas f
        JOIN hotel_fasilitas hf ON f.id_fasilitas = hf.id_fasilitas
        WHERE hf.id_hotel = ?
        ORDER BY 
            CASE f.kategori
                WHEN 'Essential' THEN 1
                WHEN 'Premium' THEN 2
                WHEN 'Luxury' THEN 3
            END,
            f.nama_fasilitas
    ");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $fasilitas = [];
    while ($row = $result->fetch_assoc()) {
        $fasilitas[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    return $fasilitas;
}

/**
 * Get facility IDs for hotel (for form checkbox pre-selection)
 * @param int $hotel_id Hotel ID
 * @return array Array of facility IDs
 */
function getHotelFasilitasIds($hotel_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT id_fasilitas FROM hotel_fasilitas WHERE id_hotel = ?
    ");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id_fasilitas'];
    }
    
    $stmt->close();
    $conn->close();
    return $ids;
}

/**
 * Update hotel facilities (delete old, insert new)
 * @param int $hotel_id Hotel ID
 * @param array $fasilitas_ids Array of facility IDs
 * @return bool Success status
 */
function updateHotelFasilitas($hotel_id, $fasilitas_ids) {
    $conn = getConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete existing facilities
        $stmt = $conn->prepare("DELETE FROM hotel_fasilitas WHERE id_hotel = ?");
        $stmt->bind_param("i", $hotel_id);
        $stmt->execute();
        
        // Insert new facilities
        if (!empty($fasilitas_ids)) {
            $stmt = $conn->prepare("INSERT INTO hotel_fasilitas (id_hotel, id_fasilitas) VALUES (?, ?)");
            foreach ($fasilitas_ids as $id_fasilitas) {
                $stmt->bind_param("ii", $hotel_id, $id_fasilitas);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        $stmt->close();
        $conn->close();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        return false;
    }
}

/**
 * Display facility badges (HTML output)
 * @param int $hotel_id Hotel ID
 */
function displayFasilitasBadges($hotel_id) {
    $fasilitas = getHotelFasilitas($hotel_id);
    
    if (empty($fasilitas)) {
        echo '<span class="text-muted small"><i class="bi bi-x-circle"></i> Tidak ada fasilitas</span>';
        return;
    }
    
    foreach ($fasilitas as $f) {
        $badge_class = match($f['kategori']) {
            'Essential' => 'bg-primary',
            'Premium' => 'bg-success',
            'Luxury' => 'bg-warning text-dark',
            default => 'bg-secondary'
        };
        
        echo '<span class="badge ' . $badge_class . ' me-1 mb-1">';
        echo '<i class="' . htmlspecialchars($f['icon']) . '"></i> ';
        echo htmlspecialchars($f['nama_fasilitas']);
        echo '</span> ';
    }
}

?>
