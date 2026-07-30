<?php
require_once __DIR__ . '/config/database.php';

class URAReporting {

    public static function getStaffDB() {
        return getStaffConnection();
    }

    public static function generateVATReport($month, $year) {
        $conn = self::getStaffDB();
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));
        $stmt = $conn->prepare("SELECT COALESCE(SUM(net_vat),0) total_vat, COUNT(*) cnt FROM bursar_vat_reports WHERE period_start>=? AND period_end<=? AND status='draft'");
        $stmt->bind_param("ss", $start, $end);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result()->fetch_assoc();
        return [
            'reporting_period' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'TIN' => '1012345678',
            'transaction_count' => $result['cnt'] ?? 0,
            'gross_amount' => $result['total_vat'] ?? 0,
            'vat_amount' => ($result['total_vat'] ?? 0) * 0.18,
            'net_amount' => ($result['total_vat'] ?? 0) * 0.82,
            'report_type' => 'VAT Return',
            'generated_date' => date('Y-m-d H:i:s')
        ];
    }

    public static function generateWithholdingTaxReport($month, $year) {
        $conn = self::getStaffDB();
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));
        $stmt = $conn->prepare("SELECT COUNT(*) cnt, COALESCE(SUM(gross_amount),0) gross, COALESCE(SUM(wht_amount),0) wht FROM bursar_withholding_tax WHERE tax_date>=? AND tax_date<=? AND status='active'");
        $stmt->bind_param("ss", $start, $end);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $result = $stmt->get_result()->fetch_assoc();
        return [
            'reporting_period' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'TIN' => '1012345678',
            'employee_count' => $result['cnt'] ?? 0,
            'paye_withheld' => 0,
            'nssf_withheld' => 0,
            'gross_amount' => $result['gross'] ?? 0,
            'wht_withheld' => $result['wht'] ?? 0,
            'total_withheld' => $result['wht'] ?? 0,
            'report_type' => 'Withholding Tax Report',
            'generated_date' => date('Y-m-d H:i:s')
        ];
    }

    public static function generateAnnualReport($year) {
        $conn = self::getStaffDB();
        $report = [
            'reporting_period' => "Year Ended December $year",
            'TIN' => '1012345678',
            'institution' => 'Iganga School of Nursing and Midwifery',
        ];
        $s1 = $conn->prepare("SELECT COALESCE(SUM(net_vat),0) total_vat FROM bursar_vat_reports WHERE YEAR(period_start)=?");
        $s1->bind_param('i', $year); if (!$s1->execute()) { error_log('$s1 execute failed: ' . ($s1->error ?? 'unknown')); };
        $report['total_vat_collected'] = $s1->get_result()->fetch_assoc()['total_vat'] ?? 0;
        $s2 = $conn->prepare("SELECT COALESCE(SUM(wht_amount),0) total_wht FROM bursar_withholding_tax WHERE YEAR(tax_date)=?");
        $s2->bind_param('i', $year); if (!$s2->execute()) { error_log('$s2 execute failed: ' . ($s2->error ?? 'unknown')); };
        $report['total_wht_collected'] = $s2->get_result()->fetch_assoc()['total_wht'] ?? 0;
        $report['total_revenue'] = $report['total_vat_collected'] + $report['total_wht_collected'];
        $report['staff_costs'] = 0;
        $report['total_expenses'] = 0;
        return $report;
    }

    public static function generateCSVExport($report_type, $month, $year) {
        switch ($report_type) {
            case 'vat':
                $report = self::generateVATReport($month, $year);
                $csv = "UGA-URA-VAT-RETURN\n";
                $csv .= "TIN,Period,Gross Supply (UGX),Output VAT (UGX),Input VAT (UGX),Net VAT Due (UGX),Transaction Count\n";
                $csv .= "{$report['TIN']},{$report['reporting_period']}," .
                        number_format($report['gross_amount'], 2) . "," .
                        number_format($report['vat_amount'], 2) . ",0.00," .
                        number_format($report['gross_amount'], 2) . "," .
                        $report['transaction_count'] . "\n";
                break;
            case 'wht':
                $report = self::generateWithholdingTaxReport($month, $year);
                $csv = "UGA-URA-WHT-RETURN\n";
                $csv .= "TIN,Period,Total Gross Payments (UGX),WHT Rate (%),WHT Withheld (UGX),Total Withheld (UGX)\n";
                $csv .= "{$report['TIN']},{$report['reporting_period']}," .
                        number_format($report['gross_amount'], 2) . ",6.00," .
                        number_format($report['wht_withheld'], 2) . "," .
                        number_format($report['total_withheld'], 2) . "\n";
                break;
            case 'annual':
                $report = self::generateAnnualReport($year);
                $csv = "UGA-URA-ANNUAL-RETURN\n";
                $csv .= "TIN,,Institution,Description,Amount (UGX)\n";
                $csv .= "{$report['TIN']},,{$report['institution']},Total Revenue," . number_format($report['total_revenue'], 2) . "\n";
                $csv .= "{$report['TIN']},,{$report['institution']},VAT Collected," . number_format($report['total_vat_collected'], 2) . "\n";
                $csv .= "{$report['TIN']},,{$report['institution']},WHT Collected," . number_format($report['total_wht_collected'], 2) . "\n";
                $csv .= "{$report['TIN']},,{$report['institution']},Total Tax Remitted," . number_format($report['total_vat_collected'] + $report['total_wht_collected'], 2) . "\n";
                break;
            default:
                return "Invalid report type\n";
        }
        return $csv;
    }
}

