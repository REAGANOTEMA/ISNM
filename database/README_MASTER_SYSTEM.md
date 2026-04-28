# ISNM School Management System - Master Database Documentation

## Overview

This documentation describes the complete database structure for the International School of Nursing and Midwifery (ISNM) School Management System. The database name is **isnm_db**.

## Database Structure

The system consists of 23 comprehensive SQL files that cover all aspects of school management:

### Core System Files

#### 1. 23_MASTER_ISNM_COMPLETE_SYSTEM.sql
**Purpose**: Master consolidation file containing all system components
- Complete database schema with all tables, views, stored procedures, and triggers
- Includes sample data and default configurations
- Single-file deployment option for the entire system

#### 2. 18_final_dashboard_operations_isnm.sql
**Purpose**: Dashboard operations and user interface support
- Comments, print logs, send logs, activity logs
- User preferences, notifications, quick actions, favorites
- Dashboard widgets and reporting
- File attachments and bulk operations

#### 3. 19_academic_management_isnm.sql
**Purpose**: Complete academic management system
- Programs, courses, sessions, semesters
- Student academic records and course registrations
- Examinations, results, and grade scales
- Attendance tracking and academic performance

#### 4. 20_finance_management_isnm.sql
**Purpose**: Comprehensive financial management
- Fee categories, structure, and student fee accounts
- Payment transactions and methods
- Budget allocations and expense tracking
- Financial reports and analytics

#### 5. 21_communication_system_isnm.sql
**Purpose**: Full communication and messaging system
- Announcements and announcements categories
- Messages, threads, and recipients
- Notifications and notification templates
- Email/SMS queues and communication logs

#### 6. 22_reporting_system_isnm.sql
**Purpose**: Advanced reporting and analytics
- Report templates and generated reports
- Dashboard widgets and KPI metrics
- Data visualizations and analytics
- Report scheduling and subscriptions

## Legacy Files (For Reference)

The following files were created during development but are superseded by the master system:

#### Authentication & Core Files
- `01_create_database.sql` - Basic database creation
- `02_create_sample_users.sql` - Sample user data
- `03_student_management_queries.sql` - Student queries
- `04_staff_management_queries.sql` - Staff queries
- `05_database_maintenance.sql` - Maintenance procedures

#### Dashboard & Operations Files
- `06_dashboard_operations.sql` - Basic dashboard operations
- `07_student_dashboard_queries.sql` - Student dashboard queries
- `08_staff_dashboard_queries.sql` - Staff dashboard queries
- `14_dashboard_crud_operations.sql` - CRUD operations
- `15_dashboard_data_management.sql` - Data management
- `16_dashboard_integration_api.sql` - API integration
- `17_final_complete_system.sql` - Previous complete system

#### System Management Files
- `09_academic_management_sql.sql` - Academic management (legacy)
- `10_finance_management_sql.sql` - Finance management (legacy)
- `11_communication_system_sql.sql` - Communication (legacy)
- `12_reporting_system_sql.sql` - Reporting (legacy)
- `13_complete_system_setup.sql` - System setup (legacy)

## Key Features

### Authentication System
- Unified user management for students and staff
- Student login: 3-field verification (index_number, full_name, phone)
- Staff login: email + password with secure hashing
- Session management and login attempt tracking
- Role-based access control

### Academic Management
- Program and course management
- Student registration and academic records
- Examination system with results and grading
- Attendance tracking with detailed reporting
- Academic performance analytics

### Financial Management
- Comprehensive fee structure management
- Payment processing with multiple methods
- Budget allocation and expense tracking
- Financial reporting and analytics
- Late fee calculation and waivers

### Communication System
- Multi-channel messaging (email, SMS, internal)
- Announcement system with categorization
- Notification management with templates
- Communication logs and analytics
- Group messaging and discussions

### Dashboard Operations
- Activity logging and audit trails
- Print, send, and comment operations
- User preferences and quick actions
- Favorites and bookmarking
- File attachments and document management

### Reporting & Analytics
- Custom report templates and generation
- Dashboard widgets and KPI tracking
- Data visualization and export
- Scheduled reports and subscriptions
- Performance analytics and trends

### Library Management
- Book catalog and inventory
- Loan tracking and management
- Fine calculation and payment
- Library usage statistics

