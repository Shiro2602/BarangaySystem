<?php
require_once 'auth_check.php';
require_once 'config.php';
require_once 'includes/config_maps.php';
require_once 'includes/permissions.php';

// Check if user has permission to view blotter
checkPermissionAndRedirect('view_blotter');

// Handle blotter record submission
if (isset($_POST['submit_blotter'])) {
    checkPermissionAndRedirect('create_blotter');
    $stmt = $conn->prepare("INSERT INTO blotter (complainant_id, complainee_id, incident_type, incident_date, 
                          incident_location, incident_details, latitude, longitude) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssdd",
        $_POST['complainant_id'],
        $_POST['complainee_id'],
        $_POST['incident_type'],
        $_POST['incident_date'],
        $_POST['incident_location'],
        $_POST['incident_details'],
        $_POST['latitude'],
        $_POST['longitude']
    );
    $stmt->execute();
}

// Handle status update
if (isset($_POST['update_status'])) {
    checkPermissionAndRedirect('change_blotter_status');
    $stmt = $conn->prepare("UPDATE blotter SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $_POST['status'], $_POST['blotter_id']);
    $stmt->execute();
    header("Location: blotter.php");
    exit();
}

// Get all blotter records
$query = "SELECT b.*, 
                     CONCAT(c.last_name, ', ', c.first_name) as complainant_name,
                     CONCAT(r.last_name, ', ', r.first_name) as complainee_name
                     FROM blotter b 
                     JOIN residents c ON b.complainant_id = c.id 
                     JOIN residents r ON b.complainee_id = r.id 
                     ORDER BY b.created_at DESC";
$result = $conn->query($query);
$blotters = $result->fetch_all(MYSQLI_ASSOC);

// Get residents for dropdown
$query = "SELECT id, CONCAT(last_name, ', ', first_name, ' ', middle_name) as full_name 
                     FROM residents ORDER BY last_name, first_name";
$result = $conn->query($query);
$residents = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blotter Records - Barangay Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
    <style>
        #locationMap {
            height: 300px;
            width: 100%;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        /* Ensure the modal is wide enough for the map */
        .modal-lg {
            max-width: 800px;
        }
        /* Ensure the map container is visible */
        .modal-body {
            padding: 1rem;
            max-height: 80vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/includes/header.php'; ?>

            <div class="col-md-9 col-lg-10 main-content">
                <div class="row mb-4">
                    <div class="col-12">
                        <h2>Blotter Records Management</h2>
                        <?php if (checkUserPermission('create_blotter')): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBlotterModal">
                            <i class="fas fa-plus"></i> Add Blotter Record
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Blotter Table -->
                <div class="table-responsive">
                    <table id="blotterTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Case #</th>
                                <th>Complainant</th>
                                <th>Complainee</th>
                                <th>Incident Type</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blotters as $blotter): ?>
                            <tr>
                                <td><?= $blotter['id'] ?></td>
                                <td><?= $blotter['complainant_name'] ?></td>
                                <td><?= $blotter['complainee_name'] ?></td>
                                <td><?= $blotter['incident_type'] ?></td>
                                <td><?= $blotter['incident_date'] ?></td>
                                <td><?= $blotter['incident_location'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $blotter['status'] == 'Resolved' ? 'success' : ($blotter['status'] == 'Dismissed' ? 'danger' : ($blotter['status'] == 'Ongoing' ? 'primary' : 'warning')) ?>">
                                        <?= $blotter['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (checkUserPermission('edit_blotter')): ?>
                                    <button class="btn btn-sm btn-info" title="View Details" data-bs-toggle="modal" 
                                            data-bs-target="#viewBlotterModal<?= $blotter['id'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (checkUserPermission('change_blotter_status')): ?>
                                    <button class="btn btn-sm btn-warning" title="Update Status" data-bs-toggle="modal" 
                                            data-bs-target="#updateStatusModal<?= $blotter['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- View Details Modal -->
                            <div class="modal fade" id="viewBlotterModal<?= $blotter['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Blotter Details - Case #<?= $blotter['id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Complainant</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['complainant_name']) ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Complainee</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['complainee_name']) ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Incident Type</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['incident_type']) ?>" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Incident Date</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['incident_date']) ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Location</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['incident_location']) ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['status']) ?>" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Incident Details</label>
                                                <textarea class="form-control" rows="4" readonly><?= htmlspecialchars($blotter['incident_details']) ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Date Filed</label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['created_at']) ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Update Status Modal -->
                            <div class="modal fade" id="updateStatusModal<?= $blotter['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Status - Case #<?= $blotter['id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="blotter_id" value="<?= $blotter['id'] ?>">
                                                <div class="mb-3">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control" required>
                                                        <option value="Pending" <?= $blotter['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Ongoing" <?= $blotter['status'] == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                                        <option value="Resolved" <?= $blotter['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                                        <option value="Dismissed" <?= $blotter['status'] == 'Dismissed' ? 'selected' : '' ?>>Dismissed</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Blotter Modal -->
    <div class="modal fade" id="addBlotterModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Blotter Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="blotterForm" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Complainant</label>
                                <select class="form-select" name="complainant_id" required>
                                    <option value="">Select Complainant</option>
                                    <?php foreach ($residents as $resident): ?>
                                        <option value="<?= $resident['id'] ?>"><?= $resident['full_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Complainee</label>
                                <select class="form-select" name="complainee_id" required>
                                    <option value="">Select Complainee</option>
                                    <?php foreach ($residents as $resident): ?>
                                        <option value="<?= $resident['id'] ?>"><?= $resident['full_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Incident Type</label>
                                <select class="form-select" name="incident_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Others">Others</option>
                                    <option value="Theft">Theft</option>
                                    <option value="Assault">Assault</option>
                                    <option value="Vandalism">Vandalism</option>
                                    <option value="Noise Complaint">Noise Complaint</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Incident Date</label>
                                <input type="datetime-local" class="form-control" name="incident_date" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Incident Location</label>
                            <input type="text" class="form-control" name="incident_location" id="incident_location" required>
                            <small class="text-muted">Click on the map to set the location</small>
                            <div id="locationMap"></div>
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="hidden" name="longitude" id="longitude">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Incident Details</label>
                            <textarea class="form-control" name="incident_details" rows="4" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="submit_blotter" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Define the coordinates from PHP
        const BARANGAY_LAT = <?php echo BARANGAY_LAT; ?>;
        const BARANGAY_LNG = <?php echo BARANGAY_LNG; ?>;
        let map;
        let marker;
        let geocoder;

        function initMap() {
            const labacCenter = { lat: BARANGAY_LAT, lng: BARANGAY_LNG };
            
            map = new google.maps.Map(document.getElementById('locationMap'), {
                center: labacCenter,
                zoom: 16,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true
            });

            geocoder = new google.maps.Geocoder();

            // Add click event to map
            map.addListener('click', function(e) {
                placeMarker(e.latLng);
            });
        }

        function placeMarker(location) {
            if (marker) {
                marker.setMap(null);
            }
            
            marker = new google.maps.Marker({
                position: location,
                map: map
            });

            // Update form fields
            document.getElementById('latitude').value = location.lat();
            document.getElementById('longitude').value = location.lng();
            
            // Reverse geocode to get address
            geocoder.geocode({ location: location }, (results, status) => {
                if (status === 'OK') {
                    if (results[0]) {
                        document.getElementById('incident_location').value = results[0].formatted_address;
                    }
                }
            });
        }

        $(document).ready(function() {
            $('#blotterTable').DataTable({
                order: [[0, 'desc']]
            });

            // Initialize map when modal is shown
            $('#addBlotterModal').on('shown.bs.modal', function () {
                if (!map) {
                    initMap();
                }
                // Trigger a resize event to ensure the map displays properly
                google.maps.event.trigger(map, 'resize');
                // Re-center the map
                map.setCenter({ lat: BARANGAY_LAT, lng: BARANGAY_LNG });
            });

            // Reset map marker when modal is closed
            $('#addBlotterModal').on('hidden.bs.modal', function () {
                if (marker) {
                    marker.setMap(null);
                }
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
                document.getElementById('incident_location').value = '';
            });
        });
    </script>
    
    <!-- Load Google Maps API after our script -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&callback=initMap" async defer></script>
</body>
</html>
