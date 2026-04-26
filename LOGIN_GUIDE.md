# ISNM School Management System - Login Guide

## Overview
The ISNM School Management System uses a dual authentication system:
- **Students**: Login with NSIN number, first name, and contact number
- **Staff**: Login with username and password

## Student Login

### Required Information
1. **NSIN Number**: Your unique National Identification Number (format: CM1234567890123)
2. **First Name**: Your registered first name
3. **Contact Number**: Your registered phone number

### Login Steps
1. Go to the login page: `enhanced_login.php`
2. Click on "Student Login" tab
3. Enter your NSIN number
4. Enter your first name (as registered)
5. Enter your contact number
6. Click "Login as Student"

### Example Login
- **NSIN Number**: CM1234567890123
- **First Name**: Aisha
- **Contact Number**: 256771234567

## Staff Login

### Required Information
1. **Username**: Your assigned username (format: firstname.lastname)
2. **Password**: Your assigned password

### Login Steps
1. Go to the login page: `enhanced_login.php`
2. Click on "Staff Login" tab
3. Enter your username
4. Enter your password
5. Click "Login as Staff"

### Staff Login Credentials

| Role | Username | Password | Dashboard |
|------|----------|----------|-----------|
| Director General | john.mugisha | password | dashboards/director-general.php |
| Chief Executive Officer | sarah.nakato | password | dashboards/director-general.php |
| Director Academics | michael.mukasa | password | dashboards/director-general.php |
| Director ICT | david.ssekandi | password | dashboards/director-general.php |
| Director Finance | grace.namulinda | password | dashboards/director-general.php |
| School Principal | peter.lutaaya | password | dashboards/principal.php |
| School Secretary | joy.nabwire | password | dashboards/secretary.php |
| Academic Registrar | henry.mugisha | password | dashboards/academic-registrar.php |
| School Bursar | patience.nabasumba | password | dashboards/bursar.php |
| HR Manager | robert.ssewanyana | password | dashboards/hr-manager.php |

## Student Login Credentials

| Student ID | NSIN Number | First Name | Contact Number | Program |
|------------|-------------|------------|----------------|---------|
| ISNM/2025/1001 | CM1234567890123 | Aisha | 256771234567 | Diploma Nursing |
| ISNM/2025/1002 | CM1234567890124 | Brian | 256772345678 | Certificate Midwifery |
| ISNM/2025/1003 | CM1234567890125 | Catherine | 256773456789 | Diploma Midwifery |
| ISNM/2025/1004 | CM1234567890126 | David | 256774567890 | Certificate Nursing |
| ISNM/2025/1005 | CM1234567890127 | Esther | 256775678901 | Diploma Nursing Extension |
| ISNM/2025/1006 | CM1234567890128 | Frank | 256776789012 | Diploma Midwifery Extension |
| ISNM/2025/1007 | CM1234567890129 | Grace | 256777890123 | Certificate Nursing |
| ISNM/2025/1008 | CM1234567890130 | Henry | 256778901234 | Certificate Midwifery |
| ISNM/2025/1009 | CM1234567890131 | Irene | 256779012345 | Diploma Nursing |
| ISNM/2025/1010 | CM1234567890132 | Joseph | 256780123456 | Diploma Midwifery |

## Security Features

### Account Lockout
- **3 Failed Attempts**: Account locked for 30 minutes
- **Automatic Reset**: Lock expires after 30 minutes
- **Security Logging**: All login attempts are logged

### Session Security
- **Session Timeout**: 30 minutes of inactivity
- **IP Validation**: Session locked to original IP address
- **Session Regeneration**: New session ID on login

### Password Security (Staff)
- **Hashed Passwords**: All passwords are bcrypt hashed
- **Strong Validation**: Input validation and sanitization
- **Secure Transmission**: HTTPS recommended for production

## Troubleshooting

### Common Issues

#### "Student not found"
- Check NSIN number format (CM + 13 digits)
- Verify first name spelling
- Confirm contact number is registered
- Ensure student status is 'active'