### Hostel Management
- Hostel and room management
- Student allocation system
- Rent collection and tracking
- Occupancy monitoring

### API Integration
- RESTful API endpoints
- API key management
- Request logging and rate limiting
- Webhook support
- Third-party integrations

## Database Tables Overview

### Core Tables (23 total)
1. **users** - Unified user storage (students + staff)
2. **login_attempts** - Login attempt tracking
3. **user_sessions** - Active session management
4. **password_resets** - Password reset tokens
5. **system_settings** - System configuration

### Academic Tables (12 total)
6. **academic_programs** - Academic programs
7. **academic_sessions** - Academic years/sessions
8. **academic_semesters** - Semester management
9. **academic_courses** - Course catalog
10. **student_academic_records** - Student academic data
11. **course_registrations** - Course enrollment
12. **examinations** - Exam management
13. **exam_results** - Student results
14. **grade_scales** - Grading system
15. **attendance_sessions** - Attendance sessions
16. **attendance_records** - Attendance tracking
17. **course_assignments** - Lecturer assignments

### Financial Tables (8 total)
18. **fee_categories** - Fee types
19. **fee_structure** - Fee configuration
20. **student_fee_accounts** - Student fee accounts
21. **payment_methods** - Payment options
22. **payment_transactions** - Payment records
23. **budget_allocations** - Budget management
24. **expense_records** - Expense tracking
25. **bank_accounts** - Bank account management

### Communication Tables (18 total)
26. **announcement_categories** - Announcement types
27. **announcements** - School announcements
28. **messages** - Internal messages
29. **message_threads** - Message conversations
30. **message_recipients** - Message delivery
31. **notifications** - User notifications
32. **notification_templates** - Notification templates
33. **communication_logs** - Communication tracking
34. **email_queue** - Email sending queue
35. **sms_queue** - SMS sending queue
36. **communication_preferences** - User preferences
37. **emergency_contacts** - Emergency information
38. **communication_groups** - User groups
39. **group_members** - Group membership
40. **sms_templates** - SMS templates
41. **email_templates** - Email templates
42. **message_read_status** - Message read tracking
43. **message_attachments** - File attachments

### Dashboard Operations Tables (19 total)
44. **dashboard_comments** - Entity comments
45. **dashboard_print_logs** - Print operations
46. **dashboard_send_logs** - Send operations
47. **dashboard_activity_logs** - Activity tracking
48. **dashboard_user_preferences** - User settings
49. **dashboard_notifications** - Dashboard notifications
50. **dashboard_quick_actions** - Quick actions
51. **dashboard_favorites** - User favorites
52. **dashboard_widgets** - Dashboard widgets
53. **dashboard_reports** - Dashboard reports
54. **dashboard_audit_trail** - Audit logging
55. **dashboard_file_attachments** - File management
56. **dashboard_bulk_operations** - Bulk operations
57. **dashboard_export_logs** - Export tracking
58. **dashboard_import_logs** - Import tracking
59. **dashboard_api_logs** - API logging
60. **dashboard_sessions** - Session tracking
61. **dashboard_permissions** - Permission system
62. **dashboard_roles** - Role management

### Library Tables (2 total)
63. **books** - Book catalog
64. **book_loans** - Loan tracking

### Hostel Tables (3 total)
65. **hostels** - Hostel information
66. **rooms** - Room management
67. **room_allocations** - Student allocations

### API Integration Tables (7 total)
68. **api_endpoints** - API endpoints
69. **api_keys** - API key management
70. **api_logs** - API request logging
71. **api_rate_limits** - Rate limiting
72. **webhooks** - Webhook management
73. **webhook_delivery_logs** - Webhook tracking
74. **integrations** - Third-party integrations

### Reporting Tables (17 total)
75. **report_categories** - Report categories
76. **report_templates** - Report templates
77. **report_parameters** - Template parameters
78. **generated_reports** - Generated reports
79. **report_schedules** - Scheduled reports
80. **dashboard_widgets** - Dashboard widgets
81. **analytics_data** - Analytics data
82. **report_access_logs** - Report access
83. **report_subscriptions** - Report subscriptions
84. **export_queues** - Export management
85. **report_comments** - Report comments
86. **report_approvals** - Report approvals
87. **custom_queries** - Custom queries
88. **report_data_cache** - Data caching
89. **kpi_metrics** - KPI definitions
90. **kpi_data** - KPI data
91. **data_visualizations** - Visualizations

