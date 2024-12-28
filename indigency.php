<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'includes/permissions.php';

// Add Indigency
if (isset($_POST['add_indigency'])) {
    checkPermissionAndRedirect('create_indigency');
    $status = 'Pending'; // Set default status to Pending
    $expiry_date = null; // Initialize expiry_date as null
    $issue_date = date('Y-m-d'); // Set current date as issue date
    
    $stmt = $conn->prepare("INSERT INTO indigency (resident_id, purpose, issue_date, or_number, status, expiry_date) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss",
        $_POST['resident_id'],
        $_POST['purpose'],
        $issue_date,  // Use automatically generated issue date
        $_POST['or_number'],
        $status,
        $expiry_date
    );
    $stmt->execute();
}

// Edit Indigency
if (isset($_POST['edit_indigency'])) {
    checkPermissionAndRedirect('edit_indigency');
    
    // Get the original issue date
    $stmt = $conn->prepare("SELECT issue_date FROM indigency WHERE id = ?");
    $stmt->bind_param("i", $_POST['indigency_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $issue_date = $row['issue_date'];
    
    // Calculate expiry date if status is Approved
    $expiry_date = null;
    if ($_POST['status'] === 'Approved') {
        $issue_date_obj = new DateTime($issue_date);
        $expiry_date = $issue_date_obj->modify('+6 months')->format('Y-m-d');
    }

    $stmt = $conn->prepare("UPDATE indigency SET 
        resident_id = ?, 
        purpose = ?, 
        or_number = ?, 
        status = ?,
        expiry_date = ?
        WHERE id = ?");
    $stmt->bind_param("issssi",
        $_POST['resident_id'],
        $_POST['purpose'],
        $_POST['or_number'],
        $_POST['status'],
        $expiry_date,
        $_POST['indigency_id']
    );
    $stmt->execute();
}

// Delete Indigency
if (isset($_POST['delete_indigency'])) {
    checkPermissionAndRedirect('delete_indigency');
    $stmt = $conn->prepare("DELETE FROM indigency WHERE id = ?");
    $stmt->bind_param("i", $_POST['indigency_id']);
    $stmt->execute();
}

// Get all residents for the dropdown
$residents_query = "SELECT id, first_name, middle_name, last_name FROM residents ORDER BY last_name, first_name";
$residents_result = $conn->query($residents_query);
$residents = [];
while ($row = $residents_result->fetch_assoc()) {
    $residents[] = $row;
}

// Get all indigency certificates with resident information
$query = "SELECT i.*, 
          CONCAT(r.first_name, ' ', r.last_name) as full_name,
          r.civil_status,
          r.address,
          TIMESTAMPDIFF(YEAR, r.birthdate, CURDATE()) as age
          FROM indigency i 
          LEFT JOIN residents r ON i.resident_id = r.id 
          ORDER BY i.issue_date DESC";
$result = $conn->query($query);
$certificates = [];
while ($row = $result->fetch_assoc()) {
    $certificates[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indigency Certificates - Barangay Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link href="css/print.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .main-content {
            padding: 20px;
        }
        .select2-container {
            width: 100% !important;
        }
        /* Select2 Custom Styles */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }
        .select2-container--bootstrap-5 .select2-selection--single {
            height: 38px;
            padding: 0;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding: 5px 12px;
            line-height: 26px;
            color: #212529;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 36px;
            position: absolute;
            right: 3px;
            width: 20px;
        }
        .select2-container--bootstrap-5 .select2-dropdown .select2-results__option {
            padding: 6px 12px;
        }
        .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            padding: 6px 12px;
            border: 1px solid #ced4da;
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
                        <h2>Indigency Certificates</h2>
                        <?php if (checkUserPermission('create_indigency')): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIndigencyModal">
                            <i class="fas fa-plus"></i> Request Indigency
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Indigency Table -->
                <div class="table-responsive">
                    <table id="indigencyTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Purpose</th>
                                <th>Issue Date</th>
                                <th>Expiry Date</th>
                                <th>OR Number</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $indigency): ?>
                            <tr>
                                <td><?= htmlspecialchars($indigency['full_name']) ?></td>
                                <td><?= htmlspecialchars($indigency['purpose']) ?></td>
                                <td><?= htmlspecialchars($indigency['issue_date']) ?></td>
                                <td><?= $indigency['expiry_date'] ? htmlspecialchars($indigency['expiry_date']) : 'N/A' ?></td>
                                <td><?= htmlspecialchars($indigency['or_number']) ?></td>
                                <td>
                                    <span class="badge <?= $indigency['status'] === 'Approved' ? 'bg-success' : ($indigency['status'] === 'Rejected' ? 'bg-danger' : 'bg-warning') ?>">
                                        <?= htmlspecialchars($indigency['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-info btn-sm view-indigency" data-id="<?= $indigency['id'] ?>" 
                                            data-resident="<?= htmlspecialchars($indigency['full_name']) ?>" 
                                            data-purpose="<?= htmlspecialchars($indigency['purpose']) ?>" 
                                            data-issue-date="<?= $indigency['issue_date'] ?>" 
                                            data-or-number="<?= htmlspecialchars($indigency['or_number']) ?>" 
                                            data-status="<?= htmlspecialchars($indigency['status']) ?>" 
                                            data-bs-toggle="modal" data-bs-target="#viewIndigencyModal">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (checkUserPermission('print_indigency')): ?>
                                    <button type="button" class="btn btn-sm btn-info print-docx"
                                            data-resident="<?= htmlspecialchars($indigency['full_name']) ?>" 
                                            data-age="<?= $indigency['age'] ?>"
                                            data-civil-status="<?= htmlspecialchars($indigency['civil_status']) ?>"
                                            data-address="<?= htmlspecialchars($indigency['address']) ?>"
                                            data-purpose="<?= htmlspecialchars($indigency['purpose']) ?>"
                                            data-issue-date="<?= $indigency['issue_date'] ?>">
                                        <i class="fas fa-file-word"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (checkUserPermission('edit_indigency')): ?>
                                    <button class="btn btn-sm btn-warning edit-indigency" 
                                            data-id="<?= $indigency['id'] ?>" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editIndigencyModal" 
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (checkUserPermission('delete_indigency')): ?>
                                    <button class="btn btn-sm btn-danger delete-indigency" 
                                            data-id="<?= $indigency['id'] ?>" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteIndigencyModal" 
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Indigency Modal -->
    <div class="modal fade" id="addIndigencyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Indigency Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="resident_id" class="form-label">Resident</label>
                            <select class="form-select resident-select" name="resident_id" required>
                                <option value="">Select Resident</option>
                                <?php foreach ($residents as $resident): ?>
                                    <option value="<?= $resident['id'] ?>">
                                        <?= htmlspecialchars($resident['first_name'] . ' ' . ($resident['middle_name'] ? $resident['middle_name'] . ' ' : '') . $resident['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="purpose" class="form-label">Purpose</label>
                            <input type="text" class="form-control" name="purpose" required>
                        </div>
                        <div class="mb-3">
                            <label for="or_number" class="form-label">OR Number</label>
                            <input type="text" class="form-control" name="or_number" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_indigency" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Indigency Modal -->
    <div class="modal fade" id="editIndigencyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Indigency Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="indigency_id" id="edit_indigency_id">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Resident</label>
                                <select name="resident_id" id="edit_resident_id" class="form-control" required>
                                    <?php foreach ($residents as $resident): ?>
                                        <option value="<?= $resident['id'] ?>"><?= $resident['first_name'] . ' ' . ($resident['middle_name'] ? $resident['middle_name'] . ' ' : '') . $resident['last_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Purpose</label>
                                <input type="text" name="purpose" id="edit_purpose" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>OR Number</label>
                                <input type="text" name="or_number" id="edit_or_number" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Status</label>
                                <select name="status" id="edit_status" class="form-control" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="edit_indigency" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Indigency Modal -->
    <div class="modal fade" id="deleteIndigencyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Indigency Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="indigency_id" id="delete_indigency_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this indigency certificate? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_indigency" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Indigency Modal -->
    <div class="modal fade" id="viewIndigencyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Indigency Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Resident Name</label>
                            <p class="view-resident-name"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purpose</label>
                            <p class="view-purpose"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Issue Date</label>
                            <p class="view-issue-date"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">OR Number</label>
                            <p class="view-or-number"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <p class="view-status"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <?php if (checkUserPermission('print_indigency')): ?>
                    <button type="button" class="btn btn-primary print-certificate">Print Certificate</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Template -->
    <div id="certificateTemplate" class="print-certificate">
        <div class="certificate-header">
            <h4>Republic of the Philippines</h4>
            <h5>Province of Cavite</h5>
            <h5>Municipality of Naic</h5>
            <h5>Barangay Labac</h5>
            <h4 class="mt-4">OFFICE OF THE BARANGAY CHAIRMAN</h4>
            <h3 class="mt-4">CERTIFICATE OF INDIGENCY</h3>
        </div>
        
        <div class="certificate-body">
            <p class="mb-4">TO WHOM IT MAY CONCERN:</p>
            
            <p>This is to certify that <strong><span id="print-resident-name">_______________</span></strong>, of legal age, Filipino Citizen is a bonafide resident of this Barangay and belongs to the indigent family in this Barangay.</p>
            
            <p>This certification is being issued upon the request of the above-named person for <strong><span id="print-purpose">_______________</span></strong> purposes.</p>
            
            <p>Issued this <strong><span id="print-issue-date">_______________</span></strong> at the Barangay Hall of Labac.</p>
            
            <p class="mb-4">OR No.: <strong><span id="print-or-number">_______________</span></strong></p>
        </div>
        
        <div class="certificate-footer">
            <div class="signature-line">
                Barangay Chairman
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#indigencyTable').DataTable({
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: [1, 2, 3, 4, 5], orderable: false }  // Disable sorting for all columns except Resident (index 0)
                ]
            });

            // Initialize Select2
            $('.resident-select').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // View Indigency
            $('.view-indigency').click(function() {
                var id = $(this).data('id');
                var resident = $(this).data('resident');
                var purpose = $(this).data('purpose');
                var issueDate = $(this).data('issue-date');
                var orNumber = $(this).data('or-number');
                var status = $(this).data('status');

                $('#viewIndigencyModal .view-resident-name').text(resident);
                $('#viewIndigencyModal .view-purpose').text(purpose);
                $('#viewIndigencyModal .view-issue-date').text(issueDate);
                $('#viewIndigencyModal .view-or-number').text(orNumber);
                $('#viewIndigencyModal .view-status').text(status);
            });

            // Print DOCX certificate
            $('.print-docx').click(function() {
                var data = {
                    resident_name: $(this).data('resident'),
                    age: $(this).data('age'),
                    civil_status: $(this).data('civil-status'),
                    address: $(this).data('address'),
                    purpose: $(this).data('purpose'),
                    issue_date: $(this).data('issue-date')
                };

                // Send AJAX request to process_indigency_docx.php
                $.ajax({
                    url: 'process_indigency_docx.php',
                    type: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    success: function(response) {
                        if (response.file) {
                            window.location.href = 'download_indigency.php?file=' + response.file;
                        } else if (response.error) {
                            alert('Error: ' + response.error);
                        } else {
                            alert('Unknown error occurred');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error: ' + error);
                    }
                });
            });

            // Reset form when modal is hidden
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
                $(this).find('.select2').val('').trigger('change');
            });

            // Edit Indigency
            $('.edit-indigency').click(function() {
                var id = $(this).data('id');
                var row = $(this).closest('tr');
                
                // Get data from the table row (updated indices)
                var resident_name = row.find('td:eq(0)').text();
                var purpose = row.find('td:eq(1)').text();
                var or_number = row.find('td:eq(4)').text();  // Updated index
                var status = row.find('td:eq(5)').text().trim();  // Updated index
                
                // Set the values in the edit modal
                $('#edit_indigency_id').val(id);
                
                // Find and select the resident in the dropdown
                var $residentSelect = $('#edit_resident_id');
                $residentSelect.find('option').each(function() {
                    if ($(this).text().trim() === resident_name.trim()) {
                        $residentSelect.val($(this).val());
                        return false;
                    }
                });
                
                $('#edit_purpose').val(purpose);
                $('#edit_or_number').val(or_number);
                $('#edit_status').val(status);
            });

            // Delete Indigency
            $('.delete-indigency').click(function() {
                var id = $(this).data('id');
                $('#delete_indigency_id').val(id);
            });
        });
    </script>
</body>
</html>
