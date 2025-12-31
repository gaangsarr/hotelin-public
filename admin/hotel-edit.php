<?php
require_once '../includes/functions.php';

requireAdmin();

// Get hotel_id from URL and validate
$hotel = requireHotelParam();
$hotel_id = $hotel['id_hotel'];

$conn = getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_hotel = cleanInput($_POST['nama_hotel']);
    $alamat = cleanInput($_POST['alamat_lengkap']);
    $id_kota = (int)$_POST['id_kota'];
    $deskripsi = cleanInput($_POST['deskripsi']);
    $rating = (int)$_POST['rating'];
    $phone = cleanInput($_POST['phone']);
    $email = cleanInput($_POST['email']);
    
    // Get fasilitas IDs from checkbox
    $fasilitas_ids = isset($_POST['fasilitas']) ? array_map('intval', $_POST['fasilitas']) : [];
    
    // Handle photo upload
    $foto_path = $hotel['foto_hotel'];
    if (isset($_FILES['foto_hotel']) && $_FILES['foto_hotel']['error'] === 0) {
        $upload = uploadFile($_FILES['foto_hotel'], UPLOAD_HOTEL);
        if ($upload['success']) {
            if (!empty($foto_path)) {
                deleteFile($foto_path);
            }
            $foto_path = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    if (empty($error)) {
        // Update hotel (no fasilitas column)
        $sql = "UPDATE hotel SET nama_hotel = ?, alamat_lengkap = ?, id_kota = ?, 
                deskripsi = ?, rating = ?, foto_hotel = ?, phone = ?, email = ?
                WHERE id_hotel = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisssssi", $nama_hotel, $alamat, $id_kota, $deskripsi, $rating, 
                          $foto_path, $phone, $email, $hotel_id);
        
        if ($stmt->execute()) {
            // Update fasilitas in separate table
            if (updateHotelFasilitas($hotel_id, $fasilitas_ids)) {
                $_SESSION['success_message'] = 'Hotel profile berhasil diupdate!';
                header('Location: ' . adminURL('hotel-edit.php', $hotel_id));
                exit();
            } else {
                $error = 'Hotel updated, tapi gagal update fasilitas!';
            }
        } else {
            $error = 'Update gagal! ' . $stmt->error;
        }
    }
}

// Get all cities
$cities = $conn->query("SELECT k.*, p.nama_provinsi FROM kota k JOIN provinsi p ON k.id_provinsi = p.id_provinsi ORDER BY k.nama_kota");

// Get all available fasilitas
$all_fasilitas = getAllFasilitas();

// Get selected fasilitas IDs for this hotel
$selected_fasilitas_ids = getHotelFasilitasIds($hotel_id);

$page_title = 'Edit Hotel Profile';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <!-- Hotel Info Alert -->
    <div class="alert alert-info mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-building-fill"></i> 
                <strong>Editing:</strong> <?php echo $hotel['nama_hotel']; ?>
                <span class="badge bg-primary ms-2"><?php echo $hotel['nama_kota']; ?></span>
            </div>
            <a href="<?php echo adminURL('dashboard.php', $hotel_id); ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-building"></i> Edit Hotel Profile</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo adminURL('hotel-edit.php', $hotel_id); ?>" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informasi Hotel</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Hotel <span class="text-danger">*</span></label>
                            <input type="text" name="nama_hotel" class="form-control" required 
                                   value="<?php echo htmlspecialchars($hotel['nama_hotel']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                            <textarea name="alamat_lengkap" class="form-control" rows="2" required><?php echo htmlspecialchars($hotel['alamat_lengkap']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kota <span class="text-danger">*</span></label>
                                <select name="id_kota" class="form-select" required>
                                    <?php while ($city = $cities->fetch_assoc()): ?>
                                        <option value="<?php echo $city['id_kota']; ?>" 
                                                <?php echo $hotel['id_kota'] == $city['id_kota'] ? 'selected' : ''; ?>>
                                            <?php echo $city['nama_kota']; ?>, <?php echo $city['nama_provinsi']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rating <span class="text-danger">*</span></label>
                                <select name="rating" class="form-select" required>
                                    <option value="1" <?php echo $hotel['rating'] == 1 ? 'selected' : ''; ?>>⭐ 1 Star</option>
                                    <option value="2" <?php echo $hotel['rating'] == 2 ? 'selected' : ''; ?>>⭐⭐ 2 Stars</option>
                                    <option value="3" <?php echo $hotel['rating'] == 3 ? 'selected' : ''; ?>>⭐⭐⭐ 3 Stars</option>
                                    <option value="4" <?php echo $hotel['rating'] == 4 ? 'selected' : ''; ?>>⭐⭐⭐⭐ 4 Stars</option>
                                    <option value="5" <?php echo $hotel['rating'] == 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ 5 Stars</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="4" placeholder="Deskripsikan hotel Anda..."><?php echo htmlspecialchars($hotel['deskripsi']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Telepon</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="phone" class="form-control" 
                                           placeholder="0361-1234567"
                                           value="<?php echo htmlspecialchars($hotel['phone']); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="info@hotel.com"
                                           value="<?php echo htmlspecialchars($hotel['email']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto Hotel</label>
                            <?php if (!empty($hotel['foto_hotel'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo BASE_URL . '/' . $hotel['foto_hotel']; ?>" 
                                         class="img-fluid rounded border" style="max-height: 250px;">
                                    <p class="small text-muted mt-1">
                                        <i class="bi bi-image"></i> Foto saat ini: <?php echo basename($hotel['foto_hotel']); ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning py-2 mb-2">
                                    <small><i class="bi bi-exclamation-triangle"></i> Belum ada foto hotel</small>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="foto_hotel" class="form-control" accept="image/*">
                            <small class="text-muted">Upload foto baru untuk mengganti foto lama. Format: JPG, PNG, GIF. Max 5MB</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bi bi-check2-square"></i> Fasilitas Hotel
                            <span class="badge bg-primary ms-2" id="selected-count"><?php echo count($selected_fasilitas_ids); ?></span>
                        </h6>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <small class="text-muted mb-3 d-block">Pilih fasilitas yang tersedia di hotel Anda:</small>
                        
                        <?php 
                        // Group by kategori
                        $grouped = [];
                        foreach ($all_fasilitas as $f) {
                            $grouped[$f['kategori']][] = $f;
                        }
                        
                        foreach ($grouped as $kategori => $items): 
                        ?>
                            <div class="mb-3">
                                <h6 class="text-<?php 
                                    echo $kategori == 'Essential' ? 'primary' : 
                                         ($kategori == 'Premium' ? 'success' : 'warning'); 
                                ?>">
                                    <?php echo $kategori; ?> Facilities
                                </h6>
                                <?php foreach ($items as $f): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input fasilitas-checkbox" 
                                               type="checkbox" 
                                               name="fasilitas[]" 
                                               value="<?php echo $f['id_fasilitas']; ?>" 
                                               id="fas_<?php echo $f['id_fasilitas']; ?>"
                                               <?php echo in_array($f['id_fasilitas'], $selected_fasilitas_ids) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="fas_<?php echo $f['id_fasilitas']; ?>">
                                            <i class="<?php echo $f['icon']; ?>"></i> <?php echo $f['nama_fasilitas']; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle"></i> Update Hotel
                        </button>
                        <a href="<?php echo adminURL('dashboard.php', $hotel_id); ?>" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Update counter when checkbox changed
document.querySelectorAll('.fasilitas-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateCount);
});

function updateCount() {
    const count = document.querySelectorAll('.fasilitas-checkbox:checked').length;
    document.getElementById('selected-count').textContent = count;
}
</script>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
