<?php
require_once '../includes/functions.php';

requireAdmin();

$conn = getConnection();

// Get all hotels
$all_hotels = getAllHotels();

$page_title = 'Select Hotel - Super Admin';
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

<div class="container my-5">
    <div class="text-center mb-5">
        <i class="bi bi-buildings" style="font-size: 5rem; color: #0d6efd;"></i>
        <h2 class="mt-4">Super Admin Dashboard</h2>
        <p class="text-muted">Selamat datang, <strong><?php echo $_SESSION['nama_lengkap']; ?></strong>! Pilih hotel yang ingin Anda kelola</p>
    </div>
    
    <!-- Hotel Statistics Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>Total Hotels</h6>
                    <h2><?php echo count($all_hotels); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>Total Rooms</h6>
                    <h2>
                        <?php 
                        $total_rooms = $conn->query("SELECT COUNT(*) as count FROM kamar")->fetch_assoc()['count'];
                        echo $total_rooms;
                        ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>Total Bookings</h6>
                    <h2>
                        <?php 
                        $total_bookings = $conn->query("SELECT COUNT(*) as count FROM pemesanan")->fetch_assoc()['count'];
                        echo $total_bookings;
                        ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hotel Cards -->
    <div class="row">
        <?php if (count($all_hotels) > 0): ?>
            <?php foreach ($all_hotels as $hotel): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100 hover-card">
                        <?php if (!empty($hotel['foto_hotel'])): ?>
                            <img src="<?php echo BASE_URL . '/' . $hotel['foto_hotel']; ?>" 
                                 class="card-img-top" style="height: 200px; object-fit: cover;" 
                                 alt="<?php echo $hotel['nama_hotel']; ?>">
                        <?php else: ?>
                            <div class="bg-secondary d-flex align-items-center justify-content-center" 
                                 style="height: 200px;">
                                <i class="bi bi-building text-white" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $hotel['nama_hotel']; ?></h5>
                            <p class="text-muted mb-2">
                                <i class="bi bi-geo-alt"></i> <?php echo $hotel['nama_kota']; ?>, <?php echo $hotel['nama_provinsi']; ?>
                            </p>
                            <p class="mb-3">
                                <?php echo str_repeat('⭐', $hotel['rating']); ?>
                            </p>
                            
                            <?php
                            // Get hotel statistics
                            $hotel_id = $hotel['id_hotel'];
                            $rooms_count = $conn->query("SELECT COUNT(*) as count FROM kamar WHERE id_hotel = $hotel_id")->fetch_assoc()['count'];
                            $bookings_count = $conn->query("SELECT COUNT(*) as count FROM pemesanan p JOIN kamar k ON p.id_kamar = k.id_kamar WHERE k.id_hotel = $hotel_id")->fetch_assoc()['count'];
                            ?>
                            
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="bi bi-door-open"></i> <?php echo $rooms_count; ?> Rooms &nbsp;|&nbsp; 
                                    <i class="bi bi-calendar-check"></i> <?php echo $bookings_count; ?> Bookings
                                </small>
                            </div>

                            <!-- Di dalam card hotel -->
                            <div class="card-text mb-2">
                                <strong><i class="bi bi-check2-square"></i> Fasilitas:</strong><br>
                                <?php 
                                $fasilitas = getHotelFasilitas($hotel['id_hotel']);
                                if (!empty($fasilitas)) {
                                    $count = 0;
                                    $max_show = 5;
                                    foreach ($fasilitas as $f) {
                                        if ($count >= $max_show) {
                                            $remaining = count($fasilitas) - $max_show;
                                            echo '<span class="badge bg-secondary">+' . $remaining . ' more</span>';
                                            break;
                                        }
                                        echo '<span class="badge bg-light text-dark me-1 mb-1">';
                                        echo '<i class="' . $f['icon'] . '"></i> ' . $f['nama_fasilitas'];
                                        echo '</span> ';
                                        $count++;
                                    }
                                } else {
                                    echo '<span class="text-muted small">Tidak ada fasilitas</span>';
                                }
                                ?>
                            </div>
                            
                            <a href="dashboard.php?hotel_id=<?php echo $hotel['id_hotel']; ?>" 
                               class="btn btn-primary w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Kelola Hotel Ini
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox" style="font-size: 5rem; color: #ddd;"></i>
                <h4 class="mt-3 text-muted">Belum ada hotel</h4>
                <p class="text-muted">Tambahkan hotel pertama Anda!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.hover-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.hover-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
}
</style>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
