# 🏥 Iganga School of Nursing and Midwifery (ISNM) Management System

A comprehensive digital management system for Iganga School of Nursing and Midwifery, featuring a professional website, role-based management dashboards, student portal, and complete administrative functionality.

## 🎯 Project Overview

The ISNM system is a complete school management solution designed specifically for healthcare education institutions. It includes:

- **Professional School Website** with all required pages
- **Role-Based Management System** with 20+ different user roles
- **Student Portal** with academic records, finances, and communication
- **Comprehensive Financial Management** with bursar dashboard
- **Application Processing System** with file uploads
- **Communication System** for messaging between all stakeholders

## 🛠️ Technologies Used

1. **PHP 8.1** - Backend development
2. **MySQL (MariaDB)** - Database management
3. **Bootstrap 5** - Responsive UI framework
4. **JavaScript & jQuery** - Interactive functionality
5. **HTML5 & CSS3** - Modern web standards
6. **Font Awesome 6** - Icon library

## ✨ Key Features

### 🌐 School Website
- **Homepage** with school information and statistics
- **About Page** with vision, mission, and governance
- **Programs Page** with detailed academic offerings
- **Application Form** with comprehensive fields and file uploads
- **Contact Page** with map integration
- **Donation & Volunteer** pages for community engagement
- **Organizational Structure** with role-based login access

### 👥 Role-Based Management System
- **Executive Leadership**: Director General, CEO
- **Directors**: Academics, ICT, Finance
- **School Management**: Principal, Deputy Principal, Bursar
- **Administrative Staff**: Registrar, HR Manager, Secretary, Librarian
- **Academic Staff**: Department Heads, Lecturers, Lab Technicians
- **Support Staff**: Matrons, Drivers, Security
- **Student Leadership**: Guild President, Class Representatives
- **Students**: Complete student portal access

### 🎓 Student Portal Features
- **Academic Records**: Results, transcripts, GPA tracking
- **Financial Information**: Fee statements, payment history
- **Document Downloads**: Academic documents, certificates
- **Communication System**: Messaging with staff and peers
- **Profile Management**: Personal information and photo uploads
- **Course Information**: Current courses and schedules

### 💰 Comprehensive Financial Management
- **Student Billing**: Automated fee assignment and invoicing
- **Payment Processing**: Multiple payment methods (Mobile Money, Bank, Cash)
- **Budget Management**: Departmental budget allocation and tracking
- **Expenditure Tracking**: Expense approval and monitoring
- **Financial Reports**: Daily, weekly, monthly, and annual reports
- **Debtors Management**: Outstanding fee tracking and reminders

### 📊 Academic Management
- **Curriculum Development**: Course design and approval
- **Examination System**: Scheduling, results, and analysis
- **Performance Tracking**: Student academic progress monitoring
- **Faculty Management**: Lecturer assignments and performance reviews
- **Quality Assurance**: Accreditation compliance and standards

### 📱 Communication System
- **Internal Messaging**: Between students, staff, and management
- **Announcements**: School-wide and targeted communications
- **Priority Messaging**: Urgent and high-priority communications
- **Message Tracking**: Read receipts and response management

## 🦤 SCREENSHOTS

### Pre-View
<div style="display: flex;flex-direction: column; grid-gap: 10px;">
     <div style="display: flex; grid-gap: 10px;">
        <img src="screenshots/1.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
        <img src="screenshots/2.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
    </div>
</div>
<br>

### Admin View
<div style="display: flex;flex-direction: column; grid-gap: 10px;">
   <div style="display: flex; grid-gap: 10px;">
        <img src="screenshots/oranbyte1.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
        <img src="screenshots/4.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
    </div>
     <div style="display: flex; grid-gap: 10px;">
        <img src="screenshots/5.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
        <img src="screenshots/6.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
    </div>
     <div style="display: flex; grid-gap: 10px;">
        <img src="screenshots/7.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
        <img src="screenshots/8.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
    </div>
     <div style="display: flex; grid-gap: 10px;">
        <img src="screenshots/9.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
        <img src="screenshots/10.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
    </div>
</div>
<br>

### Teacher View
<div style="display: flex;flex-direction: column; grid-gap: 10px;">
    <div style="display: flex; grid-gap: 10px;">
        <img src="screenshots/11.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
        <img src="screenshots/12.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
    </div>
</div>
<br>

### Student View
<div style="display: flex;flex-direction: column; grid-gap: 10px;">
   <div style="display: flex; grid-gap: 10px;">
        <img src="screenshots/13.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
        <img src="screenshots/14.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
    </div>
    <div style="display: flex; grid-gap: 10px;">
        <img src="screenshots/15.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
        <img src="screenshots/16.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
    </div>
    <div style="display: flex; grid-gap: 10px;">
        <img src="screenshots/20.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
    </div>
    
</div>
<br>


### Owner View
<div style="display: flex;flex-direction: column; grid-gap: 10px;">
    <div style="display: flex; grid-gap: 10px;">
        <img src="screenshots/17.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
        <img src="screenshots/19.png" alt="screenshots" width="49%" style="border: 2px solid lightgreen"/>
    </div>
    
</div>
<br>