## Stored Procedures

### Core Procedures
- `authenticate_user()` - Unified authentication
- `record_failed_attempt()` - Login attempt tracking
- `create_user_session()` - Session creation
- `get_student_dashboard_data()` - Student dashboard data
- `get_staff_dashboard_data()` - Staff dashboard data
- `process_payment()` - Payment processing

### Academic Procedures
- `register_student_courses()` - Course registration
- `calculate_student_gpa()` - GPA calculation
- `mark_attendance()` - Attendance marking
- `generate_student_transcript()` - Transcript generation

### Financial Procedures
- `create_student_fee_account()` - Fee account creation
- `generate_fee_statement()` - Fee statement generation
- `calculate_late_fees()` - Late fee calculation
- `get_financial_summary()` - Financial summary

### Communication Procedures
- `send_message()` - Message sending
- `create_announcement()` - Announcement creation
- `send_notification()` - Notification sending
- `mark_message_read()` - Message read status

### Reporting Procedures
- `generate_report()` - Report generation
- `calculate_kpi_data()` - KPI calculation
- `schedule_report()` - Report scheduling
- `get_dashboard_data()` - Dashboard data

## Views

### Summary Views
- `student_profile_view` - Student profile summary
- `staff_profile_view` - Staff profile summary
- `fee_collection_summary` - Fee collection statistics
- `academic_performance_summary` - Academic performance
- `student_course_registration_summary` - Course registration stats
- `course_enrollment_statistics` - Course enrollment data
- `payment_transaction_summary` - Payment transaction data

## Triggers

### Automated Operations
- User activity logging
- Book availability updates
- Room occupancy tracking
- Fee account updates
- Academic record updates
- Notification status updates

## Security Features

- Password hashing for staff accounts
- Prepared statements in all procedures
- SQL injection prevention
- Login attempt limiting
- Session management
- Role-based access control
- Audit trail logging

## Data Relationships

### Key Foreign Key Relationships
- Users → All user-specific tables
- Academic Programs → Courses, Student Records
- Sessions → Semester, Fee Accounts, Attendance
- Courses → Registrations, Examinations, Attendance
- Fee Structure → Student Fee Accounts
- Payment Methods → Payment Transactions

## Deployment Instructions

### Quick Setup
1. Run the master file: `23_MASTER_ISNM_COMPLETE_SYSTEM.sql`
2. This will create all tables, views, stored procedures, and sample data
3. Configure PHP application to use the `isnm_db` database
4. Update database connection settings in `db.php`

### Individual Module Setup
If you need to deploy individual modules, run the specific SQL files in order:
1. Core system files (1-5)
2. Module-specific files (6-10)
3. Integration files (11-13)

## Default Credentials

### Sample Users
- **Admin**: admin@isnm.edu.ug / password123
- **Lecturer**: john.lecturer@isnm.edu.ug / password123
- **Secretary**: jane.secretary@isnm.edu.ug / password123

### Sample Students (No password required)
- **Alice Student**: STU2024001
- **Bob Student**: STU2024002
- **Carol Student**: STU2024003

## System Configuration

### Default Settings
- School: International School of Nursing and Midwifery
- Currency: UGX (Ugandan Shillings)
- Timezone: Africa/Kampala
- Session Timeout: 3600 seconds (1 hour)
- Max Login Attempts: 5

## Maintenance

### Regular Tasks
- Backup database daily
- Clear expired sessions weekly
- Archive old records monthly
- Update statistics quarterly
- Review system logs weekly

### Performance Optimization
- Indexes are created on all frequently queried columns
- Views are optimized for common reporting needs
- Stored procedures reduce query complexity
- Caching implemented for dashboard data

## Support

For technical support or questions about the database structure:
1. Review this documentation
2. Check the specific SQL file comments
3. Examine the stored procedures for business logic
4. Review the views for data relationships

## Version History

- **v1.0**: Initial complete system (23_MASTER_ISNM_COMPLETE_SYSTEM.sql)
- **v0.9**: Individual module files (18-22)
- **v0.8**: Legacy system files (01-17)

---

**Note**: The master file (23_MASTER_ISNM_COMPLETE_SYSTEM.sql) contains the complete, up-to-date system structure and should be used for all new deployments.
