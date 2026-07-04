# ISNM System - Database and Staff Credentials Setup

## Overview
This document contains the complete setup for the ISNM (Iganga School of Nursing & Midwifery) ERP System with new database credentials and staff accounts.

## Database Credentials

### Database 1: ICT Management
```
Hostname:        localhost
Database:        igangaschoolofl_ict
Username:        igangaschoolofl_ict
Password:        HHCrQVjr6QNKzSEVtx9J
Port:            3306
Charset:         utf8mb4
```

### Database 2: Student Management
```
Hostname:        localhost
Database:        igangaschoolofl_students_db
Username:        igangaschoolofl_students_db
Password:        hbkKdmMHUfHTHuxWKPRf
Port:            3306
Charset:         utf8mb4
```

### Database 3: Staff Management
```
Hostname:        localhost
Database:        igangaschoolofl_staffs_db
Username:        igangaschoolofl_staffs_db
Password:        AgKzJjZZnT5q58jCahs8
Port:            3306
Charset:         utf8mb4
```

### Database 4: Website Content
```
Hostname:        localhost
Database:        igangaschoolofl_website_db
Username:        igangaschoolofnursingandmidwifery
Password:        AaCH75gXpekcFQj5wPZn
Port:            3306
Charset:         utf8mb4
```

---

## Staff Credentials - Complete List

### LEADERSHIP & STRATEGY

| Position | Email | Password | Department |
|----------|-------|----------|-----------|
| Director General | directorgeneral@igangaschoolofnursingandmidwifery.ac.ug | DorisJoy2026 | Leadership |
| Chief Executive Officer | ceo@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God | Leadership |

### ACADEMIC AFFAIRS

| Position | Email | Password | Department |
|----------|-------|----------|-----------|
| Director Academics | directoracademic@igangaschoolofnursingandmidwifery.ac.ug | Stephen123 | Academic Affairs |
| School Principal | principal@igangaschoolofnursingandmidwifery.ac.ug | isnm2026 | Academic Affairs |
| Deputy Principal | dep-principal@igangaschoolofnursingandmidwifery.ac.ug | Isnm2026 | Academic Affairs |
| Academic Registrar | academicregistrar@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God | Academic Affairs |
| Head of Nursing | nursing-dep@igangaschoolofnursingandmidwifery.ac.ug | isnm4life | Nursing |
| Head of Midwifery | midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug | Life2save | Midwifery |
| Senior Lecturer | senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug | isnm2026 | Academic Affairs |
| Lecturer | lecturers@igangaschoolofnursingandmidwifery.ac.ug | Isnm4life | Academic Affairs |

### FINANCE & ACCOUNTS

| Position | Email | Password | Department |
|----------|-------|----------|-----------|
| Director Finance | finance@igangaschoolofnursingandmidwifery.ac.ug | DorisJoy2026 | Finance |
| School Bursar | bursar@igangaschoolofnursingandmidwifery.ac.ug | bursar@isnm | Finance |

### HUMAN RESOURCES & ADMINISTRATION

| Position | Email | Password | Department |
|----------|-------|----------|-----------|
| HR Manager | hr-manager@igangaschoolofnursingandmidwifery.ac.ug | Alexis2026 | Human Resources |
| School Secretary | secretary@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God | Administration |

### STUDENT SERVICES

| Position | Email | Password | Department |
|----------|-------|----------|-----------|
| Director Admissions | admissions@igangaschoolofnursingandmidwifery.ac.ug | 2268926931 | Admissions |
| Admissions Requirements Officer | admissions-req@igangaschoolofnursingandmidwifery.ac.ug | 2268926931 | Admissions |
| School Librarian | library@igangaschoolofnursingandmidwifery.ac.ug | isnm2026 | Library |
| Matron | matron@igangaschoolofnursingandmidwifery.ac.ug | Isnm2026 | Student Services |
| Warden | warden@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God | Student Services |
| Sickbay Nurse | sickbay@igangaschoolofnursingandmidwifery.ac.ug | isnm2026 | Health Services |
| Guild President | guildpresident@igangaschoolofnursingandmidwifery.ac.ug | isnm4life | Student Services |

### OPERATIONS & LOGISTICS

