# ISNM Staff Login System - Final Setup Summary

## Last Updated: May 28, 2026

---

## Staff Dashboard Login Credentials

All staff accounts use the following standardized credentials format:

| Role | Email | Initial Password | Dashboard |
|------|-------|------------------|-----------|
| Director General | `director.general@isnm.ac.ug` | `staff@123` | Director General Dashboard |
| CEO | `ceo@isnm.ac.ug` | `staff@123` | CEO Dashboard |
| School Principal | `principal@isnm.ac.ug` | `staff@123` | Principal Dashboard |
| School Secretary | `secretary@isnm.ac.ug` | `staff@123` | Secretary Dashboard |
| Academic Registrar | `registrar@isnm.ac.ug` | `staff@123` | Academic Registrar Dashboard |
| School Bursar | `bursar@isnm.ac.ug` | `staff@123` | Bursar Dashboard |
| HR Manager | `hr.manager@isnm.ac.ug` | `staff@123` | HR Manager Dashboard |
| Director Academics | `director.academics@isnm.ac.ug` | `staff@123` | Director Academics Dashboard |
| Director ICT | `director.ict@isnm.ac.ug` | `staff@123` | Director ICT Dashboard |
| Director Finance | `director.finance@isnm.ac.ug` | `staff@123` | Director Finance Dashboard |

---

## Login Process

### Via Organogram (Recommended)
1. Navigate to `organogram.php`
2. Click on a staff position
3. You will be redirected to `staff-login.php?position=[role]`
4. Enter your email and password
5. Click **Login**

### Direct Staff Login
1. Navigate to `staff-login.php`
2. Enter your email and password
3. Click **Login**

---

## Password Reset & Change

**Important:** All staff must change their password on first login for security.

### Reset Password via Email
1. Go to `staff-password-reset.php`
2. Click **Forgot Password?**
3. Enter your email address
4. Follow the password reset instructions
5. Set a new secure password

### Change Password After Login
1. Click your profile in the dashboard
2. Select **Change Password**
3. Enter your current password
4. Enter your new password
5. Confirm the new password
6. Save changes

---

## System Architecture

### Database Structure
- **Database:** `igangaschoolofl_staffs_db` (primary staff database)
- **Table:** `staff`
- **Password Hashing:** BCrypt (PASSWORD_DEFAULT)
- **Session Management:** 30-minute timeout

### Files Modified
1. **database/create_users_table.php** - PHP seed script with standardized staff data
2. **sql/staffs/04_final_complete_staffs_database.sql** - SQL initialization with all dashboard staff
3. **auth-service.php** - Authentication service with password verification for new default password

### Key Features
✅ Standardized email format: `@isnm.ac.ug`  
✅ Unified default password: `staff@123`  
✅ BCrypt password hashing (secure)  
✅ Password reset capability (staff-password-reset.php)  
✅ Role-based dashboard routing  
✅ Organogram-based role enforcement  
✅ Session security with timeout  
✅ Failed login tracking  
✅ First-login password change enforcement  

---

## Setup Instructions

### For Initial System Setup
Run one of the following:

**Option 1: Via PHP Seed Script**
```bash
curl http://localhost/ISNM/database/create_users_table.php
```

**Option 2: Via MySQL**
```bash
mysql -u root -p igangaschoolofl_staffs_db < sql/staffs/04_final_complete_staffs_database.sql
```

### Verify Setup
1. Test login with: `director.general@isnm.ac.ug` / `staff@123`
2. Verify dashboard loads correctly
3. Test password reset functionality
4. Confirm role-based access control

---

## Security Notes

- All passwords are hashed using BCrypt (industry standard)
- Hashed password: `$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K`
- Plain text password comparison used for initial login only if password hasn't been changed
- Sessions expire after 30 minutes of inactivity
- Failed login attempts are logged and tracked
- All staff must change password on first login

---

## Troubleshooting

### Login Issues
- **"Invalid email or password"**: Verify email is exactly as listed above
- **Blank page after login**: Check database connection and role configuration
- **Session timeout**: User logged out due to 30-minute inactivity

### Password Reset Issues
- **Email not received**: Check email service configuration
- **Invalid token**: Token may have expired; request a new reset

### Database Issues
- **Connection failed**: Verify database is running
- **Access denied**: Check database credentials in `config/database.php`

---

## Contact Support

For system issues, contact:
- **System Administrator**: director.general@isnm.ac.ug
- **Technical Support**: director.ict@isnm.ac.ug

---

*This document is the final configuration reference for the ISNM Staff Authentication System.*
