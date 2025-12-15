# Project: Blood Bank Management System
**Tech Stack:** PHP (Vanilla), MySQL (XAMPP), Bootstrap 5.
**Database Name:** `bloodbank`

## Database Structure (Key Tables)
1. **Donor:** `Donor_ID`, `Name`, `Phone_Number`, `Password`, `Blood_Group`, `Last_Donation_Date`, `Gender`, `Age`.
2. **Blood_Unit:** `Unit_ID`, `Blood_Group`, `Status` ('Available', 'Used','Expired','Reserved'), `Expiry_Date`, `Collection_Date`,`Donor_ID` (FK),`Staff_ID` (FK),`Request_ID` (FK).
3. **Hospital:** `Hospital_ID`, `Hospital_Name`, `Phone`, `Password`,`Contact_Email`,`Location`.
4. **Request:** `Request_ID`, `Hospital_ID` (FK), `Required_Blood_Group`, `Quantity`, `Urgency_Level`, `Status`, `Request_Date`.

## Current Progress
- **Donor System:** Registration, Login (Phone/Pass), Dashboard (View History, Eligibility Check) are COMPLETE.
- **Hospital System:** Login page, Search by Blood Group, Requesting Blood Bag as Needed.

## Coding Rules
- Use `db_connect.php` for database connections.
- Use `session_start()` at the top of protected pages.
- Use Bootstrap 5 for all styling.