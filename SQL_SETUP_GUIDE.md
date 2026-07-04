# Missing SQL Tables - Setup Guide

## Status Summary

### ✅ Tables That Already Exist
These tables are already in your database and do NOT need to be created:

1. **igangaschoolofl_students_db**
   - `applications` - Student applications (exists)
   - `contact_submissions` - Contact forms (exists)
   - `notifications` - Student notifications (exists)
   - `student_fee_accounts` - Student fees (exists)

2. **igangaschoolofl_staffs_db**
   - `notifications` - Staff notifications (exists)
   - `staff_notifications` - Staff notifications alt table (exists)
   - `student_fee_accounts` - Fee accounts (exists)

3. **igangaschoolofl_website_db**
   - `contact_submissions` - Website contact forms (exists)

### ✨ NEW Tables to Create
These tables are MISSING and need to be created for the responsive system:

1. **igangaschoolofl_students_db** (4 new tables)
   - `form_submissions` - Universal form submission handler
   - `feedback_submissions` - User feedback collection
   - `complaint_submissions` - Complaint management
   - `volunteer_applications` - Volunteer application tracking

2. **igangaschoolofl_website_db** (1 new table)
   - `website_announcements` - News/announcements published by directors

---

## Installation Methods

### Method 1: Using phpMyAdmin (Easiest)

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Go to "SQL" tab at the top
3. Copy and paste the SQL from: `/sql/missing_responsive_tables.sql`
4. Click "Go" to execute

### Method 2: Using MySQL Command Line

```bash
# Navigate to project directory
cd c:\xampp\htdocs\ISNM

# Execute SQL file for students database
mysql -u root igangaschoolofl_students_db < sql/missing_responsive_tables.sql

# Execute SQL file for website database
mysql -u root igangaschoolofl_website_db < sql/missing_responsive_tables.sql
```

### Method 3: Using PHP Script (If you have database credentials)

Place this in a temporary PHP file and run it once:

```php
<?php
require_once __DIR__ . '/config/database.php';

// Read SQL file
$sqlFile = __DIR__ . '/sql/missing_responsive_tables.sql';
$sql = file_get_contents($sqlFile);

// Split by semicolons and execute
$statements = array_filter(array_map('trim', explode(';', $sql)));

$studentConn = getStudentsConnection();
$websiteConn = getWebsiteConnection();

foreach ($statements as $statement) {
    if (!empty($statement) && stripos($statement, '--') === false) {
        if (stripos($statement, 'igangaschoolofl_students_db') !== false || 
            stripos($statement, 'form_submissions') !== false || 
            stripos($statement, 'feedback') !== false || 
            stripos($statement, 'complaint') !== false || 
            stripos($statement, 'volunteer') !== false) {
            
            if ($studentConn->query($statement)) {
                echo "✓ Created table successfully\n";
            } else {
                echo "✗ Error: " . $studentConn->error . "\n";
            }
        } elseif (stripos($statement, 'igangaschoolofl_website_db') !== false || 
                  stripos($statement, 'website_announcements') !== false) {
            
            if ($websiteConn->query($statement)) {
                echo "✓ Created table successfully\n";
            } else {
                echo "✗ Error: " . $websiteConn->error . "\n";
            }
        }
    }
}

echo "\n✓ All missing tables created successfully!";
?>
```

### Method 4: Direct Execution via PHP CLI

```bash
cd c:\xampp\htdocs\ISNM
php -r "require 'config/database.php'; $sql = file_get_contents('sql/missing_responsive_tables.sql'); echo $sql;" | mysql -u root
```

---

## Verification

### Check if Tables Exist

Run these queries in phpMyAdmin SQL tab:

**Students Database:**
```sql
-- Check students database tables
SELECT TABLE_NAME 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'igangaschoolofl_students_db' 
AND TABLE_NAME IN ('form_submissions', 'feedback_submissions', 'complaint_submissions', 'volunteer_applications');
```

