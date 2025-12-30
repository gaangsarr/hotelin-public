<?php
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    header('Location: ../admin/index.php');
    exit();
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get all user bookings
$sql = "SELECT p.*, k.tipe_kamar, k.nomor_kamar, h.nama_hotel, h.alamat_lengkap,
        (SELECT status_bayar FROM pembayaran WHERE id_pemesanan = p.id_pemesanan LIMIT 1) as status_bayar
        FROM pemesanan p
        JOIN kamar k ON p.id_kamar = k.id_kamar
        JOIN hotel h ON k.id_hotel = h.id_hotel
        WHERE p.id_pengguna = ?
        ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();

$page_title = 'My Bookings';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-calendar-check"></i> My Bookings</h2>
        <a href="../index.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Booking Baru
        </a>
    </div>
    
    <?php if ($bookings->num_rows > 0): ?>
        <div class="row">
            <?php while ($booking = $bookings->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Booking #<?php echo str_pad($booking['id_pemesanan'], 6, '0', STR_PAD_LEFT); ?></h6>
                                <span class="badge bg-<?php 
                                    echo $booking['status_pesanan'] == 'Confirmed' ? 'success' : 
                                         ($booking['status_pesanan'] == 'Cancelled' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo $booking['status_pesanan']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $booking['nama_hotel']; ?></h5>
                            <p class="text-muted small mb-2"><?php echo $booking['tipe_kamar']; ?> #<?php echo $booking['nomor_kamar']; ?></p>
                            
                            <div class="mb-3">
                                <p class="small mb-1">
                                    <i class="bi bi-calendar-check"></i> 
                                    <?php echo date('d M Y', strtotime($booking['tanggal_checkin'])); ?> - 
                                    <?php echo date('d M Y', strtotime($booking['tanggal_checkout'])); ?>
                                </p>
                                <p class="small mb-1">
                                    <i class="bi bi-moon"></i> <?php echo $booking['total_malam']; ?> malam | 
                                    <i class="bi bi-people"></i> <?php echo $booking['jumlah_tamu']; ?> tamu
                                </p>
                                <p class="small mb-0">
                                    <strong>Total: <?php echo formatRupiah($booking['total_harga']); ?></strong>
                                </p>
                            </div>
                            
                            <?php if ($booking['status_bayar']): ?>
                                <div class="alert alert-<?php 
                                    echo $booking['status_bayar'] == 'Confirmed' ? 'success' : 
                                         ($booking['status_bayar'] == 'Rejected' ? 'danger' : 'warning'); 
                                ?> py-2 mb-3">
                                    <small>
                                        <i class="bi bi-credit-card"></i> 
                                        Pembayaran: <strong><?php echo $booking['status_bayar']; ?></strong>
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger py-2 mb-3">
                                    <small><i class="bi bi-exclamation-triangle"></i> Belum bayar</small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-2">
                                <a href="booking-detail.php?id=<?php echo $booking['id_pemesanan']; ?>" 
                                   class="btn btn-outline-primary btn-sm flex-grow-1">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                
                                <?php if ($booking['status_pesanan'] == 'Pending' && !$booking['status_bayar']): ?>
                                    <a href="../payment.php?booking_id=<?php echo $booking['id_pemesanan']; ?>" 
                                       class="btn btn-success btn-sm flex-grow-1">
                                        <i class="bi bi-credit-card"></i> Bayar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <small class="text-muted">
                                Dipesan: <?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 5rem; color: #ddd;"></i>
            <h4 class="mt-3 text-muted">Belum ada booking</h4>
            <p class="text-muted mb-4">Mulai booking hotel favorit Anda sekarang!</p>
            <a href="../index.php" class="btn btn-primary">
                <i class="bi bi-search"></i> Cari Hotel
            </a>
        </div>
    <?php endif; ?>
</div>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
