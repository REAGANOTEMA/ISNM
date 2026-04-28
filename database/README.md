# ISNM School Management System - Database Setup

This folder contains SQL scripts for setting up and managing the ISNM School Management System database.

## 📁 File Structure

### 🗄️ Database Setup Files

1. **`01_create_database.sql`** - Main database setup
   - Creates the `isnm_school` database
   - Sets up all required tables (users, login_attempts, user_sessions, password_resets, system_settings)
   - Creates stored procedures for common operations
   - Sets up database triggers for automatic cleanup

2. **`02_create_sample_users.sql`** - Sample data creation
   - Creates sample student accounts (15 students across different programs)
   - Creates sample staff accounts (25 staff members across various roles)
   - Sets up views for easy data access
   - Default password for all staff: `password123`

3. **`03_student_management_queries.sql`** - Student-specific queries
   - Student login verification (3-field: index_number, full_name, phone)
   - Student account creation and management
   - Student reports and statistics
   - Stored procedures for student operations

4. **`04_staff_management_queries.sql`** - Staff-specific queries
   - Staff login verification (email + password)
   - Staff account creation and management
   - Staff reports and statistics
   - Stored procedures for staff operations

5. **`05_database_maintenance.sql`** - Database maintenance
   - Cleanup queries for expired data
   - Database optimization procedures
   - Health check queries
   - Scheduled maintenance setup

## 🚀 Quick Setup

### Step 1: Create Database
```sql
-- Run this in MySQL command line or your preferred MySQL client
SOURCE 01_create_database.sql;
```

### Step 2: Add Sample Data (Optional)
```sql
-- Add sample users for testing
SOURCE 02_create_sample_users.sql;
```

### Step 3: Verify Setup
```sql
-- Check if tables were created
SHOW TABLES;

-- Check sample data
SELECT COUNT(*) as total_users FROM users;
SELECT type, COUNT(*) as count FROM users GROUP BY type;
```

## 🔐 Authentication System

### Student Login (3-Field Verification)
Students authenticate using:
- **Index Number**: Format `U001/CM/056/16`
- **Full Name**: Exact match with database
- **Phone Number**: Uganda format (10 digits, starts with 7)

### Staff Login (Email + Password)
Staff authenticate using:
- **Email**: Unique email address
- **Password**: Hashed password using `password_hash()`

## 📊 Database Schema

### Users Table Structure
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    index_number VARCHAR(50) UNIQUE,        -- For students only
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,              -- For staff only
    phone VARCHAR(20),
    password VARCHAR(255),                  -- For staff only (hashed)
    role VARCHAR(50) NOT NULL,
    type ENUM('student', 'staff') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL
);
```

### Supporting Tables
- **`login_attempts`** - Tracks all login attempts for security
- **`user_sessions`** - Manages active user sessions
- **`password_resets`** - Handles password reset tokens
- **`system_settings`** - Stores system configuration

## 🔧 Maintenance

### Daily Maintenance
```sql
-- Clean up expired sessions and old tokens
CALL daily_maintenance();
```

### Weekly Maintenance
```sql
-- Optimize tables and clean old data
CALL weekly_maintenance();
```

### Monthly Maintenance
```sql
-- Create backups and clean inactive accounts
CALL monthly_maintenance();
```

### Health Check
```sql
-- Check database integrity
CALL check_database_health();
```

## 📈 Sample Data

### Student Accounts (15 total)
- **Nursing Students**: `U001/CM/001/24` to `U005/CM/005/24`
- **Midwifery Students**: `U001/CN/001/24` to `U005/CN/005/24`
- **Diploma Students**: `U001/DMORDN/001/24` to `U005/DMORDN/005/24`

### Staff Accounts (25 total)
- **Administration**: Director General, Principal, Registrar, Secretary, Bursar
- **Directors**: Academics, Finance, ICT, Deputy Principal
- **Academic Staff**: Lecturers, Senior Lecturers, Department Heads
- **Support Staff**: Librarian, HR, Lab Technicians, Drivers, Security

### Default Credentials
- **Staff Email Format**: `firstname.lastname@isnm.ac.ug`
- **Default Password**: `password123` (for all sample staff accounts)
- **Student Login**: Use index number, full name, and phone from sample data

## 🔒 Security Features

### Login Security
- **Maximum Attempts**: 5 failed attempts
- **Lockout Duration**: 15 minutes
- **Session Timeout**: 30 minutes of inactivity
- **IP Validation**: Prevents session hijacking
- **Password Hashing**: Uses `password_hash()` with `PASSWORD_DEFAULT`

### Audit Trail
- **Login Attempts**: All attempts logged with IP and timestamp
- **Session Tracking**: Active sessions monitored
- **Password Changes**: Reset tokens tracked

## 🚨 Important Notes

1. **Database Name**: `isnm_school`
2. **Character Set**: `utf8mb4` (full Unicode support)
3. **Timezone**: Uganda timezone (`+03:00`)
4. **Engine**: InnoDB (supports transactions and foreign keys)

## 📞 Support

For database-related issues:
1. Check the maintenance scripts in `05_database_maintenance.sql`
2. Run health checks using `CALL check_database_health()`
3. Review the authentication queries in `03_student_management_queries.sql` and `04_staff_management_queries.sql`

## 🔄 Updates

When updating the system:
1. Always backup the database first
2. Test changes on sample data
3. Run maintenance procedures after updates
4. Monitor system health regularly
