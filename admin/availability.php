<?php
require_once '../includes/functions.php';

requireAdmin();

// Get hotel_id from URL and validate
$hotel = requireHotelParam();
$hotel_id = $hotel['id_hotel'];

$conn = getConnection();

// Get date range (default: next 7 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+7 days'));

// Get all rooms for this hotel
$rooms = $conn->query("
    SELECT k.*, h.nama_hotel
    FROM kamar k
    JOIN hotel h ON k.id_hotel = h.id_hotel
    WHERE k.id_hotel = $hotel_id
    ORDER BY k.nomor_kamar
");

$page_title = 'Room Availability';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid my-4">
    <!-- Hotel Info -->
    <div class="alert alert-info mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-building-fill"></i> 
                <strong>Check Availability:</strong> <?php echo $hotel['nama_hotel']; ?>
                <span class="badge bg-primary ms-2"><?php echo $hotel['nama_kota']; ?></span>
            </div>
            <a href="<?php echo adminURL('dashboard.php', $hotel_id); ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-calendar-check"></i> Room Availability</h2>
    </div>
    
    <!-- Date Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-calendar-range"></i> Pilih Periode</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo adminURL('availability.php', $hotel_id); ?>" class="row g-3">
                <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
                <div class="col-md-5">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo $start_date; ?>" 
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo $end_date; ?>" 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Check
                    </button>
                </div>
            </form>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> 
                    Periode: <strong><?php echo date('d M Y', strtotime($start_date)); ?></strong> - 
                    <strong><?php echo date('d M Y', strtotime($end_date)); ?></strong>
                    (<?php echo ceil((strtotime($end_date) - strtotime($start_date)) / (60*60*24)); ?> hari)
                </small>
            </div>
        </div>
    </div>
    
    <!-- Availability Summary -->
    <div class="row mb-4">
        <?php
        $total_rooms = $rooms->num_rows;
        $available_count = 0;
        $booked_count = 0;
        
        // Count availability
        $rooms->data_seek(0);
        while ($room = $rooms->fetch_assoc()) {
            if (isRoomAvailable($room['id_kamar'], $start_date, $end_date) && $room['status'] == 'Available') {
                $available_count++;
            } else {
                $booked_count++;
            }
        }
        ?>
        
        <div class="col-md-4">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Rooms</h6>
                    <h3 class="mb-0"><?php echo $total_rooms; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white border-0">
                <div class="card-body">
                    <h6 class="mb-1">Available</h6>
                    <h3 class="mb-0"><?php echo $available_count; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white border-0">
                <div class="card-body">
                    <h6 class="mb-1">Not Available</h6>
                    <h3 class="mb-0"><?php echo $booked_count; ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Availability List -->
    <?php if ($rooms->num_rows > 0): ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Availability</th>
                                <th>Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rooms->data_seek(0);
                            while ($room = $rooms->fetch_assoc()): 
                            ?>
                                <?php
                                // Check if room is available in date range
                                $available = isRoomAvailable($room['id_kamar'], $start_date, $end_date);
                                
                                // Get bookings in date range
                                $bookings_query = $conn->query("
                                    SELECT p.*, u.nama_lengkap
                                    FROM pemesanan p
                                    JOIN pengguna u ON p.id_pengguna = u.id_pengguna
                                    WHERE p.id_kamar = {$room['id_kamar']}
                                    AND p.status_pesanan IN ('Pending', 'Confirmed')
                                    AND (
                                        (tanggal_checkin <= '$end_date' AND tanggal_checkout > '$start_date')
                                    )
                                    ORDER BY p.tanggal_checkin
                                ");
                                ?>
                                <tr>
                                    <td>
                                        <strong>Kamar #<?php echo $room['nomor_kamar']; ?></strong>
                                    </td>
                                    <td><?php echo $room['tipe_kamar']; ?></td>
                                    <td>
                                        <i class="bi bi-people"></i> <?php echo $room['kapasitas']; ?> orang
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $room['status'] == 'Available' ? 'success' : 
                                                 ($room['status'] == 'Maintenance' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo $room['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($available && $room['status'] == 'Available'): ?>
                                            <span class="text-success fw-bold">
                                                <i class="bi bi-check-circle-fill"></i> Available
                                            </span>
                                        <?php else: ?>
                                            <span class="text-danger fw-bold">
                                                <i class="bi bi-x-circle-fill"></i> Not Available
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($bookings_query->num_rows > 0): ?>
                                            <button class="btn btn-sm btn-outline-info" type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#bookings_<?php echo $room['id_kamar']; ?>">
                                                <i class="bi bi-list"></i> <?php echo $bookings_query->num_rows; ?> booking(s)
                                            </button>
                                            <div class="collapse mt-2" id="bookings_<?php echo $room['id_kamar']; ?>">
                                                <ul class="list-group list-group-sm">
                                                    <?php while ($booking = $bookings_query->fetch_assoc()): ?>
                                                        <li class="list-group-item small">
                                                            <strong><?php echo $booking['nama_lengkap']; ?></strong>
                                                            <br>
                                                            <i class="bi bi-calendar"></i> 
                                                            <?php echo date('d M', strtotime($booking['tanggal_checkin'])); ?> - 
                                                            <?php echo date('d M Y', strtotime($booking['tanggal_checkout'])); ?>
                                                            <span class="badge bg-<?php echo $booking['status_pesanan'] == 'Confirmed' ? 'success' : 'warning'; ?> ms-1">
                                                                <?php echo $booking['status_pesanan']; ?>
                                                            </span>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="bi bi-people"></i> <?php echo $booking['jumlah_tamu']; ?> tamu
                                                            </small>
                                                        </li>
                                                    <?php endwhile; ?>
                                                </ul>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                <i class="bi bi-dash-circle"></i> No bookings
                                            </span>
                                        <?php endif; ?>
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
                <h4 class="mt-3 text-muted">Belum ada kamar di hotel ini</h4>
                <p class="text-muted">Tambahkan kamar terlebih dahulu</p>
                <a href="<?php echo adminURL('room-add.php', $hotel_id); ?>" class="btn btn-success mt-3">
                    <i class="bi bi-plus-circle"></i> Tambah Kamar
                </a>
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
}

.list-group-sm .list-group-item {
    padding: 0.5rem 0.75rem;
}

.collapse {
    transition: all 0.3s ease;
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