## 🚀 Installation & Setup

### Prerequisites
- **XAMPP** (or equivalent PHP + MySQL environment)
- **PHP 8.1+**
- **MySQL/MariaDB**
- **Web Browser** (Chrome, Firefox, Safari, Edge)

### Step 1: Start XAMPP
1. Open XAMPP Control Panel
2. Start both **Apache** and **MySQL** services

### Step 2: Database Setup
1. Open **phpMyAdmin** (click "Admin" button next to MySQL in XAMPP)
2. Create a new database named **`isnm_school`**
3. Import the database schema: `database/isnm_database.sql`
4. Verify all tables are created successfully

### Step 3: Project Placement
1. Copy the **ISNM** folder to: `C:\xampp\htdocs\`
2. Ensure the directory structure is: `C:\xampp\htdocs\ISNM\`
3. Verify `index.php` exists in the main directory

### Step 4: Access the System
1. Open your web browser
2. Navigate to: **`http://localhost/ISNM`**
3. The school website homepage should load

## 🔐 Default Login Credentials

The system includes comprehensive role-based access. Default credentials are:

| Role | Position | Email | Password |
|------|----------|-------|----------|
| Executive | Director General | director@isnm.ac.ug | admin123 |
| Management | School Principal | principal@isnm.ac.ug | admin123 |
| Finance | School Bursar | bursar@isnm.ac.ug | admin123 |
| Academic | Director Academics | academics@isnm.ac.ug | admin123 |
| Student | Student Portal | student@isnm.ac.ug | student123 |

**Note**: Change default passwords after initial setup for security.

## 📁 Project Structure

```
ISNM/
├── index.php                    # Homepage
├── about.php                    # About page
├── programs.php                 # Academic programs
├── application.php              # Application form
├── contact.php                  # Contact information
├── donation.php                 # Donation page
├── volunteer.php                # Volunteer page
├── organizational-structure.php  # Staff login portal
├── login.php                    # Login page
├── process-login.php            # Login processing
├── process-application.php       # Application processing
├── logout.php                   # Logout script
├── css/
│   └── isnm-style.css           # Custom styles
├── dashboards/
│   ├── director-general.php     # Director General dashboard
│   ├── school-principal.php     # School Principal dashboard
│   ├── director-academics.php   # Director Academics dashboard
│   ├── school-bursar.php        # Bursar dashboard
│   ├── student.php               # Student portal
│   └── dashboard-style.css      # Dashboard styles
├── database/
│   └── isnm_database.sql        # Database schema
├── shared/
│   └── _header.php               # Shared header
├── images/
│   └── school-logo.png          # School logo
└── README.md                    # This file
```

## 🎯 Key Functionalities

### 🌐 Website Features
- **Responsive Design**: Works on all devices
- **Professional Branding**: ISNM colors and logo throughout
- **Interactive Elements**: Smooth animations and transitions
- **Map Integration**: Google Maps for location
- **SEO Optimized**: Meta tags and structured data

### 📱 Student Portal
- **Academic Dashboard**: Overview of academic performance
- **Financial Management**: Fee statements and payment history
- **Document Downloads**: Transcripts, certificates, and academic records
- **Communication**: Messaging with staff and peers
- **Profile Management**: Personal information and photo uploads

### 💼 Management Dashboards
- **Director General**: Complete system oversight
- **School Principal**: Academic and administrative management
- **Director Academics**: Curriculum and faculty management
- **School Bursar**: Comprehensive financial management
- **All Staff**: Role-specific tools and permissions

### 💰 Financial System
- **Student Billing**: Automated fee calculation and invoicing
- **Payment Processing**: Multiple payment methods
- **Budget Management**: Departmental budgets and tracking
- **Financial Reports**: Comprehensive reporting tools
- **Debt Management**: Outstanding fee tracking

## 🔧 Customization

### Branding
- Update logo: Replace `images/school-logo.png`
- Modify colors: Edit `css/isnm-style.css` CSS variables
- Update contact info: Edit footer in shared files

### Database
- Add new fields: Modify `database/isnm_database.sql`
- Update roles: Add to `organizational_positions` table
- Customize programs: Update `programs` table

### Features
- Add new pages: Follow existing page structure
- Extend dashboards: Use dashboard template system
- Integrate payments: Add payment gateway APIs

## 🛡️ Security Features

- **Role-Based Access**: 20+ different user roles with specific permissions
- **Session Management**: Secure login/logout functionality
- **Input Validation**: Form validation and sanitization
- **File Upload Security**: Type checking and size limits
- **Password Protection**: Hashed password storage

## 📞 Support & Contact

**Developer**: Reagan Otema  
**WhatsApp**: +256772514889 (MTN)  
**WhatsApp**: +256730314979 (Airtel)  
**Email**: reagan.otema@example.com

## 📄 License

This project is developed specifically for Iganga School of Nursing and Midwifery. All rights reserved.

## 🤝 Contributing

For improvements and bug reports:
1. Test changes thoroughly
2. Follow existing code structure
3. Document new features
4. Maintain security standards

---

**Note**: This system is designed to be a complete digital solution for ISNM, integrating all aspects of school management into a single, cohesive platform.



