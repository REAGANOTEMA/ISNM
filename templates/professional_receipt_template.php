<?php
// ISNM Professional Receipt Template
// Official receipt template with school logo and professional design

function generateProfessionalReceipt($data) {
    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt - ISNM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 2px solid #2c3e50;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }
        .receipt-header {
            background: linear-gradient(135deg, #2c3e50 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .school-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid white;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .receipt-title {
            font-size: 24px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .receipt-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        .receipt-body {
            padding: 30px;
        }
        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2c3e50;
        }
        .info-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            font-weight: 700;
            color: #2c3e50;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        .receipt-table th,
        .receipt-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .receipt-table th {
            background: #2c3e50;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .receipt-table tr:last-child td {
            border-bottom: none;
        }
        .total-row {
            background: #f8f9fa;
            font-weight: 700;
        }
        .total-row td {
            padding: 15px;
            font-size: 18px;
            color: #2c3e50;
        }
        .receipt-footer {
            background: linear-gradient(135deg, #2c3e50 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            text-align: center;
            margin-top: 30px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            color: rgba(44, 62, 80, 0.1);
            z-index: -1;
            font-weight: 700;
            letter-spacing: 5px;
        }
        .official-stamp {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
        }
        .signature-line {
            border-top: 2px solid #2c3e50;
            margin-top: 20px;
            padding-top: 20px;
            text-align: center;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt-container {
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
    
    <div class="receipt-container">
        <div class="receipt-header">
            <img src="../images/school-logo.png" alt="ISNM Logo" class="school-logo">
            <div class="receipt-title">OFFICIAL RECEIPT</div>
            <div class="receipt-subtitle">Iganga School of Nursing and Midwifery</div>
        </div>
        
        <div class="receipt-body">
            <div class="receipt-info">
                <div class="info-section">
                    <div class="info-title">Receipt Information</div>
                    <div class="info-item">
                        <span class="info-label">Receipt Number:</span>
                        <span class="info-value">' . $data['receipt_number'] . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date:</span>
                        <span class="info-value">' . $data['date'] . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value">' . $data['payment_method'] . '</span>
                    </div>
                </div>
                
                <div class="info-section">
                    <div class="info-title">Student Information</div>
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value">' . $data['student_name'] . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Registration No:</span>
                        <span class="info-value">' . $data['registration_number'] . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Program:</span>
                        <span class="info-value">' . $data['program'] . '</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Year:</span>
                        <span class="info-value">' . $data['year'] . '</span>
                    </div>
                </div>
            </div>
            
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount (UGX)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>' . $data['description'] . '</td>
                        <td>' . number_format($data['amount'], 2) . '</td>
                        <td><span class="official-stamp">PAID</span></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="2"><strong>Total Amount:</strong></td>
                        <td><strong>UGX ' . number_format($data['amount'], 2) . '</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="receipt-footer">
            <div class="signature-line">
                <p><strong>Generated by:</strong> ' . $data['generated_by'] . '</p>
                <p><strong>Generated on:</strong> ' . date('Y-m-d H:i:s') . '</p>
                <p><em>This is an electronically generated receipt and is valid without signature.</em></p>
            </div>
        </div>
    </div>
</body>
</html>';
}
?>
