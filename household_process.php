<?php
session_start();
require_once 'auth_check.php';
require_once 'config.php';
require_once 'includes/permissions.php';

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'view') {
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
            
            $members = [];
            while ($row = mysqli_fetch_assoc($member_result)) {
                $members[] = $row;
            }
            
            echo json_encode($members);
            exit();
        } elseif ($_GET['action'] === 'get_household') {
            $household_id = mysqli_real_escape_string($conn, $_GET['id']);
            $query = "SELECT * FROM households WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $household_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $household = mysqli_fetch_assoc($result);
            
            echo json_encode($household);
            exit();
        }
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete':
            checkPermissionAndRedirect('delete_household');
            try {
                if (!isset($_POST['id']) || empty($_POST['id'])) {
                    throw new Exception('Household ID is required');
                }

                $household_id = mysqli_real_escape_string($conn, $_POST['id']);
                
                mysqli_begin_transaction($conn);
                
                // First, check if household exists
                $check_query = "SELECT id FROM households WHERE id = ?";
                $stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($stmt, "i", $household_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) === 0) {
                    throw new Exception('Household not found');
                }
                
                // Update all residents to remove their household_id
                $update_residents = "UPDATE residents SET household_id = NULL WHERE household_id = ?";
                $stmt = mysqli_prepare($conn, $update_residents);
                mysqli_stmt_bind_param($stmt, "i", $household_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Error updating residents: ' . mysqli_error($conn));
                }
                
                // Then delete the household
                $delete_query = "DELETE FROM households WHERE id = ?";
                $stmt = mysqli_prepare($conn, $delete_query);
                mysqli_stmt_bind_param($stmt, "i", $household_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Error deleting household: ' . mysqli_error($conn));
                }
                
                mysqli_commit($conn);
                echo json_encode(['success' => true, 'message' => 'Household deleted successfully']);
                
            } catch (Exception $e) {
                if (isset($conn) && mysqli_ping($conn)) {
                    mysqli_rollback($conn);
                }
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();
        case 'edit':
            checkPermissionAndRedirect('edit_household');
            $household_id = mysqli_real_escape_string($conn, $_POST['id']);
            $household_head_id = mysqli_real_escape_string($conn, $_POST['household_head']);
            $address = mysqli_real_escape_string($conn, $_POST['address']);

            mysqli_begin_transaction($conn);

            try {
                // Update household information
                $update_query = "UPDATE households SET household_head_id = ?, address = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "isi", $household_head_id, $address, $household_id);
                mysqli_stmt_execute($stmt);

                // Update the new household head's household_id
                $update_resident = "UPDATE residents SET household_id = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_resident);
                mysqli_stmt_bind_param($stmt, "ii", $household_id, $household_head_id);
                mysqli_stmt_execute($stmt);

                mysqli_commit($conn);
                echo json_encode(['success' => true]);
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit();
            }
        default:
            if (isset($_POST['household_head']) && isset($_POST['address'])) {
                checkPermissionAndRedirect('create_household');
                // Handle adding new household
                $response = array();
                
                try {
                    if (empty($_POST['household_head'])) {
                        throw new Exception('Household head is required');
                    }
                    
                    if (empty($_POST['address'])) {
                        throw new Exception('Address is required');
                    }

                    $household_head_id = mysqli_real_escape_string($conn, $_POST['household_head']);
                    $address = mysqli_real_escape_string($conn, $_POST['address']);

                    mysqli_begin_transaction($conn);

                    // Check if household head is already assigned
                    $check_head = "SELECT household_id FROM residents WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $check_head);
                    mysqli_stmt_bind_param($stmt, "i", $household_head_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $resident = mysqli_fetch_assoc($result);

                    if ($resident && !is_null($resident['household_id'])) {
                        throw new Exception('Selected resident is already part of a household');
                    }

                    // Create new household
                    $query = "INSERT INTO households (household_head_id, address) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "is", $household_head_id, $address);
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Error creating household: ' . mysqli_error($conn));
                    }
                    
                    $household_id = mysqli_insert_id($conn);

                    // Update the household head's household_id
                    $update_query = "UPDATE residents SET household_id = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($stmt, "ii", $household_id, $household_head_id);
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Error updating household head: ' . mysqli_error($conn));
                    }

                    // Update household members if any are selected
                    if (isset($_POST['household_members']) && is_array($_POST['household_members'])) {
                        $update_members = "UPDATE residents SET household_id = ? WHERE id = ? AND id != ?";
                        $stmt = mysqli_prepare($conn, $update_members);
                        
                        foreach ($_POST['household_members'] as $member_id) {
                            if ($member_id != $household_head_id) {
                                $member_id = mysqli_real_escape_string($conn, $member_id);
                                mysqli_stmt_bind_param($stmt, "iii", $household_id, $member_id, $household_head_id);
                                
                                if (!mysqli_stmt_execute($stmt)) {
                                    throw new Exception('Error updating household member: ' . mysqli_error($conn));
                                }
                            }
                        }
                    }

                    mysqli_commit($conn);
                    echo json_encode(['success' => true, 'message' => 'Household created successfully']);
                    
                } catch (Exception $e) {
                    if (isset($conn) && mysqli_ping($conn)) {
                        mysqli_rollback($conn);
                    }
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                exit();
            }
    }
}
?>
