<?php
session_start();
require_once 'auth_check.php';
require_once 'config.php';

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
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
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
    } elseif (isset($_POST['household_head']) && isset($_POST['address'])) {
        // Handle adding new household
        $household_head_id = mysqli_real_escape_string($conn, $_POST['household_head']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);

        mysqli_begin_transaction($conn);

        try {
            // Create new household
            $query = "INSERT INTO households (household_head_id, address) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "is", $household_head_id, $address);
            mysqli_stmt_execute($stmt);
            
            $household_id = mysqli_insert_id($conn);

            // Update the household head's household_id
            $update_query = "UPDATE residents SET household_id = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ii", $household_id, $household_head_id);
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            $_SESSION['success'] = "Household created successfully!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = "Error creating household: " . $e->getMessage();
        }
        
        header('Location: household.php');
        exit();
    }
}
