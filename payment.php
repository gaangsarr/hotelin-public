<?php
require_once 'includes/functions.php';

requireLogin();

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if ($booking_id <= 0) {
    header('Location: user/my-bookings.php');
    exit();
}

$conn = getConnection();

// Get booking details
$sql = "SELECT p.*, k.tipe_kamar, k.nomor_kamar, h.nama_hotel, h.alamat_lengkap,
        u.nama_lengkap, u.email, u.nomor_telepon
        FROM pemesanan p
        JOIN kamar k ON p.id_kamar = k.id_kamar
        JOIN hotel h ON k.id_hotel = h.id_hotel
        JOIN pengguna u ON p.id_pengguna = u.id_pengguna
        WHERE p.id_pemesanan = ? AND p.id_pengguna = ?";
$stmt = $conn->prepare($sql);
$user_id = $_SESSION['user_id'];
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: user/my-bookings.php');
    exit();
}

// Check if payment already exists
$payment_check = $conn->query("SELECT * FROM pembayaran WHERE id_pemesanan = $booking_id");
$existing_payment = $payment_check->fetch_assoc();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode_bayar = $_POST['metode_bayar'];
    $jumlah_bayar = $booking['total_harga']; // Full payment
    
    // Upload bukti bayar
    if (isset($_FILES['bukti_bayar']) && $_FILES['bukti_bayar']['error'] === 0) {
        $upload = uploadFile($_FILES['bukti_bayar'], UPLOAD_PAYMENT);
        
        if ($upload['success']) {
            // Insert payment
            $bukti_path = $upload['filename'];
            $sql = "INSERT INTO pembayaran (id_pemesanan, jumlah_bayar, metode_bayar, bukti_bayar, status_bayar) 
                    VALUES (?, ?, ?, ?, 'Pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("idss", $booking_id, $jumlah_bayar, $metode_bayar, $bukti_path);
            
            if ($stmt->execute()) {
                $success = 'Pembayaran berhasil diupload! Menunggu konfirmasi admin.';
                // Refresh payment data
                $existing_payment = $conn->query("SELECT * FROM pembayaran WHERE id_pemesanan = $booking_id")->fetch_assoc();
            } else {
                $error = 'Upload pembayaran gagal!';
            }
        } else {
            $error = $upload['message'];
        }
    } else {
        $error = 'Harap upload bukti pembayaran!';
    }
}

