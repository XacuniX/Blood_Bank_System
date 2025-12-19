<?php
/**
 * Audit Logger
 * Tracks all database changes (INSERT, UPDATE, DELETE) to audit_log table
 */

/**
 * Log an action to the audit_log table
 * 
 * @param mysqli $conn Database connection
 * @param int|null $staff_id Staff ID performing the action (null if system/donor/hospital)
 * @param string $action_type Type of action (INSERT, UPDATE, DELETE, LOGIN, LOGOUT, etc.)
 * @param string $description Detailed description of what happened
 * @return bool Success status
 */
function log_audit($conn, $staff_id, $action_type, $description) {
    // If staff_id is null, try to get from session
    if ($staff_id === null && isset($_SESSION['staff_id'])) {
        $staff_id = $_SESSION['staff_id'];
    }
    
    $stmt = $conn->prepare("INSERT INTO audit_log (Staff_ID, Action_Type, Description, Timestamp) 
                            VALUES (?, ?, ?, NOW())");
    
    if ($stmt) {
        $stmt->bind_param("iss", $staff_id, $action_type, $description);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

/**
 * Log INSERT action
 */
function log_insert($conn, $staff_id, $table, $record_id, $details = '') {
    $action = "INSERT";
    $description = "Inserted new record into {$table}. ID: {$record_id}";
    if (!empty($details)) {
        $description .= ". Details: {$details}";
    }
    return log_audit($conn, $staff_id, $action, $description);
}

/**
 * Log UPDATE action
 */
function log_update($conn, $staff_id, $table, $record_id, $changes) {
    $action = "UPDATE";
    $description = "Updated {$table} record ID: {$record_id}. Changes: {$changes}";
    return log_audit($conn, $staff_id, $action, $description);
}

/**
 * Log DELETE action
 */
function log_delete($conn, $staff_id, $table, $record_id, $details = '') {
    $action = "DELETE";
    $description = "Deleted record from {$table}. ID: {$record_id}";
    if (!empty($details)) {
        $description .= ". Details: {$details}";
    }
    return log_audit($conn, $staff_id, $action, $description);
}

/**
 * Log LOGIN action
 */
function log_login($conn, $user_type, $user_id, $username) {
    $action = "LOGIN";
    $description = "{$user_type} login: {$username} (ID: {$user_id})";
    
    // For non-staff logins, staff_id is null
    $staff_id = ($user_type === 'Staff') ? $user_id : null;
    
    return log_audit($conn, $staff_id, $action, $description);
}

/**
 * Log LOGOUT action
 */
function log_logout($conn, $user_type, $user_id, $username) {
    $action = "LOGOUT";
    $description = "{$user_type} logout: {$username} (ID: {$user_id})";
    
    // For non-staff logins, staff_id is null
    $staff_id = ($user_type === 'Staff') ? $user_id : null;
    
    return log_audit($conn, $staff_id, $action, $description);
}

/**
 * Log blood unit status change
 */
function log_blood_status_change($conn, $staff_id, $unit_id, $old_status, $new_status) {
    $action = "UPDATE";
    $description = "Blood unit {$unit_id} status changed from '{$old_status}' to '{$new_status}'";
    return log_audit($conn, $staff_id, $action, $description);
}

/**
 * Log blood request action
 */
function log_blood_request($conn, $hospital_id, $blood_group, $quantity, $urgency) {
    $action = "INSERT";
    $description = "Hospital ID {$hospital_id} requested {$quantity} unit(s) of {$blood_group} blood. Urgency: {$urgency}";
    return log_audit($conn, null, $action, $description);
}

/**
 * Log blood request status change
 */
function log_request_status_change($conn, $staff_id, $request_id, $old_status, $new_status) {
    $action = "UPDATE";
    $description = "Blood request {$request_id} status changed from '{$old_status}' to '{$new_status}'";
    return log_audit($conn, $staff_id, $action, $description);
}

/**
 * Log donor registration
 */
function log_donor_registration($conn, $donor_id, $name, $blood_group) {
    $action = "INSERT";
    $description = "New donor registered: {$name} (ID: {$donor_id}, Blood Group: {$blood_group})";
    return log_audit($conn, null, $action, $description);
}

/**
 * Log donor profile update
 */
function log_donor_update($conn, $donor_id, $field, $old_value, $new_value) {
    $action = "UPDATE";
    $description = "Donor {$donor_id} updated {$field} from '{$old_value}' to '{$new_value}'";
    return log_audit($conn, null, $action, $description);
}

/**
 * Get recent audit logs
 * 
 * @param mysqli $conn Database connection
 * @param int $limit Number of records to retrieve
 * @param string $action_type Filter by action type (optional)
 * @return mysqli_result|false Query result
 */
function get_audit_logs($conn, $limit = 50, $action_type = null) {
    if ($action_type) {
        $stmt = $conn->prepare("SELECT a.*, s.Username 
                                FROM audit_log a 
                                LEFT JOIN staff s ON a.Staff_ID = s.Staff_ID 
                                WHERE a.Action_Type = ? 
                                ORDER BY a.Timestamp DESC 
                                LIMIT ?");
        $stmt->bind_param("si", $action_type, $limit);
        $stmt->execute();
        return $stmt->get_result();
    } else {
        $sql = "SELECT a.*, s.Username 
                FROM audit_log a 
                LEFT JOIN staff s ON a.Staff_ID = s.Staff_ID 
                ORDER BY a.Timestamp DESC 
                LIMIT {$limit}";
        return $conn->query($sql);
    }
}

/**
 * Get audit logs for specific staff member
 */
function get_staff_audit_logs($conn, $staff_id, $limit = 50) {
    $stmt = $conn->prepare("SELECT a.*, s.Username 
                            FROM audit_log a 
                            LEFT JOIN staff s ON a.Staff_ID = s.Staff_ID 
                            WHERE a.Staff_ID = ? 
                            ORDER BY a.Timestamp DESC 
                            LIMIT ?");
    $stmt->bind_param("ii", $staff_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get audit statistics
 */
function get_audit_statistics($conn) {
    $sql = "SELECT 
                Action_Type, 
                COUNT(*) as count,
                MAX(Timestamp) as last_action
            FROM audit_log 
            GROUP BY Action_Type 
            ORDER BY count DESC";
    
    return $conn->query($sql);
}
?>