**Website Database:**
```sql
-- Check website database tables
SELECT TABLE_NAME 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'igangaschoolofl_website_db' 
AND TABLE_NAME = 'website_announcements';
```

### Expected Output
If all tables created successfully, you should see:
- form_submissions
- feedback_submissions
- complaint_submissions
- volunteer_applications
- website_announcements

---

## Table Descriptions

### 1. form_submissions
- **Purpose**: Universal handler for all form types
- **Used by**: FormRouter class, all form pages
- **Key fields**: 
  - `form_type`: application, contact, feedback, complaint, volunteer
  - `status`: pending, read, responded, closed
  - `assigned_to`: Staff ID who handles this form

### 2. feedback_submissions
- **Purpose**: Collect and manage user feedback
- **Used by**: Feedback forms, director dashboard
- **Key fields**:
  - `rating`: 1-5 star rating
  - `category`: Type of feedback
  - `status`: received, reviewed

### 3. complaint_submissions
- **Purpose**: Track complaints and grievances
- **Used by**: Complaint system, HR/Admin dashboards
- **Key fields**:
  - `severity`: low, medium, high, urgent
  - `status`: filed, acknowledged, investigating, resolved, closed
  - `resolution`: Resolution text

### 4. volunteer_applications
- **Purpose**: Manage volunteer applications
- **Used by**: Volunteer form, director dashboard
- **Key fields**:
  - `status`: pending, reviewed, accepted, rejected, interviewed
  - `skills`: Required volunteer skills
  - `availability`: When volunteer can work

### 5. website_announcements
- **Purpose**: News/announcements published by directors
- **Used by**: Website homepage, news section
- **Key fields**:
  - `featured`: Show on homepage
  - `status`: draft, published, archived
  - `views`: View count tracking

---

## Integration with Existing System

### These tables work with:

1. **Form Router** (`/includes/form_router.php`)
   - Automatically routes forms to correct staff
   - Creates notifications
   - Stores submissions

2. **News Publisher** (`/includes/news_publisher.php`)
   - Directors publish to website_announcements
   - Displays on homepage

3. **Student Search** (`/includes/student_search.php`)
   - Searches form submission data

4. **Notifications** (dashboard-header.php)
   - Displays staff notifications
   - Marks as read

---

## Troubleshooting

### Error: "Table already exists"
**Solution**: This is normal with `CREATE TABLE IF NOT EXISTS`. Just ignore it.

### Error: "Access denied for user"
**Solution**: Make sure MySQL is running and you have correct credentials in `config/database.php`

### Error: "Unknown database"
**Solution**: Check database names in `config/database.php`. Should be:
- `igangaschoolofl_students_db`
- `igangaschoolofl_staffs_db`
- `igangaschoolofl_website_db`

### No output after running SQL
**Solution**: That's normal! Run verification queries above to confirm tables were created.

---

## Data Import (Optional)

If you have existing form data in other tables, you can migrate it:

```sql
-- Example: Migrate contact forms to form_submissions
INSERT INTO form_submissions (form_type, name, email, subject, message, status, created_at)
SELECT 'contact', 
       CONCAT(first_name, ' ', last_name), 
       email, 
       subject, 
       message, 
       status, 
       created_at
FROM contact_submissions
WHERE email NOT IN (SELECT email FROM form_submissions WHERE form_type = 'contact');
```

---

## Next Steps

1. **Run the SQL**: Use one of the 4 methods above
2. **Verify**: Run the verification queries
3. **Test**: Submit a form to test the system
4. **Monitor**: Check notifications in staff dashboard

---

## Files Involved

- `/sql/missing_responsive_tables.sql` - SQL script for missing tables
- `/includes/form_router.php` - Form routing logic
- `/includes/news_publisher.php` - News publishing logic
- `/includes/dashboard-header.php` - Notification display
- `/db_migrate_responsive_systems.php` - Automated migration script

---

**Status**: Ready to implement
**Last Updated**: 2024
**Version**: 1.0

