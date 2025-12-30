<?php
require_once '../includes/functions.php';

requireAdmin();

// Get hotel_id from URL and validate
$hotel = requireHotelParam();
$hotel_id = $hotel['id_hotel'];

// Get order_id
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    $_SESSION['error_message'] = 'Pemesanan tidak ditemukan!';
    header('Location: ' . adminURL('orders.php', $hotel_id));
    exit();
}

$conn = getConnection();

// Get order details - VERIFY belongs to selected hotel
$stmt = $conn->prepare("
    SELECT p.*, k.tipe_kamar, k.nomor_kamar, k.foto_kamar, h.nama_hotel, h.id_hotel,
           u.nama_lengkap, u.email, u.nomor_telepon
    FROM pemesanan p
    JOIN kamar k ON p.id_kamar = k.id_kamar
    JOIN hotel h ON k.id_hotel = h.id_hotel
    JOIN pengguna u ON p.id_pengguna = u.id_pengguna
    WHERE p.id_pemesanan = ? AND h.id_hotel = ?
");

$stmt->bind_param("ii", $order_id, $hotel_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error_message'] = 'Pemesanan tidak ditemukan atau bukan milik hotel ini!';
    header('Location: ' . adminURL('orders.php', $hotel_id));
    exit();
}

// Get payment details
$payment = $conn->query("SELECT * FROM pembayaran WHERE id_pemesanan = $order_id")->fetch_assoc();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = cleanInput($_POST['status_pesanan']);
    
    $stmt = $conn->prepare("UPDATE pemesanan SET status_pesanan = ? WHERE id_pemesanan = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Status booking berhasil diupdate!';
        header('Location: ' . adminURL('order-detail.php', $hotel_id, ['id' => $order_id]));
        exit();
    }
}

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $new_payment_status = cleanInput($_POST['status_bayar']);
    
    if ($payment) {
        $stmt = $conn->prepare("UPDATE pembayaran SET status_bayar = ? WHERE id_pembayaran = ?");
        $stmt->bind_param("si", $new_payment_status, $payment['id_pembayaran']);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Status pembayaran berhasil diupdate!';
            header('Location: ' . adminURL('order-detail.php', $hotel_id, ['id' => $order_id]));
            exit();
        }
    }
}

