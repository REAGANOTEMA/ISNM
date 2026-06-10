<?php
/**
 * SimpleXlsxReader — reads .xlsx files without any external library
 * Uses PHP's ZipArchive + SimpleXML (both built into PHP)
 */
class SimpleXlsxReader {

    public static function read(string $file): array {
        if (!file_exists($file) || !class_exists('ZipArchive')) return [];

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) return [];

        // Read shared strings
        $strings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $ss = simplexml_load_string($ssXml);
            if ($ss) {
                foreach ($ss->si as $si) {
                    // Concatenate all <t> nodes (handles rich text)
                    $val = '';
                    foreach ($si->r as $r) { $val .= (string)($r->t ?? ''); }
                    if ($val === '') $val = (string)($si->t ?? '');
                    $strings[] = $val;
                }
            }
        }

        // Read first sheet
        $rows = [];
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            // Try sheet index from workbook
            $wbXml = $zip->getFromName('xl/workbook.xml');
            if ($wbXml) {
                $wb = simplexml_load_string($wbXml);
                $ns = $wb->getNamespaces(true);
                $r = isset($ns['r']) ? $wb->sheets->sheet[0]->attributes($ns['r']) : null;
                if ($r) {
                    $rid = (string)$r['id'];
                    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
                    if ($relsXml) {
                        $rels = simplexml_load_string($relsXml);
                        foreach ($rels->Relationship as $rel) {
                            if ((string)$rel['Id'] === $rid) {
                                $target = 'xl/' . ltrim((string)$rel['Target'], '/');
                                $sheetXml = $zip->getFromName($target);
                                break;
                            }
                        }
                    }
                }
            }
        }

        $zip->close();

        if (!$sheetXml) return [];

        $sheet = simplexml_load_string($sheetXml);
        if (!$sheet) return [];

        foreach ($sheet->sheetData->row ?? [] as $row) {
            $rowData = [];
            $maxCol = 0;
            foreach ($row->c as $cell) {
                $colLetter = preg_replace('/[0-9]/', '', (string)$cell['r']);
                $colIdx = self::colToIndex($colLetter);
                $maxCol = max($maxCol, $colIdx);
                $type = (string)($cell['t'] ?? '');
                $val  = (string)($cell->v ?? '');
                if ($type === 's') $val = $strings[(int)$val] ?? '';
                elseif ($type === 'inlineStr') $val = (string)($cell->is->t ?? '');
                $rowData[$colIdx] = trim($val);
            }
            // Fill gaps
            $full = [];
            for ($i = 0; $i <= $maxCol; $i++) $full[] = $rowData[$i] ?? '';
            $rows[] = $full;
        }

        return $rows;
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
