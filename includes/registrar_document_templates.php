<?php
/**
 * Professional Academic Document Generator
 * Generates HTML for Transcripts and Certificates
 */

// Prevent duplicate loading
if (!function_exists('generateProfessionalTranscript')):

/**
 * Generate a professional Academic Transcript HTML
 * @param array $student - Student record from database
 * @param array $courses - Array of course records (code, name, credits, ca, exam, total, grade)
 * @param array $settings - Registrar settings (institution_name, etc.)
 * @param string $transcript_number - Unique transcript reference
 * @return string - Complete HTML document
 */
function generateProfessionalTranscript($student, $courses, $settings, $transcript_number = '') {
    $institution = $settings['institution_name'] ?? 'ISNM';
    $logo_path = '../assets/img/school-logo.svg';
    $logo_data = '';
    if (file_exists(__DIR__ . '/../assets/img/school-logo.svg')) {
        $logo_data = base64_encode(file_get_contents(__DIR__ . '/../assets/img/school-logo.svg'));
    }
    
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Academic Transcript</title>';
    $html .= '<style>
        @page { size: A4; margin: 20mm 15mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Georgia", "Times New Roman", serif; background: #f0f0f0; display: flex; justify-content: center; padding: 40px 0; }
        .transcript-page { width: 210mm; min-height: 297mm; background: #fff; padding: 0; position: relative; box-shadow: 0 8px 40px rgba(0,0,0,0.12); margin: 0 auto; }
        .transcript-inner { padding: 30px 35px; }
        
        /* Decorative Border */
        .transcript-border { position: absolute; top: 8px; left: 8px; right: 8px; bottom: 8px; border: 2px solid #1a237e; pointer-events: none; }
        .transcript-border-inner { position: absolute; top: 14px; left: 14px; right: 14px; bottom: 14px; border: 1px solid #c0a060; pointer-events: none; }
        
        /* Header */
        .letterhead { text-align: center; padding-bottom: 20px; border-bottom: 3px double #1a237e; margin-bottom: 20px; position: relative; }
        .letterhead .logo-container { display: inline-block; vertical-align: middle; margin-right: 15px; }
        .letterhead .logo-container img, .letterhead .logo-container svg { width: 70px; height: 70px; }
        .letterhead .inst-info { display: inline-block; vertical-align: middle; text-align: left; }
        .letterhead h1 { font-size: 16px; color: #1a237e; font-weight: 700; letter-spacing: 1px; margin: 0; text-transform: uppercase; }
        .letterhead .subtitle { font-size: 10px; color: #555; margin: 2px 0; }
        .letterhead .address { font-size: 9px; color: #777; margin: 2px 0; }
        
        /* Title */
        .doc-title { text-align: center; margin: 20px 0; }
        .doc-title h2 { font-size: 18px; color: #1a237e; letter-spacing: 3px; text-transform: uppercase; font-weight: 700; }
        .doc-title .title-line { width: 120px; height: 2px; background: linear-gradient(to right, transparent, #c0a060, transparent); margin: 6px auto; }
        .doc-title .transcript-ref { font-size: 10px; color: #888; margin-top: 4px; }
        
        /* Student Info */
        .student-info { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 11px; }
        .student-info td { padding: 3px 8px; border: none; vertical-align: top; }
        .student-info .label { font-weight: 700; color: #1a237e; width: 120px; }
        .student-info .value { color: #333; }
        
        /* Student Photo placeholder */
        .photo-placeholder { float: right; width: 80px; height: 100px; border: 1px solid #ccc; margin: 0 0 10px 15px; text-align: center; font-size: 9px; color: #999; padding-top: 35px; background: #f9f9f9; }
        
        /* Academic Table */
        .academic-table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 10px; }
        .academic-table thead th { background: #1a237e; color: #fff; padding: 7px 6px; text-align: center; font-weight: 600; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        .academic-table thead th:first-child { border-radius: 4px 0 0 0; }
        .academic-table thead th:last-child { border-radius: 0 4px 0 0; }
        .academic-table tbody td { padding: 5px 6px; text-align: center; border-bottom: 1px solid #e8e8e8; }
        .academic-table tbody td:first-child { text-align: left; }
        .academic-table tbody tr:nth-child(even) { background: #f8f9ff; }
        .academic-table tbody tr:hover { background: #eef0ff; }
        .academic-table .course-name { text-align: left; max-width: 200px; }
        
        /* GPA Summary */
        .gpa-summary { margin: 15px 0; padding: 12px 16px; background: linear-gradient(135deg, #f5f7ff, #e8ecff); border: 1px solid #d0d5f0; border-radius: 6px; font-size: 11px; }
        .gpa-summary table { width: 100%; border-collapse: collapse; }
        .gpa-summary td { padding: 3px 12px; }
        .gpa-summary .gpa-label { font-weight: 600; color: #1a237e; }
        .gpa-summary .gpa-value { font-weight: 700; color: #1a237e; font-size: 13px; }
        
        /* Classification */
        .classification { text-align: center; padding: 10px; margin: 15px 0; background: #fff8e0; border: 1px solid #e8d5a0; border-radius: 6px; font-size: 11px; font-weight: 700; color: #8a6d00; }
        
        /* Signatures */
        .signatures { margin-top: 30px; }
        .signatures table { width: 100%; border-collapse: collapse; }
        .signatures td { width: 33%; text-align: center; padding: 10px; }
        .signatures .sig-line { width: 160px; height: 1px; border-top: 1px solid #333; margin: 30px auto 6px; }
        .signatures .sig-title { font-size: 10px; font-weight: 700; color: #1a237e; text-transform: uppercase; }
        .signatures .sig-name { font-size: 10px; color: #555; }
        .signatures .sig-date { font-size: 9px; color: #888; }
        
        /* Footer */
        .footer { text-align: center; font-size: 8px; color: #aaa; border-top: 1px solid #ddd; padding-top: 10px; margin-top: 20px; }
        .footer .verification { color: #1a237e; font-weight: 600; }
        
        /* Watermark */
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 80px; color: rgba(26,35,126,0.03); font-weight: 900; pointer-events: none; white-space: nowrap; z-index: 0; letter-spacing: 15px; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .transcript-page { box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style></head><body>';
    
    // Print controls
    $html .= '<div class="no-print" style="text-align:center;margin-bottom:12px;font-family:sans-serif">
        <button onclick="window.print()" style="padding:8px 24px;background:#1a237e;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px;margin-right:8px">🖨 Print</button>
        <button onclick="window.close()" style="padding:8px 24px;background:#666;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px">✕ Close</button>
    </div>';
    
    $html .= '<div class="transcript-page"><div class="transcript-border"></div><div class="transcript-border-inner"></div>';
    $html .= '<div class="transcript-inner">';
    $html .= '<div class="watermark">TRANSCRIPT</div>';
    
    // Letterhead
    $html .= '<div class="letterhead">';
    if ($logo_data) {
        $html .= '<div class="logo-container"><img src="data:image/svg+xml;base64,'.$logo_data.'" alt="Logo" style="width:70px;height:70px"></div>';
    } else {
        $html .= '<div class="logo-container"><svg width="70" height="70" viewBox="0 0 200 200"><circle cx="100" cy="100" r="95" fill="none" stroke="#1a237e" stroke-width="4"/><circle cx="100" cy="100" r="85" fill="none" stroke="#ff6f00" stroke-width="2"/><text x="100" y="55" text-anchor="middle" font-size="28" font-weight="bold" fill="#1a237e" font-family="serif">ISNM</text><path d="M 70 120 Q 100 95 130 120" stroke="#1a237e" stroke-width="3" fill="none"/><line x1="100" y1="65" x2="100" y2="110" stroke="#1a237e" stroke-width="3"/><line x1="85" y1="95" x2="115" y2="95" stroke="#1a237e" stroke-width="3"/><text x="100" y="155" text-anchor="middle" font-size="10" fill="#1a237e" font-family="serif">NURSING &amp; MIDWIFERY</text></svg></div>';
    }
    $html .= '<div class="inst-info">';
    $html .= '<h1>'.$institution.'</h1>';
    $html .= '<div class="subtitle">INTERNATIONAL SCHOOL OF NURSING &amp; MIDWIFERY</div>';
    $html .= '<div class="address">P.O. Box 1234, Kampala, Uganda | Tel: +256 700 000 000 | Email: registrar@isnm.ac.ug</div>';
    $html .= '</div></div>';
    
    // Title
    $tnumber = $transcript_number ?: 'T-'.date('Ymd').'-'.mt_rand(1000,9999);
    $html .= '<div class="doc-title"><h2>ACADEMIC TRANSCRIPT</h2><div class="title-line"></div><div class="transcript-ref">Transcript No: '.htmlspecialchars($tnumber).' | Issue Date: '.date('d F Y').'</div></div>';
    
    // Student Info
    $photo_html = '<div class="photo-placeholder">📷<br>Photo</div>';
    $full_name = $student['full_name'] ?? trim(($student['first_name']??'').' '.($student['other_name']??'').' '.($student['surname']??''));
    $reg_no = $student['registration_number'] ?? $student['student_number'] ?? '';
    $program = $student['course'] ?? $student['program'] ?? '';
    $dob = $student['date_of_birth'] ?? '-';
    $gender = $student['gender'] ?? '-';
    $admission = $student['created_at'] ?? $student['intake_date'] ?? '-';
    
    $html .= $photo_html;
    $html .= '<table class="student-info">';
    $html .= '<tr><td class="label">Student Name:</td><td class="value"><strong>'.htmlspecialchars($full_name).'</strong></td><td class="label">Registration No:</td><td class="value">'.htmlspecialchars($reg_no).'</td></tr>';
    $html .= '<tr><td class="label">Programme:</td><td class="value">'.htmlspecialchars($program).'</td><td class="label">Date of Birth:</td><td class="value">'.htmlspecialchars($dob).'</td></tr>';
    $html .= '<tr><td class="label">Gender:</td><td class="value">'.htmlspecialchars($gender).'</td><td class="label">Admission Date:</td><td class="value">'.htmlspecialchars($admission).'</td></tr>';
    $html .= '<tr><td class="label">Academic Year:</td><td class="value">'.($settings['current_academic_year']??date('Y')).'</td><td class="label">Student No:</td><td class="value">'.htmlspecialchars($student['student_number']??'').'</td></tr>';
    $html .= '</table>';
    $html .= '<div style="clear:both"></div>';
    
    // Academic Record Table
    $html .= '<table class="academic-table"><thead><tr>';
    $html .= '<th style="width:10%">Code</th><th class="course-name">Course Title</th><th style="width:8%">Credit</th><th style="width:10%">CA (30%)</th><th style="width:10%">Exam (70%)</th><th style="width:10%">Total</th><th style="width:8%">Grade</th><th style="width:10%">GP</th>';
    $html .= '</tr></thead><tbody>';
    
    if (!empty($courses)) {
        $total_points = 0; $total_credits = 0; $semester = '';
        foreach ($courses as $c) {
            $sem = $c['semester'] ?? '';
            if ($sem && $sem !== $semester) {
                if ($semester) {
                    $html .= '<tr style="background:#f0f2ff;font-weight:700"><td colspan="8" style="text-align:left;padding:5px 8px;color:#1a237e;font-size:10px">End of '.htmlspecialchars($semester).'</td></tr>';
                }
                $semester = $sem;
                $html .= '<tr style="background:#e8ecff;font-weight:700"><td colspan="8" style="text-align:left;padding:6px 8px;color:#1a237e;font-size:10px;text-transform:uppercase">'.htmlspecialchars($semester).'</td></tr>';
            }
            $code = $c['course_code'] ?? $c['code'] ?? '-';
            $name = $c['course_name'] ?? $c['name'] ?? '-';
            $credit = intval($c['credit_hours'] ?? $c['credits'] ?? $c['credit'] ?? 0);
            $ca = $c['continuous_assessment_marks'] ?? $c['ca'] ?? '-';
            $exam = $c['final_exam_marks'] ?? $c['exam'] ?? '-';
            $total = $c['marks_obtained'] ?? $c['total'] ?? $c['total_marks'] ?? '-';
            $grade = $c['grade'] ?? '-';
            
            // Grade points (A=4.0, B=3.5, C=3.0, D=2.0, E=1.0, F=0.0)
            $gp = 0;
            $g = strtoupper($grade);
            if ($g === 'A') $gp = 4.0;
            elseif ($g === 'B' || $g === 'B+') $gp = 3.5;
            elseif ($g === 'C' || $g === 'C+') $gp = 3.0;
            elseif ($g === 'D') $gp = 2.0;
            elseif ($g === 'E') $gp = 1.0;
            elseif ($g === 'F') $gp = 0.0;
            
            $total_points += $gp * $credit;
            $total_credits += $credit;
            
            $grade_class = ($gp >= 3.5) ? ' style="color:#2e7d32;font-weight:700"' : (($gp >= 2.0) ? ' style="color:#e65100;font-weight:700"' : ' style="color:#c62828;font-weight:700"');
            
            $html .= '<tr>';
            $html .= '<td>'.htmlspecialchars($code).'</td>';
            $html .= '<td class="course-name">'.htmlspecialchars($name).'</td>';
            $html .= '<td>'.$credit.'</td>';
            $html .= '<td>'.(is_numeric($ca) ? number_format($ca,1) : $ca).'</td>';
            $html .= '<td>'.(is_numeric($exam) ? number_format($exam,1) : $exam).'</td>';
            $html .= '<td><strong>'.(is_numeric($total) ? number_format($total,1) : $total).'</strong></td>';
            $html .= '<td'.$grade_class.'>'.$grade.'</td>';
            $html .= '<td>'.number_format($gp,1).'</td>';
            $html .= '</tr>';
        }
        if ($semester) {
            $html .= '<tr style="background:#f0f2ff;font-weight:700"><td colspan="8" style="text-align:left;padding:5px 8px;color:#1a237e;font-size:10px">End of '.htmlspecialchars($semester).'</td></tr>';
        }
    } else {
        $html .= '<tr><td colspan="8" style="text-align:center;padding:20px;color:#999">No academic records found for this student.</td></tr>';
    }
    $html .= '</tbody></table>';
    
    // GPA Summary
    $gpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;
    $cgpa = $gpa; // Simplified - real CGPA would need all semesters
    
    // Classification
    $class = '';
    if ($cgpa >= 3.5) $class = 'First Class';
    elseif ($cgpa >= 3.0) $class = 'Second Class Upper Division';
    elseif ($cgpa >= 2.0) $class = 'Second Class Lower Division';
    elseif ($cgpa >= 1.0) $class = 'Pass';
    else $class = 'Fail';
    
    $html .= '<div class="gpa-summary"><table><tr>';
    $html .= '<td class="gpa-label">Total Credit Hours:</td><td class="gpa-value">'.$total_credits.'</td>';
    $html .= '<td class="gpa-label">Grade Points Total:</td><td class="gpa-value">'.number_format($total_points,2).'</td>';
    $html .= '<td class="gpa-label">GPA:</td><td class="gpa-value">'.number_format($gpa,2).'</td>';
    $html .= '<td class="gpa-label">CGPA:</td><td class="gpa-value">'.number_format($cgpa,2).'</td>';
    $html .= '</tr></table></div>';
    
    $html .= '<div class="classification">AWARD CLASSIFICATION: '.$class.'</div>';
    
    // Signatures
    $html .= '<div class="signatures"><table><tr>';
    $html .= '<td><div class="sig-line"></div><div class="sig-title">Academic Registrar</div><div class="sig-name">_________________________</div><div class="sig-date">Date: '.date('d/m/Y').'</div></td>';
    $html .= '<td><div class="sig-line"></div><div class="sig-title">Principal</div><div class="sig-name">_________________________</div><div class="sig-date">Date: '.date('d/m/Y').'</div></td>';
    $html .= '<td><div class="sig-line"></div><div class="sig-title">Official Stamp</div><div class="sig-name">[SEAL]</div><div class="sig-date">'.$institution.'</div></td>';
    $html .= '</tr></table></div>';
    
    // Footer
    $html .= '<div class="footer">';
    $html .= '<span class="verification">Verify at: https://isnm.ac.ug/verify?ref='.$tnumber.'</span> | ';
    $html .= 'This is a computer-generated document. No signature required if digitally verified.';
    $html .= '<br>© '.date('Y').' '.$institution.'. All rights reserved.';
    $html .= '</div>';
    
    $html .= '</div></div></body></html>';
    return $html;
}

/**
 * Generate a professional Certificate HTML
 * @param array $student - Student record from database
 * @param array $settings - Registrar settings
 * @param string $cert_type - Certificate type (Certificate/Diploma/Degree)
 * @param string $cert_number - Unique certificate reference
 * @param string $class_of_award - e.g. First Class, Second Class Upper, etc.
 * @return string - Complete HTML document
 */
function generateProfessionalCertificate($student, $settings, $cert_type = 'Certificate', $cert_number = '', $class_of_award = '') {
    $institution = $settings['institution_name'] ?? 'ISNM';
    $logo_path = '../assets/img/school-logo.svg';
    $logo_data = '';
    if (file_exists(__DIR__ . '/../assets/img/school-logo.svg')) {
        $logo_data = base64_encode(file_get_contents(__DIR__ . '/../assets/img/school-logo.svg'));
    }
    
    $full_name = $student['full_name'] ?? trim(($student['first_name']??'').' '.($student['other_name']??'').' '.($student['surname']??''));
    $program = $student['course'] ?? $student['program'] ?? '';
    $reg_no = $student['registration_number'] ?? $student['student_number'] ?? '';
    $cnumber = $cert_number ?: 'C-'.date('Ymd').'-'.mt_rand(1000,9999);
    
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Certificate - '.htmlspecialchars($full_name).'</title>';
    $html .= '<style>
        @page { size: A4 landscape; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Georgia", "Times New Roman", serif; background: #e8e0d0; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 30px; }
        .certificate-page { width: 297mm; min-height: 210mm; background: #fffdf5; position: relative; box-shadow: 0 12px 50px rgba(0,0,0,0.15); overflow: hidden; }
        
        /* Ornate Border - Outer */
        .cert-border-outer { position: absolute; top: 12px; left: 12px; right: 12px; bottom: 12px; border: 3px solid #8a6d00; pointer-events: none; }
        .cert-border-mid { position: absolute; top: 18px; left: 18px; right: 18px; bottom: 18px; border: 2px solid #c0a060; pointer-events: none; }
        .cert-border-inner { position: absolute; top: 24px; left: 24px; right: 24px; bottom: 24px; border: 1px solid #e8d5a0; pointer-events: none; }
        
        /* Corner Decorations */
        .corner { position: absolute; width: 40px; height: 40px; border-color: #8a6d00; border-style: solid; }
        .corner-tl { top: 12px; left: 12px; border-width: 3px 0 0 3px; }
        .corner-tr { top: 12px; right: 12px; border-width: 3px 3px 0 0; }
        .corner-bl { bottom: 12px; left: 12px; border-width: 0 0 3px 3px; }
        .corner-br { bottom: 12px; right: 12px; border-width: 0 3px 3px 0; }
        
        /* Content */
        .cert-content { position: relative; z-index: 1; padding: 45px 55px; text-align: center; min-height: 210mm; display: flex; flex-direction: column; justify-content: center; }
        
        /* Gold accent line */
        .gold-line { width: 80%; height: 2px; background: linear-gradient(to right, transparent, #c0a060, transparent); margin: 10px auto; }
        .gold-line-thick { width: 60%; height: 3px; background: linear-gradient(to right, transparent, #8a6d00, transparent); margin: 10px auto; }
        
        /* Logo */
        .cert-logo { margin-bottom: 10px; }
        .cert-logo img, .cert-logo svg { width: 80px; height: 80px; }
        
        /* Institution Name */
        .cert-inst-name { font-size: 20px; font-weight: 700; color: #1a237e; letter-spacing: 2px; text-transform: uppercase; margin: 5px 0; }
        .cert-inst-sub { font-size: 11px; color: #666; letter-spacing: 1px; margin-bottom: 10px; }
        
        /* Certificate Title */
        .cert-title { font-size: 32px; font-weight: 700; color: #8a6d00; letter-spacing: 6px; text-transform: uppercase; margin: 15px 0; text-shadow: 1px 1px 2px rgba(138,109,0,0.1); }
        
        /* Body Text */
        .cert-body-text { font-size: 13px; color: #444; line-height: 1.8; margin: 10px 0; }
        
        /* Student Name - Large Calligraphy Style */
        .cert-student-name { font-size: 36px; font-weight: 700; color: #1a237e; margin: 18px 0; letter-spacing: 2px; font-family: "Georgia", serif; text-shadow: 1px 1px 3px rgba(26,35,126,0.1); }
        
        /* Program */
        .cert-program { font-size: 16px; color: #333; font-weight: 600; margin: 8px 0; }
        
        /* Classification Award */
        .cert-award { font-size: 14px; color: #8a6d00; font-weight: 700; margin: 12px 0; padding: 8px 20px; border: 1px solid #e8d5a0; background: #fffcf0; display: inline-block; border-radius: 4px; }
        
        /* Date */
        .cert-date { font-size: 12px; color: #555; margin: 12px 0; }
        
        /* Signatures */
        .cert-signatures { margin-top: 25px; }
        .cert-signatures table { width: 100%; border-collapse: collapse; }
        .cert-signatures td { width: 33%; text-align: center; padding: 5px; }
        .cert-signatures .sig-line { width: 140px; height: 1px; border-top: 2px solid #333; margin: 35px auto 5px; }
        .cert-signatures .sig-role { font-size: 11px; font-weight: 700; color: #1a237e; text-transform: uppercase; letter-spacing: 1px; }
        .cert-signatures .sig-name { font-size: 11px; color: #555; }
        
        /* Certificate Number */
        .cert-number { position: absolute; bottom: 35px; right: 40px; font-size: 9px; color: #999; z-index: 1; }
        
        /* Watermark */
        .cert-watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 120px; color: rgba(138,109,0,0.03); font-weight: 900; pointer-events: none; white-space: nowrap; z-index: 0; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .certificate-page { box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style></head><body>';
    
    // Print controls
    $html .= '<div class="no-print" style="text-align:center;margin-bottom:12px;font-family:sans-serif">
        <button onclick="window.print()" style="padding:8px 24px;background:#1a237e;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px;margin-right:8px">🖨 Print</button>
        <button onclick="window.close()" style="padding:8px 24px;background:#666;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px">✕ Close</button>
    </div>';
    
    $html .= '<div class="certificate-page">';
    $html .= '<div class="cert-border-outer"></div><div class="cert-border-mid"></div><div class="cert-border-inner"></div>';
    $html .= '<div class="corner corner-tl"></div><div class="corner corner-tr"></div><div class="corner corner-bl"></div><div class="corner corner-br"></div>';
    $html .= '<div class="cert-watermark">CERTIFICATE</div>';
    $html .= '<div class="cert-number">Cert. No: '.htmlspecialchars($cnumber).'</div>';
    
    $html .= '<div class="cert-content">';
    
    // Logo
    $html .= '<div class="cert-logo">';
    if ($logo_data) {
        $html .= '<img src="data:image/svg+xml;base64,'.$logo_data.'" alt="Logo">';
    } else {
        $html .= '<svg width="80" height="80" viewBox="0 0 200 200"><circle cx="100" cy="100" r="95" fill="none" stroke="#1a237e" stroke-width="4"/><circle cx="100" cy="100" r="85" fill="none" stroke="#8a6d00" stroke-width="2"/><text x="100" y="55" text-anchor="middle" font-size="28" font-weight="bold" fill="#1a237e" font-family="serif">ISNM</text><path d="M 70 120 Q 100 95 130 120" stroke="#1a237e" stroke-width="3" fill="none"/><line x1="100" y1="65" x2="100" y2="110" stroke="#1a237e" stroke-width="3"/><line x1="85" y1="95" x2="115" y2="95" stroke="#1a237e" stroke-width="3"/><text x="100" y="155" text-anchor="middle" font-size="10" fill="#1a237e" font-family="serif">NURSING &amp; MIDWIFERY</text></svg>';
    }
    $html .= '</div>';
    
    // Institution
    $html .= '<div class="cert-inst-name">'.$institution.'</div>';
    $html .= '<div class="cert-inst-sub">INTERNATIONAL SCHOOL OF NURSING &amp; MIDWIFERY</div>';
    $html .= '<div class="gold-line"></div>';
    
    // Title
    $html .= '<div class="cert-title">'.strtoupper($cert_type).'</div>';
    $html .= '<div class="gold-line-thick"></div>';
    
    // Body
    $html .= '<div class="cert-body-text">This is to certify that</div>';
    $html .= '<div class="cert-student-name">'.htmlspecialchars(strtoupper($full_name)).'</div>';
    $html .= '<div class="cert-body-text">having successfully completed the prescribed programme of studies and satisfied all the requirements for the award of</div>';
    $html .= '<div class="cert-program">'.htmlspecialchars(strtoupper($program)).'</div>';
    
    if ($class_of_award) {
        $html .= '<div class="cert-award">Awarded: '.htmlspecialchars(strtoupper($class_of_award)).'</div>';
    }
    
    $html .= '<div class="cert-date">Given under the Seal of the Institution on this '.date('jS').' day of '.date('F Y').'</div>';
    $html .= '<div class="gold-line-thick"></div>';
    
    // Signatures
    $html .= '<div class="cert-signatures"><table><tr>';
    $html .= '<td><div class="sig-line"></div><div class="sig-role">Academic Registrar</div><div class="sig-name">ISNM</div></td>';
    $html .= '<td><div style="width:60px;height:60px;border:2px solid #8a6d00;border-radius:50%;margin:15px auto 5px;line-height:60px;font-size:10px;color:#8a6d00">SEAL</div></td>';
    $html .= '<td><div class="sig-line"></div><div class="sig-role">Principal</div><div class="sig-name">ISNM</div></td>';
    $html .= '</tr></table></div>';
    
    $html .= '</div></div></body></html>';
    return $html;
}

endif;
