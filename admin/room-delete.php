<?php
require_once '../includes/functions.php';

requireAdmin();

// Get hotel_id from URL and validate
$hotel = requireHotelParam();
$hotel_id = $hotel['id_hotel'];

// Get room_id
$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($room_id <= 0) {
    $_SESSION['error_message'] = 'Kamar tidak ditemukan!';
    header('Location: ' . adminURL('rooms.php', $hotel_id));
    exit();
}

$conn = getConnection();

// Get room details - VERIFY belongs to selected hotel
$stmt = $conn->prepare("
    SELECT k.*, h.nama_hotel 
    FROM kamar k 
    JOIN hotel h ON k.id_hotel = h.id_hotel 
    WHERE k.id_kamar = ? AND k.id_hotel = ?
");
$stmt->bind_param("ii", $room_id, $hotel_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    $_SESSION['error_message'] = 'Kamar tidak ditemukan atau bukan milik hotel ini!';
    header('Location: ' . adminURL('rooms.php', $hotel_id));
    exit();
}

// Check if room has active bookings
$check = $conn->prepare("SELECT COUNT(*) as count FROM pemesanan WHERE id_kamar = ? AND status_pesanan IN ('Pending', 'Confirmed')");
$check->bind_param("i", $room_id);
$check->execute();
$has_bookings = $check->get_result()->fetch_assoc()['count'] > 0;

if ($has_bookings) {
    $_SESSION['error_message'] = 'Tidak dapat menghapus kamar dengan booking aktif!';
    header('Location: ' . adminURL('rooms.php', $hotel_id, ['error' => 'active_booking']));
    exit();
}

// Delete room photo if exists
if (!empty($room['foto_kamar'])) {
    deleteFile($room['foto_kamar']);
}

// Delete room
$stmt = $conn->prepare("DELETE FROM kamar WHERE id_kamar = ? AND id_hotel = ?");
$stmt->bind_param("ii", $room_id, $hotel_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Kamar #' . $room['nomor_kamar'] . ' berhasil dihapus!';
    header('Location: ' . adminURL('rooms.php', $hotel_id, ['deleted' => '1']));
} else {
    $_SESSION['error_message'] = 'Gagal menghapus kamar!';
    header('Location: ' . adminURL('rooms.php', $hotel_id, ['error' => 'delete_failed']));
}

$conn->close();
exit();
?>
