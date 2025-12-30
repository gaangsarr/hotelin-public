<?php
require_once 'includes/functions.php';

$conn = getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$kota_id = isset($_GET['kota']) ? (int)$_GET['kota'] : 0;
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;

// Build query
$sql = "SELECT h.*, k.nama_kota, p.nama_provinsi,
        (SELECT COUNT(*) FROM kamar WHERE id_hotel = h.id_hotel AND status = 'Available') as available_rooms
        FROM hotel h
        JOIN kota k ON h.id_kota = k.id_kota
        JOIN provinsi p ON k.id_provinsi = p.id_provinsi
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (h.nama_hotel LIKE '%$search%' OR h.deskripsi LIKE '%$search%')";
}

if ($kota_id > 0) {
    $sql .= " AND h.id_kota = $kota_id";
}

if ($rating > 0) {
    $sql .= " AND h.rating >= $rating";
}

$sql .= " ORDER BY h.rating DESC, h.nama_hotel ASC";

$hotels = $conn->query($sql);

// Get all cities for filter
$cities = $conn->query("SELECT k.*, p.nama_provinsi FROM kota k JOIN provinsi p ON k.id_provinsi = p.id_provinsi ORDER BY k.nama_kota");

$page_title = 'Cari Hotel Terbaik';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Hero Section -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">Temukan Hotel Impian Anda</h1>
                <p class="lead">Booking hotel mudah, cepat, dan terpercaya di seluruh Indonesia</p>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter -->
<div class="bg-light py-4">
    <div class="container">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Cari hotel..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <select name="kota" class="form-select">
                    <option value="0">Semua Kota</option>
                    <?php while ($city = $cities->fetch_assoc()): ?>
                        <option value="<?php echo $city['id_kota']; ?>" <?php echo $kota_id == $city['id_kota'] ? 'selected' : ''; ?>>
                            <?php echo $city['nama_kota']; ?>, <?php echo $city['nama_provinsi']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <select name="rating" class="form-select">
                    <option value="0">Semua Rating</option>
                    <option value="5" <?php echo $rating == 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐</option>
                    <option value="4" <?php echo $rating == 4 ? 'selected' : ''; ?>>⭐⭐⭐⭐+</option>
                    <option value="3" <?php echo $rating == 3 ? 'selected' : ''; ?>>⭐⭐⭐+</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Cari
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hotels List -->
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Daftar Hotel</h3>
        <span class="text-muted"><?php echo $hotels->num_rows; ?> hotel ditemukan</span>
    </div>
    
    <?php if ($hotels->num_rows > 0): ?>
        <div class="row">
            <?php while ($hotel = $hotels->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($hotel['foto_hotel'])): ?>
                            <img src="<?php echo BASE_URL . '/' . $hotel['foto_hotel']; ?>" 
                                 class="card-img-top hotel-card-img" 
                                 alt="<?php echo $hotel['nama_hotel']; ?>">
                        <?php else: ?>
                            <div class="hotel-card-img bg-secondary d-flex align-items-center justify-content-center">
                                <i class="bi bi-building text-white" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo $hotel['nama_hotel']; ?></h5>
                                <span class="badge bg-warning text-dark badge-rating">
                                    <?php echo str_repeat('⭐', $hotel['rating']); ?>
                                </span>
                            </div>
                            
                            <p class="text-muted small mb-2">
                                <i class="bi bi-geo-alt"></i> <?php echo $hotel['nama_kota']; ?>, <?php echo $hotel['nama_provinsi']; ?>
                            </p>
                            
                            <p class="card-text small text-muted">
                                <?php echo substr($hotel['deskripsi'], 0, 100); ?>...
                            </p>
                            
                            <?php if (!empty($hotel['fasilitas'])): ?>
                                <div class="mb-3">
                                    <?php 
                                    $fasilitas = parseFasilitas($hotel['fasilitas']);
                                    $displayed = array_slice($fasilitas, 0, 3);
                                    foreach ($displayed as $f): 
                                    ?>
                                        <span class="facilities-badge">
                                            <i class="bi bi-check-circle"></i> <?php echo $f; ?>
                                        </span>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($fasilitas) > 3): ?>
                                        <span class="facilities-badge">
                                            +<?php echo count($fasilitas) - 3; ?> lainnya
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($hotel['available_rooms'] > 0): ?>
                                        <small class="text-success">
                                            <i class="bi bi-check-circle-fill"></i> <?php echo $hotel['available_rooms']; ?> kamar tersedia
                                        </small>
                                    <?php else: ?>
                                        <small class="text-danger">
                                            <i class="bi bi-x-circle-fill"></i> Penuh
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <a href="hotel-detail.php?id=<?php echo $hotel['id_hotel']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    Lihat Detail <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 5rem; color: #ddd;"></i>
            <h4 class="mt-3 text-muted">Tidak ada hotel ditemukan</h4>
            <p class="text-muted">Coba ubah filter pencarian Anda</p>
        </div>
    <?php endif; ?>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
