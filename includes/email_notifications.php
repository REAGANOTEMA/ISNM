<?php
/**
 * Professional Email Notification Dispatcher
 * Sends beautiful HTML emails with school branding via PHPMailer (Gmail SMTP)
 * Used by: contact, volunteer, donation, application handlers
 */

if (!function_exists('sendProfessionalEmail')) {
    function sendProfessionalEmail($to, $toName, $subject, $htmlBody) {
        require_once __DIR__ . '/../phpmailer/src/Exception.php';
        require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../phpmailer/src/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'erp.schoolmanagementsystem@gmail.com';
            $mail->Password = 'whqbysomdhdjthvr';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->setFrom('erp.schoolmanagementsystem@gmail.com', 'ISNM Notification System');
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('Email send failed to ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('buildProfessionalEmailTemplate')) {
    function buildProfessionalEmailTemplate($title, $contentBlocks, $cta = null) {
        $ctaHtml = '';
        if ($cta) {
            $ctaHtml = '
            <div style="text-align:center;margin:30px 0">
                <a href="' . htmlspecialchars($cta['url']) . '" style="display:inline-block;padding:14px 36px;background:#1a237e;color:#ffffff;text-decoration:none;border-radius:8px;font-size:16px;font-weight:600;letter-spacing:0.3px">' . htmlspecialchars($cta['text']) . '</a>
            </div>';
        }

        $contentHtml = '';
        foreach ($contentBlocks as $block) {
            if (isset($block['type']) && $block['type'] === 'table') {
                $rows = '';
                foreach ($block['data'] as $label => $value) {
                    $rows .= '<tr><td style="padding:10px 16px;border-bottom:1px solid #e8ecf0;color:#475569;font-weight:500;width:180px">' . htmlspecialchars($label) . '</td><td style="padding:10px 16px;border-bottom:1px solid #e8ecf0;color:#0f172a">' . nl2br(htmlspecialchars($value)) . '</td></tr>';
                }
                $contentHtml .= '
                <table style="width:100%;border-collapse:collapse;margin:16px 0;background:#f8fafc;border-radius:8px;overflow:hidden;font-size:14px">
                    <tbody>' . $rows . '</tbody>
                </table>';
            } else {
                $contentHtml .= '<p style="margin:12px 0;color:#475569;font-size:15px;line-height:1.7">' . nl2br(htmlspecialchars($block)) . '</p>';
            }
        }

        return '
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f0f2f5;font-family:\'Inter\',\'Segoe UI\',Helvetica,Arial,sans-serif">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5;padding:30px 10px">
                <tr><td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08)">
                        <!-- Header with school branding -->
                        <tr>
                            <td style="background:linear-gradient(135deg,#1a237e 0%,#0d47a1 100%);padding:30px 36px;text-align:center">
                                <img src="https://isnm.ac.ug/images/school-logo.png" alt="ISNM" style="width:72px;height:72px;border-radius:50%;border:3px solid #ffd600;object-fit:cover;margin-bottom:10px">
                                <h1 style="color:#ffffff;font-size:22px;font-weight:700;margin:8px 0 4px;letter-spacing:0.5px">Iganga School of Nursing<br>&amp; Midwifery</h1>
                                <p style="color:rgba(255,255,255,0.75);font-size:13px;margin:0">' . htmlspecialchars($title) . '</p>
                            </td>
                        </tr>
                        <!-- Body -->
                        <tr>
                            <td style="padding:36px">
                                ' . $contentHtml . '
                                ' . $ctaHtml . '
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style="background:#f8fafc;padding:24px 36px;text-align:center;border-top:1px solid #e8ecf0">
                                <p style="margin:0 0 8px;color:#64748b;font-size:13px">Iganga School of Nursing &amp; Midwifery</p>
                                <p style="margin:0 0 4px;color:#94a3b8;font-size:12px">P.O. Box 123, Iganga, Uganda | Tel: 0782 990 403</p>
                                <p style="margin:0;color:#94a3b8;font-size:12px">Email: info@igangaschoolofnursingandmidwifery.ac.ug | Website: isnm.ac.ug</p>
                                <p style="margin:12px 0 0;color:#cbd5e1;font-size:11px">This is an automated notification from the ISNM School Management System.</p>
                            </td>
                        </tr>
                    </table>
                </td></tr>
            </table>
        </body>
        </html>';
    }
}

if (!function_exists('notifyDirectorGeneral')) {
    function notifyDirectorGeneral($subject, $bodyContent, $cta = null) {
        $to = defined('EMAIL_DIRECTOR_GENERAL') ? EMAIL_DIRECTOR_GENERAL : 'director_general@igangaschoolofnursingandmidwifery.ac.ug';
        $html = buildProfessionalEmailTemplate('Director General Notification', $bodyContent, $cta);
        return sendProfessionalEmail($to, 'Director General', $subject, $html);
    }
}

if (!function_exists('notifyAllDirectors')) {
    function notifyAllDirectors($subject, $bodyContent, $cta = null) {
        $recipients = [
            [EMAIL_DIRECTOR_GENERAL, 'Director General'],
            [EMAIL_CEO, 'CEO'],
            [EMAIL_DIRECTOR_ACADEMICS, 'Director Academics'],
            [EMAIL_DIRECTOR_FINANCE, 'Director Finance'],
            [EMAIL_DIRECTOR_ICT, 'Director ICT'],
            [EMAIL_ADMISSIONS, 'Admissions Director'],
            [EMAIL_REGISTRAR, 'Academic Registrar'],
            [EMAIL_SECRETARY, 'School Secretary'],
            [EMAIL_PRINCIPAL, 'Principal'],
        ];
        $html = buildProfessionalEmailTemplate('New Application Notification', $bodyContent, $cta);
        $sent = 0;
        foreach ($recipients as $r) {
            if (sendProfessionalEmail($r[0], $r[1], $subject, $html)) $sent++;
        }
        return $sent;
    }
}
