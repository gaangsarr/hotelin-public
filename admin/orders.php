<?php
require_once '../includes/functions.php';

requireAdmin();

// Get hotel_id from URL and validate
$hotel = requireHotelParam();
$hotel_id = $hotel['id_hotel'];

$conn = getConnection();

// Filter
$filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT p.*, k.tipe_kamar, k.nomor_kamar, h.nama_hotel, u.nama_lengkap, u.email, u.nomor_telepon,
        (SELECT status_bayar FROM pembayaran WHERE id_pemesanan = p.id_pemesanan LIMIT 1) as status_bayar
        FROM pemesanan p
        JOIN kamar k ON p.id_kamar = k.id_kamar
        JOIN hotel h ON k.id_hotel = h.id_hotel
        JOIN pengguna u ON p.id_pengguna = u.id_pengguna
        WHERE k.id_hotel = $hotel_id";

if ($filter != 'all') {
    $sql .= " AND p.status_pesanan = '$filter'";
}

$sql .= " ORDER BY p.created_at DESC";

$orders = $conn->query($sql);

// Count by status - FILTERED
$pending = $conn->query("
    SELECT COUNT(*) as count FROM pemesanan p 
    JOIN kamar k ON p.id_kamar = k.id_kamar 
    WHERE k.id_hotel = $hotel_id AND p.status_pesanan = 'Pending'
")->fetch_assoc()['count'];

$confirmed = $conn->query("
    SELECT COUNT(*) as count FROM pemesanan p 
    JOIN kamar k ON p.id_kamar = k.id_kamar 
    WHERE k.id_hotel = $hotel_id AND p.status_pesanan = 'Confirmed'
")->fetch_assoc()['count'];

$cancelled = $conn->query("
    SELECT COUNT(*) as count FROM pemesanan p 
    JOIN kamar k ON p.id_kamar = k.id_kamar 
    WHERE k.id_hotel = $hotel_id AND p.status_pesanan = 'Cancelled'
")->fetch_assoc()['count'];

// Pending payments count
$pending_payment = $conn->query("
    SELECT COUNT(*) as count FROM pembayaran pb
    JOIN pemesanan p ON pb.id_pemesanan = p.id_pemesanan
    JOIN kamar k ON p.id_kamar = k.id_kamar
    WHERE k.id_hotel = $hotel_id AND pb.status_bayar = 'Pending'
")->fetch_assoc()['count'];

$page_title = 'Orders Management';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid my-4">
    <!-- Hotel Info -->
    <div class="alert alert-info mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-building-fill"></i> 
                <strong>Orders untuk:</strong> <?php echo $hotel['nama_hotel']; ?>
                <span class="badge bg-primary ms-2"><?php echo $hotel['nama_kota']; ?></span>
            </div>
            <a href="<?php echo adminURL('dashboard.php', $hotel_id); ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-list-check"></i> Orders Management</h2>
    </div>
    
    <!-- Pending Payment Alert -->
    <?php if ($pending_payment > 0): ?>
        <div class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i> 
            <strong><?php echo $pending_payment; ?> pembayaran menunggu konfirmasi!</strong> 
            Silakan cek dan verifikasi pembayaran.
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <h6 class="text-muted mb-1">All Orders</h6>
                    <h3 class="mb-0"><?php echo $orders->num_rows; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white border-0">
                <div class="card-body">
                    <h6 class="mb-1">Pending</h6>
                    <h3 class="mb-0"><?php echo $pending; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white border-0">
                <div class="card-body">
                    <h6 class="mb-1">Confirmed</h6>
                    <h3 class="mb-0"><?php echo $confirmed; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white border-0">
                <div class="card-body">
                    <h6 class="mb-1">Cancelled</h6>
                    <h3 class="mb-0"><?php echo $cancelled; ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'all' ? 'active' : ''; ?>" 
               href="<?php echo adminURL('orders.php', $hotel_id, ['status' => 'all']); ?>">
                <i class="bi bi-list"></i> All Orders 
                <span class="badge bg-light text-dark ms-1"><?php echo $orders->num_rows; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'Pending' ? 'active' : ''; ?>" 
               href="<?php echo adminURL('orders.php', $hotel_id, ['status' => 'Pending']); ?>">
                <i class="bi bi-clock"></i> Pending 
                <span class="badge bg-<?php echo $filter == 'Pending' ? 'light text-dark' : 'warning'; ?> ms-1"><?php echo $pending; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'Confirmed' ? 'active' : ''; ?>" 
               href="<?php echo adminURL('orders.php', $hotel_id, ['status' => 'Confirmed']); ?>">
                <i class="bi bi-check-circle"></i> Confirmed 
                <span class="badge bg-<?php echo $filter == 'Confirmed' ? 'light text-dark' : 'success'; ?> ms-1"><?php echo $confirmed; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'Cancelled' ? 'active' : ''; ?>" 
               href="<?php echo adminURL('orders.php', $hotel_id, ['status' => 'Cancelled']); ?>">
                <i class="bi bi-x-circle"></i> Cancelled 
                <span class="badge bg-<?php echo $filter == 'Cancelled' ? 'light text-dark' : 'danger'; ?> ms-1"><?php echo $cancelled; ?></span>
            </a>
        </li>
    </ul>
    
    <?php if ($orders->num_rows > 0): ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Guests</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo str_pad($order['id_pemesanan'], 6, '0', STR_PAD_LEFT); ?></strong>
                                        <br><small class="text-muted"><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo $order['nama_lengkap']; ?></strong>
                                        <br><small class="text-muted">
                                            <i class="bi bi-envelope"></i> <?php echo $order['email']; ?>
                                        </small>
                                        <br><small class="text-muted">
                                            <i class="bi bi-telephone"></i> <?php echo $order['nomor_telepon']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?php echo $order['tipe_kamar']; ?></strong>
                                        <br><small class="text-muted">Kamar #<?php echo $order['nomor_kamar']; ?></small>
                                    </td>
                                    <td>
                                        <i class="bi bi-calendar-check"></i> 
                                        <?php echo date('d M Y', strtotime($order['tanggal_checkin'])); ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-calendar-x"></i> 
                                        <?php echo date('d M Y', strtotime($order['tanggal_checkout'])); ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-people"></i> <?php echo $order['jumlah_tamu']; ?>
                                        <br><small class="text-muted"><?php echo $order['total_malam']; ?> night(s)</small>
                                    </td>
                                    <td><strong><?php echo formatRupiah($order['total_harga']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status_pesanan'] == 'Confirmed' ? 'success' : 
                                                 ($order['status_pesanan'] == 'Cancelled' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo $order['status_pesanan']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($order['status_bayar']): ?>
                                            <span class="badge bg-<?php 
                                                echo $order['status_bayar'] == 'Confirmed' ? 'success' : 
                                                     ($order['status_bayar'] == 'Rejected' ? 'danger' : 'warning'); 
                                            ?>">
                                                <i class="bi bi-credit-card"></i> <?php echo $order['status_bayar']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-x-circle"></i> No Payment
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo adminURL('order-detail.php', $hotel_id, ['id' => $order['id_pemesanan']]); ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 5rem; color: #ddd;"></i>
                <h4 class="mt-3 text-muted">
                    <?php 
                    if ($filter == 'all') {
                        echo 'Belum ada order di hotel ini';
                    } else {
                        echo 'Tidak ada order dengan status ' . $filter;
                    }
                    ?>
                </h4>
                <p class="text-muted">Order akan muncul ketika ada customer yang booking kamar</p>
                <?php if ($filter != 'all'): ?>
                    <a href="<?php echo adminURL('orders.php', $hotel_id, ['status' => 'all']); ?>" class="btn btn-primary mt-3">
                        <i class="bi bi-list"></i> Lihat Semua Order
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
}

.nav-pills .nav-link {
    color: #6c757d;
    transition: all 0.3s ease;
}

.nav-pills .nav-link:hover {
    background-color: #e9ecef;
}

.nav-pills .nav-link.active {
    background-color: #0d6efd;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
</style>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
