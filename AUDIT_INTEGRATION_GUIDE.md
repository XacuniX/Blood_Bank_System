# Audit Logger Integration Guide

## Overview
The audit logging system automatically tracks all database changes (INSERT, UPDATE, DELETE) and user actions (LOGIN, LOGOUT) to the `audit_log` table.

## Files Created
1. **audit_logger.php** - Core audit logging functions
2. **audit_logs.php** - Staff viewer for audit logs (requires staff login)

## How to Integrate

### Step 1: Include the Audit Logger
Add this to files where you perform database operations:
```php
include 'audit_logger.php';
```

### Step 2: Call Logging Functions

#### For INSERT Operations
```php
// After successful INSERT
$new_id = $conn->insert_id;
log_insert($conn, $staff_id, 'table_name', $new_id, 'Additional details');

// Example: Donor registration (already implemented)
log_donor_registration($conn, $donor_id, $name, $blood_group);
```

#### For UPDATE Operations
```php
// After successful UPDATE
log_update($conn, $staff_id, 'table_name', $record_id, 'Field1: old -> new, Field2: old -> new');

// Example: Donor profile update
log_donor_update($conn, $donor_id, 'Phone_Number', $old_phone, $new_phone);
```

#### For DELETE Operations
```php
// After successful DELETE
log_delete($conn, $staff_id, 'table_name', $record_id, 'Optional details');

// Example: Delete expired blood units
$affected = $conn->affected_rows;
log_delete($conn, $staff_id, 'blood_unit', 'multiple', "Deleted {$affected} expired units");
```

#### For LOGIN/LOGOUT
```php
// After successful login
log_login($conn, 'Staff', $staff_id, $username);
log_login($conn, 'Donor', $donor_id, $donor_name);
log_login($conn, 'Hospital', $hospital_id, $hospital_name);

// Before logout
log_logout($conn, 'Staff', $staff_id, $username);
```

## Integration Examples

### Example 1: donor_update_profile.php
```php
include 'audit_logger.php';

if ($field === 'phone') {
    // Get old value first
    $old_value = $_SESSION['phone'] ?? 'unknown';
    
    $stmt = $conn->prepare("UPDATE Donor SET Phone_Number = ? WHERE Donor_ID = ?");
    $stmt->bind_param("si", $value, $donor_id);
    
    if ($stmt->execute()) {
        // Log the update
        log_donor_update($conn, $donor_id, 'Phone_Number', $old_value, $value);
        $_SESSION['success'] = 'Phone number updated successfully.';
    }
}
```

### Example 2: staff_login.php
```php
include 'audit_logger.php';

if ($password_valid) {
    session_start();
    $_SESSION['staff_id'] = $staff['Staff_ID'];
    $_SESSION['username'] = $staff['Username'];
    
    // Log successful login
    log_login($conn, 'Staff', $staff['Staff_ID'], $staff['Username']);
    
    header("Location: staff_dashboard.php");
    exit();
}
```

### Example 3: hospital_request_form.php
```php
include 'audit_logger.php';

$stmt = $conn->prepare("INSERT INTO Request (...) VALUES (...)");
if ($stmt->execute()) {
    $request_id = $conn->insert_id;
    
    // Log blood request
    log_blood_request($conn, $hospital_id, $blood_group, $quantity, $urgency_level);
    
    $_SESSION['success'] = 'Request submitted successfully.';
}
```

### Example 4: Cleanup Expired Blood Units (staff feature)
```php
include 'audit_logger.php';

$cleanup_sql = "DELETE FROM blood_unit WHERE Expiry_Date < CURDATE() AND Status = 'Expired'";
if ($conn->query($cleanup_sql)) {
    $deleted_count = $conn->affected_rows;
    
    // Log the cleanup action
    log_delete($conn, $_SESSION['staff_id'], 'blood_unit', 'multiple', 
               "Cleanup: Deleted {$deleted_count} expired blood unit(s)");
    
    $_SESSION['success'] = "Cleaned up {$deleted_count} expired blood unit(s).";
}
```

## Files That Need Integration

### Priority 1: Authentication Files
- [ ] **staff_login.php** - Add log_login()
- [ ] **staff_logout.php** - Add log_logout()
- [ ] **donor_login.php** - Add log_login()
- [ ] **donor_logout.php** - Add log_logout()
- [ ] **hospital_login.php** - Add log_login()
- [x] **donor_register.php** - Already integrated

### Priority 2: Data Modification Files
- [ ] **donor_update_profile.php** - Add log_update() for phone/password changes
- [ ] **hospital_request_form.php** - Add log_blood_request()
- [ ] **staff_officer_dashboard.php** - Add logging for blood unit operations

### Priority 3: Future Features
- [ ] Blood unit status changes
- [ ] Request status changes (Pending -> Approved -> Fulfilled)
- [ ] Staff management (add/remove staff)
- [ ] Hospital management

## Viewing Audit Logs

### Access the Audit Log Viewer:
1. Login as staff member
2. Navigate to: `http://localhost/bloodbank/audit_logs.php`
3. Or add a link in staff_dashboard.php

### Features:
- Filter by action type (INSERT, UPDATE, DELETE, LOGIN, LOGOUT)
- Adjust number of records displayed (50, 100, 200, 500)
- View statistics by action type
- See timestamp, staff member, and detailed description

## Database Schema

The audit_log table structure:
```sql
CREATE TABLE `audit_log` (
  `Log_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Staff_ID` int(11) DEFAULT NULL,
  `Action_Type` varchar(50) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`Log_ID`),
  FOREIGN KEY (`Staff_ID`) REFERENCES `staff` (`Staff_ID`)
);
```

## Best Practices

1. **Always log after successful operations** - Don't log failed attempts
2. **Include meaningful descriptions** - Make it easy to understand what happened
3. **Use specific log functions** - Use log_donor_registration() instead of generic log_insert()
4. **Pass staff_id when available** - For staff actions, always include staff ID
5. **Log user actions** - Even non-staff actions (donor updates, hospital requests)
6. **Don't log sensitive data** - Never log passwords, even hashed ones

## Security Notes

- Audit logs are read-only for most staff
- Only Admin role should have access to delete/modify audit logs
- Logs capture Staff_ID when available, NULL for donor/hospital actions
- Timestamp is automatically set to current time
- Foreign key relationship ensures data integrity

## Testing

To test the audit logger:
1. Register a new donor - Check audit_logs.php for INSERT log
2. Update donor profile - Check for UPDATE log
3. Login/Logout as different users - Check for LOGIN/LOGOUT logs
4. Submit hospital blood request - Check for INSERT log

## Next Steps

1. Add audit_logs.php link to staff_dashboard.php navigation
2. Integrate logging into remaining files (see checklist above)
3. Consider adding admin-only audit log cleanup function
4. Add date range filtering to audit_logs.php viewer
5. Export audit logs to CSV/PDF for compliance
