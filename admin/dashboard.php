<?php
require_once '../includes/functions.php';

requireAdmin();

// Get hotel_id from URL and validate
$hotel = requireHotelParam();
$hotel_id = $hotel['id_hotel'];

$conn = getConnection();

// Statistics - FILTERED BY HOTEL
$total_bookings = $conn->query("
    SELECT COUNT(*) as count FROM pemesanan p 
    JOIN kamar k ON p.id_kamar = k.id_kamar 
    WHERE k.id_hotel = $hotel_id
")->fetch_assoc()['count'];

$pending_bookings = $conn->query("
    SELECT COUNT(*) as count FROM pemesanan p 
    JOIN kamar k ON p.id_kamar = k.id_kamar 
    WHERE k.id_hotel = $hotel_id AND p.status_pesanan = 'Pending'
")->fetch_assoc()['count'];

$confirmed_bookings = $conn->query("
    SELECT COUNT(*) as count FROM pemesanan p 
    JOIN kamar k ON p.id_kamar = k.id_kamar 
    WHERE k.id_hotel = $hotel_id AND p.status_pesanan = 'Confirmed'
")->fetch_assoc()['count'];

$total_rooms = $conn->query("SELECT COUNT(*) as count FROM kamar WHERE id_hotel = $hotel_id")->fetch_assoc()['count'];
$available_rooms = $conn->query("SELECT COUNT(*) as count FROM kamar WHERE id_hotel = $hotel_id AND status = 'Available'")->fetch_assoc()['count'];

// Pending payments
$pending_payments = $conn->query("
    SELECT COUNT(*) as count FROM pembayaran pb
    JOIN pemesanan p ON pb.id_pemesanan = p.id_pemesanan
    JOIN kamar k ON p.id_kamar = k.id_kamar
    WHERE k.id_hotel = $hotel_id AND pb.status_bayar = 'Pending'
")->fetch_assoc()['count'];

// Total revenue
$total_revenue = $conn->query("
    SELECT SUM(pb.jumlah_bayar) as total FROM pembayaran pb
    JOIN pemesanan p ON pb.id_pemesanan = p.id_pemesanan
    JOIN kamar k ON p.id_kamar = k.id_kamar
    WHERE k.id_hotel = $hotel_id AND pb.status_bayar = 'Confirmed'
")->fetch_assoc()['total'] ?? 0;

// Recent bookings
$recent_bookings = $conn->query("
    SELECT p.*, k.tipe_kamar, k.nomor_kamar, h.nama_hotel, u.nama_lengkap,
    (SELECT status_bayar FROM pembayaran WHERE id_pemesanan = p.id_pemesanan LIMIT 1) as status_bayar
    FROM pemesanan p
    JOIN kamar k ON p.id_kamar = k.id_kamar
    JOIN hotel h ON k.id_hotel = h.id_hotel
    JOIN pengguna u ON p.id_pengguna = u.id_pengguna
    WHERE k.id_hotel = $hotel_id
    ORDER BY p.created_at DESC
    LIMIT 5
");

// Today's check-ins
$today = date('Y-m-d');
$today_checkins = $conn->query("
    SELECT COUNT(*) as count FROM pemesanan p
    JOIN kamar k ON p.id_kamar = k.id_kamar
    WHERE k.id_hotel = $hotel_id 
    AND p.tanggal_checkin = '$today' 
    AND p.status_pesanan = 'Confirmed'
")->fetch_assoc()['count'];

$page_title = 'Dashboard - ' . $hotel['nama_hotel'];
include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
        <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="container-fluid my-4">
    <!-- Hotel Info Banner -->
    <div class="alert alert-info mb-4 shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-10">
                <h5 class="mb-1">
                    <i class="bi bi-building-fill"></i> <strong><?php echo $hotel['nama_hotel']; ?></strong>
                    <?php echo str_repeat('⭐', $hotel['rating']); ?>
                </h5>
                <p class="mb-0">
                    <i class="bi bi-geo-alt-fill"></i> <?php echo $hotel['alamat_lengkap']; ?>
                    <span class="badge bg-primary ms-2"><?php echo $hotel['nama_kota']; ?></span>
                </p>
            </div>
            <div class="col-md-2 text-end">
                <a href="index.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-left-right"></i> Switch Hotel
                </a>
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
        <span class="text-muted">Welcome, <strong><?php echo $_SESSION['nama_lengkap']; ?></strong>!</span>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Total Bookings</p>
                            <h3 class="mb-0"><?php echo $total_bookings; ?></h3>
                            <small class="text-muted">All time</small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-calendar-check" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Pending Payment</p>
                            <h3 class="mb-0 text-warning"><?php echo $pending_payments; ?></h3>
                            <small class="text-muted">Need action</small>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-clock-history" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Available Rooms</p>
                            <h3 class="mb-0 text-success"><?php echo $available_rooms; ?> / <?php echo $total_rooms; ?></h3>
                            <small class="text-muted">Ready to book</small>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-door-open" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1 small">Total Revenue</p>
                            <h5 class="mb-0 text-info"><?php echo formatRupiah($total_revenue); ?></h5>
                            <small class="text-muted">Confirmed only</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-cash-stack" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Check-ins Alert -->
    <?php if ($today_checkins > 0): ?>
        <div class="alert alert-info mb-4 shadow-sm">
            <i class="bi bi-info-circle-fill"></i> 
            <strong><?php echo $today_checkins; ?> check-in hari ini!</strong> 
            Pastikan kamar sudah siap dan bersih.
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Recent Bookings -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Booking Terbaru</h5>
                    <a href="<?php echo adminURL('orders.php', $hotel_id); ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_bookings->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td><small>#<?php echo str_pad($booking['id_pemesanan'], 6, '0', STR_PAD_LEFT); ?></small></td>
                                            <td><?php echo $booking['nama_lengkap']; ?></td>
                                            <td><small><?php echo $booking['tipe_kamar']; ?></small></td>
                                            <td><small><?php echo date('d M Y', strtotime($booking['tanggal_checkin'])); ?></small></td>
                                            <td><small><?php echo formatRupiah($booking['total_harga']); ?></small></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $booking['status_pesanan'] == 'Confirmed' ? 'success' : 
                                                         ($booking['status_pesanan'] == 'Cancelled' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo $booking['status_pesanan']; ?>
                                                </span>
                                                <?php if ($booking['status_bayar']): ?>
                                                    <br><small class="badge bg-secondary mt-1"><?php echo $booking['status_bayar']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo adminURL('order-detail.php', $hotel_id, ['id' => $booking['id_pemesanan']]); ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                            <p class="mt-3 mb-0">Belum ada booking untuk hotel ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-lightning-fill"></i> Quick Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="<?php echo adminURL('hotel-edit.php', $hotel_id); ?>" class="btn btn-outline-primary">
                        <i class="bi bi-building"></i> Edit Hotel Profile
                    </a>
                    <a href="<?php echo adminURL('room-add.php', $hotel_id); ?>" class="btn btn-outline-success">
                        <i class="bi bi-plus-circle"></i> Tambah Kamar Baru
                    </a>
                    <a href="<?php echo adminURL('rooms.php', $hotel_id); ?>" class="btn btn-outline-info">
                        <i class="bi bi-door-open"></i> Manage Rooms
                    </a>
                    <a href="<?php echo adminURL('orders.php', $hotel_id); ?>" class="btn btn-outline-warning">
                        <i class="bi bi-list-check"></i> Lihat Semua Orders
                    </a>
                    <a href="<?php echo adminURL('availability.php', $hotel_id); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-calendar-week"></i> Check Availability
                    </a>
                </div>
            </div>
            
            <!-- Summary Stats -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-graph-up"></i> Summary Stats</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                        <span class="text-muted">Total Rooms:</span>
                        <strong class="text-primary"><?php echo $total_rooms; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                        <span class="text-muted">Available Rooms:</span>
                        <strong class="text-success"><?php echo $available_rooms; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                        <span class="text-muted">Pending Bookings:</span>
                        <strong class="text-warning"><?php echo $pending_bookings; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Confirmed Bookings:</span>
                        <strong class="text-success"><?php echo $confirmed_bookings; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
