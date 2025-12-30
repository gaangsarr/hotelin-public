<?php
require_once '../includes/functions.php';

requireAdmin();

// Get hotel_id from URL and validate
$hotel = requireHotelParam();
$hotel_id = $hotel['id_hotel'];

$conn = getConnection();

// Get rooms - FILTERED BY HOTEL
$rooms = $conn->query("
    SELECT k.*, h.nama_hotel,
    (SELECT COUNT(*) FROM pemesanan p WHERE p.id_kamar = k.id_kamar AND p.status_pesanan IN ('Pending', 'Confirmed')) as active_bookings
    FROM kamar k
    JOIN hotel h ON k.id_hotel = h.id_hotel
    WHERE k.id_hotel = $hotel_id
    ORDER BY k.nomor_kamar
");

$page_title = 'Manage Rooms';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid my-4">
    <!-- Hotel Info -->
    <div class="alert alert-info mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-building-fill"></i> 
                <strong>Mengelola Kamar:</strong> <?php echo $hotel['nama_hotel']; ?>
                <span class="badge bg-primary ms-2"><?php echo $hotel['nama_kota']; ?></span>
            </div>
            <a href="<?php echo adminURL('dashboard.php', $hotel_id); ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-door-open"></i> Manage Rooms</h2>
        <div>
            <a href="<?php echo adminURL('availability.php', $hotel_id); ?>" class="btn btn-outline-info me-2">
                <i class="bi bi-calendar-check"></i> Check Availability
            </a>
            <a href="<?php echo adminURL('room-add.php', $hotel_id); ?>" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Tambah Kamar
            </a>
        </div>
    </div>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Kamar berhasil dihapus!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Kamar berhasil diupdate!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Kamar baru berhasil ditambahkan!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> 
            <?php 
            if ($_GET['error'] == 'active_booking') {
                echo 'Tidak dapat menghapus kamar dengan booking aktif!';
            } else {
                echo 'Terjadi kesalahan!';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($rooms->num_rows > 0): ?>
        <!-- Room Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Kamar</h6>
                        <h3><?php echo $rooms->num_rows; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Available</h6>
                        <h3>
                            <?php 
                            $available_count = $conn->query("SELECT COUNT(*) as count FROM kamar WHERE id_hotel = $hotel_id AND status = 'Available'")->fetch_assoc()['count'];
                            echo $available_count;
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-title">Booked</h6>
                        <h3>
                            <?php 
                            $booked_count = $conn->query("SELECT COUNT(*) as count FROM kamar WHERE id_hotel = $hotel_id AND status = 'Booked'")->fetch_assoc()['count'];
                            echo $booked_count;
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Maintenance</h6>
                        <h3>
                            <?php 
                            $maintenance_count = $conn->query("SELECT COUNT(*) as count FROM kamar WHERE id_hotel = $hotel_id AND status = 'Maintenance'")->fetch_assoc()['count'];
                            echo $maintenance_count;
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Rooms Grid -->
        <div class="row">
            <?php 
            $rooms->data_seek(0);
            while ($room = $rooms->fetch_assoc()): 
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100 hover-card">
                        <?php if (!empty($room['foto_kamar'])): ?>
                            <img src="<?php echo BASE_URL . '/' . $room['foto_kamar']; ?>" 
                                 class="card-img-top" 
                                 style="height: 200px; object-fit: cover;"
                                 alt="<?php echo $room['tipe_kamar']; ?>">
                        <?php else: ?>
                            <div class="bg-secondary d-flex align-items-center justify-content-center" 
                                 style="height: 200px;">
                                <i class="bi bi-door-open text-white" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="card-title mb-1"><?php echo $room['tipe_kamar']; ?></h5>
                                    <span class="badge bg-secondary">Kamar #<?php echo $room['nomor_kamar']; ?></span>
                                </div>
                                <span class="badge bg-<?php 
                                    echo $room['status'] == 'Available' ? 'success' : 
                                         ($room['status'] == 'Maintenance' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo $room['status']; ?>
                                </span>
                            </div>
                            
                            <hr>
                            
                            <p class="mb-2">
                                <i class="bi bi-people-fill text-primary"></i> 
                                <strong>Kapasitas:</strong> <?php echo $room['kapasitas']; ?> orang
                            </p>
                            <p class="mb-2">
                                <i class="bi bi-cash-coin text-success"></i> 
                                <strong>Harga:</strong> <?php echo formatRupiah($room['harga_malam']); ?> / malam
                            </p>
                            
                            <?php if (!empty($room['deskripsi'])): ?>
                                <p class="small text-muted mb-2">
                                    <?php echo substr($room['deskripsi'], 0, 80); ?><?php echo strlen($room['deskripsi']) > 80 ? '...' : ''; ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($room['active_bookings'] > 0): ?>
                                <div class="alert alert-info py-2 mb-2">
                                    <small>
                                        <i class="bi bi-calendar-check"></i> 
                                        <strong><?php echo $room['active_bookings']; ?></strong> active booking(s)
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-2 mt-3">
                                <a href="<?php echo adminURL('room-edit.php', $hotel_id, ['id' => $room['id_kamar']]); ?>" 
                                   class="btn btn-sm btn-outline-primary flex-grow-1">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="<?php echo adminURL('room-delete.php', $hotel_id, ['id' => $room['id_kamar']]); ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Yakin ingin menghapus kamar #<?php echo $room['nomor_kamar']; ?>?\n\nKamar dengan booking aktif tidak bisa dihapus.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> Updated: <?php echo date('d M Y H:i', strtotime($room['updated_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 5rem; color: #ddd;"></i>
            <h4 class="mt-3 text-muted">Belum ada kamar di hotel ini</h4>
            <p class="text-muted mb-4">Tambahkan kamar pertama Anda sekarang!</p>
            <a href="<?php echo adminURL('room-add.php', $hotel_id); ?>" class="btn btn-success btn-lg">
                <i class="bi bi-plus-circle"></i> Tambah Kamar Pertama
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.hover-card {
    transition: all 0.3s ease;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.card-img-top {
    transition: transform 0.3s ease;
}

.hover-card:hover .card-img-top {
    transform: scale(1.05);
}
</style>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
