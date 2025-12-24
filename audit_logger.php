<?php
// admin/audit_logger.php

function log_activity($conn, $performed_by, $role, $action, $table, $target_id, $details) {
    
    // SQL matches your NEW table structure
    $sql = "INSERT INTO Audit_Log (Performed_By, Role, Action_Type, Target_Table, Target_ID, Details, Log_Date) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
    $stmt = $conn->prepare($sql);
    
    // Bind parameters (s=string, i=integer)
    // s s s s i s = 6 items
    if ($stmt) {
        $stmt->bind_param("ssssis", $performed_by, $role, $action, $table, $target_id, $details);
        $stmt->execute();
        $stmt->close();
    }
}
?>