# Requirements Portal - Quick Reference Card

## 🚀 Get Started in 3 Minutes

### 1️⃣ Initialize Database
```
http://localhost/ISNM.worktrees/agents-organogram-department-navigation/setup_requirements_portal.php
```
👉 Click "Proceed with Setup"

### 2️⃣ Create Director Account
In admin panel:
- Full Name: Requirements Director
- Email: director@example.com
- **Role: Director of Requirements** ← Key!
- Password: [secure]

### 3️⃣ Login & Use
- Logout
- Go to Staff Login
- Login with director email
- ✨ Dashboard loads automatically!

---

## 📊 Dashboard Overview

```
┌─────────────────────────────────────────┐
│ 📋 Requirements Portal                  │
│ Welcome, Director Name                  │
├─────────────────────────────────────────┤
│ STATS: 250 Students | 180 Cleared       │
├─────────────────────────────────────────┤
│ SEARCH: [____________] [Filter ▼] [Go] │
├─────────────────────────────────────────┤
│ STUDENT LIST:                           │
│ ✓ John Doe (U001/CM/001/20)  72% ▓▓▓   │
│   ☑ Surgical Gloves                    │
│   ☑ Face Masks                         │
│   ☐ Compound brooms                    │
│   [✓ Clear All] [✗ Reset All]         │
│                                         │
│ ✓ Jane Smith (U001/CM/002/20) 90% ▓▓▓  │
│   ... (more items)                     │
└─────────────────────────────────────────┘
```

---

## 🎯 Common Tasks

### Search Students
1. Enter name, admission #, or phone in search box
2. Click "Search"
3. Results filter in real-time

### Mark Requirement Complete
1. Find the item checkbox
2. Click to check ✓
3. Auto-saves via AJAX (no page reload!)

### Clear All for Student
1. Click "✓ Clear All" button
2. Confirm in popup
3. All 20 items marked complete

### Reset All for Student
1. Click "✗ Reset All" button
2. Confirm in popup
3. All items unmarked

### Export to CSV
1. Click "📥 Export CSV"
2. File downloads automatically
3. Open in Excel/Sheets

---

## 📋 The 20 Items

### Medical (4)
- ☐ Surgical Gloves
- ☐ Examination Gloves
- ☐ Face Masks
- ☐ Heavy duty Gloves

### Office (2)
- ☐ Photocopying Ream
- ☐ Ruled Paper Reams

### Cleaning (14)
- ☐ Omo
- ☐ Toilet Papers
- ☐ Compound brooms
- ☐ Soft brooms
- ☐ Rake
- ☐ Cobweb brush
- ☐ Scrubbing Brush
- ☐ Squeezer
- ☐ Toilet Brush
- ☐ JIK
- ☐ Vim
- ☐ Mops
- ☐ Sanitizer
- ☐ Liquid Soap

---

## 🔍 Filter Options

| Filter | Shows |
|--------|-------|
| All Students | Everyone (default) |
| All Cleared | Only complete students |
| Pending | Only incomplete students |
| Initialized | Already in system |

---

## 📱 UI Elements

| Element | Action |
|---------|--------|
| 🔍 Search | Find students |
| Filter ▼ | Change view |
| ✓ Clear All | Complete all items |
| ✗ Reset All | Undo all completions |
| 📥 Export | Download CSV file |
| ↻ Reset | Clear filters |
| ☑/☐ Checkbox | Mark single item |
| Progress Bar | Shows % complete |

---

## 📊 Statistics Panel

```
Total Students: 250          All Cleared: 180
Pending: 70                  Overall: 72%
```

- **Total Students**: All active students
- **All Cleared**: Students with complete requirements
- **Pending**: Students with incomplete items
- **Overall**: Percentage of all items cleared

---

## ⚙️ Admin Tools

### Verify Installation
```
http://localhost/.../verify_requirements_portal.php
```
✓ Checks all components installed

### View Documentation
- **User Guide**: REQUIREMENTS_PORTAL_README.md
- **Install Guide**: INSTALLATION_GUIDE.md
- **This Card**: Quick Reference (you are here!)

### Validate PHP
```
validate_syntax.bat
```
✓ Checks code syntax

---

## 🆘 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| No students show | Run verify script |
| Dashboard won't load | Check login & role |
| Checkboxes don't work | Check browser console |
| Export empty | Ensure students loaded |
| Role not found | Run setup script again |

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| dashboards/requirements-director.php | Main dashboard |
| includes/requirements_functions.php | Helper functions |
| setup_requirements_portal.php | Initialize system |
| verify_requirements_portal.php | Check installation |
| auth-handler.php | API endpoints |

---

## 🔐 Access Control

✓ **Who can access:**
- Staff with "Director of Requirements" role
- Authenticated sessions only
- Role verified on every request

✓ **What's protected:**
- All student data (FERPA compliant)
- All requirement records
- All changes logged with staff name

---

## 📈 Data Saved

Each action saves:
- ✓ Item checked
- ✓ Staff member name
- ✓ Timestamp
- ✓ Completion date
- ✓ Notes (optional)

All data persists in staffs_db.student_requirements table.

---

## 💾 CSV Export Format

```
Admission #, Name, Phone, Item1, Item2, ..., Progress
U001/CM/1/20, John Doe, 0701234567, Yes, Yes, No, ..., 72%
U001/CM/2/20, Jane Smith, 0702345678, Yes, Yes, Yes, ..., 100%
```

Perfect for Excel analysis, reporting, and record-keeping.

---

## 🎓 Student Journey

```
Student Created
    ↓
First Dashboard Load → Auto-initialize (20 items created)
    ↓
Director Reviews → Checks off as completed
    ↓
Progress Updates → Real-time percentage
    ↓
All Cleared → 100% complete
    ↓
CSV Export → Documentation
```

---

## 📞 Support

**Quick Help:**
1. verify_requirements_portal.php - System status
2. REQUIREMENTS_PORTAL_README.md - Features
3. INSTALLATION_GUIDE.md - Detailed help

**Common Issues:**
- Check verify script first
- Read troubleshooting guide
- Review error logs
- Contact admin if stuck

---

## 🎉 You're All Set!

- ✅ System installed
- ✅ Database configured
- ✅ Staff role created
- ✅ Ready to manage requirements!

Start marking off items and track student progress! 🚀

---

**Version:** 1.0  
**Updated:** [Today]  
**Status:** Production Ready

Print this card or save as reference! 📋
