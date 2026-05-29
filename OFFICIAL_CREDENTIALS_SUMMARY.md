# ISNM System - Official Department Credentials & Role Updates

## Summary of Changes
All system SQL files have been updated with official department emails and passwords. The "Lab Technicians" role has been completely replaced with "Sickbay" across all dashboards and staff records.

---

## Department Credentials (Complete List)

| Department | Email | Password |
|-----------|-------|----------|
| School Principal | principal@igangaschoolofnursingandmidwifery.ac.ug | isnm2026 |
| Deputy Principal | dep-principal@igangaschoolofnursingandmidwifery.ac.ug | Isnm2026 |
| Academic Registrar | academicregistrar@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God |
| HR Manager | hr-manager@igangaschoolofnursingandmidwifery.ac.ug | Alexis2026 |
| School Secretary | secretary@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God |
| School Librarian | library@igangaschoolofnursingandmidwifery.ac.ug | isnm2026 |
| Head of Nursing | nursing-dep@igangaschoolofnursingandmidwifery.ac.ug | isnm4life |
| Head of Midwifery | midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug | Life2save |
| Senior Lecturers | senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug | isnm2026 |
| Lecturers | lecturers@igangaschoolofnursingandmidwifery.ac.ug | Isnm4life |
| Matrons | matron@igangaschoolofnursingandmidwifery.ac.ug | Isnm2026 |
| Wardens | warden@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God |
| **Sickbay** | **sickbay@igangaschoolofnursingandmidwifery.ac.ug** | **isnm2026** |
| Drivers | drivers@igangaschoolofnursingandmidwifery.ac.ug | isnm4life |
| Security | security@igangaschoolofnursingandmidwifery.ac.ug | safty1st |
| Store Keeper | store@igangaschoolofnursingandmidwifery.ac.ug | Isnm4life |
| Guild President | guildpresident@igangaschoolofnursingandmidwifery.ac.ug | isnm4life |
| Admissions | admissions@igangaschoolofnursingandmidwifery.ac.ug | 2268926931 |
| Director ICT | dannybict@igangaschoolofnursingandmidwifery.ac.ug | Lovely2God |

---

## Files Updated

### 1. **sql/staffs/05_all_departments_complete_dashboards.sql**
   - All staff credentials updated with official emails
   - All passwords replaced with bcrypt hashes
   - Lab Technicians replaced with Sickbay

### 2. **sql/staffs/04_final_complete_staffs_database.sql**
   - Staff role definitions updated
   - "Lab Technicians" role replaced with "Sickbay"
   - All staff seed entries updated with official credentials
   - Dashboard path updated: `dashboards/sickbay.php`
   - Stored procedure renamed: `get_sickbay_dashboard_statistics()` (was `get_lab_technicians_dashboard_statistics()`)

### 3. **sql/staffs/12_sickbay_dashboard.sql**
   - Renamed to support Sickbay operations
   - User accounts updated
   - All references to "Lab Technicians" changed to "Sickbay"

### 4. **sql/staffs/99_MASTER_ALL_DEPARTMENTS.sql**
   - Master credentials summary updated
   - All official department credentials documented
   - Lab Technicians reference removed

---

## Key Changes

✅ **Lab Technicians → Sickbay**: Complete transition across all SQL files
✅ **Official Emails**: All departments now use official school domain emails
✅ **Secure Passwords**: All passwords hashed with bcrypt ($2y$10$ format)
✅ **Professional Naming**: All roles and email addresses standardized
✅ **Complete Documentation**: All credentials documented in master file

---

## Email Access Information

POP/IMAP Server: `mail.igangaschoolofnursingandmidwifery.ac.ug`
SMTP Server: `mail.igangaschoolofnursingandmidwifery.ac.ug` (Port 587)

All department staff should use their assigned email address as the username for email client configuration.

---

## Database Deployment

To deploy these credentials to the system:

```bash
# Load the main staff database
mysql -u root -p igangaschoolofl_staffs_db < sql/staffs/04_final_complete_staffs_database.sql

# Load all dashboards
mysql -u root -p igangaschoolofl_staffs_db < sql/staffs/05_all_departments_complete_dashboards.sql

# Or run the master file to load everything
mysql -u root -p igangaschoolofl_staffs_db < sql/staffs/99_MASTER_ALL_DEPARTMENTS.sql
```

---

**Status**: ✅ All credentials finalized and professionally configured
**Last Updated**: May 29, 2026
