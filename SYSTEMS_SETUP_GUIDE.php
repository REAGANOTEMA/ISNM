<?php
/**
 * Comprehensive Implementation Guide for ISNM Financial & HR Management Systems
 * 
 * This file contains instructions for setting up and using both systems
 */

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ISNM System Implementation Guide</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            padding: 40px 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section h2 {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .subsection {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        
        .subsection h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .code-box {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        
        .highlight {
            background: #fffacd;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        
        ul, ol {
            margin: 15px 0 15px 25px;
        }
        
        li {
            margin-bottom: 8px;
        }
        
        .btn-section {
            display: flex;
            gap: 10px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.3);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f0f0f0;
            font-weight: 600;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .footer {
            background: #f0f0f0;
            padding: 20px 40px;
            text-align: center;
            color: #666;
            font-size: 13px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-ready {
            background: #d4edda;
            color: #155724;
        }
        
        .status-partial {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 ISNM Financial & HR Management Systems</h1>
            <p>Comprehensive Implementation Guide</p>
        </div>
        
        <div class="content">
            <!-- Section 1: Overview -->
            <div class="section">
                <h2>📋 System Overview</h2>
                
                <div class="subsection">
                    <h3>What You Have Received</h3>
                    <p>Two professional management systems integrated into your ISNM platform:</p>
                    <ul>
                        <li><strong>Bursar Financial Management System</strong> - Complete financial operations, billing, payments, and reporting</li>
                        <li><strong>HR Human Resources System</strong> - Complete staff management, payroll, recruitment, and performance tracking</li>
                    </ul>
                </div>
            </div>
            
            <!-- Section 2: Bursar System -->
            <div class="section">
                <h2>💼 Bursar Portal Setup & Usage</h2>
                
                <div class="subsection">
                    <h3>Access Credentials</h3>
                    <table>
                        <tr>
                            <th>Field</th>
                            <th>Value</th>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td><code>bursar@igangaschoolofnursingandmidwifery.ac.ug</code></td>
                        </tr>
                        <tr>
                            <td>Password</td>
                            <td><code>bursar@isnm</code></td>
                        </tr>
                        <tr>
                            <td>Login URL</td>
                            <td><a href="bursar_login.php">bursar_login.php</a></td>
                        </tr>
                    </table>
                </div>
                
                <div class="subsection">
                    <h3>Initial Setup</h3>
                    <ol>
                        <li>Open your terminal/command prompt</li>
                        <li>Navigate to your MySQL client or phpMyAdmin</li>
                        <li>Run the SQL script: <code>sql/bursar_system.sql</code></li>
                        <li>Visit <code>bursar_setup.php</code> to initialize the system</li>
                        <li>Login with credentials above at <code>bursar_login.php</code></li>
                    </ol>
                </div>
                
                <div class="subsection">
                    <h3>Key Features</h3>
                    <ul>
                        <li>✓ Student fee structure management</li>
                        <li>✓ Invoice generation and tracking</li>
                        <li>✓ Payment recording (cash, bank, mobile money, cheque)</li>
                        <li>✓ Automatic receipt generation</li>
                        <li>✓ Financial reports (daily, weekly, monthly)</li>
                        <li>✓ Budget management and expenditure tracking</li>
                        <li>✓ Penalty configuration and tracking</li>
                        <li>✓ Scholarship/sponsorship management</li>
                        <li>✓ Professional dashboard with analytics</li>
                        <li>✓ PDF/Excel export capabilities</li>
                        <li>✓ Activity logs and audit trail</li>
                        <li>✓ Role-based access control</li>
                    </ul>
                </div>
                
                <div class="subsection">
                    <h3>Database Tables (Bursar)</h3>
                    <p>Total of <strong>15 main tables</strong> created for comprehensive financial management:</p>
                    <code>bursar_users, programs, fee_structures, student_fee_assignments, student_invoices, payments, payment_receipts, scholarships, fee_adjustments, penalty_configurations, student_penalties, budgets, expenditures, general_ledger, cost_centers</code>
                </div>
                
                <div class="btn-section">
                    <a href="bursar_setup.php" class="btn btn-primary">Setup Bursar System</a>
                    <a href="bursar_login.php" class="btn btn-primary">Go to Bursar Login</a>
                </div>
            </div>
            
            <!-- Section 3: HR System -->
            <div class="section">
                <h2>👥 HR Portal Setup & Usage</h2>
                
                <div class="subsection">
                    <h3>Access Credentials</h3>
                    <table>
                        <tr>
                            <th>Field</th>
                            <th>Value</th>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td><code>hr@igangaschoolofnursingandmidwifery.ac.ug</code></td>
                        </tr>
                        <tr>
                            <td>Password</td>
                            <td><code>Lovely2God</code></td>
                        </tr>
                        <tr>
                            <td>Login URL</td>
                            <td><a href="hr_login.php">hr_login.php</a></td>
                        </tr>
                    </table>
                </div>
                
                <div class="subsection">
                    <h3>Initial Setup</h3>
                    <ol>
                        <li>Open your terminal/command prompt</li>
                        <li>Navigate to your MySQL client or phpMyAdmin</li>
                        <li>Run the SQL script: <code>sql/hr_system.sql</code> (note: this uses <strong>staff database</strong>)</li>
                        <li>Visit <code>hr_setup.php</code> to initialize the system</li>
                        <li>Login with credentials above at <code>hr_login.php</code></li>
                    </ol>
                </div>
                
                <div class="subsection">
                    <h3>Key Features</h3>
                    <ul>
                        <li>✓ Complete staff records management</li>
                        <li>✓ Employment details and contract tracking</li>
                        <li>✓ Recruitment and hiring module</li>
                        <li>✓ Job vacancy posting and application tracking</li>
                        <li>✓ Interview scheduling and tracking</li>
                        <li>✓ Onboarding checklist system</li>
                        <li>✓ Daily attendance and time management</li>
                        <li>✓ Leave management system with balance tracking</li>
                        <li>✓ Duty roster management</li>
                        <li>✓ Performance appraisal system</li>
                        <li>✓ Training and CPD tracking</li>
                        <li>✓ Professional licenses and certification tracking</li>
                        <li>✓ Disciplinary and conduct records</li>
                        <li>✓ Employment contracts and compliance tracking</li>
                        <li>✓ Salary structure and payroll support</li>
                        <li>✓ Payslip generation</li>
                        <li>✓ Professional reports and analytics</li>
                        <li>✓ Staff self-service portal features</li>
                    </ul>
                </div>
                
                <div class="subsection">
                    <h3>Database Tables (HR)</h3>
                    <p>Total of <strong>18 main tables</strong> created for comprehensive HR management:</p>
                    <code>hr_users, staff_records, employment_details, job_vacancies, job_applications, attendance, leave_requests, leave_balance, duty_roster, staff_appraisals, performance_indicators, training_programs, staff_training, incident_reports, disciplinary_actions, employment_contracts, salary_structures, payslips</code>
                </div>
                
                <div class="btn-section">
                    <a href="hr_setup.php" class="btn btn-secondary">Setup HR System</a>
                    <a href="hr_login.php" class="btn btn-secondary">Go to HR Login</a>
                </div>
            </div>
            
            <!-- Section 4: Database Information -->
            <div class="section">
                <h2>🗄️ Database Configuration</h2>
                
                <div class="subsection">
                    <h3>Multiple Database Architecture</h3>
                    <p>Your system uses separate databases for different modules:</p>
                    <table>
                        <tr>
                            <th>Module</th>
                            <th>Database</th>
                            <th>SQL File</th>
                        </tr>
                        <tr>
                            <td>Bursar (Financial)</td>
                            <td><code>igangaschoolofl_students_db</code></td>
                            <td><code>sql/bursar_system.sql</code></td>
                        </tr>
                        <tr>
                            <td>HR (Staff)</td>
                            <td><code>igangaschoolofl_staffs_db</code></td>
                            <td><code>sql/hr_system.sql</code></td>
                        </tr>
                    </table>
                </div>
                
                <div class="subsection">
                    <h3>Running Setup Scripts</h3>
                    <p><strong>In phpMyAdmin:</strong></p>
                    <ol>
                        <li>Select the appropriate database</li>
                        <li>Click "SQL" tab</li>
                        <li>Open the SQL file (copy and paste content, or use "Choose File" if available)</li>
                        <li>Click "Go"</li>
                    </ol>
                    
                    <p style="margin-top: 15px;"><strong>Via Command Line (MySQL):</strong></p>
                    <div class="code-box">
mysql -u username -p database_name < sql/bursar_system.sql
mysql -u username -p database_name < sql/hr_system.sql
                    </div>
                </div>
            </div>
            
            <!-- Section 5: File Structure -->
            <div class="section">
                <h2>📁 File Structure</h2>
                
                <div class="subsection">
                    <h3>Bursar System Files</h3>
                    <ul>
                        <li><code>bursar_login.php</code> - Login page</li>
                        <li><code>bursar_dashboard.php</code> - Main dashboard (Professional UI)</li>
                        <li><code>bursar_setup.php</code> - System initialization</li>
                        <li><code>bursar_logout.php</code> - Logout handler</li>
                        <li><code>bursar_payments.php</code> - Payment recording</li>
                        <li><code>bursar_student_fees.php</code> - Fee management</li>
                        <li><code>bursar_invoices.php</code> - Invoice management</li>
                        <li><code>bursar_receipts.php</code> - Receipt management</li>
                        <li><code>bursar_reports.php</code> - Financial reports</li>
                        <li><code>bursar_budgets.php</code> - Budget management</li>
                        <li><code>bursar_settings.php</code> - System settings</li>
                    </ul>
                </div>
                
                <div class="subsection">
                    <h3>HR System Files</h3>
                    <ul>
                        <li><code>hr_login.php</code> - Login page</li>
                        <li><code>hr_dashboard.php</code> - Main dashboard (Professional UI)</li>
                        <li><code>hr_setup.php</code> - System initialization</li>
                        <li><code>hr_logout.php</code> - Logout handler</li>
                        <li><code>hr_staff_records.php</code> - Staff records management</li>
                        <li><code>hr_recruitment.php</code> - Recruitment module</li>
                        <li><code>hr_attendance.php</code> - Attendance tracking</li>
                        <li><code>hr_leave.php</code> - Leave management</li>
                        <li><code>hr_payroll.php</code> - Payroll support</li>
                        <li><code>hr_performance.php</code> - Performance management</li>
                        <li><code>hr_training.php</code> - Training & development</li>
                        <li><code>hr_reports.php</code> - HR reports</li>
                        <li><code>hr_settings.php</code> - System settings</li>
                    </ul>
                </div>
                
                <div class="subsection">
                    <h3>SQL Database Scripts</h3>
                    <ul>
                        <li><code>sql/bursar_system.sql</code> - Complete Bursar schema</li>
                        <li><code>sql/hr_system.sql</code> - Complete HR schema</li>
                    </ul>
                </div>
            </div>
            
            <!-- Section 6: Next Steps -->
            <div class="section">
                <h2>🚀 Quick Start Guide</h2>
                
                <div class="subsection">
                    <h3>Step 1: Run Database Scripts</h3>
                    <p>Execute both SQL scripts to create all necessary tables and data:</p>
                    <ul>
                        <li>Run <code>sql/bursar_system.sql</code> in <code>igangaschoolofl_students_db</code></li>
                        <li>Run <code>sql/hr_system.sql</code> in <code>igangaschoolofl_staffs_db</code></li>
                    </ul>
                </div>
                
                <div class="subsection">
                    <h3>Step 2: Initialize Systems</h3>
                    <ul>
                        <li>Visit <code>bursar_setup.php</code> → Click "Setup Bursar System"</li>
                        <li>Visit <code>hr_setup.php</code> → Click "Setup HR System"</li>
                    </ul>
                </div>
                
                <div class="subsection">
                    <h3>Step 3: Login & Explore</h3>
                    <ul>
                        <li>Bursar: <code>bursar_login.php</code> with demo credentials</li>
                        <li>HR: <code>hr_login.php</code> with demo credentials</li>
                    </ul>
                </div>
                
                <div class="subsection">
                    <h3>Step 4: Customize</h3>
                    <ul>
                        <li>Update institution details in system settings</li>
                        <li>Configure fee structures for your programs</li>
                        <li>Set up staff categories and roles</li>
                        <li>Import existing student and staff data</li>
                    </ul>
                </div>
            </div>
            
            <!-- Section 7: Support -->
            <div class="section">
                <h2>💡 Important Notes</h2>
                
                <div class="subsection">
                    <p>✓ Both systems use <strong>professional, modern designs</strong> with gradient backgrounds and intuitive interfaces</p>
                    <p>✓ All dashboards include <strong>real-time statistics</strong> and analytics</p>
                    <p>✓ Complete <strong>audit trail and activity logging</strong> is implemented</p>
                    <p>✓ Systems support <strong>role-based access control</strong> for security</p>
                    <p>✓ Database schemas include <strong>all requested features and more</strong></p>
                    <p>✓ Both systems are <strong>production-ready</strong> and professional quality</p>
                    <p>⚠ Remember to <strong>change default passwords</strong> in production</p>
                    <p>⚠ <strong>Back up your databases</strong> regularly</p>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>ISNM Financial & HR Management Systems | Iganga School of Nursing and Midwifery | © 2025</p>
            <p>For support and customization requests, contact your system administrator</p>
        </div>
    </div>
</body>
</html>
