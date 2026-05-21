# Requirements Portal - Installation Guide

## Quick Start (3 Steps)

### Step 1: Run Setup Script
Visit in browser:
```
http://localhost/ISNM.worktrees/agents-organogram-department-navigation/setup_requirements_portal.php
```
Click "Proceed with Setup" to initialize the database.

### Step 2: Create Director Account
1. Login to your admin/CEO dashboard
2. Go to Staff Management
3. Create new staff account:
   - Name: (e.g., "John Doe")
   - Email: director@example.com (any email)
   - Password: (secure password)
   - **Role: "Director of Requirements"** ← Important!

### Step 3: Login & Use
1. Logout and go to staff login
2. Login with director account email and password
3. You'll be redirected to Requirements Portal
4. Start managing student requirements!

---

## Detailed Installation

### Prerequisites
- XAMPP running (Apache, MySQL, PHP)
- ISNM system already installed
- Active students in students_db
- MySQL access

### System Files

The installation creates/modifies these files:

**New Files Created:**
```
dashboards/requirements-director.php      (1,500 lines) - Main dashboard
includes/requirements_functions.php       (500+ lines)  - Helper functions
setup_requirements_portal.php             (400+ lines)  - Setup script
verify_requirements_portal.php            (200+ lines)  - Verification tool
REQUIREMENTS_PORTAL_README.md             (300+ lines)  - User guide
INSTALLATION_GUIDE.md                     (This file)
```

**Modified Files:**
```
auth-handler.php                          (Added 300+ lines) - API endpoints
```

### Database Schema

#### Tables Created

