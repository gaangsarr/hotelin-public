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
    $nomor_kamar = cleanInput($_POST['nomor_kamar']);
    $tipe_kamar = cleanInput($_POST['tipe_kamar']);
    $harga_malam = (int)$_POST['harga_malam'];
    $kapasitas = (int)$_POST['kapasitas'];
    $deskripsi = cleanInput($_POST['deskripsi']);
    $status = cleanInput($_POST['status']);
    
    // Handle photo upload
    $foto_path = '';
    if (isset($_FILES['foto_kamar']) && $_FILES['foto_kamar']['error'] === 0) {
        $upload = uploadFile($_FILES['foto_kamar'], UPLOAD_ROOM);
        if ($upload['success']) {
            $foto_path = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    if (empty($error)) {
        // Check if room number already exists for this hotel
        $check = $conn->prepare("SELECT id_kamar FROM kamar WHERE id_hotel = ? AND nomor_kamar = ?");
        $check->bind_param("is", $hotel_id, $nomor_kamar);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = 'Nomor kamar sudah ada di hotel ini!';
        } else {
            $sql = "INSERT INTO kamar (id_hotel, nomor_kamar, tipe_kamar, harga_malam, kapasitas, deskripsi, status, foto_kamar) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssisss", $hotel_id, $nomor_kamar, $tipe_kamar, $harga_malam, $kapasitas, $deskripsi, $status, $foto_path);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Kamar baru berhasil ditambahkan!';
                header('Location: ' . adminURL('rooms.php', $hotel_id, ['added' => '1']));
                exit();
            } else {
                $error = 'Gagal menambahkan kamar! ' . $stmt->error;
            }
        }
    }
}

$page_title = 'Tambah Kamar Baru';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <!-- Hotel Info -->
    <div class="alert alert-info mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-building-fill"></i> 
                <strong>Tambah Kamar untuk:</strong> <?php echo $hotel['nama_hotel']; ?>
                <span class="badge bg-primary ms-2"><?php echo $hotel['nama_kota']; ?></span>
            </div>
            <a href="<?php echo adminURL('rooms.php', $hotel_id); ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Rooms
            </a>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-plus-circle"></i> Tambah Kamar Baru</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo adminURL('room-add.php', $hotel_id); ?>" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informasi Kamar</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Kamar <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_kamar" class="form-control" required placeholder="101">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipe Kamar <span class="text-danger">*</span></label>
                                <select name="tipe_kamar" class="form-select" required>
                                    <option value="">Pilih Tipe...</option>
                                    <option value="Standard">Standard</option>
                                    <option value="Deluxe">Deluxe</option>
                                    <option value="Suite">Suite</option>
                                    <option value="Presidential Suite">Presidential Suite</option>
                                    <option value="Family Room">Family Room</option>
                                    <option value="Connecting Room">Connecting Room</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Harga per Malam <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="harga_malam" class="form-control" required min="0" placeholder="500000">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kapasitas <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="kapasitas" class="form-control" required min="1" max="10" placeholder="2">
                                    <span class="input-group-text">orang</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="Available">Available</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="4" placeholder="Deskripsi fasilitas kamar..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto Kamar</label>
                            <input type="file" name="foto_kamar" class="form-control" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF. Max 5MB</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-info-circle-fill"></i> Informasi</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Hotel:</strong><br><?php echo $hotel['nama_hotel']; ?></p>
                        <p class="mb-2"><strong>Lokasi:</strong><br><?php echo $hotel['nama_kota']; ?></p>
                        <hr>
                        <p class="small text-muted mb-0">
                            <i class="bi bi-lightbulb"></i> Tips: Pastikan nomor kamar unik dan sesuai dengan denah hotel Anda.
                        </p>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> Tambah Kamar
                        </button>
                        <a href="<?php echo adminURL('rooms.php', $hotel_id); ?>" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
