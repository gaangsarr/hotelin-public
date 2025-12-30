<?php
require_once 'includes/functions.php';

$hotel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($hotel_id <= 0) {
    header('Location: index.php');
    exit();
}

$conn = getConnection();

// Get hotel details
$sql = "SELECT h.*, k.nama_kota, p.nama_provinsi
        FROM hotel h
        JOIN kota k ON h.id_kota = k.id_kota
        JOIN provinsi p ON k.id_provinsi = p.id_provinsi
        WHERE h.id_hotel = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$hotel = $stmt->get_result()->fetch_assoc();

if (!$hotel) {
    header('Location: index.php');
    exit();
}

// Get available rooms
$rooms = $conn->query("SELECT * FROM kamar WHERE id_hotel = $hotel_id ORDER BY harga_malam ASC");

$page_title = $hotel['nama_hotel'];
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container my-5">
    <!-- Hotel Info -->
    <div class="row mb-5">
        <div class="col-md-6">
            <?php if (!empty($hotel['foto_hotel'])): ?>
                <img src="<?php echo BASE_URL . '/' . $hotel['foto_hotel']; ?>" 
                     class="img-fluid rounded shadow" 
                     alt="<?php echo $hotel['nama_hotel']; ?>">
            <?php else: ?>
                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 400px;">
                    <i class="bi bi-building text-white" style="font-size: 6rem;"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2><?php echo $hotel['nama_hotel']; ?></h2>
                    <p class="text-muted mb-2">
                        <i class="bi bi-geo-alt-fill"></i> <?php echo $hotel['alamat_lengkap']; ?>
                    </p>
                    <p class="text-muted">
                        <i class="bi bi-map"></i> <?php echo $hotel['nama_kota']; ?>, <?php echo $hotel['nama_provinsi']; ?>
                    </p>
                </div>
                <span class="badge bg-warning text-dark" style="font-size: 1.2rem;">
                    <?php echo str_repeat('⭐', $hotel['rating']); ?>
                </span>
            </div>
            
            <div class="mb-4">
                <h5>Deskripsi</h5>
                <p class="text-muted"><?php echo nl2br($hotel['deskripsi']); ?></p>
            </div>
            
            <?php if (!empty($hotel['fasilitas'])): ?>
                <div class="mb-4">
                    <h5>Fasilitas Hotel</h5>
                    <div>
                        <?php 
                        $fasilitas = parseFasilitas($hotel['fasilitas']);
                        foreach ($fasilitas as $f): 
                        ?>
                            <span class="facilities-badge">
                                <i class="bi bi-check-circle-fill text-success"></i> <?php echo $f; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="border-top pt-3">
                <h6>Kontak</h6>
                <p class="mb-1"><i class="bi bi-telephone"></i> <?php echo $hotel['phone']; ?></p>
                <p class="mb-0"><i class="bi bi-envelope"></i> <?php echo $hotel['email']; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Available Rooms -->
    <div class="mb-5">
        <h3 class="mb-4">Pilih Kamar</h3>
        
        <?php if ($rooms->num_rows > 0): ?>
            <div class="row">
                <?php while ($room = $rooms->fetch_assoc()): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="row g-0">
                                <div class="col-md-5">
                                    <?php if (!empty($room['foto_kamar'])): ?>
                                        <img src="<?php echo BASE_URL . '/' . $room['foto_kamar']; ?>" 
                                             class="img-fluid h-100 rounded-start" 
                                             style="object-fit: cover;"
                                             alt="<?php echo $room['tipe_kamar']; ?>">
                                    <?php else: ?>
                                        <div class="bg-secondary h-100 rounded-start d-flex align-items-center justify-content-center">
                                            <i class="bi bi-door-open text-white" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-7">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title"><?php echo $room['tipe_kamar']; ?></h5>
                                            <span class="badge bg-<?php echo $room['status'] == 'Available' ? 'success' : 'danger'; ?>">
                                                <?php echo $room['status']; ?>
                                            </span>
                                        </div>
                                        
                                        <p class="small text-muted mb-2">
                                            <i class="bi bi-door-closed"></i> Kamar #<?php echo $room['nomor_kamar']; ?>
                                        </p>
                                        
                                        <p class="small mb-2">
                                            <i class="bi bi-people"></i> Maksimal <?php echo $room['kapasitas']; ?> tamu
                                        </p>
                                        
                                        <p class="small text-muted mb-3">
                                            <?php echo $room['deskripsi']; ?>
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-end">
                                            <div>
                                                <small class="text-muted">Harga per malam</small>
                                                <h5 class="text-primary mb-0">
                                                    <?php echo formatRupiah($room['harga_malam']); ?>
                                                </h5>
                                            </div>
                                            
                                            <?php if ($room['status'] == 'Available'): ?>
                                                <a href="booking.php?room_id=<?php echo $room['id_kamar']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    Pesan Sekarang
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    Tidak Tersedia
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Belum ada kamar tersedia untuk hotel ini.
            </div>
        <?php endif; ?>
    </div>
    
    <div class="text-center">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Hotel
        </a>
    </div>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