**1. requirement_items**
```
CREATE TABLE requirement_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,        -- Item name
    category VARCHAR(50) NOT NULL,            -- Medical, Office, Cleaning
    status VARCHAR(20) DEFAULT 'active',      -- active/inactive
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**2. student_requirements**
```
CREATE TABLE student_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,                  -- From students_db.users.id
    student_admission_number VARCHAR(50),     -- Index number
    student_name VARCHAR(100),                -- Full name
    student_phone VARCHAR(20),                -- Contact
    requirement_item_id INT NOT NULL,         -- FK to requirement_items
    is_cleared BOOLEAN DEFAULT FALSE,         -- Completion status
    cleared_by VARCHAR(100),                  -- Staff member name
    cleared_date DATETIME,                    -- When cleared
    notes TEXT,                               -- Optional notes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (requirement_item_id) REFERENCES requirement_items(id),
    INDEX idx_student (student_id),
    INDEX idx_item (requirement_item_id),
    INDEX idx_cleared (is_cleared)
);
```

### 20 Requirement Items

The system automatically inserts these 20 items:

**Medical (4 items)**
1. Surgical Gloves
2. Examination Gloves
3. Face Masks
4. Heavy duty Gloves

**Office (2 items)**
1. Photocopying Ream
2. Ruled Paper Reams

**Cleaning (14 items)**
1. Omo
2. Toilet Papers
3. Compound brooms
4. Soft brooms
5. Rake
6. Cobweb brush
7. Scrubbing Brush
8. Squeezer
9. Toilet Brush
10. JIK
11. Vim
12. Mops
13. Sanitizer
14. Liquid Soap

### Staff Role

A new role is added to staff_roles table:

| Field | Value |
|-------|-------|
| role_name | Director of Requirements |
| dashboard_path | dashboards/requirements-director.php |
| description | Manages student requirements and clearance tracking |

---

## Installation Steps

### Step 1: Upload/Copy Files

Copy these files to your ISNM installation:
- `dashboards/requirements-director.php`
- `includes/requirements_functions.php`
- `setup_requirements_portal.php`
- `verify_requirements_portal.php`
- `REQUIREMENTS_PORTAL_README.md`

Update existing file:
- `auth-handler.php` (add API endpoints)

### Step 2: Initialize Database

Open in browser:
```
http://localhost/ISNM.worktrees/agents-organogram-department-navigation/setup_requirements_portal.php
```

You'll see a confirmation page. Click "Proceed with Setup"

The script will:
1. ✓ Create requirement_items table
2. ✓ Create student_requirements table  
3. ✓ Add Director of Requirements role
4. ✓ Insert 20 requirement items

### Step 3: Verify Installation

Open verification script:
```
http://localhost/ISNM.worktrees/agents-organogram-department-navigation/verify_requirements_portal.php
```

Should show:
- ✓ Staff database connection OK
- ✓ Students database connection OK
- ✓ requirement_items table exists
- ✓ All 20 requirement items loaded
- ✓ student_requirements table exists
- ✓ Director of Requirements role exists
- ✓ All 4 required files exist
- ✓ Found X active students

### Step 4: Create Staff Account

1. Login to admin/CEO dashboard
2. Navigate to Staff Management section
3. Create new staff with:
   - **Full Name**: (e.g., "Requirements Director")
   - **Email**: director@example.com (any valid email)
   - **Password**: (secure, at least 8 characters)
   - **Phone**: (optional)
   - **Role**: "Director of Requirements" (select from dropdown)
4. Click Create/Save

### Step 5: Test Access

1. Logout from admin account
2. Go to Staff Login page
3. Login with director's email and password
4. Should redirect to: `dashboards/requirements-director.php`
5. Should see list of students with requirements

---

## Testing the System

### Test 1: Load Dashboard
- [ ] Login as Director of Requirements
- [ ] See all students listed
- [ ] Statistics show correct numbers

### Test 2: Search Students
- [ ] Search by name
- [ ] Search by admission number  
- [ ] Search by phone number
- [ ] Results filter correctly

### Test 3: Check Requirements
- [ ] Click checkbox to mark item complete
- [ ] Student progress bar updates
- [ ] Clear button applies to all items

### Test 4: Export CSV
- [ ] Click "Export CSV" button
- [ ] File downloads as `requirements_YYYY-MM-DD.csv`
- [ ] Open in Excel/Sheets
- [ ] Contains all students and items

### Test 5: Filter Options
- [ ] Filter by "All Cleared" - shows only complete students
- [ ] Filter by "Pending" - shows incomplete students
- [ ] All filters work correctly

---

## Troubleshooting

### Problem: "Database connection failed"

**Solution:**
1. Check config/database.php has correct:
   - DB_HOST (usually 'localhost')
   - STAFF_DB_USER (usually 'root')
   - STAFF_DB_PASS (your MySQL password)
   - STAFF_DB_NAME (usually 'staffs_db')
2. Ensure MySQL is running in XAMPP
3. Check credentials in XAMPP MySQL settings

### Problem: "Table doesn't exist"

**Solution:**
1. Run setup script again: setup_requirements_portal.php
2. Verify in phpMyAdmin that tables exist in staffs_db
3. Check for SQL errors in browser console

### Problem: "Director of Requirements role not found"

**Solution:**
1. Run setup script: setup_requirements_portal.php
2. Manually add role via phpMyAdmin:
   - Go to staffs_db > staff_roles
   - Insert new row:
     - role_name: "Director of Requirements"
     - dashboard_path: "dashboards/requirements-director.php"
     - description: "Manages student requirements and clearance tracking"

### Problem: "No students appear"

**Solution:**
1. Check students_db.users table has records with:
   - role = 'student'
   - is_active = 1
2. If no students, create test data
3. Verify database connection in config

### Problem: "Can't find the dashboard"

**Solution:**
1. Check file exists: dashboards/requirements-director.php
2. Try direct URL: 
   ```
   http://localhost/ISNM.worktrees/agents-organogram-department-navigation/dashboards/requirements-director.php
   ```
3. Check PHP error logs in XAMPP

### Problem: "AJAX requests failing"

**Solution:**
1. Open browser DevTools (F12)
2. Check Console tab for errors
3. Check Network tab - auth-handler.php should respond with JSON
4. Verify auth-handler.php has requirement endpoint code
5. Check that user is logged in (check $_SESSION)

---

## Uninstallation

To remove the Requirements Portal:

1. **Delete files:**
   ```
   dashboards/requirements-director.php
   includes/requirements_functions.php
   setup_requirements_portal.php
   verify_requirements_portal.php
   REQUIREMENTS_PORTAL_README.md
   ```

2. **Revert auth-handler.php** to original

3. **Drop database tables** (optional):
   ```sql
   DROP TABLE staffs_db.student_requirements;
   DROP TABLE staffs_db.requirement_items;
   DELETE FROM staffs_db.staff_roles 
   WHERE role_name = 'Director of Requirements';
   ```

---

## Directory Structure

After installation:
```
ISNM/
├── dashboards/
│   ├── requirements-director.php     ← New
│   ├── ceo.php
│   ├── student.php
│   └── ...other dashboards
├── includes/
│   ├── requirements_functions.php    ← New
│   ├── functions.php
│   ├── staff_dashboard_access.php
│   └── ...other includes
├── config/
│   └── database.php
├── setup_requirements_portal.php     ← New
├── verify_requirements_portal.php    ← New
├── REQUIREMENTS_PORTAL_README.md     ← New
├── INSTALLATION_GUIDE.md             ← New
├── auth-handler.php                  ← Modified
└── ...other files
```

---

## Performance Considerations

### Database Optimization

The system includes indexes on:
- `student_requirements.student_id` - Fast student lookups
- `student_requirements.requirement_item_id` - Fast item lookups  
- `student_requirements.is_cleared` - Fast filtering

### Pagination

Dashboard displays 20 students per page to:
- Reduce page load time
- Minimize database queries
- Improve user experience

### AJAX Updates

Status updates use AJAX to:
- No page reload needed
- Instant feedback to user
- Efficient data transfer

---

## Security Notes

✓ **Implemented:**
- Authenticated access (staff only via session)
- Role-based access (Director of Requirements role check)
- Prepared statements (prevent SQL injection)
- Sanitized input (htmlspecialchars, addslashes)
- CSRF tokens (session-based)

✓ **Recommendations:**
- Use HTTPS in production
- Set strong passwords for Director accounts
- Regularly backup staffs_db
- Review access logs
- Keep PHP/MySQL updated

---

## Support & Documentation

- **User Guide**: See REQUIREMENTS_PORTAL_README.md
- **Setup Issues**: Run verify_requirements_portal.php
- **Database Errors**: Check XAMPP MySQL error logs
- **System Logs**: Check PHP error_log in XAMPP

---

## Version Info

- **Portal Version**: 1.0
- **Required PHP**: 7.4+
- **Required MySQL**: 5.7+
- **ISNM Version**: Tested with current version

---

## License

Part of ISNM School Management System. All rights reserved.

For questions or issues, contact system administrator.
