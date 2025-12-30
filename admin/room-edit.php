<?php
require_once '../includes/functions.php';

requireAdmin();

// Get hotel_id from URL and validate
$hotel = requireHotelParam();
$hotel_id = $hotel['id_hotel'];

// Get room_id
$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($room_id <= 0) {
    $_SESSION['error_message'] = 'Kamar tidak ditemukan!';
    header('Location: ' . adminURL('rooms.php', $hotel_id));
    exit();
}

$conn = getConnection();

// Get room details - VERIFY belongs to selected hotel
$stmt = $conn->prepare("
    SELECT k.*, h.nama_hotel 
    FROM kamar k 
    JOIN hotel h ON k.id_hotel = h.id_hotel 
    WHERE k.id_kamar = ? AND k.id_hotel = ?
");
$stmt->bind_param("ii", $room_id, $hotel_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    $_SESSION['error_message'] = 'Kamar tidak ditemukan atau bukan milik hotel ini!';
    header('Location: ' . adminURL('rooms.php', $hotel_id));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor_kamar = cleanInput($_POST['nomor_kamar']);
    $tipe_kamar = cleanInput($_POST['tipe_kamar']);
    $harga_malam = (int)$_POST['harga_malam'];
    $kapasitas = (int)$_POST['kapasitas'];
    $deskripsi = cleanInput($_POST['deskripsi']);
    $status = cleanInput($_POST['status']);
    
    // Handle photo upload
    $foto_path = $room['foto_kamar'];
    if (isset($_FILES['foto_kamar']) && $_FILES['foto_kamar']['error'] === 0) {
        $upload = uploadFile($_FILES['foto_kamar'], UPLOAD_ROOM);
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
        // Check if room number already exists for this hotel (exclude current room)
        $check = $conn->prepare("SELECT id_kamar FROM kamar WHERE id_hotel = ? AND nomor_kamar = ? AND id_kamar != ?");
        $check->bind_param("isi", $hotel_id, $nomor_kamar, $room_id);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = 'Nomor kamar sudah digunakan kamar lain!';
        } else {
            $sql = "UPDATE kamar SET nomor_kamar = ?, tipe_kamar = ?, harga_malam = ?, kapasitas = ?, 
                    deskripsi = ?, status = ?, foto_kamar = ? 
                    WHERE id_kamar = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssissssi", $nomor_kamar, $tipe_kamar, $harga_malam, $kapasitas, 
                              $deskripsi, $status, $foto_path, $room_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Kamar berhasil diupdate!';
                header('Location: ' . adminURL('rooms.php', $hotel_id, ['updated' => '1']));
                exit();
            } else {
                $error = 'Update gagal! ' . $stmt->error;
            }
        }
    }
}

$page_title = 'Edit Kamar';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <!-- Hotel Info -->
    <div class="alert alert-info mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-building-fill"></i> 
                <strong>Edit Kamar:</strong> <?php echo $hotel['nama_hotel']; ?> - Kamar #<?php echo $room['nomor_kamar']; ?>
                <span class="badge bg-primary ms-2"><?php echo $hotel['nama_kota']; ?></span>
            </div>
            <a href="<?php echo adminURL('rooms.php', $hotel_id); ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Rooms
            </a>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil"></i> Edit Kamar #<?php echo $room['nomor_kamar']; ?></h2>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo adminURL('room-edit.php', $hotel_id, ['id' => $room_id]); ?>" enctype="multipart/form-data">
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
                                <input type="text" name="nomor_kamar" class="form-control" required 
                                       value="<?php echo htmlspecialchars($room['nomor_kamar']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipe Kamar <span class="text-danger">*</span></label>
                                <select name="tipe_kamar" class="form-select" required>
                                    <option value="Standard" <?php echo $room['tipe_kamar'] == 'Standard' ? 'selected' : ''; ?>>Standard</option>
                                    <option value="Deluxe" <?php echo $room['tipe_kamar'] == 'Deluxe' ? 'selected' : ''; ?>>Deluxe</option>
                                    <option value="Suite" <?php echo $room['tipe_kamar'] == 'Suite' ? 'selected' : ''; ?>>Suite</option>
                                    <option value="Presidential Suite" <?php echo $room['tipe_kamar'] == 'Presidential Suite' ? 'selected' : ''; ?>>Presidential Suite</option>
                                    <option value="Family Room" <?php echo $room['tipe_kamar'] == 'Family Room' ? 'selected' : ''; ?>>Family Room</option>
                                    <option value="Connecting Room" <?php echo $room['tipe_kamar'] == 'Connecting Room' ? 'selected' : ''; ?>>Connecting Room</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Harga per Malam <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="harga_malam" class="form-control" required min="0" 
                                           value="<?php echo $room['harga_malam']; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kapasitas <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="kapasitas" class="form-control" required min="1" max="10" 
                                           value="<?php echo $room['kapasitas']; ?>">
                                    <span class="input-group-text">orang</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="Available" <?php echo $room['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                <option value="Booked" <?php echo $room['status'] == 'Booked' ? 'selected' : ''; ?>>Booked</option>
                                <option value="Maintenance" <?php echo $room['status'] == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="4"><?php echo htmlspecialchars($room['deskripsi']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto Kamar</label>
                            <?php if (!empty($room['foto_kamar'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo BASE_URL . '/' . $room['foto_kamar']; ?>" 
                                         class="img-fluid rounded border" style="max-height: 250px;">
                                    <p class="small text-muted mt-1">
                                        <i class="bi bi-image"></i> Foto saat ini: <?php echo basename($room['foto_kamar']); ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning py-2 mb-2">
                                    <small><i class="bi bi-exclamation-triangle"></i> Belum ada foto kamar</small>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="foto_kamar" class="form-control" accept="image/*">
                            <small class="text-muted">Upload foto baru untuk mengganti foto lama. Format: JPG, PNG, GIF. Max 5MB</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-info-circle-fill"></i> Informasi Kamar</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Hotel:</strong><br><?php echo $hotel['nama_hotel']; ?></p>
                        <p class="mb-2"><strong>Kamar ID:</strong><br>#<?php echo $room['id_kamar']; ?></p>
                        <p class="mb-2"><strong>Dibuat:</strong><br><?php echo date('d M Y H:i', strtotime($room['created_at'])); ?></p>
                        <p class="mb-2"><strong>Update Terakhir:</strong><br><?php echo date('d M Y H:i', strtotime($room['updated_at'])); ?></p>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle"></i> Update Kamar
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