$page_title = 'Order Detail';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <!-- Hotel Info -->
    <div class="alert alert-info mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-building-fill"></i> 
                <strong>Order Detail:</strong> <?php echo $hotel['nama_hotel']; ?>
                <span class="badge bg-primary ms-2"><?php echo $hotel['nama_kota']; ?></span>
            </div>
            <a href="<?php echo adminURL('orders.php', $hotel_id); ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-earmark-text"></i> Booking #<?php echo str_pad($order['id_pemesanan'], 6, '0', STR_PAD_LEFT); ?></h2>
        <div>
            <span class="badge bg-<?php 
                echo $order['status_pesanan'] == 'Confirmed' ? 'success' : 
                     ($order['status_pesanan'] == 'Cancelled' ? 'danger' : 'warning'); 
            ?> fs-6">
                <?php echo $order['status_pesanan']; ?>
            </span>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Booking Details -->
        <div class="col-md-8">
            <!-- Room Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-door-open"></i> Informasi Kamar</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (!empty($order['foto_kamar'])): ?>
                            <div class="col-md-4">
                                <img src="<?php echo BASE_URL . '/' . $order['foto_kamar']; ?>" 
                                     class="img-fluid rounded" style="width: 100%; height: 200px; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <div class="col-md-<?php echo !empty($order['foto_kamar']) ? '8' : '12'; ?>">
                            <h4><?php echo $order['tipe_kamar']; ?></h4>
                            <p class="mb-2">
                                <i class="bi bi-hash"></i> <strong>Nomor Kamar:</strong> <?php echo $order['nomor_kamar']; ?>
                            </p>
                            <p class="mb-2">
                                <i class="bi bi-building"></i> <strong>Hotel:</strong> <?php echo $order['nama_hotel']; ?>
                            </p>
                            <p class="mb-2">
                                <i class="bi bi-people"></i> <strong>Jumlah Tamu:</strong> <?php echo $order['jumlah_tamu']; ?> orang
                            </p>
                            <p class="mb-2">
                                <i class="bi bi-moon"></i> <strong>Total Malam:</strong> <?php echo $order['total_malam']; ?> malam
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Schedule -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Jadwal Booking</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded mb-3">
                                <h6 class="text-muted mb-2">Check-in</h6>
                                <h4 class="mb-0">
                                    <i class="bi bi-calendar-check text-success"></i> 
                                    <?php echo date('d F Y', strtotime($order['tanggal_checkin'])); ?>
                                </h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded mb-3">
                                <h6 class="text-muted mb-2">Check-out</h6>
                                <h4 class="mb-0">
                                    <i class="bi bi-calendar-x text-danger"></i> 
                                    <?php echo date('d F Y', strtotime($order['tanggal_checkout'])); ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-clock"></i> Check-in: 14:00 | Check-out: 12:00
                    </div>
                </div>
            </div>
            
            <!-- Customer Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Informasi Customer</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <p class="mb-1 text-muted">Nama Lengkap</p>
                            <h6><i class="bi bi-person-fill text-primary"></i> <?php echo $order['nama_lengkap']; ?></h6>
                        </div>
                        <div class="col-md-6 mb-3">
                            <p class="mb-1 text-muted">Email</p>
                            <h6><i class="bi bi-envelope-fill text-info"></i> <?php echo $order['email']; ?></h6>
                        </div>
                        <div class="col-md-6 mb-3">
                            <p class="mb-1 text-muted">Nomor Telepon</p>
                            <h6><i class="bi bi-telephone-fill text-success"></i> <?php echo $order['nomor_telepon']; ?></h6>
                        </div>
                        <div class="col-md-6 mb-3">
                            <p class="mb-1 text-muted">User ID</p>
                            <h6><i class="bi bi-hash"></i> <?php echo $order['id_pengguna']; ?></h6>
                        </div>
                    </div>
                    <?php if (!empty($order['permintaan_khusus'])): ?>
                        <hr>
                        <p class="mb-1 text-muted">Permintaan Khusus</p>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-info-circle"></i> <?php echo nl2br(htmlspecialchars($order['permintaan_khusus'])); ?>
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
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted">Metode Pembayaran</p>
                                <h6><?php echo $payment['metode_bayar']; ?></h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted">Status Pembayaran</p>
                                <h6>
                                    <span class="badge bg-<?php 
                                        echo $payment['status_bayar'] == 'Confirmed' ? 'success' : 
                                             ($payment['status_bayar'] == 'Rejected' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo $payment['status_bayar']; ?>
                                    </span>
                                </h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted">Jumlah Bayar</p>
                                <h5 class="text-success"><?php echo formatRupiah($payment['jumlah_bayar']); ?></h5>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="mb-1 text-muted">Tanggal Bayar</p>
                                <h6><?php echo date('d M Y H:i', strtotime($payment['tanggal_bayar'])); ?></h6>
                            </div>
                        </div>
                        
                        <?php if (!empty($payment['bukti_bayar'])): ?>
                            <hr>
                            <p class="mb-2 text-muted">Bukti Pembayaran</p>
                            <a href="<?php echo BASE_URL . '/' . $payment['bukti_bayar']; ?>" target="_blank">
                                <img src="<?php echo BASE_URL . '/' . $payment['bukti_bayar']; ?>" 
                                     class="img-fluid rounded border" style="max-height: 300px;">
                            </a>
                            <br><small class="text-muted">Klik untuk melihat ukuran penuh</small>
                        <?php endif; ?>
                        
                        <!-- Update Payment Status -->
                        <?php if ($payment['status_bayar'] == 'Pending'): ?>
                            <hr>
                            <form method="POST" action="" class="d-flex gap-2">
                                <input type="hidden" name="update_payment" value="1">
                                <button type="submit" name="status_bayar" value="Confirmed" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Konfirmasi Pembayaran
                                </button>
                                <button type="submit" name="status_bayar" value="Rejected" class="btn btn-danger"
                                        onclick="return confirm('Yakin ingin menolak pembayaran ini?')">
                                    <i class="bi bi-x-circle"></i> Tolak Pembayaran
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> Belum ada pembayaran untuk booking ini.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Price Summary -->
            <div class="card shadow-sm mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-calculator"></i> Ringkasan Harga</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Harga per Malam:</span>
                        <strong><?php echo formatRupiah($order['total_harga'] / $order['total_malam']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Jumlah Malam:</span>
                        <strong><?php echo $order['total_malam']; ?> malam</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total:</strong>
                        <h4 class="text-success mb-0"><?php echo formatRupiah($order['total_harga']); ?></h4>
                    </div>
                </div>
            </div>
            
            <!-- Update Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-gear"></i> Update Status Booking</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="update_status" value="1">
                        <div class="mb-3">
                            <label class="form-label">Status Booking</label>
                            <select name="status_pesanan" class="form-select" required>
                                <option value="Pending" <?php echo $order['status_pesanan'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Confirmed" <?php echo $order['status_pesanan'] == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Cancelled" <?php echo $order['status_pesanan'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle"></i> Update Status
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Booking Info -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Info Booking</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Booking ID:</strong><br>#<?php echo str_pad($order['id_pemesanan'], 6, '0', STR_PAD_LEFT); ?></p>
                    <p class="mb-2"><strong>Dibuat:</strong><br><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></p>
                    <p class="mb-0"><strong>Update Terakhir:</strong><br><?php echo date('d M Y H:i', strtotime($order['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
