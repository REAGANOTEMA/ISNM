# Requirements Portal - Director Dashboard

## Overview

The Requirements Portal is a comprehensive student requirements and clearance tracking system designed for the Director of Requirements role. It allows directors to manage 20 essential requirement items across Medical, Office, and Cleaning categories, tracking which students have completed their requirements.

## Features

### 📊 Dashboard Statistics
- **Total Students**: Shows all active students in the system
- **All Cleared**: Count of students who have completed all requirements
- **Pending**: Students with incomplete requirements
- **Overall Progress**: Percentage of total requirements cleared across all students

### 🔍 Search & Filter
- **Search Bar**: Search students by name, admission number, or phone number
- **Filter Options**:
  - All Students
  - All Cleared (students with complete requirements)
  - Pending Requirements (students with incomplete items)
  - Initialized (students with tracking started)

### ✅ Requirements Checklist
- **20 Required Items** organized by category:
  - **Medical** (4 items): Surgical Gloves, Examination Gloves, Face Masks, Heavy Duty Gloves
  - **Office** (2 items): Photocopying Ream, Ruled Paper Reams
  - **Cleaning** (14 items): Omo, Toilet Papers, Compound brooms, Soft brooms, Rake, Cobweb brush, Scrubbing Brush, Squeezer, Toilet Brush, JIK, Vim, Mops, Sanitizer, Liquid Soap

### 🎯 Bulk Actions
- **Clear All**: Mark all requirements as completed for a student
- **Reset All**: Reset all clearances for a student

### 📤 Export
- **CSV Export**: Download complete requirements status for all students with progress percentages

### 📱 Responsive Design
- Works on desktop, tablet, and mobile devices
- Clean, intuitive interface matching existing dashboards

## Installation & Setup

### Step 1: Run Setup Script

Navigate to the setup script in your browser:

```
http://localhost/ISNM.worktrees/agents-organogram-department-navigation/setup_requirements_portal.php
```

The setup script will:
1. Create `requirement_items` table
2. Create `student_requirements` table
3. Add "Director of Requirements" role to staff_roles
4. Insert all 20 requirement items

### Step 2: Create Staff Account

1. Login to your admin/CEO dashboard
2. Create a new staff account with these settings:
   - **Role**: "Director of Requirements"
   - **Email**: director@example.com
   - **Password**: [secure password]

### Step 3: Access Dashboard

1. Login with the Director of Requirements account
2. You'll be redirected to: `dashboards/requirements-director.php`

## How to Use

### Managing Student Requirements

1. **View All Students**
   - Dashboard loads all active students from students_db
   - Each student appears as a collapsible card
   - Progress bar shows completion percentage

2. **Search for a Student**
   - Enter student name, admission number, or phone
   - Click "Search" to filter results

3. **Clear Requirements**
   - Click checkbox next to each item to mark as cleared
   - Changes save automatically via AJAX
   - Cleared items show strikethrough text

4. **Bulk Actions**
   - **Clear All**: Clears all 20 items for a student at once
   - **Reset All**: Unchecks all items, useful for corrections

5. **View Progress**
   - Progress bar shows percentage of completed items
   - Stats update in real-time

### Exporting Data

1. Click **📥 Export CSV** button
2. CSV file downloads with:
   - Student admission number and name
   - All 20 requirement items (Yes/No)
   - Overall progress percentage
   - Perfect for reporting and analysis

## Database Schema

### requirement_items
```sql
- id (INT, PRIMARY KEY)
- name (VARCHAR 100, UNIQUE)
- category (VARCHAR 50)
- status (VARCHAR 20, default: 'active')
- created_at (TIMESTAMP)
```

### student_requirements
```sql
- id (INT, PRIMARY KEY)
- student_id (INT, FOREIGN KEY)
- student_admission_number (VARCHAR 50)
- student_name (VARCHAR 100)
- student_phone (VARCHAR 20)
- requirement_item_id (INT, FOREIGN KEY)
- is_cleared (BOOLEAN, default: false)
- cleared_by (VARCHAR 100)
- cleared_date (DATETIME)
- notes (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

## API Endpoints

All endpoints are handled through `auth-handler.php` as POST requests with JSON responses.

### Requirement Clear
```
POST /auth-handler.php
- action: requirement_clear
- student_id: integer
- item_id: integer
```

### Requirement Unclear
```
POST /auth-handler.php
- action: requirement_unclear
- student_id: integer
- item_id: integer
```

### Clear All Requirements
```
POST /auth-handler.php
- action: requirement_clear_all
- student_id: integer
```

### Reset All Requirements
```
POST /auth-handler.php
- action: requirement_unclear_all
- student_id: integer
```

### CSV Export
```
GET /dashboards/requirements-director.php?export=csv
```

## Functions Reference

### includes/requirements_functions.php

#### getStudentsList($limit, $offset)
Get all active students from students_db with pagination

#### searchStudents($searchTerm)
Search students by name, admission number, or phone

#### getAllRequirementItems()
Get all active requirement items

#### getStudentRequirements($studentId)
Get all requirements for a specific student

#### initializeStudentRequirements($studentId, $admissionNumber, $studentName, $studentPhone)
Create requirement records for a student (called automatically on first load)

#### clearRequirement($studentId, $requirementId, $clearedBy)
Mark a single requirement as cleared

#### unclearRequirement($studentId, $requirementId)
Mark a single requirement as not cleared

#### clearAllRequirements($studentId, $clearedBy)
Mark all requirements as cleared for a student

#### getRequirementStats()
Get dashboard statistics (total students, cleared, pending, etc.)

#### getStudentProgress($studentId)
Get completion percentage for a specific student

#### filterStudents($filterBy, $filterValue)
Filter students based on criteria (all_cleared, pending, initialized)

#### exportRequirementsToCSV()
Generate CSV export of all requirements

## File Structure

```
/dashboards/requirements-director.php    - Main dashboard page
/includes/requirements_functions.php     - All helper functions
/setup_requirements_portal.php           - Setup script
/auth-handler.php                        - API endpoints (updated)
```

## Security

- ✓ Authenticated access (staff only)
- ✓ Role-based access (Director of Requirements)
- ✓ Prepared statements to prevent SQL injection
- ✓ CSRF protection via session-based authentication
- ✓ Sanitized input for all searches and filters
- ✓ HTTPS recommended in production

## Troubleshooting

### Setup Script Issues

If tables already exist:
- Script uses `CREATE TABLE IF NOT EXISTS`
- Won't overwrite existing tables
- Safe to run multiple times

### Students Not Appearing

1. Ensure students_db has active students with `role = 'student'` and `is_active = 1`
2. Check database connection in `/config/database.php`
3. Verify staff account has "Director of Requirements" role

### AJAX Requests Failing

1. Check browser console for errors
2. Verify auth-handler.php is accessible
3. Confirm session is active (user logged in)
4. Check PHP error logs

### CSV Export Empty

1. Ensure students have been loaded and initialized
2. Verify database connection
3. Check that requirement_items table is populated

## Customization

### Adding New Requirement Items

Edit the setup script or use direct SQL:

```sql
INSERT INTO staffs_db.requirement_items (name, category, status)
VALUES ('New Item', 'Category', 'active');
```

### Changing Category Names

Update the items in `requirement_items` table:

```sql
UPDATE requirement_items SET category = 'New Category' WHERE id = X;
```

### Modifying Report Fields

Edit the CSV export function in `requirements_functions.php`:

```php
function exportRequirementsToCSV()
```

## Support

For issues or feature requests, contact the system administrator.

## License

Part of the ISNM School Management System. All rights reserved.
