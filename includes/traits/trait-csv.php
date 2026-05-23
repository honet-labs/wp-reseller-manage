<?php
if (!defined('ABSPATH')) { exit; }

trait WRPM_Trait_CSV {
    private function wrpm_csv_send($filename, $rows) {
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel
        fprintf($out, "\xEF\xBB\xBF");
        foreach ($rows as $r) {
            fputcsv($out, $r);
        }
        fclose($out);
        exit;
    }

    private function wrpm_parse_csv_upload($file_key) {
        if (empty($_FILES[$file_key]) || empty($_FILES[$file_key]['tmp_name'])) {
            return ['ok' => false, 'error' => 'No file'];
        }
        $tmp = $_FILES[$file_key]['tmp_name'];
        $fh = fopen($tmp, 'r');
        if (!$fh) return ['ok' => false, 'error' => 'Cannot read file'];

        $first = fgets($fh);
        if ($first === false) { fclose($fh); return ['ok' => false, 'error' => 'Empty CSV']; }
        $comma = substr_count($first, ',');
        $semi = substr_count($first, ';');
        $delim = ($semi > $comma) ? ';' : ',';
        rewind($fh);

        $header = fgetcsv($fh, 0, $delim);
        if (!$header) { fclose($fh); return ['ok' => false, 'error' => 'Missing header']; }
        $rows = [];
        while (($row = fgetcsv($fh, 0, $delim)) !== false) {
            if (!$row || (count($row) === 1 && trim((string)$row[0]) === '')) continue;
            $rows[] = $row;
        }
        fclose($fh);
        return ['ok' => true, 'header' => $header, 'rows' => $rows];
    }
}
