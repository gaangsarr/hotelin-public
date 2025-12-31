<?php
require_once 'includes/functions.php';

requireLogin(); // Must be logged in to book

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if ($room_id <= 0) {
    header('Location: index.php');
    exit();
}

$conn = getConnection();

// Get room and hotel details
$sql = "SELECT k.*, h.nama_hotel, h.alamat_lengkap
        FROM kamar k
        JOIN hotel h ON k.id_hotel = h.id_hotel
        WHERE k.id_kamar = ? AND k.status = 'Available'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkin = $_POST['tanggal_checkin'];
    $checkout = $_POST['tanggal_checkout'];
    $jumlah_tamu = (int)$_POST['jumlah_tamu'];
    $catatan = cleanInput($_POST['catatan']);
    
    // Validation
    if (strtotime($checkin) < strtotime(date('Y-m-d'))) {
        $error = 'Tanggal check-in tidak boleh di masa lalu!';
    } elseif (strtotime($checkout) <= strtotime($checkin)) {
        $error = 'Tanggal check-out harus setelah check-in!';
    } elseif ($jumlah_tamu > $room['kapasitas']) {
        $error = "Jumlah tamu melebihi kapasitas kamar (max {$room['kapasitas']} orang)!";
    } elseif (!isRoomAvailable($room_id, $checkin, $checkout)) {
        $error = 'Kamar tidak tersedia untuk tanggal yang dipilih!';
    } else {
        // Calculate total
        $total_malam = (strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24);
        $total_harga = $total_malam * $room['harga_malam'];
        
        // Insert booking
        $user_id = $_SESSION['user_id'];
        $sql = "INSERT INTO pemesanan (id_pengguna, id_kamar, jumlah_tamu, tanggal_checkin, tanggal_checkout, total_harga, status_pesanan, catatan) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiissds", $user_id, $room_id, $jumlah_tamu, $checkin, $checkout, $total_harga, $catatan);
        
        if ($stmt->execute()) {
            $booking_id = $conn->insert_id;
            
            // Update room status
            $conn->query("UPDATE kamar SET status = 'Booked' WHERE id_kamar = $room_id");
            
            // Redirect to payment
            header("Location: payment.php?booking_id=$booking_id");
            exit();
        } else {
            $error = 'Booking gagal! Coba lagi.';
        }
    }
}

$page_title = 'Booking Kamar';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Form Booking</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Tanggal Check-in <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_checkin" class="form-control" required 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo isset($_POST['tanggal_checkin']) ? $_POST['tanggal_checkin'] : date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tanggal Check-out <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_checkout" class="form-control" required 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   value="<?php echo isset($_POST['tanggal_checkout']) ? $_POST['tanggal_checkout'] : date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Jumlah Tamu <span class="text-danger">*</span></label>
                            <input type="number" name="jumlah_tamu" class="form-control" required 
                                   min="1" max="<?php echo $room['kapasitas']; ?>" value="1">
                            <small class="text-muted">Maksimal <?php echo $room['kapasitas']; ?> orang</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Catatan Khusus</label>
                            <textarea name="catatan" class="form-control" rows="3" 
                                      placeholder="Contoh: Minta lantai atas, non-smoking, dll"><?php echo isset($_POST['catatan']) ? htmlspecialchars($_POST['catatan']) : ''; ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="hotel-detail.php?id=<?php echo $room['id_hotel']; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Lanjut ke Pembayaran <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Booking Summary -->
            <div class="card shadow sticky-top" style="top: 20px;">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Ringkasan Booking</h6>
                </div>
                <div class="card-body">
                    <h6 class="mb-2"><?php echo $room['nama_hotel']; ?></h6>
                    <p class="small text-muted mb-3"><?php echo $room['alamat_lengkap']; ?></p>
                    
                    <hr>
                    
                    <h6 class="mb-2"><?php echo $room['tipe_kamar']; ?></h6>
                    <p class="small text-muted mb-1">Kamar #<?php echo $room['nomor_kamar']; ?></p>
                    <p class="small mb-3">
                        <i class="bi bi-people"></i> Kapasitas: <?php echo $room['kapasitas']; ?> orang
                    </p>

                    <!-- Facilities Display in Booking Summary -->
                    <div class="mb-3">
                        <strong><i class="bi bi-check2-square"></i> Fasilitas Hotel:</strong><br>
                        <div class="mt-2">
                            <?php displayFasilitasBadges($room['id_hotel']); ?>
                        </div>
                    </div>

                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Harga per malam:</span>
                        <strong><?php echo formatRupiah($room['harga_malam']); ?></strong>
                    </div>
                    
                    <div class="alert alert-info mt-3 small">
                        <i class="bi bi-info-circle"></i> Total harga akan dihitung berdasarkan jumlah malam menginap.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
