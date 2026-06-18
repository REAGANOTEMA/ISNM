<?php
/**
 * SimpleXlsxReader — reads .xlsx files without external libraries.
 * Supports ZipArchive when available and a PowerShell extraction fallback on Windows.
 */
class SimpleXlsxReader {

    public static function read(string $file): array {
        if (!file_exists($file)) {
            return [];
        }

        $tempDir = null;
        $sheetsXml = [];

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($file) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (strpos($name, 'xl/worksheets/sheet') === 0 && substr($name, -4) === '.xml') {
                        $sheetsXml[] = $zip->getFromName($name);
                    }
                }
                $sharedStrings = self::readSharedStringsFromXml($zip->getFromName('xl/sharedStrings.xml'));
                $zip->close();
                return self::parseSheets($sheetsXml, $sharedStrings);
            }
        }

        $pythonRows = self::readWithPython($file);
        if ($pythonRows !== null) {
            return $pythonRows;
        }

        $tempDir = self::extractWithPowerShell($file);
        if ($tempDir === null) {
            return [];
        }

        try {
            $sharedStrings = [];
            $sharedStringsFile = $tempDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'sharedStrings.xml';
            if (is_file($sharedStringsFile)) {
                $sharedStrings = self::readSharedStringsFromXml(file_get_contents($sharedStringsFile));
            }

            $sheetFiles = glob($tempDir . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'worksheets' . DIRECTORY_SEPARATOR . 'sheet*.xml');
            natcasesort($sheetFiles);
            foreach ($sheetFiles as $sheetFile) {
                $sheetsXml[] = file_get_contents($sheetFile);
            }

            return self::parseSheets($sheetsXml, $sharedStrings);
        } finally {
            self::removeDirectory($tempDir);
        }
    }

    private static function parseSheets(array $sheetsXml, array $sharedStrings): array {
        $rows = [];

        foreach ($sheetsXml as $sheetXml) {
            if (!$sheetXml) {
                continue;
            }

            $sheet = simplexml_load_string($sheetXml);
            if (!$sheet) {
                continue;
            }

            foreach ($sheet->sheetData->row ?? [] as $row) {
                $rowData = [];
                $maxCol = 0;

                foreach ($row->c as $cell) {
                    $colLetter = preg_replace('/[0-9]/', '', (string) $cell['r']);
                    $colIdx = self::colToIndex($colLetter);
                    $maxCol = max($maxCol, $colIdx);
                    $rowData[$colIdx] = self::cellValue($cell, $sharedStrings);
                }

                $full = [];
                for ($i = 0; $i <= $maxCol; $i++) {
                    $full[] = trim((string) ($rowData[$i] ?? ''));
                }

                if (count(array_filter($full, fn($v) => trim($v) !== '')) > 0) {
                    $rows[] = $full;
                }
            }

            $rows[] = [];
        }

        return array_values(array_filter($rows, fn($row) => count(array_filter($row, fn($v) => trim($v) !== '')) > 0));
    }

    private static function readSharedStringsFromXml($xml): array {
        if (!$xml) {
            return [];
        }

        $strings = [];
        $ss = simplexml_load_string($xml);
        if (!$ss) {
            return [];
        }

        foreach ($ss->si as $si) {
            $val = '';
            foreach ($si->r as $r) {
                $val .= (string) ($r->t ?? '');
            }
            if ($val === '') {
                $val = (string) ($si->t ?? '');
            }
            $strings[] = $val;
        }

        return $strings;
    }

    private static function cellValue($cell, array $sharedStrings): string {
        $type = (string) ($cell['t'] ?? '');
        $val = (string) ($cell->v ?? '');

        if ($type === 's') {
            return $sharedStrings[(int) $val] ?? '';
        }

        if ($type === 'inlineStr') {
            $inline = '';
            foreach ($cell->is->t ?? [] as $t) {
                $inline .= (string) $t;
            }
            return $inline;
        }

        $formula = (string) ($cell->f ?? '');
        if ($formula !== '' && $val === '') {
            foreach ($cell->v ?? [] as $v) {
                return (string) $v;
            }
        }

        return $val;
    }

    private static function readWithPython(string $file): ?array {
        $python = getenv('PYTHON_BIN');
        $candidates = [];
        if ($python) {
            $candidates[] = $python;
        }
        $candidates[] = 'C:\\Python314\\python.exe';
        $candidates[] = 'python';

        $script = <<<'PY'
import json, os, re, sys, zipfile
import xml.etree.ElementTree as ET

path = sys.argv[1]
output_path = sys.argv[2]
ns = {
    'm': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
    'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
}

def col_to_idx(col):
    idx = 0
    for ch in col.upper():
        idx = idx * 26 + (ord(ch) - 64)
    return idx - 1

def cell_text(cell):
    texts = []
    for t in cell.findall('.//m:t', ns):
        if t.text:
            texts.append(t.text)
    return ''.join(texts).strip()

def value(cell, strings):
    t = cell.attrib.get('t', '')
    v = cell.find('m:v', ns)
    raw = v.text if v is not None and v.text is not None else ''
    if t == 's':
        try:
            return strings[int(raw)].strip()
        except Exception:
            return ''
    if t == 'inlineStr':
        return cell_text(cell)
    if t == 'b':
        return '1' if raw == '1' else '0'
    return raw.strip()

try:
    with zipfile.ZipFile(path) as z:
        strings = []
        ss = z.read('xl/sharedStrings.xml') if 'xl/sharedStrings.xml' in z.namelist() else b''
        if ss:
            root = ET.fromstring(ss)
            for si in root.findall('m:si', ns):
                strings.append(cell_text(si))
        sheets = sorted([n for n in z.namelist() if n.startswith('xl/worksheets/sheet') and n.endswith('.xml')])
        rows = []
        for name in sheets:
            root = ET.fromstring(z.read(name))
            for row in root.findall('.//m:sheetData/m:row', ns):
                data = {}
                for c in row.findall('m:c', ns):
                    ref = c.attrib.get('r', '')
                    col = re.sub(r'\d+', '', ref)
                    if not col:
                        continue
                    data[col_to_idx(col)] = value(c, strings)
                if not data:
                    continue
                full = [data.get(i, '') for i in range(max(data.keys()) + 1)]
                if any(full):
                    rows.append(full)
            rows.append([])
    print(json.dumps(rows, ensure_ascii=False))
    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(json.dumps(rows, ensure_ascii=False))
except Exception as exc:
    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(json.dumps([]))
    sys.exit(0)
PY;

        $scriptFile = tempnam(sys_get_temp_dir(), 'xlsx_reader_') . '.py';
        $outputFile = tempnam(sys_get_temp_dir(), 'xlsx_rows_') . '.json';
        if ($scriptFile === false || $outputFile === false || file_put_contents($scriptFile, $script) === false) {
            return null;
        }

        set_time_limit(0);
        foreach ($candidates as $candidate) {
            $command = $candidate . ' ' . escapeshellarg($scriptFile) . ' ' . escapeshellarg($file) . ' ' . escapeshellarg($outputFile) . ' 2>&1';
            exec($command, $output, $code);
            if (is_file($outputFile)) {
                $json = trim((string) file_get_contents($outputFile));
                @unlink($scriptFile);
                @unlink($outputFile);
                $decoded = json_decode($json, true);
                return is_array($decoded) ? $decoded : [];
            }
            $output = [];
        }

        @unlink($scriptFile);
        @unlink($outputFile);
        return null;
    }

    private static function extractWithPowerShell(string $file): ?string {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $base = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($base === false) {
            return null;
        }
        unlink($base);
        $tempDir = $base . '_extract';
        if (!mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            return null;
        }

        $ps = 'C:\\WINDOWS\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
        $command = 'Expand-Archive -LiteralPath ' . self::psQuote($file) . ' -DestinationPath ' . self::psQuote($tempDir) . ' -Force';
        exec(self::psQuote($ps) . ' -NoProfile -ExecutionPolicy Bypass -Command ' . self::psQuote($command) . ' 2>&1', $output, $code);

        if ($code !== 0 || !is_dir($tempDir . DIRECTORY_SEPARATOR . 'xl')) {
            self::removeDirectory($tempDir);
            return null;
        }

        return $tempDir;
    }

    private static function psQuote(string $value): string {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    private static function removeDirectory(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    private static function colToIndex(string $col): int {
        $col = strtoupper($col);
        $idx = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $idx = $idx * 26 + (ord($col[$i]) - 64);
        }
        return $idx - 1;
    }
}