| Position | Email | Password | Department |
|----------|-------|----------|-----------|
| Director ICT | dannybict@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God | ICT |
| Director ICT (Alt) | directorict@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God | ICT |
| Computer Lab Manager | computer-lab@igangaschoolofnursingandmidwifery.ac.ug | Techno123 | ICT |
| Computer Lab Technician | computerlab@igangaschoolofnursingandmidwifery.ac.ug | Techno123 | ICT |
| Skills Lab Manager | skillslab@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God | ICT |
| Skills Lab Technician | skills-lab@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God | ICT |
| Storekeeper | store@igangaschoolofnursingandmidwifery.ac.ug | Isnm4life | Logistics |
| Driver | drivers@igangaschoolofnursingandmidwifery.ac.ug | isnm4life | Logistics |
| Security Officer | security@igangaschoolofnursingandmidwifery.ac.ug | safty1st | Security |

---

## Setup Instructions

### Step 1: Update Configuration
The `.env` file has been updated with new database credentials:
```
STUDENTS_DB_USER=igangaschoolofl_students_db
STUDENTS_DB_PASS=hbkKdmMHUfHTHuxWKPRf

STAFF_DB_USER=igangaschoolofl_staffs_db
STAFF_DB_PASS=AgKzJjZZnT5q58jCahs8

WEBSITE_DB_USER=igangaschoolofnursingandmidwifery
WEBSITE_DB_PASS=AaCH75gXpekcFQj5wPZn

ICT_DB_USER=igangaschoolofl_ict
ICT_DB_PASS=HHCrQVjr6QNKzSEVtx9J
```

### Step 2: Verify Databases
Run the health check to verify all databases are accessible:
```bash
C:\xampp\php\php.exe health-check.php
```

### Step 3: Setup System
Run the complete system setup script:
```bash
# Verify databases and users
C:\xampp\php\php.exe complete_system_setup.php --verbose

# To also seed staff credentials
C:\xampp\php\php.exe complete_system_setup.php --seed-staff --verbose
```

### Step 4: Seed Staff Accounts
To populate staff accounts with provided credentials:
```bash
C:\xampp\php\php.exe seed_staff_credentials.php
```

### Step 5: Access System
Staff can now login with their provided credentials:
- **Login URL:** http://localhost/ISNM/staff-login.php
- **Email:** Use credentials from above list
- **Password:** Use corresponding password from list

---

## File References

### Configuration Files
- `.env` - Database credentials and environment settings
- `config/database.php` - Database connection configuration
- `credentials_database.php` - Staff and database credentials reference

### Setup Scripts
- `health-check.php` - System health verification
- `complete_system_setup.php` - Complete system initialization
- `seed_staff_credentials.php` - Populate staff accounts
- `database_init.php` - Database initialization
- `master_database_repair.php` - Emergency recovery

### Documentation
- `DATABASE_RECOVERY_GUIDE.md` - Recovery procedures
- `DATABASE_FIX_SUMMARY.md` - System fixes overview
- `STAFF_CREDENTIALS_SETUP.md` - This file

---

## Security Considerations

### Important Security Notes

1. **Protect Credentials**
   - Store this document in a secure location
   - Limit access to authorized personnel only
   - Do not share credentials via email or chat
   - Use a secure password manager if possible

2. **Database Passwords**
   - Each database has unique credentials
   - Users can only access their assigned database
   - Root access is not provided to staff

3. **Staff Passwords**
   - Initial passwords provided for first login
   - Staff should change passwords upon first login
   - Encourage strong password policies
   - Implement 2FA for sensitive roles (optional)

4. **Access Control**
   - Each staff member has role-based access
   - Admin users have full system access
   - Regular users have limited permissions
   - Review access levels periodically

---

## Troubleshooting

### Cannot Connect to Database
**Error:** "Access denied for user..."
**Solution:** Verify credentials in `.env` file match the provided database credentials

### Staff Account Not Found
**Error:** "No matching user found"
**Solution:** Run `seed_staff_credentials.php` to populate staff accounts

### Locked Staff Account
**Error:** "Account is locked"
**Solution:** Contact HR Manager or Director to unlock account

### Password Reset
**Process:**
1. Staff member navigates to password reset page
2. Enter email address
3. Follow reset link in email
4. Create new password
5. Login with new password

---

## Next Steps

1. ✓ Update .env with new credentials (DONE)
2. ✓ Create staff seeding scripts (DONE)
3. Run complete system setup
4. Seed staff accounts
5. Test login with each role
6. Configure backup schedule
7. Train staff on system
8. Monitor system performance

---

## Support Contacts

For system issues or credential problems:
- **Director ICT:** dannybict@igangaschoolofnursingandmidwifery.ac.ug (Lovely2God)
- **IT Support:** For technical issues
- **HR Manager:** For staff account issues

---

## Document Revision

- **Created:** 2026-07-04
- **Updated:** 2026-07-04
- **Version:** 1.0
- **Status:** PRODUCTION READY

**CONFIDENTIAL - For authorized personnel only. Do not share publicly.**

