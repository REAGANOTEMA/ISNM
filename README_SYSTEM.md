# ISNM Student Management System

A comprehensive Student Management System for Iganga School of Nursing and Midwifery (ISNM) built with PHP 8+, MySQL, and Bootstrap.

## 🚀 Features

### Core Functionality
- **Student Management**: Complete CRUD operations with photo upload
- **Excel Import**: Bulk import students using PhpSpreadsheet
- **User Management**: Role-based access control system
- **Authentication**: Secure login/logout with session management
- **Dashboard**: Role-specific dashboards with real-time statistics
- **Audit Logging**: Track all system changes
- **Search & Filter**: Advanced student search and filtering
- **Pagination**: Efficient data pagination
- **Photo Upload**: Student passport photo management

### Security Features
- **Prepared Statements**: SQL injection prevention
- **Password Hashing**: Secure password storage
- **Session Security**: Timeout-based session management
- **Role-Based Access**: Granular permission system
- **Input Sanitization**: XSS prevention
- **File Upload Security**: Image validation and size limits

## 📋 System Requirements

- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx Web Server
- PhpSpreadsheet Library
- Bootstrap 5.3+
- Font Awesome 6.0+

## 🛠️ Installation

### 1. Database Setup

```sql
-- Create database
CREATE DATABASE isnm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import the schema
mysql -u root -p isnm_db < database/isnm_complete_schema.sql
```

### 2. File Setup

1. Clone/copy the files to your web server directory
2. Ensure the following directories are writable:
   - `uploads/students/`
   - `students_data/`

### 3. Composer Dependencies

```bash
cd /path/to/isnm
composer require phpoffice/phpspreadsheet
```

### 4. Configuration

Edit `config/config.php` if needed:
- Database credentials (default: root/empty password)
- File upload settings
- Session timeout settings

## 👥 User Roles & Permissions

| Role | Permissions | Description |
|------|-------------|-------------|
| Admin | Full Access | System administrator |
| Principal | Full Access | School principal |
| Director | Full Access + Reports | School director |
| Bursar | Read, Reports, Fees | Financial management |
| HR | Read, Create, Update, Reports | Human resources |
| Secretary | Read, Create, Update | Student registration |
| Lecturer | Read Only | View assigned students |

## 📁 Project Structure

```
ISNM/
├── config/
│   ├── database.php          # Database configuration
│   └── config.php           # Application settings
├── controllers/
│   ├── AuthController.php   # Authentication logic
│   ├── StudentController.php # Student management
│   └── UserController.php    # User management
├── models/
│   ├── Student.php          # Student data model
│   └── User.php             # User data model
├── views/
│   ├── dashboard.php        # Main dashboard
│   ├── students.php         # Student management
│   ├── users.php            # User management
│   ├── login.php            # Login page
│   ├── logout.php           # Logout handler
│   └── profile.php          # User profile
├── database/
│   └── isnm_complete_schema.sql  # Database schema
├── uploads/
│   └── students/            # Student photos
├── students_data/           # Excel files for import
├── import.php               # Excel import script
└── vendor/                  # Composer dependencies
```

## 🎯 Default Login

- **Username**: admin
- **Password**: admin123

## 📊 Excel Import Format

Place Excel files in the `students_data/` folder with the following columns:

| Column | Field | Required |
|--------|-------|----------|
| A | full_name | Yes |
| B | registration_number | Yes |
| C | national_student_id_number | Yes |
| D | index_number | Yes |
| E | mobile_number | Yes |
| F | course | Yes |
| G | year | Yes |
| H | set_name | Yes |
| I | gender | Yes |

Run import: `http://localhost/ISNM/import.php`

## 🔧 API Endpoints

### Student Management
- `GET students.php` - List students with pagination
- `GET students.php?action=create` - Create student form
- `POST students.php?action=create` - Create student
- `GET students.php?action=edit&id={id}` - Edit student form
- `POST students.php?action=edit&id={id}` - Update student
- `GET students.php?action=delete&id={id}` - Delete student

### User Management
- `GET users.php` - List users
- `GET users.php?action=create` - Create user form
- `POST users.php?action=create` - Create user
- `GET users.php?action=edit&id={id}` - Edit user form
- `POST users.php?action=edit&id={id}` - Update user
- `GET users.php?action=delete&id={id}` - Delete user

### Authentication
- `GET login.php` - Login page
- `POST login.php` - Authenticate user
- `GET logout.php` - Logout user
- `GET profile.php` - User profile
- `POST profile.php` - Change password

## 📈 Database Schema

### Main Tables

1. **students** - Student records
2. **users** - System users
3. **roles** - User roles and permissions
4. **courses** - Course information
5. **audit_logs** - System activity logs

### Key Features

- **Soft Delete**: Students are marked as 'deleted' instead of permanent removal
- **Audit Trail**: All changes are logged with user, timestamp, and data
- **Unique Constraints**: Registration numbers are unique
- **Indexes**: Optimized for search and filtering
- **Views**: Pre-defined views for active records

## 🎨 UI Features

- **Responsive Design**: Works on desktop, tablet, and mobile
- **Bootstrap 5**: Modern, clean interface
- **Role-Based Navigation**: Menu items based on permissions
- **Real-time Updates**: Dashboard reflects current data
- **Search & Filter**: Advanced filtering options
- **Photo Management**: Student photo upload and display
- **Pagination**: Efficient data handling

## 🔒 Security Measures

- **SQL Injection Prevention**: All queries use prepared statements
- **XSS Protection**: Input sanitization and output encoding
- **CSRF Protection**: Session-based security
- **File Upload Security**: Image type and size validation
- **Password Security**: Strong hashing with salt
- **Session Management**: Secure session handling
- **Access Control**: Role-based permissions

## 📝 Usage Examples

### Add New Student
1. Login as admin/secretary
2. Go to Students → Add Student
3. Fill in all required fields
4. Upload passport photo (optional)
5. Click "Create Student"

### Import Excel File
1. Prepare Excel file with correct column format
2. Place file in `students_data/` folder
3. Run `import.php` from browser or command line
4. Monitor import progress and results

### Manage Users
1. Login as admin
2. Go to Users
3. Create, edit, or deactivate users
4. Assign appropriate roles

## 🚨 Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check MySQL server is running
   - Verify database credentials in `config/database.php`
   - Ensure database `isnm_db` exists

2. **File Upload Failed**
   - Check `uploads/students/` directory permissions
   - Verify file size doesn't exceed 2MB limit
   - Ensure file is valid image type (JPG, PNG)

3. **Excel Import Issues**
   - Verify PhpSpreadsheet is installed via Composer
   - Check Excel file format (.xlsx)
   - Ensure column headers match exactly

4. **Permission Denied**
   - Check user role and permissions
   - Verify session is active
   - Contact administrator for access

## 📞 Support

For technical support or issues:
1. Check error logs in PHP error log
2. Verify database connection
3. Check file permissions
4. Review system requirements

## 🔄 Updates & Maintenance

### Regular Tasks
- Backup database regularly
- Monitor audit logs
- Update user accounts as needed
- Clean up old deleted records
- Review system performance

### Security Updates
- Update PHP version regularly
- Keep dependencies updated
- Review user permissions
- Monitor for security issues

## 📄 License

This system is proprietary to Iganga School of Nursing and Midwifery.

---

**Version**: 1.0.0  
**Last Updated**: 2026  
**Developer**: ISNM IT Department
