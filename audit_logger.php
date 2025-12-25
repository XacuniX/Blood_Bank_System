<?php
function log_activity($conn, $performed_by, $role, $action, $table, $target_id, $details) {
    $sql = "INSERT INTO Audit_Log (Performed_By, Role, Action_Type, Target_Table, Target_ID, Details, Log_Date) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssssis", $performed_by, $role, $action, $table, $target_id, $details);
        $stmt->execute();
        $stmt->close();
    }
}
?>