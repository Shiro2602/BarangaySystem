<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'includes/permissions.php';

// Add Clearance
if (isset($_POST['add_clearance'])) {
    checkPermissionAndRedirect('create_clearance');
    $stmt = $conn->prepare("INSERT INTO clearances (resident_id, purpose, issue_date, expiry_date, or_number, amount, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssds",
        $_POST['resident_id'],
        $_POST['purpose'],
        $_POST['issue_date'],
        $_POST['expiry_date'],
        $_POST['or_number'],
        $_POST['amount'],
        $_POST['status']
    );
    $stmt->execute();
}

// Edit Clearance
if (isset($_POST['edit_clearance'])) {
    checkPermissionAndRedirect('edit_clearance');
    $stmt = $conn->prepare("UPDATE clearances SET 
        resident_id = ?, 
        purpose = ?, 
        issue_date = ?, 
        expiry_date = ?, 
        or_number = ?, 
        amount = ?, 
        status = ? 
        WHERE id = ?");
    $stmt->bind_param("issssdsi",
        $_POST['resident_id'],
        $_POST['purpose'],
        $_POST['issue_date'],
        $_POST['expiry_date'],
        $_POST['or_number'],
        $_POST['amount'],
        $_POST['status'],
        $_POST['clearance_id']
    );
    $stmt->execute();
}

// Delete Clearance
if (isset($_POST['delete_clearance'])) {
    checkPermissionAndRedirect('delete_clearance');
    $stmt = $conn->prepare("DELETE FROM clearances WHERE id = ?");
    $stmt->bind_param("i", $_POST['clearance_id']);
    $stmt->execute();
}

// Get all clearances with resident information
$query = "SELECT c.*, CONCAT(r.first_name, ' ', r.last_name) as resident_name 
          FROM clearances c 
          LEFT JOIN residents r ON c.resident_id = r.id 
          ORDER BY c.issue_date DESC";
$result = $conn->query($query);
$clearances = $result->fetch_all(MYSQLI_ASSOC);

// Get all residents for the dropdown
$residents_query = "SELECT 
    id,
    CONCAT(last_name, ', ', first_name, ' ', COALESCE(middle_name, '')) as full_name
    FROM residents 
    ORDER BY last_name, first_name";
$residents_result = $conn->query($residents_query);
$residents = $residents_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Clearance - Barangay System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .print-certificate {
            width: 8.5in;
            height: 11in;
            padding: 0.5in;
            margin: auto;
            border: 1px solid #ddd;
            display: none;
        }
        @media print {
            .print-certificate {
                display: block;
            }
            .no-print {
                display: none;
            }
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
                        <h2>Barangay Clearance Management</h2>
                        <?php if (checkUserPermission('create_clearance')): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestClearanceModal">
                            <i class="fas fa-plus"></i> Request Clearance
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Clearance Table -->
                <div class="table-responsive">
                    <table id="clearanceTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Purpose</th>
                                <th>Issue Date</th>
                                <th>Expiry Date</th>
                                <th>OR Number</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clearances as $clearance): ?>
                            <tr data-id="<?= $clearance['id'] ?>">
                                <td class="resident-name"><?= htmlspecialchars($clearance['resident_name']) ?></td>
                                <td class="purpose"><?= htmlspecialchars($clearance['purpose']) ?></td>
                                <td class="issue-date"><?= $clearance['issue_date'] ?></td>
                                <td><?= $clearance['expiry_date'] ?></td>
                                <td class="or-number"><?= htmlspecialchars($clearance['or_number']) ?></td>
                                <td class="amount"><?= number_format($clearance['amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $clearance['status'] == 'Approved' ? 'success' : 
                                        ($clearance['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= htmlspecialchars($clearance['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm view-clearance" 
                                            data-id="<?= $clearance['id'] ?>" 
                                            data-resident="<?= htmlspecialchars($clearance['resident_name']) ?>" 
                                            data-purpose="<?= htmlspecialchars($clearance['purpose']) ?>" 
                                            data-issue-date="<?= $clearance['issue_date'] ?>" 
                                            data-expiry-date="<?= $clearance['expiry_date'] ?>" 
                                            data-or-number="<?= htmlspecialchars($clearance['or_number']) ?>" 
                                            data-amount="<?= number_format($clearance['amount'], 2) ?>" 
                                            data-status="<?= htmlspecialchars($clearance['status']) ?>" 
                                            data-bs-toggle="modal" data-bs-target="#viewClearanceModal">
                                        <i class="fas fa-eye"></i> 
                                    </button>
                                    <?php if (checkUserPermission('edit_clearance')): ?>
                                    <button class="btn btn-sm btn-warning edit-clearance" data-id="<?= $clearance['id'] ?>" data-bs-toggle="modal" data-bs-target="#editClearanceModal" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (checkUserPermission('delete_clearance')): ?>
                                    <button class="btn btn-sm btn-danger delete-clearance" data-id="<?= $clearance['id'] ?>" data-bs-toggle="modal" data-bs-target="#deleteClearanceModal" title="Delete">
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

    <!-- Request Clearance Modal -->
    <div class="modal fade" id="requestClearanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Barangay Clearance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Resident</label>
                            <select name="resident_id" class="form-select select2" required>
                                <option value="">Search for resident...</option>
                                <?php foreach ($residents as $resident): ?>
                                    <option value="<?= htmlspecialchars($resident['id']) ?>">
                                        <?= htmlspecialchars($resident['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Purpose</label>
                            <textarea name="purpose" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label>OR Number</label>
                            <input type="text" name="or_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Amount</label>
                            <input type="number" name="amount" class="form-control" step="0.01" required>
                        </div>
                        <input type="hidden" name="status" value="Pending">
                        <div class="mb-3">
                            <label>Issue Date</label>
                            <input type="date" name="issue_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_clearance" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Clearance Modal -->
    <div class="modal fade" id="viewClearanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Clearance Details</h5>
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
                            <label class="form-label">Expiry Date</label>
                            <p class="view-expiry-date"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">OR Number</label>
                            <p class="view-or-number"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <p class="view-amount"></p>
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
                    <?php if (checkUserPermission('print_clearance')): ?>
                    <button type="button" class="btn btn-primary print-certificate">Print Certificate</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Clearance Modal -->
    <div class="modal fade" id="editClearanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Clearance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="clearance_id" id="edit_clearance_id">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Resident</label>
                                <select name="resident_id" id="edit_resident_id" class="form-select select2" required>
                                    <?php foreach ($residents as $resident): ?>
                                        <option value="<?= htmlspecialchars($resident['id']) ?>">
                                            <?= htmlspecialchars($resident['full_name']) ?>
                                        </option>
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
                                <label>Issue Date</label>
                                <input type="date" name="issue_date" id="edit_issue_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Expiry Date</label>
                                <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>OR Number</label>
                                <input type="text" name="or_number" id="edit_or_number" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Amount</label>
                                <input type="number" name="amount" id="edit_amount" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" id="edit_status" class="form-control" required>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="edit_clearance" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Clearance Modal -->
    <div class="modal fade" id="deleteClearanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Clearance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="clearance_id" id="delete_clearance_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this clearance? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_clearance" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Print Template (Hidden) -->
    <div id="certificateTemplate" class="print-certificate">
        <div class="certificate-header">
            <h4>Republic of the Philippines</h4>
            <h5>Province of Cavite</h5>
            <h5>Municipality of Naic</h5>
            <h5>Barangay Labac</h5>
            <h4 class="mt-4">OFFICE OF THE BARANGAY CHAIRMAN</h4>
            <h3 class="mt-4">BARANGAY CLEARANCE</h3>
        </div>
        
        <div class="certificate-body">
            <p class="mb-4">TO WHOM IT MAY CONCERN:</p>
            
            <p>This is to certify that <strong><span id="print-resident-name">_______________</span></strong>, of legal age, Filipino Citizen is a bonafide resident of this Barangay.</p>
            
            <p>This certification is being issued upon the request of the above-named person for <strong><span id="print-purpose">_______________</span></strong> purposes.</p>
            
            <p>Issued this <strong><span id="print-issue-date">_______________</span></strong> at the Barangay Hall of Labac.</p>
            
            <p class="mb-4">OR No.: <strong><span id="print-or-number">_______________</span></strong></p>
            <p>Amount: ₱<strong><span id="print-amount">_______________</span></strong></p>
        </div>
        
        <div class="certificate-footer">
            <div class="signature-line">
                Barangay Chairman
            </div>
        </div>
    </div>

    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#clearanceTable').DataTable({
                columnDefs: [
                    { targets: [1, 2, 3, 4, 5, 6, 7], orderable: false } // Disable sorting for all columns except resident name (index 0)
                ],
                order: [[0, 'asc']], // Sort by resident name by default
                language: {
                    search: "Search Clearance:"
                }
            });

            // Initialize Select2 for all select2 elements
            $('.select2').each(function() {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $(this).closest('.modal'),
                    placeholder: 'Search for resident...'
                });
            });

            // Reset form when modal is hidden
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
                $(this).find('.select2').val('').trigger('change');
            });

            // View Clearance
            $('.view-clearance').click(function() {
                var id = $(this).data('id');
                var resident = $(this).data('resident');
                var purpose = $(this).data('purpose');
                var issueDate = $(this).data('issue-date');
                var expiryDate = $(this).data('expiry-date');
                var orNumber = $(this).data('or-number');
                var amount = $(this).data('amount');
                var status = $(this).data('status');

                // Populate the view modal
                $('#viewClearanceModal .view-resident-name').text(resident);
                $('#viewClearanceModal .view-purpose').text(purpose);
                $('#viewClearanceModal .view-issue-date').text(issueDate);
                $('#viewClearanceModal .view-expiry-date').text(expiryDate);
                $('#viewClearanceModal .view-or-number').text(orNumber);
                $('#viewClearanceModal .view-amount').text(amount);
                $('#viewClearanceModal .view-status').text(status);
            });

            // Print certificate from view modal
            $('#viewClearanceModal .print-certificate').click(function() {
                var resident = $('#viewClearanceModal .view-resident-name').text();
                var purpose = $('#viewClearanceModal .view-purpose').text();
                var issueDate = $('#viewClearanceModal .view-issue-date').text();
                var expiryDate = $('#viewClearanceModal .view-expiry-date').text();
                var orNumber = $('#viewClearanceModal .view-or-number').text();
                var amount = $('#viewClearanceModal .view-amount').text();

                // Clone the certificate template
                var certificateContent = $('#certificateTemplate').clone();
                
                // Update the certificate content
                certificateContent.find('.resident-name').text(resident);
                certificateContent.find('.purpose').text(purpose);
                certificateContent.find('.issue-date').text(issueDate);
                certificateContent.find('.expiry-date').text(expiryDate);
                certificateContent.find('.or-number').text(orNumber);
                certificateContent.find('.amount').text(amount);

                // Create a new window for printing
                var printWindow = window.open('', '_blank');
                printWindow.document.write('<html><head><title>Clearance Certificate</title>');
                printWindow.document.write('<link rel="stylesheet" href="css/certificate.css">');
                printWindow.document.write('</head><body>');
                printWindow.document.write(certificateContent.html());
                printWindow.document.write('</body></html>');
                
                // Wait for CSS to load then print
                setTimeout(function() {
                    printWindow.print();
                    printWindow.close();
                }, 500);
            });

            // Delete confirmation
            $('.delete-clearance').click(function() {
                var id = $(this).data('id');
                $('#delete_clearance_id').val(id);
            });

            // Initialize select2 for resident selection
            $('.resident-select').select2({
                theme: 'bootstrap4',
                placeholder: 'Select a resident',
                width: '100%'
            });
        });
    </script>
</body>
</html>
