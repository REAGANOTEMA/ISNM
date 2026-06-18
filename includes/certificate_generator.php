<?php
/**
 * ISNM Professional Certificate & Transcript Generator
 * Premium document generation with school branding and print support
 */

if (!function_exists('generateCertificateHTML')) {
function generateCertificateHTML(array $data): string {
    $data = array_merge([
        'certificate_type' => 'Certificate of Completion',
        'student_name' => '_______________________',
        'registration_number' => '_______________________',
        'program' => '_______________________',
        'program_duration' => '_______________________',
        'academic_year' => date('Y'),
        'completion_date' => date('F j, Y'),
        'grade' => 'Pass',
        'gpa' => 'N/A',
        'class' => 'Pass',
        'principal_name' => '_______________________',
        'director_name' => '_______________________',
        'certificate_number' => 'ISNM/' . date('Y') . '/' . strtoupper(substr(uniqid(), -6)),
        'issue_date' => date('F j, Y'),
    ], $data);

    $gradient = 'linear-gradient(135deg, #0f4c3a 0%, #1a6b4e 30%, #d4a843 70%, #f5d76e 100%)';
    $goldGradient = 'linear-gradient(135deg, #d4a843, #f5d76e, #d4a843)';

    return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . htmlspecialchars($data['certificate_type']) . ' | ISNM</title>
<style>
    @page { margin: 0; size: A4 landscape; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "Georgia", "Times New Roman", serif; background: #1a1a2e; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
    .certificate-wrapper { width: 1120px; min-height: 793px; position: relative; overflow: hidden; }
    .certificate-border { position: absolute; inset: 12px; border: 3px solid #d4a843; border-radius: 20px; pointer-events: none; z-index: 2; }
    .certificate-border-inner { position: absolute; inset: 18px; border: 1.5px solid rgba(212,168,67,0.4); border-radius: 14px; pointer-events: none; z-index: 2; }
    .certificate-bg { position: absolute; inset: 0; background: linear-gradient(135deg, #fdf8ed 0%, #faf3e0 40%, #f5e6c8 100%); z-index: 0; }
    .certificate-bg::before { content: ""; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg width=60 height=60 viewBox=0 0 60 60 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22%23d4a843%22 fill-opacity=%220.05%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/svg%3E"); z-index: 0; }
    .certificate-content { position: relative; z-index: 1; padding: 50px 60px; height: 100%; display: flex; flex-direction: column; }
    .corner-ornament { position: absolute; width: 80px; height: 80px; z-index: 3; }
    .corner-ornament svg { width: 100%; height: 100%; }
    .corner-tl { top: 20px; left: 20px; }
    .corner-tr { top: 20px; right: 20px; transform: scaleX(-1); }
    .corner-bl { bottom: 20px; left: 20px; transform: scaleY(-1); }
    .corner-br { bottom: 20px; right: 20px; transform: scale(-1,-1); }
    .top-section { text-align: center; margin-bottom: 15px; position: relative; }
    .top-section::after { content: ""; display: block; width: 60%; height: 2px; background: ' . $goldGradient . '; margin: 15px auto 0; border-radius: 2px; }
    .school-logo { width: 85px; height: 85px; border-radius: 50%; border: 3px solid #d4a843; object-fit: cover; margin-bottom: 10px; box-shadow: 0 4px 15px rgba(212,168,67,0.3); }
    .school-name { font-size: 22px; font-weight: 700; ' . $gradient . '; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; letter-spacing: 2px; text-transform: uppercase; font-family: "Georgia", serif; }
    .school-motto { font-size: 13px; color: #8b7355; font-style: italic; letter-spacing: 3px; margin-top: 3px; }
    .cert-type { text-align: center; margin: 20px 0; }
    .cert-type-badge { display: inline-block; background: ' . $gradient . '; color: #fff; padding: 8px 40px; border-radius: 30px; font-size: 14px; font-weight: 600; letter-spacing: 3px; text-transform: uppercase; box-shadow: 0 4px 15px rgba(15,76,58,0.3); }
    .cert-title { text-align: center; margin: 18px 0; }
    .cert-title h1 { font-size: 38px; font-weight: 700; ' . $gradient . '; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; letter-spacing: 4px; text-transform: uppercase; font-family: "Georgia", serif; }
    .presented-text { text-align: center; font-size: 16px; color: #666; font-style: italic; margin: 10px 0; }
    .student-name { text-align: center; font-size: 36px; font-weight: 700; color: #0f4c3a; letter-spacing: 3px; margin: 10px 0; font-family: "Georgia", serif; text-transform: uppercase; border-bottom: 2px dashed #d4a843; display: inline-block; padding: 0 30px 8px; }
    .student-name-wrapper { text-align: center; }
    .cert-body { text-align: center; font-size: 15px; color: #555; line-height: 1.8; max-width: 85%; margin: 15px auto; }
    .cert-body strong { color: #0f4c3a; }
    .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 18px 40px; }
    .info-item { background: rgba(212,168,67,0.08); border: 1px solid rgba(212,168,67,0.2); border-radius: 10px; padding: 12px 15px; text-align: center; }
    .info-item .label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #8b7355; font-weight: 600; }
    .info-item .value { font-size: 16px; font-weight: 700; color: #0f4c3a; margin-top: 4px; }
    .signature-section { display: flex; justify-content: space-between; margin: 20px 60px 10px; padding-top: 15px; border-top: 1px solid rgba(212,168,67,0.3); }
    .signature-box { text-align: center; width: 200px; }
    .signature-line { width: 160px; height: 1px; background: #333; margin: 0 auto 6px; position: relative; }
    .signature-label { font-size: 11px; color: #8b7355; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
    .signature-name { font-size: 13px; font-weight: 700; color: #0f4c3a; margin-top: 2px; }
    .seal-section { position: absolute; bottom: 50px; right: 70px; text-align: center; z-index: 4; }
    .seal-circle { width: 90px; height: 90px; border-radius: 50%; border: 3px solid #d4a843; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.95); box-shadow: 0 4px 20px rgba(212,168,67,0.3); }
    .seal-inner { width: 65px; height: 65px; border-radius: 50%; border: 2px solid #0f4c3a; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; color: #0f4c3a; text-align: center; line-height: 1.2; letter-spacing: 1px; }
    .seal-text { font-size: 9px; color: #8b7355; margin-top: 5px; font-weight: 600; letter-spacing: 1px; }
    .footer-num { text-align: center; font-size: 11px; color: #aaa; margin-top: auto; padding-top: 10px; letter-spacing: 1px; }
    .ribbon { position: absolute; top: 40px; right: 50px; z-index: 5; }
    @media print { body { background: #fff; padding: 0; } .certificate-wrapper { width: 100%; min-height: auto; } }
</style>
</head>
<body>
<div class="certificate-wrapper">
    <div class="certificate-border"></div>
    <div class="certificate-border-inner"></div>
    <div class="certificate-bg"></div>
    <div class="corner-ornament corner-tl"><svg viewBox="0 0 80 80"><path d="M0 80V0h80v8H8v72H0z" fill="#d4a843" opacity="0.3"/><path d="M0 0h80v4H4v76H0V0z" fill="#d4a843" opacity="0.15"/><circle cx="15" cy="15" r="3" fill="#d4a843" opacity="0.5"/></svg></div>
    <div class="corner-ornament corner-tr"><svg viewBox="0 0 80 80"><path d="M0 80V0h80v8H8v72H0z" fill="#d4a843" opacity="0.3"/><path d="M0 0h80v4H4v76H0V0z" fill="#d4a843" opacity="0.15"/><circle cx="15" cy="15" r="3" fill="#d4a843" opacity="0.5"/></svg></div>
    <div class="corner-ornament corner-bl"><svg viewBox="0 0 80 80"><path d="M0 80V0h80v8H8v72H0z" fill="#d4a843" opacity="0.3"/><path d="M0 0h80v4H4v76H0V0z" fill="#d4a843" opacity="0.15"/><circle cx="15" cy="15" r="3" fill="#d4a843" opacity="0.5"/></svg></div>
    <div class="corner-ornament corner-br"><svg viewBox="0 0 80 80"><path d="M0 80V0h80v8H8v72H0z" fill="#d4a843" opacity="0.3"/><path d="M0 0h80v4H4v76H0V0z" fill="#d4a843" opacity="0.15"/><circle cx="15" cy="15" r="3" fill="#d4a843" opacity="0.5"/></svg></div>
    <div class="certificate-content">
        <div class="top-section">
            <img src="../images/school-logo.png" alt="ISNM" class="school-logo">
            <div class="school-name">Iganga School of Nursing &amp; Midwifery</div>
            <div class="school-motto">&#8220;Chosen to Serve, Based on a Disciplined Mind for Health Action&#8221;</div>
        </div>
        <div class="cert-type"><span class="cert-type-badge">' . htmlspecialchars($data['certificate_type']) . '</span></div>
        <div class="cert-title"><h1>Certificate of Completion</h1></div>
        <div class="presented-text">This is proudly presented to</div>
        <div class="student-name-wrapper"><div class="student-name">' . htmlspecialchars(strtoupper($data['student_name'])) . '</div></div>
        <div class="cert-body">
            For successfully completing the <strong>' . htmlspecialchars($data['program']) . '</strong> program
            (' . htmlspecialchars($data['program_duration']) . ') during the academic year <strong>' . htmlspecialchars($data['academic_year']) . '</strong>.
            <br>In witness whereof, this certificate is issued on <strong>' . htmlspecialchars($data['issue_date']) . '</strong>.
        </div>
        <div class="info-grid">
            <div class="info-item"><div class="label">Reg. Number</div><div class="value">' . htmlspecialchars($data['registration_number']) . '</div></div>
            <div class="info-item"><div class="label">Grade</div><div class="value">' . htmlspecialchars($data['grade']) . '</div></div>
            <div class="info-item"><div class="label">Class</div><div class="value">' . htmlspecialchars($data['class']) . '</div></div>
        </div>
        <div class="signature-section">
            <div class="signature-box"><div class="signature-line" style="width:140px"></div><div class="signature-label">Principal</div><div class="signature-name">' . htmlspecialchars($data['principal_name']) . '</div></div>
            <div class="signature-box"><div class="signature-line" style="width:140px"></div><div class="signature-label">Director General</div><div class="signature-name">' . htmlspecialchars($data['director_name']) . '</div></div>
            <div class="signature-box"><div class="signature-line" style="width:140px"></div><div class="signature-label">Academic Registrar</div><div class="signature-name">_______________________</div></div>
        </div>
        <div class="seal-section">
            <div class="seal-circle"><div class="seal-inner">ISNM<br>OFFICIAL</div></div>
            <div class="seal-text">OFFICIAL SEAL</div>
        </div>
        <div class="footer-num">Certificate No: ' . htmlspecialchars($data['certificate_number']) . ' | Generated: ' . htmlspecialchars($data['issue_date']) . '</div>
    </div>
</div>
</body>
</html>';
}
}

if (!function_exists('generateTranscriptHTML')) {
function generateTranscriptHTML(array $student, array $records, string $type = 'progress'): string {
    $total_credits = array_sum(array_column($records, 'credits'));
    $total_marks = array_sum(array_column($records, 'marks'));
    $count = count($records);
    $avg = $count > 0 ? round($total_marks / $count, 2) : 0;

    $gpa_map = [80 => 4.0, 75 => 3.5, 70 => 3.0, 65 => 2.5, 60 => 2.0, 50 => 1.5, 40 => 1.0, 0 => 0.0];
    $gpa = 0;
    foreach ($gpa_map as $min => $gp) { if ($avg >= $min) { $gpa = $gp; break; } }

    $rows = '';
    foreach ($records as $r) {
        $course = htmlspecialchars($r['course_code'] ?? $r['course_name'] ?? '---');
        $name = htmlspecialchars($r['course_name'] ?? '---');
        $cred = (int)($r['credits'] ?? 0);
        $sem = htmlspecialchars($r['semester'] ?? '---');
        $year = htmlspecialchars($r['academic_year'] ?? '---');
        $marks = $r['marks'] ?? '---';
        $grade = $r['grade'] ?? ($marks !== '---' ? ($marks >= 80 ? 'A' : ($marks >= 70 ? 'B' : ($marks >= 60 ? 'C' : ($marks >= 50 ? 'D' : 'F')))) : '---');
        $rows .= '<tr><td>' . $course . '</td><td>' . $name . '</td><td>' . $cred . '</td><td>' . $sem . '</td><td>' . $year . '</td><td>' . $marks . '</td><td>' . $grade . '</td></tr>';
    }

    $standing = $gpa >= 3.5 ? 'Excellent' : ($gpa >= 3.0 ? 'Good' : ($gpa >= 2.0 ? 'Satisfactory' : 'Probation'));

    return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Academic Transcript | ISNM</title>
<style>
    @page { margin: 15mm; size: A4 portrait; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "Georgia", "Times New Roman", serif; background: #f0f0f0; padding: 15px; }
    .transcript-wrapper { max-width: 210mm; margin: 0 auto; background: #fff; border: 2px solid #0f4c3a; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1); position: relative; }
    .t-header { background: linear-gradient(135deg, #0f4c3a 0%, #1a6b4e 50%, #0f4c3a 100%); color: #fff; padding: 30px 35px 20px; text-align: center; position: relative; }
    .t-header::after { content: ""; position: absolute; bottom: 0; left: 10%; right: 10%; height: 3px; background: linear-gradient(90deg, transparent, #d4a843, transparent); border-radius: 2px; }
    .t-logo { width: 75px; height: 75px; border-radius: 50%; border: 3px solid #d4a843; object-fit: cover; margin-bottom: 8px; }
    .t-school { font-size: 20px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; }
    .t-motto { font-size: 11px; font-style: italic; opacity: 0.8; margin-top: 3px; }
    .t-title { text-align: center; padding: 12px; background: linear-gradient(135deg, #d4a843, #f5d76e, #d4a843); color: #0f4c3a; font-size: 16px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; }
    .t-body { padding: 25px 30px; }
    .t-section { margin-bottom: 20px; }
    .t-section-title { font-size: 14px; font-weight: 700; color: #0f4c3a; border-bottom: 2px solid #d4a843; padding-bottom: 6px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px; }
    .t-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .t-info { background: #faf6ee; padding: 8px 12px; border-radius: 6px; border-left: 3px solid #d4a843; }
    .t-info .lbl { font-size: 10px; text-transform: uppercase; color: #8b7355; letter-spacing: 0.5px; }
    .t-info .val { font-size: 13px; font-weight: 600; color: #0f4c3a; }
    table.t-records { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
    table.t-records th { background: #0f4c3a; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
    table.t-records td { padding: 7px 10px; border-bottom: 1px solid #e8e0d0; }
    table.t-records tr:nth-child(even) td { background: #faf6ee; }
    table.t-records tr:last-child td { border-bottom: none; }
    .t-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 15px; }
    .t-summary-item { background: linear-gradient(135deg, #0f4c3a, #1a6b4e); color: #fff; padding: 10px; border-radius: 8px; text-align: center; }
    .t-summary-item .lbl { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; }
    .t-summary-item .val { font-size: 16px; font-weight: 700; }
    .t-signatures { display: flex; justify-content: space-between; margin-top: 25px; padding-top: 15px; border-top: 2px solid #d4a843; }
    .t-sig { text-align: center; width: 160px; }
    .t-sig-line { width: 120px; height: 1px; background: #333; margin: 0 auto 5px; }
    .t-sig-label { font-size: 10px; color: #8b7355; text-transform: uppercase; letter-spacing: 0.5px; }
    .t-sig-name { font-size: 11px; font-weight: 700; color: #0f4c3a; }
    .t-footer { text-align: center; padding: 12px; font-size: 10px; color: #999; border-top: 1px solid #e8e0d0; margin-top: 10px; }
    .t-watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%) rotate(-30deg); font-size: 100px; color: rgba(15,76,58,0.04); font-weight: 700; letter-spacing: 10px; z-index: -1; pointer-events: none; }
    .t-stamp { display: inline-block; background: #dc3545; color: #fff; padding: 4px 14px; border-radius: 12px; font-size: 10px; font-weight: 700; letter-spacing: 1px; }
    @media print { body { background: #fff; padding: 0; } .transcript-wrapper { box-shadow: none; border: 1px solid #000; } }
</style>
</head>
<body>
<div class="t-watermark">OFFICIAL</div>
<div class="transcript-wrapper">
    <div class="t-header">
        <img src="../images/school-logo.png" alt="ISNM" class="t-logo">
        <div class="t-school">Iganga School of Nursing &amp; Midwifery</div>
        <div class="t-motto">"Chosen to Serve, Based on a Disciplined Mind for Health Action"</div>
    </div>
    <div class="t-title">' . ($type === 'progress' ? 'PROGRESS REPORT' : 'OFFICIAL ACADEMIC TRANSCRIPT') . '</div>
    <div class="t-body">
        <div class="t-section">
            <div class="t-section-title">Student Information</div>
            <div class="t-info-grid">
                <div class="t-info"><div class="lbl">Full Name</div><div class="val">' . htmlspecialchars($student['full_name'] ?? '---') . '</div></div>
                <div class="t-info"><div class="lbl">Reg. Number</div><div class="val">' . htmlspecialchars($student['registration_number'] ?? $student['student_number'] ?? '---') . '</div></div>
                <div class="t-info"><div class="lbl">Program</div><div class="val">' . htmlspecialchars($student['program'] ?? $student['course'] ?? '---') . '</div></div>
                <div class="t-info"><div class="lbl">Academic Year</div><div class="val">' . htmlspecialchars($student['academic_year'] ?? date('Y')) . '</div></div>
            </div>
        </div>
        <div class="t-section">
            <div class="t-section-title">Academic Records</div>
            <table class="t-records">
                <thead><tr><th>Course</th><th>Course Name</th><th>Credits</th><th>Semester</th><th>Year</th><th>Marks</th><th>Grade</th></tr></thead>
                <tbody>' . $rows . '</tbody>
            </table>
        </div>
        <div class="t-section">
            <div class="t-section-title">Performance Summary</div>
            <div class="t-summary">
                <div class="t-summary-item"><div class="lbl">Total Courses</div><div class="val">' . $count . '</div></div>
                <div class="t-summary-item"><div class="lbl">Total Credits</div><div class="val">' . $total_credits . '</div></div>
                <div class="t-summary-item"><div class="lbl">Average Score</div><div class="val">' . $avg . '%</div></div>
                <div class="t-summary-item"><div class="lbl">CGPA</div><div class="val">' . number_format($gpa, 2) . '</div></div>
            </div>
            <div style="text-align:center;margin-top:10px;"><span class="t-stamp">' . strtoupper($standing) . ' STANDING</span></div>
        </div>
        <div class="t-signatures">
            <div class="t-sig"><div class="t-sig-line"></div><div class="t-sig-label">Academic Registrar</div><div class="t-sig-name">________________</div></div>
            <div class="t-sig"><div class="t-sig-line"></div><div class="t-sig-label">Principal</div><div class="t-sig-name">________________</div></div>
            <div class="t-sig"><div class="t-sig-line"></div><div class="t-sig-label">Director General</div><div class="t-sig-name">________________</div></div>
        </div>
    </div>
    <div class="t-footer">This is an electronically generated document. Certificate No: ISNM/TR/' . date('Y') . '/' . strtoupper(substr(uniqid(), -6)) . ' | Generated: ' . date('F j, Y, H:i') . '</div>
</div>
</body>
</html>';
}
}
