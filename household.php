<?php
session_start();
require_once 'auth_check.php';
require_once 'config.php';

// Get all households with their members
$query = "SELECT h.id, h.address, 
          CONCAT(head.first_name, ' ', head.last_name) as household_head,
          COUNT(r.id) as member_count
          FROM households h
          LEFT JOIN residents head ON h.household_head_id = head.id
          LEFT JOIN residents r ON h.id = r.household_id
          GROUP BY h.id";
$result = mysqli_query($conn, $query);

// Get household details if ID is provided
$household_members = [];
if (isset($_GET['id'])) {
    $household_id = mysqli_real_escape_string($conn, $_GET['id']);
    $member_query = "SELECT r.*, 
                     CASE WHEN r.id = h.household_head_id THEN 'Head' ELSE 'Member' END as role
                     FROM residents r
                     JOIN households h ON r.household_id = h.id
                     WHERE h.id = ?";
    $stmt = mysqli_prepare($conn, $member_query);
    mysqli_stmt_bind_param($stmt, "i", $household_id);
    mysqli_stmt_execute($stmt);
    $member_result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($member_result)) {
        $household_members[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Household Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/header.php'; ?>

            <div class="col-md-9 col-lg-10 main-content">
                <div class="row">
                    <div class="col-md-12">
                        <h2>Household Management</h2>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Households List</h5>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHouseholdModal">
                                    <i class="bi bi-plus"></i> Add Household
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Household Head</th>
                                                <th>Address</th>
                                                <th>Members Count</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['household_head']); ?></td>
                                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                                <td><?php echo $row['member_count']; ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-info btn-sm view-household" data-bs-toggle="modal" 
                                                            data-bs-target="#viewHouseholdModal" data-id="<?php echo $row['id']; ?>">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button type="button" class="btn btn-warning btn-sm" 
                                                            onclick="editHousehold(<?php echo $row['id']; ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm delete-household" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteHouseholdModal" 
                                                            data-id="<?php echo $row['id']; ?>"
                                                            data-head="<?php echo htmlspecialchars($row['household_head']); ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if (!empty($household_members)) : ?>
                                <div class="mt-4">
                                    <h4>Household Members</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Role</th>
                                                    <th>Gender</th>
                                                    <th>Birthdate</th>
                                                    <th>Civil Status</th>
                                                    <th>Contact</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($household_members as $member) : ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                                    <td><?php echo $member['role']; ?></td>
                                                    <td><?php echo htmlspecialchars($member['gender']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['birthdate']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['civil_status']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['contact_number']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Household Modal -->
    <div class="modal fade" id="addHouseholdModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Household</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addHouseholdForm">
                        <div class="mb-3">
                            <label for="household_head" class="form-label">Household Head</label>
                            <select class="form-select" name="household_head" id="household_head" required>
                                <option value="">Select Household Head</option>
                                <?php
                                // Query for household head (only those without household)
                                $head_query = "SELECT id, first_name, last_name FROM residents WHERE household_id IS NULL";
                                $head_result = mysqli_query($conn, $head_query);
                                while ($resident = mysqli_fetch_assoc($head_result)) {
                                    echo "<option value='" . $resident['id'] . "'>" . 
                                         htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="household_members" class="form-label">Household Members</label>
                            <select class="form-select" name="household_members[]" id="household_members" multiple>
                                <?php
                                // Query for members (all residents)
                                $members_query = "SELECT id, first_name, last_name, 
                                    CASE 
                                        WHEN household_id IS NOT NULL THEN CONCAT(first_name, ' ', last_name, ' (Has Household)') 
                                        ELSE CONCAT(first_name, ' ', last_name)
                                    END as display_name 
                                    FROM residents 
                                    WHERE id != COALESCE((SELECT household_head_id FROM households WHERE id = household_id), 0)
                                    ORDER BY household_id IS NULL DESC, first_name, last_name";
                                $members_result = mysqli_query($conn, $members_query);
                                while ($resident = mysqli_fetch_assoc($members_result)) {
                                    echo "<option value='" . $resident['id'] . "'>" . 
                                         htmlspecialchars($resident['display_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <div class="form-text">Hold Ctrl (Windows) or Command (Mac) to select multiple members. Members marked with "(Has Household)" are already part of another household.</div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="address" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Household</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Household Members Modal -->
    <div class="modal fade" id="viewHouseholdModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Household Members</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Gender</th>
                                    <th>Birthdate</th>
                                    <th>Civil Status</th>
                                    <th>Contact</th>
                                </tr>
                            </thead>
                            <tbody id="householdMembersBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Household Modal -->
    <div class="modal fade" id="editHouseholdModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Household</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editHouseholdForm">
                        <input type="hidden" id="edit_household_id" name="id">
                        <div class="mb-3">
                            <label for="edit_household_head" class="form-label">Household Head</label>
                            <select class="form-select" id="edit_household_head" name="household_head" required>
                                <?php
                                $residents_query = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM residents ORDER BY full_name";
                                $residents_result = mysqli_query($conn, $residents_query);
                                while ($resident = mysqli_fetch_assoc($residents_result)) {
                                    echo "<option value='" . $resident['id'] . "'>" . htmlspecialchars($resident['full_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="edit_address" name="address" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteHouseholdModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the household of <span id="deleteHouseholdHead"></span>?</p>
                    <p class="text-danger">This will remove all household associations for its members.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let deleteHouseholdId = null;

            // Add Household Form Submission
            $('#addHouseholdForm').on('submit', function(e) {
                e.preventDefault();
                
                // Disable the submit button to prevent double submission
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true);
                
                // Get form data
                const formData = new FormData(this);
                const serializedData = {};
                formData.forEach((value, key) => {
                    if (serializedData[key]) {
                        if (!Array.isArray(serializedData[key])) {
                            serializedData[key] = [serializedData[key]];
                        }
                        serializedData[key].push(value);
                    } else {
                        serializedData[key] = value;
                    }
                });
                
                console.log('Sending data:', serializedData); // Debug log
                
                $.ajax({
                    url: 'household_process.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        console.log('Response received:', response); // Debug log
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error creating household: ' + (response.message || 'Unknown error'));
                            submitBtn.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Ajax error:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText
                        });
                        
                        let errorMessage = 'Error creating household. ';
                        
                        if (xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage += response.message || error || 'Please try again.';
                            } catch (e) {
                                console.error('Parse error:', e);
                                console.log('Raw response:', xhr.responseText);
                                errorMessage += 'Server response error. Please try again.';
                            }
                        } else {
                            errorMessage += error || 'Please try again.';
                        }
                        
                        alert(errorMessage);
                        submitBtn.prop('disabled', false);
                    }
                });
            });

            // Clear form when modal is hidden
            $('#addHouseholdModal').on('hidden.bs.modal', function() {
                $('#addHouseholdForm')[0].reset();
                $('#addHouseholdForm').find('button[type="submit"]').prop('disabled', false);
            });

            // View Household Members
            $('.view-household').click(function() {
                const householdId = $(this).data('id');
                
                // Clear previous content
                $('#householdMembersBody').empty();
                
                // Show loading indicator
                $('#householdMembersBody').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');
                
                // Show the modal
                $('#viewHouseholdModal').modal('show');
                
                // Fetch household members
                $.ajax({
                    url: 'household_process.php',
                    type: 'GET',
                    data: {
                        action: 'view',
                        id: householdId
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        let html = '';
                        
                        if (data.length > 0) {
                            data.forEach(function(member) {
                                html += `
                                    <tr>
                                        <td>${member.first_name} ${member.last_name}</td>
                                        <td>${member.role}</td>
                                        <td>${member.gender}</td>
                                        <td>${member.birthdate}</td>
                                        <td>${member.civil_status}</td>
                                        <td>${member.contact_number}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            html = '<tr><td colspan="6" class="text-center">No members found</td></tr>';
                        }
                        
                        $('#householdMembersBody').html(html);
                    },
                    error: function() {
                        $('#householdMembersBody').html('<tr><td colspan="6" class="text-center text-danger">Error loading members</td></tr>');
                    }
                });
            });

            // Delete household button click
            $('.delete-household').click(function() {
                deleteHouseholdId = $(this).data('id');
                const householdHead = $(this).data('head');
                $('#deleteHouseholdHead').text(householdHead);
            });

            // Confirm delete button click
            $('#confirmDelete').click(function() {
                if (!deleteHouseholdId) {
                    alert('No household selected for deletion');
                    return;
                }

                const btn = $(this);
                btn.prop('disabled', true);
                
                $.ajax({
                    url: 'household_process.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        id: deleteHouseholdId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Delete response:', response);
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error deleting household: ' + (response.message || 'Unknown error'));
                            btn.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText
                        });
                        
                        let errorMessage = 'Error deleting household. ';
                        
                        if (xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage += response.message || error || 'Please try again.';
                            } catch (e) {
                                console.error('Parse error:', e);
                                console.log('Raw response:', xhr.responseText);
                                errorMessage += 'Server response error. Please try again.';
                            }
                        } else {
                            errorMessage += error || 'Please try again.';
                        }
                        
                        alert(errorMessage);
                        btn.prop('disabled', false);
                    }
                });
            });

            // Clear deleteHouseholdId when modal is hidden
            $('#deleteHouseholdModal').on('hidden.bs.modal', function() {
                deleteHouseholdId = null;
                $('#confirmDelete').prop('disabled', false);
            });

            // Edit Household
            window.editHousehold = function(id) {
                // Fetch household data
                $.ajax({
                    url: 'household_process.php',
                    type: 'GET',
                    data: {
                        action: 'get_household',
                        id: id
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        $('#edit_household_id').val(data.id);
                        $('#edit_household_head').val(data.household_head_id);
                        $('#edit_address').val(data.address);
                        $('#editHouseholdModal').modal('show');
                    },
                    error: function() {
                        alert('Error fetching household data');
                    }
                });
            };

            // Handle edit form submission
            $('#editHouseholdForm').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'household_process.php',
                    type: 'POST',
                    data: {
                        action: 'edit',
                        id: $('#edit_household_id').val(),
                        household_head: $('#edit_household_head').val(),
                        address: $('#edit_address').val()
                    },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            $('#editHouseholdModal').modal('hide');
                            location.reload(); // Reload to show updated data
                        } else {
                            alert(result.message || 'Error updating household');
                        }
                    },
                    error: function() {
                        alert('Error updating household');
                    }
                });
            });
        });
    </script>
</body>
</html>
