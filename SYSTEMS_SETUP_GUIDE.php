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
            <h1>ðŸŽ“ ISNM Financial & HR Management Systems</h1>
            <p>Comprehensive Implementation Guide</p>
        </div>
        
        <div class="content">
            <!-- Section 1: Overview -->
            <div class="section">
                <h2>ðŸ“‹ System Overview</h2>
                
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
                <h2>ðŸ’¼ Bursar Portal Setup & Usage</h2>
                
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
                        <li>Run the SQL script: <code>sql/students/bursar_system.sql</code></li>
                        <li>Visit <code>bursar_setup.php</code> to initialize the system</li>
                        <li>Login with credentials above at <code>bursar_login.php</code></li>
                    </ol>
                </div>
                
                <div class="subsection">
                    <h3>Key Features</h3>
                    <ul>
                        <li>âœ“ Student fee structure management</li>
                        <li>âœ“ Invoice generation and tracking</li>
                        <li>âœ“ Payment recording (cash, bank, mobile money, cheque)</li>
                        <li>âœ“ Automatic receipt generation</li>
                        <li>âœ“ Financial reports (daily, weekly, monthly)</li>
                        <li>âœ“ Budget management and expenditure tracking</li>
                        <li>âœ“ Penalty configuration and tracking</li>
                        <li>âœ“ Scholarship/sponsorship management</li>
                        <li>âœ“ Professional dashboard with analytics</li>
                        <li>âœ“ PDF/Excel export capabilities</li>
                        <li>âœ“ Activity logs and audit trail</li>
                        <li>âœ“ Role-based access control</li>
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
                <h2>ðŸ‘¥ HR Portal Setup & Usage</h2>
                
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
                        <li>Run the SQL script: <code>sql/staffs/hr_system.sql</code> (note: this uses <strong>staff database</strong>)</li>
                        <li>Visit <code>hr_setup.php</code> to initialize the system</li>
                        <li>Login with credentials above at <code>hr_login.php</code></li>
                    </ol>
                </div>
                
                <div class="subsection">
                    <h3>Key Features</h3>
                    <ul>
                        <li>âœ“ Complete staff records management</li>
                        <li>âœ“ Employment details and contract tracking</li>
                        <li>âœ“ Recruitment and hiring module</li>
                        <li>âœ“ Job vacancy posting and application tracking</li>
                        <li>âœ“ Interview scheduling and tracking</li>
                        <li>âœ“ Onboarding checklist system</li>
                        <li>âœ“ Daily attendance and time management</li>
                        <li>âœ“ Leave management system with balance tracking</li>
                        <li>âœ“ Duty roster management</li>
                        <li>âœ“ Performance appraisal system</li>
                        <li>âœ“ Training and CPD tracking</li>
                        <li>âœ“ Professional licenses and certification tracking</li>
                        <li>âœ“ Disciplinary and conduct records</li>
                        <li>âœ“ Employment contracts and compliance tracking</li>
                        <li>âœ“ Salary structure and payroll support</li>
                        <li>âœ“ Payslip generation</li>
                        <li>âœ“ Professional reports and analytics</li>
                        <li>âœ“ Staff self service portal features</li>
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
                <h2>ðŸ—„ï¸ Database Configuration</h2>
                
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
                            <td><code>igangaschool_students</code></td>
                            <td><code>sql/students/bursar_system.sql</code></td>
                        </tr>
                        <tr>
                            <td>HR (Staff)</td>
                            <td><code>igangaschool_staffs</code></td>
                            <td><code>sql/staffs/hr_system.sql</code></td>
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
mysql -u username -p database_name < sql/students/bursar_system.sql
mysql -u username -p database_name < sql/staffs/hr_system.sql
                    </div>
                </div>
            </div>
            
            <!-- Section 5: File Structure -->
            <div class="section">
                <h2>ðŸ“ File Structure</h2>
                
                <div class="subsection">
                    <h3>Bursar System</h3>
                    <ul>
                        <li><code>staff-login.php</code> - Staff login (all roles)</li>
                        <li><code>dashboards/school-bursar.php</code> - Unified Bursar Dashboard with sections: overview, record_payment, generate_invoice, fee_structure, student_statement, receipt_print, financial_reports, budget, daily_collections</li>
                        <li><code>bursar_setup.php</code> - System initialization</li>
                        <li><code>bursar_logout.php</code> - Logout handler</li>
                    </ul>
                    <p><em>Legacy standalone bursar files have been consolidated into the unified <code>dashboards/school-bursar.php</code> dashboard.</em></p>
                </div>
                
                <div class="subsection">
                    <h3>HR System</h3>
                    <ul>
                        <li><code>staff-login.php</code> - Staff login (all roles)</li>
                        <li><code>dashboards/hr-manager.php</code> - Unified HR Dashboard with sections: overview, staff-records, attendance, leave, payroll, performance, training, recruitment, contracts, disciplinary, communications, reports</li>
                        <li><code>hr_setup.php</code> - System initialization</li>
                        <li><code>hr_logout.php</code> - Logout handler</li>
                    </ul>
                    <p><em>Legacy standalone HR files have been consolidated into the unified <code>dashboards/hr-manager.php</code> dashboard.</em></p>
                </div>
                
                <div class="subsection">
                    <h3>SQL Database Scripts</h3>
                    <ul>
                        <li><code>sql/students/bursar_system.sql</code> - Complete Bursar schema</li>
                        <li><code>sql/staffs/hr_system.sql</code> - Complete HR schema</li>
                    </ul>
                </div>
            </div>
            
            <!-- Section 6: Next Steps -->
            <div class="section">
                <h2>ðŸš€ Quick Start Guide</h2>
                
                <div class="subsection">
                    <h3>Step 1: Run Database Scripts</h3>
                    <p>Execute both SQL scripts to create all necessary tables and data:</p>
                    <ul>
                        <li>Run <code>sql/students/bursar_system.sql</code> in <code>igangaschool_students</code></li>
                        <li>Run <code>sql/staffs/hr_system.sql</code> in <code>igangaschool_staffs</code></li>
                    </ul>
                </div>
                
                <div class="subsection">
                    <h3>Step 2: Initialize Systems</h3>
                    <ul>
                        <li>Visit <code>bursar_setup.php</code> â†’ Click "Setup Bursar System"</li>
                        <li>Visit <code>hr_setup.php</code> â†’ Click "Setup HR System"</li>
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
                <h2>ðŸ’¡ Important Notes</h2>
                
                <div class="subsection">
                    <p>âœ“ Both systems use <strong>professional, modern designs</strong> with gradient backgrounds and intuitive interfaces</p>
                    <p>âœ“ All dashboards include <strong>real time statistics</strong> and analytics</p>
                    <p>âœ“ Complete <strong>audit trail and activity logging</strong> is implemented</p>
                    <p>âœ“ Systems support <strong>role based access control</strong> for security</p>
                    <p>âœ“ Database schemas include <strong>all requested features and more</strong></p>
                    <p>âœ“ Both systems are <strong>production ready</strong> and professional quality</p>
                    <p>âš  Remember to <strong>change default passwords</strong> in production</p>
                    <p>âš  <strong>Back up your databases</strong> regularly</p>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>ISNM Financial & HR Management Systems | Iganga School of Nursing and Midwifery | Â© 2025</p>
            <p>For support and customization requests, contact your system administrator</p>
        </div>
    </div>
</body>
</html>