#### "Invalid credentials"
- Verify username spelling
- Check password case sensitivity
- Ensure staff status is 'active'
- Confirm account is not locked

#### "Account locked"
- Wait 30 minutes for automatic unlock
- Contact administrator for manual unlock
- Check for suspicious activity

#### "Database connection failed"
- Verify database server is running
- Check database credentials in config.php
- Ensure database exists and is accessible

### Support Contacts
- **Technical Support**: ict@isnm.ac.ug
- **Administrative Support**: admin@isnm.ac.ug
- **Student Support**: registrar@isnm.ac.ug

## Database Setup

### Import Enhanced Database
1. Access phpMyAdmin or MySQL command line
2. Import `database/enhanced_login_system.sql`
3. Verify all tables are created
4. Check sample data is inserted

### Verify Login Functionality
1. Test student login with sample data
2. Test staff login with sample credentials
3. Verify dashboard redirections work
4. Check session management

## URL Structure

### Login Pages
- **Main Login**: `/enhanced_login.php`
- **Legacy Login**: `/login.php` (redirects to enhanced)
- **Staff Login**: `/staff-login.php` (redirects to enhanced)

### Dashboard URLs
- **Director General**: `/dashboards/director-general.php`
- **Principal**: `/dashboards/principal.php`
- **Secretary**: `/dashboards/secretary.php`
- **Academic Registrar**: `/dashboards/academic-registrar.php`
- **Bursar**: `/dashboards/bursar.php`
- **HR Manager**: `/dashboards/hr-manager.php`
- **Student Profile**: `/student_profile.php`

## Best Practices

### For Students
- Keep your NSIN number confidential
- Use your registered contact number
- Report any login issues immediately
- Log out after using shared computers

### For Staff
- Change default passwords on first login
- Use strong, unique passwords
- Don't share login credentials
- Report suspicious activity

### For Administrators
- Regularly review login logs
- Monitor for failed login attempts
- Keep software updated
- Backup database regularly

## Development Notes

### File Structure
```
ISNM/
├── enhanced_login.php          # Main login page
├── includes/
│   ├── auth_functions.php      # Authentication functions
│   ├── config.php              # Database configuration
│   └── functions.php           # Utility functions
├── database/
│   └── enhanced_login_system.sql # Database schema
└── dashboards/                 # Role-specific dashboards
```

### Key Functions
- `authenticateStudent()` - Student authentication
- `authenticateStaff()` - Staff authentication
- `checkAuth()` - Authorization check
- `hasPermission()` - Permission validation
- `logout()` - Secure logout

### Security Measures
- SQL injection prevention with prepared statements
- XSS protection with output sanitization
- CSRF protection with session tokens
- Input validation and sanitization
- Account lockout after failed attempts

## Migration Instructions

### From Old System
1. Backup existing database
2. Run enhanced database script
3. Update login pages to redirect to enhanced system
4. Test all login methods
5. Update user documentation

### Data Migration
1. Export existing user data
2. Transform to new schema format
3. Import to enhanced database
4. Verify data integrity
5. Test login functionality

## Testing Checklist

### Student Login Testing
- [ ] Login with correct NSIN, name, phone
- [ ] Login with incorrect NSIN
- [ ] Login with incorrect name
- [ ] Login with incorrect phone
- [ ] Account lockout after 3 attempts
- [ ] Automatic unlock after 30 minutes

### Staff Login Testing
- [ ] Login with correct username/password
- [ ] Login with incorrect password
- [ ] Login with incorrect username
- [ ] Account lockout after 3 attempts
- [ ] Automatic unlock after 30 minutes
- [ ] Dashboard redirection based on role

### Security Testing
- [ ] SQL injection prevention
- [ ] XSS protection
- [ ] Session security
- [ ] Input validation
- [ ] Logout functionality

This guide provides comprehensive information for using and maintaining the ISNM School Management System login functionality.
