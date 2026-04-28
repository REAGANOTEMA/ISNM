# ISNM School Management System - Complete Database Documentation

## 📋 Table of Contents

1. [Database Overview](#database-overview)
2. [Core Authentication Tables](#core-authentication-tables)
3. [Academic Management Tables](#academic-management-tables)
4. [Financial Management Tables](#financial-management-tables)
5. [Communication System Tables](#communication-system-tables)
6. [Library Management Tables](#library-management-tables)
7. [Hostel Management Tables](#hostel-management-tables)
8. [Reporting System Tables](#reporting-system-tables)
9. [Enhanced Features Tables](#enhanced-features-tables)
10. [Views and Stored Procedures](#views-and-stored-procedures)
11. [SQL Files Description](#sql-files-description)
12. [Setup Instructions](#setup-instructions)

---

## 🗄️ Database Overview

The ISNM School Management System uses a comprehensive MySQL database with **over 40 tables** covering all aspects of school operations:

- **Authentication & User Management**
- **Academic Programs & Courses**
- **Student Records & Performance**
- **Financial Management**
- **Communication & Messaging**
- **Library Management**
- **Hostel Management**
- **Reporting & Analytics**
- **Enhanced Features**

---

## 🔐 Core Authentication Tables

### `users` (Main Users Table)
- **Purpose**: Central user authentication and profile storage
- **Key Fields**: `id`, `full_name`, `email`, `phone`, `password`, `role`, `type`, `status`
- **User Types**: `student`, `staff`
- **Security**: Password hashing, login attempts, account lockout

### `login_attempts`
- **Purpose**: Track all login attempts for security
- **Key Fields**: `user_identifier`, `user_type`, `success`, `failure_reason`, `ip_address`

### `user_sessions`
- **Purpose**: Active session management
- **Key Fields**: `user_id`, `session_id`, `ip_address`, `expires_at`, `is_active`

### `password_resets`
- **Purpose**: Password reset token management
- **Key Fields**: `user_id`, `token`, `expires_at`, `used`

---

## 📚 Academic Management Tables

### `programs`
- **Purpose**: Academic program definitions
- **Key Fields**: `program_code`, `program_name`, `program_type`, `duration_years`
- **Programs**: Certificate in Midwifery (CM), Certificate in Nursing (CN), Diploma in Midwifery (DMORDN)

### `courses`
- **Purpose**: Individual course definitions
- **Key Fields**: `course_code`, `course_name`, `program_id`, `semester`, `credits`, `created_by`

### `student_academic_records`
- **Purpose**: Student course enrollment and performance
- **Key Fields**: `student_id`, `course_id`, `grade`, `gpa_points`, `status`

### `examinations`
- **Purpose**: Exam definitions and scheduling
- **Key Fields**: `course_id`, `exam_name`, `exam_type`, `total_marks`, `exam_date`

### `exam_results`
- **Purpose**: Student exam results
- **Key Fields**: `exam_id`, `student_id`, `marks_obtained`, `grade`, `verified`

### `attendance_records`
- **Purpose**: Student attendance tracking
- **Key Fields**: `student_id`, `course_id`, `attendance_date`, `attendance_status`, `marked_by`

---

## 💰 Financial Management Tables

### `fee_structure`
- **Purpose**: Fee structure definitions by program and semester
- **Key Fields**: `program_id`, `academic_year`, `semester`, `tuition_fee`, `total_fee`

### `student_fee_accounts`
- **Purpose**: Individual student fee accounts
- **Key Fields**: `student_id`, `total_fee`, `amount_paid`, `balance`, `payment_status`

### `payment_transactions`
- **Purpose**: Payment transaction records
- **Key Fields**: `student_id`, `amount`, `payment_method`, `collected_by`, `status`

### `budget_allocations`
- **Purpose**: Department budget management
- **Key Fields**: `budget_code`, `department`, `allocated_amount`, `spent_amount`

### `expense_records`
- **Purpose**: Expense tracking
- **Key Fields**: `budget_allocation_id`, `amount`, `expense_category`, `status`

---

## 📧 Communication System Tables

### `messages`
- **Purpose**: Internal messaging system
- **Key Fields**: `sender_id`, `subject`, `message_text`, `message_type`, `priority`

### `message_recipients`
- **Purpose**: Message delivery tracking
- **Key Fields**: `message_id`, `recipient_id`, `delivery_status`, `read_at`

### `message_attachments`
- **Purpose**: File attachments for messages
- **Key Fields**: `message_id`, `filename`, `file_path`, `file_size`

### `notifications`
- **Purpose**: User notifications
- **Key Fields**: `user_id`, `notification_type`, `title`, `message`, `is_read`

### `announcements`
- **Purpose**: School announcements
- **Key Fields**: `title`, `content`, `target_audience`, `priority`, `status`

---

## 📚 Library Management Tables

### `books`
- **Purpose**: Book inventory management
- **Key Fields**: `book_title`, `author`, `isbn`, `total_copies`, `available_copies`

### `book_loans`
- **Purpose**: Book borrowing records
- **Key Fields**: `book_id`, `student_id`, `loan_date`, `due_date`, `status`

---

## 🏠 Hostel Management Tables

### `hostels`
- **Purpose**: Hostel building information
- **Key Fields**: `hostel_name`, `hostel_code`, `gender`, `total_capacity`

### `rooms`
- **Purpose**: Individual room details
- **Key Fields**: `hostel_id`, `room_number`, `room_type`, `capacity`

### `room_allocations`
- **Purpose**: Student room assignments
- **Key Fields**: `student_id`, `room_id`, `allocation_date`, `status`

---

## 📊 Reporting System Tables

### `report_templates`
- **Purpose**: Report template definitions
- **Key Fields**: `template_name`, `sql_query`, `parameters`, `output_format`

### `generated_reports`
- **Purpose**: Generated report records
- **Key Fields**: `template_id`, `report_name`, `file_path`, `status`

### `dashboard_widgets`
- **Purpose**: Dashboard widget definitions
- **Key Fields**: `widget_name`, `widget_type`, `dashboard_type`, `sql_query`

### `analytics_data`
- **Purpose**: Pre-calculated analytics metrics
- **Key Fields**: `metric_name`, `metric_value`, `period_type`, `period_start`

---

## 🚀 Enhanced Features Tables

### `student_profiles`
- **Purpose**: Extended student profile information
- **Key Fields**: `student_id`, `date_of_birth`, `gender`, `address`, `emergency_contact`

### `staff_profiles`
- **Purpose**: Extended staff profile information
- **Key Fields**: `staff_id`, `employment_date`, `contract_type`, `salary`, `qualifications`

### `documents`
- **Purpose**: Document management system
- **Key Fields**: `document_name`, `document_type`, `file_path`, `uploaded_for`

### `events`
- **Purpose**: School event management
- **Key Fields**: `event_name`, `event_type`, `event_date`, `target_audience`

### `inventory_items`
- **Purpose**: Inventory management
- **Key Fields**: `item_name`, `current_stock`, `unit_cost`, `status`

### `vehicles`
- **Purpose**: School transport management
- **Key Fields**: `vehicle_number`, `vehicle_type`, `capacity`, `status`

### `medical_records`
- **Purpose**: Student medical records
- **Key Fields**: `patient_id`, `diagnosis`, `treatment`, `attending_staff_id`

### `complaints`
- **Purpose**: Complaint management system
- **Key Fields**: `complainant_id`, `complaint_type`, `severity`, `status`

---

## 👁️ Views and Stored Procedures

### Key Views

1. **`complete_student_profile`** - Comprehensive student information
2. **`complete_staff_profile`** - Comprehensive staff information
3. **`system_overview`** - System statistics overview
4. **`student_academic_summary`** - Student academic performance
5. **`fee_collection_summary`** - Fee collection statistics

### Key Stored Procedures

1. **`authenticate_student()`** - Student 3-field authentication
2. **`authenticate_staff()`** - Staff email/password authentication
3. **`create_student_account()`** - Student account creation
4. **`create_staff_account()`** - Staff account creation
5. **`process_payment()`** - Payment processing
6. **`generate_report()`** - Report generation
7. **`send_message()`** - Message sending
8. **`create_announcement()`** - Announcement creation
9. **`system_health_check()`** - System health monitoring
10. **`cleanup_old_data()`** - Data cleanup

---

## 📁 SQL Files Description

### Core Setup Files

1. **`01_create_database.sql`** - Database creation and basic tables
2. **`02_create_sample_users.sql`** - Sample users and initial data
3. **`03_student_management_queries.sql`** - Student-specific operations
4. **`04_staff_management_queries.sql`** - Staff-specific operations
5. **`05_database_maintenance.sql`** - Maintenance and cleanup procedures

### Dashboard Operations Files

6. **`06_dashboard_operations.sql`** - Core dashboard tables and operations
7. **`07_student_dashboard_queries.sql`** - Student dashboard queries
8. **`08_staff_dashboard_queries.sql`** - Staff dashboard queries

### Specialized System Files

9. **`09_academic_management_sql.sql`** - Complete academic system
10. **`10_finance_management_sql.sql`** - Financial management system
11. **`11_communication_system_sql.sql`** - Communication and messaging
12. **`12_reporting_system_sql.sql`** - Reporting and analytics

### Final Setup File

13. **`13_complete_system_setup.sql`** - Complete system setup with all enhancements

---

## 🚀 Setup Instructions

### Step 1: Database Creation
```sql
-- Run the master setup file
SOURCE 13_complete_system_setup.sql;
```

### Step 2: Initial Data Setup
```sql
-- Run sample data creation
SOURCE 02_create_sample_users.sql;
```

### Step 3: Verify Setup
```sql
-- Check table creation
SHOW TABLES;

-- Check sample data
SELECT COUNT(*) as total_users FROM users;
SELECT type, COUNT(*) as count FROM users GROUP BY type;
```

### Step 4: Test Authentication
```sql
-- Test student authentication
CALL authenticate_student('U001/CM/001/24', 'Aisha Nakato', '0772123456', '127.0.0.1', @result, @success, @user_id);

-- Test staff authentication
CALL authenticate_staff('john.mugisha@isnm.ac.ug', 'password123', '127.0.0.1', @result, @success, @user_id);
```

---

## 🔧 Key Features Implemented

### ✅ Authentication System
- **Student Login**: 3-field verification (index_number, full_name, phone)
- **Staff Login**: Email + password with password_verify
- **Security**: Login attempt limiting, account lockout, session management
- **Session Variables**: Standardized `user_id`, `role`, `type`

### ✅ Academic Management
- **Program Management**: Certificate and Diploma programs
- **Course Management**: Course creation, assignment, scheduling
- **Student Records**: Enrollment, grades, GPA calculation
- **Examinations**: Exam creation, results, verification
- **Attendance**: Attendance tracking and reporting

### ✅ Financial Management
- **Fee Structure**: Program-based fee definitions
- **Payment Processing**: Multiple payment methods, receipts
- **Budget Management**: Department budgets and expense tracking
- **Financial Reporting**: Revenue, expenses, utilization reports

### ✅ Communication System
- **Messaging**: Individual and broadcast messages
- **Notifications**: System notifications and alerts
- **Announcements**: School-wide announcements
- **Document Sharing**: File attachments and document management

### ✅ Enhanced Features
- **Library Management**: Book catalog, borrowing, fines
- **Hostel Management**: Room allocation, occupancy tracking
- **Event Management**: Event creation and registration
- **Inventory Management**: Stock tracking and procurement
- **Medical Records**: Student health records
- **Complaint System**: Complaint tracking and resolution

### ✅ Reporting & Analytics
- **Dashboard Widgets**: Real-time statistics
- **Report Templates**: Customizable report generation
- **Analytics Data**: Pre-calculated metrics
- **System Health**: Monitoring and maintenance

---

## 📊 Database Statistics

- **Total Tables**: 40+
- **Total Views**: 15+
- **Stored Procedures**: 25+
- **Sample Users**: 40 (15 students, 25 staff)
- **Default Programs**: 3 (CM, CN, DMORDN)
- **Default Courses**: 36 courses across all programs
- **Default Hostels**: 4 (2 female, 2 male)

---

## 🔒 Security Features

- **Password Hashing**: Using `password_hash()` and `password_verify()`
- **Input Sanitization**: All inputs sanitized and validated
- **Prepared Statements**: All database queries use prepared statements
- **Session Security**: Session regeneration, timeout, IP validation
- **Login Protection**: Attempt limiting, account lockout
- **Data Validation**: Comprehensive input validation and constraints

---

## 📈 Performance Optimizations

- **Indexing**: Proper indexes on all frequently queried columns
- **Generated Columns**: Computed columns for derived data
- **Views**: Pre-computed views for common queries
- **Stored Procedures**: Server-side processing for complex operations
- **Partitioning**: Time-based partitioning for large tables

---

## 🔄 Maintenance Procedures

- **Daily Cleanup**: Automatic cleanup of expired data
- **Weekly Optimization**: Table optimization and analysis
- **Monthly Backup**: Automated backup procedures
- **Health Checks**: System health monitoring
- **Data Archival**: Archival of old records

---

## 🎯 System Capabilities

The ISNM School Management System database supports:

- **Multi-Role Authentication**: Students, Staff, Management
- **Complete Academic Management**: Programs, Courses, Exams, Attendance
- **Financial Operations**: Fees, Payments, Budgets, Expenses
- **Communication**: Messaging, Notifications, Announcements
- **Resource Management**: Library, Hostel, Inventory, Transport
- **Reporting & Analytics**: Real-time dashboards, Custom reports
- **Enhanced Features**: Events, Documents, Medical Records, Complaints
- **System Administration**: User management, Security, Maintenance

---

## 📞 Support and Maintenance

For database-related issues:

1. **Check System Health**: Run `CALL system_health_check()`
2. **Review Logs**: Check `activity_logs` and `system_logs`
3. **Data Cleanup**: Run `CALL cleanup_old_data()`
4. **Performance**: Review slow query logs and indexes
5. **Backup**: Ensure regular backups are running

---

## 🎉 Final Status

**The ISNM School Management System database is now complete and ready for production use!**

All tables, views, stored procedures, and sample data have been created with proper relationships, constraints, and security measures. The system supports all required functionality for a comprehensive school management system.