if (isset($_GET['generate'])) {
    $type = $_GET['type'] ?? 'vat';
    $month = (int)($_GET['month'] ?? date('n'));
    $year = (int)($_GET['year'] ?? date('Y'));
    $prefix = 'URA';
if ($type === 'vat') $prefix = 'URA_VAT';
elseif ($type === 'wht') $prefix = 'URA_WHT';
elseif ($type === 'annual') $prefix = 'URA_ANNUAL';
    $filename = $prefix . '_' . $year . str_pad($month,2,'0',STR_PAD_LEFT) . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo URAReporting::generateCSVExport($type, $month, $year);
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>URA Reporting Portal - Iganga School of Nursing</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f0f2f5;font-family:'Segoe UI',sans-serif;padding:30px}
.card{border:none;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);margin-bottom:20px}
.card-header{border-radius:12px 12px 0 0!important;font-weight:600}
</style>
</head>
<body>
<div class="container">
<div class="d-flex justify-content-between align-items-center mb-4">
<h2><img src="images/ura.png" alt="URA" style="height:32px;width:auto;margin-right:10px;vertical-align:middle" onerror="this.style.display='none'"> URA Tax Reporting Portal</h2>
<div class="d-flex gap-2">
  <a href="?generate=1&type=vat" class="btn btn-sm btn-outline-success"><i class="fas fa-download me-1"></i>VAT CSV</a>
  <a href="?generate=1&type=wht" class="btn btn-sm btn-outline-info"><i class="fas fa-download me-1"></i>WHT CSV</a>
  <a href="dashboards/school-bursar.php?page=ura" class="btn btn-outline-secondary btn-sm">Back to Bursar</a>
