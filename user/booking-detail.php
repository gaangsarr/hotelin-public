<?php
require_once '../includes/functions.php';

requireLogin();

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

$conn = getConnection();

// Get booking details
$sql = "SELECT p.*, k.tipe_kamar, k.nomor_kamar, k.harga_malam, k.foto_kamar,
        h.nama_hotel, h.alamat_lengkap, h.phone, h.email as hotel_email,
        u.nama_lengkap, u.email, u.nomor_telepon
        FROM pemesanan p
        JOIN kamar k ON p.id_kamar = k.id_kamar
        JOIN hotel h ON k.id_hotel = h.id_hotel
        JOIN pengguna u ON p.id_pengguna = u.id_pengguna
        WHERE p.id_pemesanan = ? AND p.id_pengguna = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: my-bookings.php');
    exit();
}

// Get payment info
$payment = $conn->query("SELECT * FROM pembayaran WHERE id_pemesanan = $booking_id")->fetch_assoc();

$page_title = 'Booking Detail';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Booking Detail #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></h2>
        <a href="my-bookings.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    
    <!-- Status Badge -->
    <div class="alert alert-<?php 
        echo $booking['status_pesanan'] == 'Confirmed' ? 'success' : 
             ($booking['status_pesanan'] == 'Cancelled' ? 'danger' : 'warning'); 
    ?> mb-4">
        <h5 class="mb-0">
            <i class="bi bi-info-circle"></i> Status Pesanan: <strong><?php echo $booking['status_pesanan']; ?></strong>
        </h5>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Hotel & Room Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-building"></i> Informasi Hotel & Kamar</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?php if (!empty($booking['foto_kamar'])): ?>
                                <img src="<?php echo BASE_URL . '/' . $booking['foto_kamar']; ?>" 
                                     class="img-fluid rounded">
                            <?php else: ?>
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                                    <i class="bi bi-door-open text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h5><?php echo $booking['nama_hotel']; ?></h5>
                            <p class="text-muted mb-2"><?php echo $booking['alamat_lengkap']; ?></p>
                            <hr>
                            <p class="mb-1"><strong>Tipe Kamar:</strong> <?php echo $booking['tipe_kamar']; ?></p>
                            <p class="mb-1"><strong>Nomor Kamar:</strong> #<?php echo $booking['nomor_kamar']; ?></p>
                            <p class="mb-0"><strong>Harga/Malam:</strong> <?php echo formatRupiah($booking['harga_malam']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Detail Pemesanan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Check-in:</strong> <?php echo date('d M Y', strtotime($booking['tanggal_checkin'])); ?> (14:00)</p>
                            <p class="mb-2"><strong>Check-out:</strong> <?php echo date('d M Y', strtotime($booking['tanggal_checkout'])); ?> (12:00)</p>
                            <p class="mb-0"><strong>Total Malam:</strong> <?php echo $booking['total_malam']; ?> malam</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Jumlah Tamu:</strong> <?php echo $booking['jumlah_tamu']; ?> orang</p>
                            <p class="mb-0"><strong>Dipesan:</strong> <?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($booking['catatan'])): ?>
                        <hr>
                        <div class="alert alert-info mb-0">
                            <strong><i class="bi bi-chat-left-text"></i> Catatan Khusus:</strong><br>
                            <?php echo nl2br($booking['catatan']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payment Info -->
            <?php if ($payment): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-credit-card"></i> Informasi Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-<?php 
                            echo $payment['status_bayar'] == 'Confirmed' ? 'success' : 
                                 ($payment['status_bayar'] == 'Rejected' ? 'danger' : 'warning'); 
                        ?>">
                            <strong>Status: <?php echo $payment['status_bayar']; ?></strong>
                        </div>
                        
                        <p class="mb-2"><strong>Jumlah:</strong> <?php echo formatRupiah($payment['jumlah_bayar']); ?></p>
                        <p class="mb-2"><strong>Metode:</strong> <?php echo $payment['metode_bayar']; ?></p>
                        <p class="mb-3"><strong>Tanggal:</strong> <?php echo date('d M Y H:i', strtotime($payment['tanggal_bayar'])); ?></p>
                        
                        <?php if (!empty($payment['bukti_bayar'])): ?>
                            <p class="mb-2"><strong>Bukti Pembayaran:</strong></p>
                            <img src="<?php echo BASE_URL . '/' . $payment['bukti_bayar']; ?>" 
                                 class="img-fluid rounded border" style="max-height: 300px;">
                        <?php endif; ?>
                        
                        <?php if (!empty($payment['catatan_admin'])): ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <strong>Catatan Admin:</strong><br>
                                <?php echo $payment['catatan_admin']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Pembayaran belum dilakukan.
                    <a href="../payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-success btn-sm ms-2">
                        Bayar Sekarang
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <!-- Price Summary -->
            <div class="card shadow-sm mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-receipt"></i> Ringkasan Harga</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo formatRupiah($booking['harga_malam']); ?> × <?php echo $booking['total_malam']; ?> malam</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total</strong>
                        <strong class="text-primary h5"><?php echo formatRupiah($booking['total_harga']); ?></strong>
                    </div>
                </div>
            </div>
            
            <!-- Contact Hotel -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Kontak Hotel</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><i class="bi bi-telephone"></i> <?php echo $booking['phone']; ?></p>
                    <p class="mb-0"><i class="bi bi-envelope"></i> <?php echo $booking['hotel_email']; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