$page_title = 'Pembayaran';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Pembayaran</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($existing_payment): ?>
                        <!-- Payment Status -->
                        <div class="alert alert-<?php echo $existing_payment['status_bayar'] == 'Confirmed' ? 'success' : ($existing_payment['status_bayar'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                            <h6 class="mb-2">
                                <i class="bi bi-info-circle"></i> Status Pembayaran: 
                                <strong><?php echo $existing_payment['status_bayar']; ?></strong>
                            </h6>
                            
                            <?php if ($existing_payment['status_bayar'] == 'Pending'): ?>
                                <p class="mb-0 small">Pembayaran Anda sedang diverifikasi oleh admin. Harap tunggu konfirmasi.</p>
                            <?php elseif ($existing_payment['status_bayar'] == 'Confirmed'): ?>
                                <p class="mb-0 small">Pembayaran Anda telah dikonfirmasi! Booking berhasil.</p>
                            <?php elseif ($existing_payment['status_bayar'] == 'Rejected'): ?>
                                <p class="mb-2 small">Pembayaran ditolak. Silakan upload ulang bukti pembayaran yang valid.</p>
                                <?php if (!empty($existing_payment['catatan_admin'])): ?>
                                    <p class="mb-0 small"><strong>Catatan Admin:</strong> <?php echo $existing_payment['catatan_admin']; ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Payment Details -->
                        <div class="border rounded p-3 mb-3">
                            <h6>Detail Pembayaran</h6>
                            <p class="mb-1"><strong>Jumlah:</strong> <?php echo formatRupiah($existing_payment['jumlah_bayar']); ?></p>
                            <p class="mb-1"><strong>Metode:</strong> <?php echo $existing_payment['metode_bayar']; ?></p>
                            <p class="mb-1"><strong>Tanggal:</strong> <?php echo date('d M Y H:i', strtotime($existing_payment['tanggal_bayar'])); ?></p>
                            
                            <?php if (!empty($existing_payment['bukti_bayar'])): ?>
                                <p class="mb-1"><strong>Bukti Pembayaran:</strong></p>
                                <img src="<?php echo BASE_URL . '/' . $existing_payment['bukti_bayar']; ?>" 
                                     class="img-fluid rounded border" style="max-height: 300px;">
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($existing_payment['status_bayar'] == 'Rejected'): ?>
                            <a href="user/my-bookings.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left"></i> Kembali ke My Bookings
                            </a>
                        <?php else: ?>
                            <a href="user/my-bookings.php" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Lihat Booking Saya
                            </a>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <!-- Payment Form -->
                        <h6 class="mb-3">Informasi Transfer Bank</h6>
                        
                        <div class="alert alert-info">
                            <strong>Bank Transfer:</strong><br>
                            Bank BCA - 1234567890<br>
                            A/N HotelBooking Indonesia<br><br>
                            
                            <strong>E-Wallet:</strong><br>
                            OVO/GoPay: 081234567890<br>
                            A/N HotelBooking
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                                <select name="metode_bayar" class="form-select" required>
                                    <option value="">-- Pilih Metode --</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="E-Wallet">E-Wallet (OVO/GoPay/Dana)</option>
                                    <option value="Credit Card">Credit Card</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Upload Bukti Pembayaran <span class="text-danger">*</span></label>
                                <input type="file" name="bukti_bayar" class="form-control" accept="image/*" required>
                                <small class="text-muted">Format: JPG, PNG, GIF. Max 5MB</small>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> 
                                <strong>Penting:</strong> Upload bukti pembayaran yang jelas dan valid. 
                                Pembayaran akan diverifikasi oleh admin dalam 1x24 jam.
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="user/my-bookings.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Bayar Nanti
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload"></i> Upload Pembayaran
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Booking Summary -->
            <div class="card shadow sticky-top" style="top: 20px;">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Ringkasan Pesanan</h6>
                </div>
                <div class="card-body">
                    <h6 class="mb-2"><?php echo $booking['nama_hotel']; ?></h6>
                    <p class="small text-muted mb-3"><?php echo $booking['tipe_kamar']; ?> #<?php echo $booking['nomor_kamar']; ?></p>
                    
                    <hr>
                    
                    <p class="small mb-1">
                        <i class="bi bi-calendar-check"></i> Check-in: 
                        <strong><?php echo date('d M Y', strtotime($booking['tanggal_checkin'])); ?></strong>
                    </p>
                    <p class="small mb-1">
                        <i class="bi bi-calendar-x"></i> Check-out: 
                        <strong><?php echo date('d M Y', strtotime($booking['tanggal_checkout'])); ?></strong>
                    </p>
                    <p class="small mb-3">
                        <i class="bi bi-moon"></i> Total: <strong><?php echo $booking['total_malam']; ?> malam</strong>
                    </p>
                    
                    <hr>
                    
                    <p class="small mb-1">
                        <i class="bi bi-people"></i> Jumlah Tamu: <?php echo $booking['jumlah_tamu']; ?> orang
                    </p>
                    
                    <?php if (!empty($booking['catatan'])): ?>
                        <p class="small mb-3">
                            <i class="bi bi-chat-left-text"></i> Catatan: <?php echo $booking['catatan']; ?>
                        </p>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="h5 mb-0">Total Bayar:</span>
                        <span class="h5 mb-0 text-primary">
                            <?php echo formatRupiah($booking['total_harga']); ?>
                        </span>
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