</div>
</div>
<div class="row">
<div class="col-md-4">
<div class="card"><div class="card-header bg-primary text-white"><img src="images/ura.png" alt="" style="height:18px;width:auto;margin-right:6px;filter:brightness(0) invert(1)" onerror="this.style.display='none'">VAT Return</div>
<div class="card-body">
<form method="get"><input type="hidden" name="generate" value="1"><input type="hidden" name="type" value="vat">
<div class="mb-2"><label class="form-label">Month</label><select name="month" class="form-select"><?php for($m=1;$m<=12;$m++): ?><option value="<?=$m?>" <?=$m==date('n')?'selected':''?>><?=date('F',mktime(0,0,0,$m,1))?></option><?php endfor; ?></select></div>
<div class="mb-2"><label class="form-label">Year</label><select name="year" class="form-select"><?php for($y=date('Y');$y>=2024;$y--): ?><option value="<?=$y?>" <?=$y==date('Y')?'selected':''?>><?=$y?></option><?php endfor; ?></select></div>
<button class="btn btn-primary w-100">Download VAT CSV</button></form>
</div></div>
</div>
<div class="col-md-4">
<div class="card"><div class="card-header bg-success text-white"><img src="images/ura.png" alt="" style="height:18px;width:auto;margin-right:6px;filter:brightness(0) invert(1)" onerror="this.style.display='none'">Withholding Tax</div>
<div class="card-body">
<form method="get"><input type="hidden" name="generate" value="1"><input type="hidden" name="type" value="wht">
<div class="mb-2"><label class="form-label">Month</label><select name="month" class="form-select"><?php for($m=1;$m<=12;$m++): ?><option value="<?=$m?>" <?=$m==date('n')?'selected':''?>><?=date('F',mktime(0,0,0,$m,1))?></option><?php endfor; ?></select></div>
<div class="mb-2"><label class="form-label">Year</label><select name="year" class="form-select"><?php for($y=date('Y');$y>=2024;$y--): ?><option value="<?=$y?>" <?=$y==date('Y')?'selected':''?>><?=$y?></option><?php endfor; ?></select></div>
<button class="btn btn-success w-100">Download WHT CSV</button></form>
</div></div>
</div>
<div class="col-md-4">
<div class="card"><div class="card-header bg-warning text-dark"><img src="images/ura.png" alt="" style="height:18px;width:auto;margin-right:6px" onerror="this.style.display='none'">Annual Report</div>
<div class="card-body">
<form method="get"><input type="hidden" name="generate" value="1"><input type="hidden" name="type" value="annual">
<div class="mb-2"><label class="form-label">Year</label><select name="year" class="form-select"><?php for($y=date('Y');$y>=2024;$y--): ?><option value="<?=$y?>" <?=$y==date('Y')?'selected':''?>><?=$y?></option><?php endfor; ?></select></div>
<button class="btn btn-warning w-100">Download Annual CSV</button></form>
</div></div>
</div>
</div>
<div class="card"><div class="card-header bg-info text-white">Recent Tax Records</div>
<div class="card-body">
<?php
$staffConn = URAReporting::getStaffDB();
$all = [];
if ($staffConn) {
    $r1 = $staffConn->query("SELECT 'VAT' type, period_start date, net_vat amount, status FROM bursar_vat_reports ORDER BY period_start DESC LIMIT 10");
    $r2 = $staffConn->query("SELECT 'WHT' type, tax_date date, wht_amount amount, status FROM bursar_withholding_tax ORDER BY tax_date DESC LIMIT 10");
    if ($r1) while ($row = $r1->fetch_assoc()) $all[] = $row;
    if ($r2) while ($row = $r2->fetch_assoc()) $all[] = $row;
    usort($all, fn($a,$b) => strcmp($b['date'], $a['date']));
}
?>
<table class="table table-sm">
<thead><tr><th>Type</th><th>Date/Period</th><th>Amount (UGX)</th><th>Status</th></tr></thead>
<tbody><?php foreach (array_slice($all, 0, 15) as $row): ?><tr>
<td><span class="badge bg-<?=$row['type']==='VAT'?'primary':'success'?>"><?=htmlspecialchars($row['type'])?></span></td>
<td><?=htmlspecialchars($row['date'])?></td>
<td><?=number_format($row['amount']??0)?></td>
<td><?=htmlspecialchars($row['status']??'-')?></td>
</tr><?php endforeach; if (empty($all)): ?><tr><td colspan="4" class="text-center text-muted">No tax records found.</td></tr><?php endif; ?></tbody>
</table>
</div></div>
</div>
</body>
</html>