<?php
// ISNM Professional Transcript Template
// Official transcript template with school logo and professional design

function generateProfessionalTranscript($student, $academic_records, $transcript_type, $academic_year, $semester) {
    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Transcript - ISNM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
        }
        .transcript-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border: 2px solid #2c3e50;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }
        .transcript-header {
            background: linear-gradient(135deg, #2c3e50 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .school-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        .transcript-title {
            font-size: 28px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .transcript-subtitle {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(44, 62, 80, 0.05);
            z-index: -1;
            font-weight: 700;
            letter-spacing: 10px;
        }
        .transcript-body {
            padding: 40px;
        }
        .section {
            margin-bottom: 40px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2c3e50;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            display: block;
        }
        .info-value {
            font-size: 16px;
            color: #2c3e50;
        }
        .academic-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        .academic-table th,
        .academic-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .academic-table th {
            background: linear-gradient(135deg, #2c3e50 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .academic-table tr:last-child td {
            border-bottom: none;
        }
        .summary-row {
            background: #f8f9fa;
            font-weight: 600;
        }
        .signature-section {
            margin-top: 50px;
            text-align: right;
        }
        .signature-line {
            border-top: 2px solid #2c3e50;
            margin-top: 20px;
            padding-top: 20px;
            text-align: center;
        }
        .official-stamp {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 12px 24px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 20px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .transcript-container {
                box-shadow: none;
                max-width: 100%;
                margin: 0;
                border: 1px solid #000;
            }
            
            .watermark {
                        display: none;
                    }
        }
    </style>
</head>
<body>
    <div class="watermark">ISNM</div>
    
    <div class="transcript-container">
        <div class="transcript-header">
            <img src="../images/school-logo.png" alt="ISNM Logo" class="school-logo">
            <div class="transcript-title">ACADEMIC TRANSCRIPT</div>
            <div class="transcript-subtitle">Official Academic Record</div>
        </div>
        
        <div class="transcript-body">
            <!-- School Information -->
            <div class="section">
                <div class="section-title">School Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">School Name:</span>
                        <span class="info-value">Iganga School of Nursing and Midwifery</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Address:</span>
                        <span class="info-value">Iganga, Uganda</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-value">+256 XXX XXX XXX</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value">info@isnm.ug</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Website:</span>
                        <span class="info-value">www.isnm.ug</span>
                    </div>
                </div>
            </div>
            
            <!-- Student Information -->
            <div class="section">
                <div class="section-title">Student Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Full Name:</span>
                        <span class="info-value">' . htmlspecialchars($student['full_name']) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Registration Number:</span>
                        <span class="info-value">' . htmlspecialchars($student['registration_number']) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">National ID:</span>
                        <span class="info-value">' . htmlspecialchars($student['national_student_id_number']) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Program:</span>
                        <span class="info-value">' . htmlspecialchars($student['course']) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Year of Study:</span>
                        <span class="info-value">' . htmlspecialchars($academic_year) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Semester:</span>
                        <span class="info-value">' . htmlspecialchars($semester) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Admission Date:</span>
                        <span class="info-value">' . date('F j, Y', strtotime($student['intake_date'])) . '</span>
                    </div>
                </div>
            </div>
            
            <!-- Academic Records -->
            <div class="section">
                <div class="section-title">Academic Records</div>
                <table class="academic-table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Credits</th>
                            <th>Semester</th>
                            <th>Academic Year</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>GPA</th>
                            <th>Lecturer</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($academic_records as $record) {
        $content .= '
                        <tr>
                            <td>' . htmlspecialchars($record['course_code']) . '</td>
                            <td>' . htmlspecialchars($record['course_name']) . '</td>
                            <td>' . htmlspecialchars($record['credits']) . '</td>
                            <td>' . htmlspecialchars($record['semester']) . '</td>
                            <td>' . htmlspecialchars($record['academic_year']) . '</td>
                            <td>' . htmlspecialchars($record['marks']) . '</td>
                            <td>' . htmlspecialchars($record['grade']) . '</td>
                            <td>' . htmlspecialchars($record['gpa']) . '</td>
                            <td>' . htmlspecialchars($record['lecturer_name']) . '</td>
                        </tr>';
    }
    
    $content .= '
                    </tbody>
                </table>
            </div>
            
            <!-- Academic Summary -->
            <div class="section">
                <div class="section-title">Academic Summary</div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Total Courses:</span>
                        <span class="info-value">' . count($academic_records) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Credits:</span>
                        <span class="info-value">' . array_sum(array_column($academic_records, 'credits')) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Cumulative GPA:</span>
                        <span class="info-value">' . number_format($student['cumulative_gpa'], 2) . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Academic Standing:</span>
                        <span class="info-value">' . ($student['cumulative_gpa'] >= 3.5 ? 'Excellent' : ($student['cumulative_gpa'] >= 3.0 ? 'Good' : 'Satisfactory')) . '</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="signature-section">
            <div class="signature-line">
                <p><strong>Transcript Number:</strong> ' . uniqid() . '</p>
                <p><strong>Generated on:</strong> ' . date('F j, Y, H:i:s') . '</p>
                <p><strong>Generated by:</strong> ' . $_SESSION['full_name'] . '</p>
                <p><strong>Position:</strong> ' . $_SESSION['position'] . '</p>
                <p><em>This is an electronically generated academic transcript and is valid without signature.</em></p>
                <div class="official-stamp">OFFICIAL</div>
            </div>
        </div>
    </div>
</body>
</html>';
    
    return $content;
}
?>
