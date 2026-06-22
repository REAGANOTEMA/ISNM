<?php
/**
 * Lightweight XLSX reader — pure PHP, no extensions required.
 * Parses the ZIP structure manually and reads shared strings + sheet data.
 */
class SimpleXLSXReader {
    private $sheets = [];
    private $sharedStrings = [];
    
    public function __construct($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }
        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new Exception("Cannot read file: $filePath");
        }
        $this->parseZip($data);
    }
    
    private function parseZip($data) {
        $len = strlen($data);
        // Find End of Central Directory Record
        $eocd = $this->findEOCD($data, $len);
        if ($eocd === false) throw new Exception("Invalid XLSX (no EOCD)");
        
        $cdOffset = $this->readUint($data, $eocd + 16, 4);
        $cdEntries = $this->readUint($data, $eocd + 10, 2);
        $cdSize = $this->readUint($data, $eocd + 12, 4);
        
        // Parse central directory
        $files = [];
        $pos = $cdOffset;
        for ($i = 0; $i < $cdEntries; $i++) {
            if ($pos + 46 > $len) break;
            $compression = $this->readUint($data, $pos + 10, 2);
            $compSize = $this->readUint($data, $pos + 20, 4);
            $uncompSize = $this->readUint($data, $pos + 24, 4);
            $nameLen = $this->readUint($data, $pos + 28, 2);
            $extraLen = $this->readUint($data, $pos + 30, 2);
            $commentLen = $this->readUint($data, $pos + 32, 2);
            $localOffset = $this->readUint($data, $pos + 42, 4);
            $name = substr($data, $pos + 46, $nameLen);
            $files[$name] = [
                'compression' => $compression,
                'compSize' => $compSize,
                'uncompSize' => $uncompSize,
                'localOffset' => $localOffset,
            ];
            $pos += 46 + $nameLen + $extraLen + $commentLen;
        }
        
        // Extract and parse relevant XML files
        foreach ($files as $name => $info) {
            $content = $this->extractFile($data, $info);
            if ($content === false) continue;
            
            if ($name === 'xl/sharedStrings.xml') {
                $this->parseSharedStrings($content);
            } elseif (preg_match('#^xl/worksheets/sheet(\d+)\.xml$#', $name, $m)) {
                $this->parseSheet($content, (int)$m[1]);
            }
        }
    }
    
    private function findEOCD($data, $len) {
        $sig = "\x50\x4b\x05\x06";
        for ($pos = $len - 22; $pos >= 0; $pos--) {
            if (substr($data, $pos, 4) === $sig) return $pos;
            if ($pos > 0 && $pos % 1000 === 0 && $len - $pos > 66000) break;
        }
        return false;
    }
    
    private function readUint($data, $pos, $bytes) {
        $val = 0;
        for ($i = 0; $i < $bytes; $i++) {
            $val |= (ord($data[$pos + $i] ?? "\x00")) << ($i * 8);
        }
        return $val;
    }
    
    private function extractFile($data, $info) {
        $pos = $info['localOffset'];
        $sig = substr($data, $pos, 4);
        if ($sig !== "\x50\x4b\x03\x04") return false;
        $nameLen = $this->readUint($data, $pos + 26, 2);
        $extraLen = $this->readUint($data, $pos + 28, 2);
        $dataStart = $pos + 30 + $nameLen + $extraLen;
        
        if ($info['compression'] === 0) {
            // Stored (no compression)
            return substr($data, $dataStart, $info['uncompSize']);
        } elseif ($info['compression'] === 8) {
            // Deflated
            $compressed = substr($data, $dataStart, $info['compSize']);
            return @gzuncompress($compressed);
        }
        return false;
    }
    
    private function parseSharedStrings($xml) {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $sis = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'si');
        foreach ($sis as $si) {
            $text = '';
            foreach ($si->childNodes as $child) {
                if ($child->nodeName === 't') {
                    $text .= $child->textContent;
                } elseif ($child->nodeName === 'r') {
                    foreach ($child->childNodes as $run) {
                        if ($run->nodeName === 't') $text .= $run->textContent;
                    }
                }
            }
            $this->sharedStrings[] = $text;
        }
    }
    
    private function parseSheet($xml, $sheetIndex) {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $rows = [];
        $sheetData = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'sheetData')->item(0);
        if (!$sheetData) return;
        
        $rowElements = $sheetData->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'row');
        foreach ($rowElements as $rowEl) {
            $cells = [];
            $cellElements = $rowEl->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'c');
            foreach ($cellElements as $cell) {
                $ref = $cell->getAttribute('r');
                $col = preg_replace('/[0-9]/', '', $ref);
                $type = $cell->getAttribute('t');
                $value = '';
                $v = $cell->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'v');
                if ($v->length > 0) {
                    $value = $v->item(0)->textContent;
                    if ($type === 's') {
                        $idx = (int)$value;
                        $value = $this->sharedStrings[$idx] ?? $value;
                    }
                }
                $cells[$col] = $value;
            }
            $rows[] = $cells;
        }
        $this->sheets[$sheetIndex] = $rows;
    }
    
    public function getSheet($index = 1) {
        return $this->sheets[$index] ?? [];
    }
    
    public function getRows($sheetIndex = 1) {
        $sheet = $this->getSheet($sheetIndex);
        if (empty($sheet)) return [];
        
        // Use first row as headers
        $headers = [];
        $firstRow = $sheet[0];
        $colOrder = array_keys($firstRow);
        foreach ($colOrder as $col) {
            $headers[] = $firstRow[$col];
        }
        
        $result = [];
        for ($i = 1; $i < count($sheet); $i++) {
            $row = $sheet[$i];
            $entry = [];
            foreach ($colOrder as $j => $col) {
                $entry[$headers[$j]] = $row[$col] ?? '';
            }
            $result[] = $entry;
        }
        return $result;
    }
}
