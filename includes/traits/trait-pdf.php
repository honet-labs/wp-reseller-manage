<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Minimal PDF generator (pure-PHP, no external dependency).
 * Adapted from your WP SIMKU plugin so UI/behavior stays consistent.
 */
trait WRPM_Trait_PDF {
    private function pdf_hex_to_rgb($hex) {
        $hex = str_replace('#', '', (string)$hex);
        if (strlen($hex) === 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1)) / 255;
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1)) / 255;
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1)) / 255;
        } else {
            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;
        }
        return [$r, $g, $b];
    }
    /**
     * PDF Type1 fonts (Helvetica) in this minimal generator expect WinAnsi-ish bytes.
     * This helper normalizes common Unicode characters (spaces/dashes) and converts
     * UTF-8 to Windows-1252 when possible to avoid mojibake like "â¦" or "â".
     */
    private function pdf_sanitize_text($t) {
        $t = (string)$t;

        // Decode any entities that might be stored.
        $t = html_entity_decode($t, ENT_QUOTES, 'UTF-8');

        // Normalize common unicode spaces to regular spaces.
        $t = str_replace([
            "\xC2\xA0", // nbsp
            "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83",
            "\xE2\x80\x84", "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87",
            "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A", // en/em/thin spaces
            "\xE2\x80\x8B", // zero-width space
        ], ' ', $t);

        // Normalize common dashes / ellipsis.
        $t = str_replace([
            "\xE2\x80\x93", // en dash
            "\xE2\x80\x94", // em dash
            "\xE2\x88\x92", // minus
        ], '-', $t);
        $t = str_replace("\xE2\x80\xA6", '...', $t); // ellipsis

        // Collapse whitespace.
        $t = preg_replace('/\s+/u', ' ', $t);
        $t = trim((string)$t);

        // Convert to Windows-1252 (WinAnsi compatible) if possible.
        if (function_exists('iconv')) {
            $conv = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $t);
            if ($conv !== false && $conv !== '') {
                $t = $conv;
            }
        } else {
            // Fallback: strip non-ASCII.
            $t = preg_replace('/[^\x20-\x7E]/', '', $t);
        }

        return (string)$t;
    }

    /** Word-wrap helper (UTF-8 aware via pdf_strlen/pdf_substr). */
    private function pdf_wrap_lines($text, $max_chars_per_line) {
        $text = (string)$text;
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim((string)$text);
        if ($text === '') return [''];

        $max = max(1, (int)$max_chars_per_line);
        $words = preg_split('/\s+/u', $text);
        $lines = [];
        $line = '';
        foreach ((array)$words as $w) {
            $w = (string)$w;
            if ($w === '') continue;
            if ($line === '') {
                if ($this->pdf_strlen($w) <= $max) {
                    $line = $w;
                } else {
                    // Split long word.
                    $pos = 0;
                    $len = $this->pdf_strlen($w);
                    while ($pos < $len) {
                        $lines[] = $this->pdf_substr($w, $pos, $max);
                        $pos += $max;
                    }
                    $line = '';
                }
            } else {
                $candidate = $line . ' ' . $w;
                if ($this->pdf_strlen($candidate) <= $max) {
                    $line = $candidate;
                } else {
                    $lines[] = $line;
                    $line = $w;
                }
            }
        }
        if ($line !== '') $lines[] = $line;
        return $lines;
    }

    private function pdf_escape_text($t) {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string)$t);
    }

    private function pdf_strlen($text) {
        if (function_exists('mb_strlen')) return (int)mb_strlen($text, 'UTF-8');
        if ($text === '') return 0;
        return (int)preg_match_all('/./us', $text, $m);
    }

    private function pdf_substr($text, $start, $len) {
        if (function_exists('mb_substr')) return (string)mb_substr($text, $start, $len, 'UTF-8');
        if ($text === '' || $len <= 0) return '';
        preg_match_all('/./us', $text, $m);
        $chars = $m[0] ?? [];
        return implode('', array_slice($chars, $start, $len));
    }

    private function simple_text_pdf($text) {
        // Minimal PDF (single page) with Helvetica + Helvetica-Bold.
        $lines = preg_split("/\r\n|\n|\r/", (string)$text);
        $maxw = 92;
        $wrapped = [];
        foreach ((array)$lines as $l) {
            $l = (string)$l;
            if ($l === '') { $wrapped[] = ''; continue; }
            while ($this->pdf_strlen($l) > $maxw) {
                $wrapped[] = $this->pdf_substr($l, 0, $maxw);
                $l = $this->pdf_substr($l, $maxw, max(0, $this->pdf_strlen($l) - $maxw));
            }
            $wrapped[] = $l;
        }

        $y = 810;
        $content = "BT\n";
        foreach ($wrapped as $idx => $l) {
            $l = $this->pdf_escape_text($l);
            if ($idx === 0) {
                $content .= "/F2 16 Tf\n";
            } else {
                $content .= "/F1 10 Tf\n";
            }
            $content .= sprintf("1 0 0 1 50 %d Tm (%s) Tj\n", $y, $l);
            $y -= 14;
            if ($y < 40) break;
        }
        $content .= "ET\n";
        $len = strlen($content);

        $objects = [];
        $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
        $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
        $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 6 0 R >> >> /Contents 5 0 R >> endobj\n";
        $objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
        $objects[] = "5 0 obj << /Length {$len} >> stream\n{$content}endstream endobj\n";
        $objects[] = "6 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj\n";

        $pdf = "%PDF-1.4\n";
        $xref = [];
        $offset = strlen($pdf);
        foreach ($objects as $obj) {
            $xref[] = $offset;
            $pdf .= $obj;
            $offset = strlen($pdf);
        }
        $xref_pos = $offset;
        $pdf .= "xref\n0 ".(count($xref)+1)."\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($xref as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= "trailer << /Size ".(count($xref)+1)." /Root 1 0 R >>\nstartxref\n{$xref_pos}\n%%EOF";
        return $pdf;
    }

    private function pdf_build_document_from_stream($content_stream) {
        // Minimal PDF (single page) with Helvetica + Helvetica-Bold.
        $content = (string)$content_stream;
        $len = strlen($content);

        $objects = [];
        $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
        $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
        $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 6 0 R >> >> /Contents 5 0 R >> endobj\n";
        $objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
        $objects[] = "5 0 obj << /Length {$len} >> stream\n{$content}endstream endobj\n";
        $objects[] = "6 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj\n";

        $pdf = "%PDF-1.4\n";
        $xref = [];
        $offset = strlen($pdf);
        foreach ($objects as $obj) {
            $xref[] = $offset;
            $pdf .= $obj;
            $offset = strlen($pdf);
        }
        $xref_pos = $offset;
        $pdf .= "xref\n0 " . (count($xref) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($xref as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= "trailer << /Size " . (count($xref) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref_pos}\n%%EOF";
        return $pdf;
    }

    private function pdf_text_at($x, $y, $text, $bold = false, $size = 10) {
        $font = $bold ? 'F2' : 'F1';
        $t = $this->pdf_escape_text($this->pdf_sanitize_text((string)$text));
        $x = (float)$x; $y = (float)$y; $size = (int)$size;
        return "BT\n/{$font} {$size} Tf\n1 0 0 1 {$x} {$y} Tm\n({$t}) Tj\nET\n";
    }

    private function pdf_trunc($text, $max_chars) {
        $text = (string)$text;
        $max = (int)$max_chars;
        if ($max <= 0) return '';
        if ($this->pdf_strlen($text) <= $max) return $text;
        // Use ASCII dots to avoid mojibake in our minimal PDF generator.
        return $this->pdf_substr($text, 0, max(0, $max - 3)) . '...';
    }

    private function wrpm_build_invoice_pdf_stream($row) {
        $s = $this->wrpm_get_settings();
        $title = (string)($s['pdf_invoice_title'] ?? 'INVOICE');
        if (trim($title) === '') $title = 'INVOICE';

        $id = (string)($row['id'] ?? '');
        $date = wp_date('d/m/Y');

        $customer = (string)($row['customer_name'] ?? '');
        $product = (string)($row['product_label'] ?? '');
        $purchase = (string)($row['start_date'] ?? '');
        $durasi = (string)((int)($row['duration_days'] ?? 0)) . ' hari';
        $expired = (string)($row['expires_at'] ?? '');
        $harga = (string)$this->wrpm_money_idr((float)($row['price'] ?? 0));

        // Layout
        $x0 = 70;
        $maxW = 455; // page width (595) - 2*70 margin
        $y_title = 770;

        $content = "q\n1 w\n0 0 0 RG\n";
        // Title (left aligned)
        $content .= $this->pdf_text_at($x0, $y_title, strtoupper($title), true, 22);

        // Custom primary color
        $p_color = (string)($s['pdf_primary_color'] ?? '#1e293b');
        list($r, $g, $b) = $this->pdf_hex_to_rgb($p_color);

        // Top right company info
        $comp_name = (string)($s['pdf_company_name'] ?? '');
        $comp_addr = (string)($s['pdf_company_address'] ?? '');
        $comp_phone = (string)($s['pdf_company_phone'] ?? '');
        $x_right = $x0 + 240;
        if ($comp_name) {
            $content .= $this->pdf_text_at($x_right, $y_title, $comp_name, true, 12);
            $y_comp = $y_title - 13;
            if ($comp_addr) {
                $content .= $this->pdf_text_at($x_right, $y_comp, $comp_addr, false, 8);
                $y_comp -= 9;
            }
            if ($comp_phone) {
                $content .= $this->pdf_text_at($x_right, $y_comp, 'Telp/WA: ' . $comp_phone, false, 8);
            }
        }

        // Colored accent line
        $content .= sprintf("q\n%.3f %.3f %.3f RG\n2 w\n%d %d m %d %d l S\nQ\n", $r, $g, $b, $x0, $y_title - 45, $x0 + $maxW, $y_title - 45);
        $content .= $this->pdf_text_at($x0, $y_title - 22, 'Invoice ID: ' . $id, false, 10);
        $content .= $this->pdf_text_at($x0, $y_title - 36, 'Tanggal invoice: ' . $date, false, 10);
        $content .= "\n";

        $content .= $this->pdf_text_at($x0, $y_title - 70, 'Details informasi:', true, 11);

        // --- Table (dynamic column width + word wrap to avoid overflow) ---
        $table_top = $y_title - 90;
        $header_h = 18;
        $font_size = 9;
        $line_h = 10;
        $pad = 4;

        // Fixed columns.
        $w_purchase = 70;
        $w_duration = 55;
        $w_expired  = 65;
        $w_price    = 70;

        $remaining = $maxW - ($w_purchase + $w_duration + $w_expired + $w_price);
        if ($remaining < 160) {
            $w_price = max(60, $w_price);
            $remaining = $maxW - ($w_purchase + $w_duration + $w_expired + $w_price);
        }

        // Split remaining width between Customer and Produk based on text length.
        $len_cust = max(1, $this->pdf_strlen($customer));
        $len_prod = max(1, $this->pdf_strlen($product));
        $w_prod = (int)round($remaining * ($len_prod / ($len_prod + $len_cust)));
        $w_prod = max(110, min(170, $w_prod));
        $w_cust = $remaining - $w_prod;
        if ($w_cust < 70) {
            $w_cust = 70;
            $w_prod = max(110, $remaining - $w_cust);
        }

        // Ensure total width == maxW by adjusting Produk width.
        $sum = $w_cust + $w_prod + $w_purchase + $w_duration + $w_expired + $w_price;
        $diff = $maxW - $sum;
        if ($diff !== 0) $w_prod += $diff;

        $cols = [
            ['Customer', (int)$w_cust],
            ['Produk', (int)$w_prod],
            ['Pembelian', (int)$w_purchase],
            ['Durasi', (int)$w_duration],
            ['Expired', (int)$w_expired],
            ['Harga', (int)$w_price],
        ];

        $w = 0;
        foreach ($cols as $c) $w += (int)$c[1];

        // Word wrap for Customer & Produk columns.
        $charW = 0.55 * $font_size; // rough average Helvetica width
        $cust_max = max(1, (int)floor(((int)$cols[0][1] - ($pad * 2)) / $charW));
        $prod_max = max(1, (int)floor(((int)$cols[1][1] - ($pad * 2)) / $charW));
        $cust_lines = $this->pdf_wrap_lines((string)$customer, $cust_max);
        $prod_lines = $this->pdf_wrap_lines((string)$product, $prod_max);
        $max_lines = max(1, count($cust_lines), count($prod_lines));

        $row_h = max(18, ($line_h * $max_lines) + 8);
        $table_h = $header_h + $row_h;
        $table_bottom = $table_top - $table_h;

        // Outer border
        $content .= sprintf("%d %d %d %d re S\n", $x0, $table_bottom, $w, $table_h);
        // Header split
        $y_split = $table_top - $header_h;
        $content .= sprintf("%d %d m %d %d l S\n", $x0, $y_split, $x0 + $w, $y_split);
        // Vertical lines
        $cx = $x0;
        foreach ($cols as $i => $c) {
            $cx += (int)$c[1];
            if ($i < count($cols) - 1) {
                $content .= sprintf("%d %d m %d %d l S\n", $cx, $table_bottom, $cx, $table_top);
            }
        }

        // Header texts
        $tx = $x0 + $pad;
        $ty = $table_top - 13;
        foreach ($cols as $c) {
            $content .= $this->pdf_text_at($tx, $ty, $c[0], true, $font_size);
            $tx += (int)$c[1];
        }

        // Row texts (top aligned)
        $base_y = $table_top - $header_h - 13;
        $tx = $x0 + $pad;

        // Customer (wrapped)
        foreach ($cust_lines as $li => $line) {
            $content .= $this->pdf_text_at($tx, $base_y - ($line_h * $li), $line, false, $font_size);
        }
        $tx += (int)$cols[0][1];

        // Produk (wrapped)
        foreach ($prod_lines as $li => $line) {
            $content .= $this->pdf_text_at($tx, $base_y - ($line_h * $li), $line, false, $font_size);
        }
        $tx += (int)$cols[1][1];

        // Other cells (single line)
        $content .= $this->pdf_text_at($tx, $base_y, $purchase, false, $font_size);
        $tx += (int)$cols[2][1];
        $content .= $this->pdf_text_at($tx, $base_y, $durasi, false, $font_size);
        $tx += (int)$cols[3][1];
        $content .= $this->pdf_text_at($tx, $base_y, $expired, false, $font_size);
        $tx += (int)$cols[4][1];
        $content .= $this->pdf_text_at($tx, $base_y, $harga, false, $font_size);

        $content .= $this->pdf_text_at($x0, $table_bottom - 30, 'Terima kasih,', false, 11);

        // Dynamic Payment details
        $pay_details = (string)($s['pdf_payment_details'] ?? '');
        if ($pay_details) {
            $y_pay = $table_bottom - 55;
            $content .= $this->pdf_text_at($x0, $y_pay, 'INSTRUKSI PEMBAYARAN:', true, 9);
            $pay_lines = preg_split("/\r\n|\n|\r/", $pay_details);
            foreach ($pay_lines as $idx => $line) {
                $content .= $this->pdf_text_at($x0, $y_pay - 12 - ($idx * 10), (string)$line, false, 8);
            }
        }

        $content .= $this->pdf_text_at(360, 28, 'generate report: ' . wp_date('d/m/Y H:i:s'), false, 8);
        $content .= "Q\n";
        return $content;
    }

    public function wrpm_output_monthly_report_pdf($ym) {
        if (!current_user_can(self::CAP_VIEW_REPORTS)) {
            wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        }

        $ym = (string)$ym;
        if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
            wp_die(esc_html__('Invalid month', self::TEXT_DOMAIN));
        }

        $start = $ym . '-01';
        $end = wp_date('Y-m-d', strtotime($start . ' +1 month'));

        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT start_date, customer_name, product_label, duration_days, price
             FROM {$this->tbl_active()}
             WHERE payment_status='paid'
               AND start_date >= %s AND start_date < %s
             ORDER BY start_date ASC",
            $start, $end
        ), ARRAY_A);

        $total = 0;
        foreach ((array)$rows as $r) $total += (float)($r['price'] ?? 0);

        $content = "q\n1 w\n0 0 0 RG\n";
        $x0 = 70; $w = 455;
        $y_title = 770;

        $content .= $this->pdf_text_at($x0 + 150, $y_title, 'Monthly report', true, 20);
        $content .= $this->pdf_text_at($x0, $y_title - 24, '| Date: ' . wp_date('m/Y', strtotime($start)), false, 10);
        $content .= $this->pdf_text_at($x0, $y_title - 38, '| Pendapatan: ' . $this->wrpm_money_idr($total), false, 10);
        $content .= $this->pdf_text_at($x0, $y_title - 70, 'Detail transaction:', false, 10);

        // Table setup
        $table_top = $y_title - 90;
        $header_h = 18;
        $min_rows = 5;
        $line_h = 10; // line height for wrapped text

        // Build rows with dynamic height (word wrap on Produk column).
        $raw = (array)$rows;

        // Columns (dynamic width, total = 455)
        $char_w = 4.6; // rough width per character at font size ~9
        $pad = 14;     // left+right padding buffer

        $max_customer_len = $this->pdf_strlen('Customer');
        $max_durasi_len = $this->pdf_strlen('Durasi (day)');
        $max_harga_len = $this->pdf_strlen('Harga');
        foreach ($raw as $rr) {
            $max_customer_len = max($max_customer_len, $this->pdf_strlen((string)($rr['customer_name'] ?? '')));
            $max_durasi_len = max($max_durasi_len, $this->pdf_strlen((string)($rr['duration_days'] ?? '')));
            if ($rr['price'] !== '' && $rr['price'] !== null) {
                $max_harga_len = max($max_harga_len, $this->pdf_strlen($this->wrpm_money_idr((float)($rr['price'] ?? 0))));
            }
        }

        $w_date = max(70, min(95, (max($this->pdf_strlen('Tanggal'), 10) * $char_w) + $pad));
        $w_customer = max(95, min(155, ($max_customer_len * $char_w) + $pad));
        $w_durasi = max(70, min(110, ($max_durasi_len * $char_w) + $pad));
        $w_harga = max(70, min(120, ($max_harga_len * $char_w) + $pad));

        // Remaining width goes to Produk (with word wrap).
        $w_produk = $w - ($w_date + $w_customer + $w_durasi + $w_harga);
        $min_produk = 130;
        if ($w_produk < $min_produk) {
            $need = $min_produk - $w_produk;
            // shrink customer first
            $reduce = min($need, $w_customer - 90);
            if ($reduce > 0) { $w_customer -= $reduce; $w_produk += $reduce; $need -= $reduce; }
            // then shrink date if still needed
            if ($need > 0) {
                $reduce = min($need, $w_date - 65);
                if ($reduce > 0) { $w_date -= $reduce; $w_produk += $reduce; $need -= $reduce; }
            }
        }

        $cols = [
            ['Tanggal', (int)round($w_date)],
            ['Customer', (int)round($w_customer)],
            ['Produk', (int)round($w_produk)],
            ['Durasi (day)', (int)round($w_durasi)],
            ['Harga', (int)round($w_harga)],
        ];
        // Fix rounding drift so total == $w
        $sumw = 0; foreach ($cols as $cc) { $sumw += (int)$cc[1]; }
        $diff = (int)$w - (int)$sumw;
        if ($diff !== 0) { $cols[2][1] = (int)$cols[2][1] + $diff; }

        // Max chars for wrapping/trunc based on computed widths
        $prod_max_chars = max(15, min(80, (int)floor(((int)$cols[2][1] - 8) / $char_w)));
        $cust_max_chars = max(10, min(40, (int)floor(((int)$cols[1][1] - 8) / $char_w)));
        $dur_max_chars  = max(4,  min(20, (int)floor(((int)$cols[3][1] - 8) / $char_w)));
        $hrg_max_chars  = max(6,  min(24, (int)floor(((int)$cols[4][1] - 8) / $char_w)));

        // Limit rows to those that fit on one page (bottom margin ~ 60).
        $available = ($table_top - 60) - $header_h;
        $data = [];
        $row_heights = [];
        $row_meta = []; // keep wrapped lines per row

        foreach ($raw as $r) {
            $prod_lines = $this->pdf_wrap_lines((string)($r['product_label'] ?? ''), $prod_max_chars);
            $line_count = max(1, count($prod_lines));
            $rh = max(18, ($line_h * $line_count) + 8);

            $next_total = array_sum($row_heights) + $rh;
            if ($next_total > $available) break;

            $data[] = $r;
            $row_heights[] = $rh;
            $row_meta[] = ['product_lines' => $prod_lines, 'line_count' => $line_count];
        }

        // Pad to minimum rows for nicer layout.
        while (count($data) < $min_rows) {
            $data[] = ['start_date'=>'','customer_name'=>'','product_label'=>'','duration_days'=>'','price'=>''];
            $row_heights[] = 18;
            $row_meta[] = ['product_lines' => [''], 'line_count' => 1];
        }

        $table_h = $header_h + array_sum($row_heights);
        $table_bottom = $table_top - $table_h;
        // Columns are computed dynamically above.

        $content .= sprintf("%d %d %d %d re S\n", $x0, $table_bottom, $w, $table_h);
        // Header split line
        $y_split = $table_top - $header_h;
        $content .= sprintf("%d %d m %d %d l S\n", $x0, $y_split, $x0 + $w, $y_split);

        // Row lines
        $yy_cursor = $y_split;
        for ($i = 0; $i < count($data) - 1; $i++) {
            $yy_cursor -= (int)$row_heights[$i];
            $content .= sprintf("%d %d m %d %d l S\n", $x0, $yy_cursor, $x0 + $w, $yy_cursor);
        }

        // Vertical lines
        $cx = $x0;
        foreach ($cols as $i => $c) {
            $cx += (int)$c[1];
            if ($i < count($cols) - 1) {
                $content .= sprintf("%d %d m %d %d l S\n", $cx, $table_bottom, $cx, $table_top);
            }
        }

        // Header text
        $tx = $x0 + 4;
        $ty = $table_top - 13;
        foreach ($cols as $c) {
            $content .= $this->pdf_text_at($tx, $ty, $c[0], true, 9);
            $tx += (int)$c[1];
        }

        // Row text
        $row_top = $y_split;
        foreach ($data as $idx => $r) {
            $rh = (int)$row_heights[$idx];
            $ty = $row_top - 13; // top-aligned baseline
            $tx = $x0 + 4;

            $v_date = $this->pdf_trunc((string)($r['start_date'] ?? ''), 10);
            $v_customer = $this->pdf_trunc((string)($r['customer_name'] ?? ''), $cust_max_chars);
            $v_durasi = $this->pdf_trunc(((string)($r['duration_days'] ?? '')), $dur_max_chars);
            $v_harga = $this->pdf_trunc(($r['price'] !== '' && $r['price'] !== null) ? $this->wrpm_money_idr((float)($r['price'] ?? 0)) : '', $hrg_max_chars);

            // Tanggal
            $content .= $this->pdf_text_at($tx, $ty, $v_date, false, 9);
            $tx += (int)$cols[0][1];
            // Customer
            $content .= $this->pdf_text_at($tx, $ty, $v_customer, false, 9);
            $tx += (int)$cols[1][1];

            // Produk (word wrap)
            $prod_lines = (array)($row_meta[$idx]['product_lines'] ?? ['']);
            $py = $ty;
            foreach ($prod_lines as $li => $line) {
                $content .= $this->pdf_text_at($tx, $py - ($line_h * $li), $line, false, 9);
            }
            $tx += (int)$cols[2][1];

            // Durasi
            $content .= $this->pdf_text_at($tx, $ty, $v_durasi, false, 9);
            $tx += (int)$cols[3][1];
            // Harga
            $content .= $this->pdf_text_at($tx, $ty, $v_harga, false, 9);

            // advance
            $row_top -= $rh;
        }

        $content .= $this->pdf_text_at(360, 28, 'generate report: ' . wp_date('d/m/Y H:i:s'), false, 8);
        $content .= "Q\n";

        $pdf = $this->pdf_build_document_from_stream($content);
        $filename = 'monthly-report-' . preg_replace('/[^0-9-]/', '', $ym) . '.pdf';

        $this->wrpm_log('export', 'report', $ym, 'Download monthly report PDF', ['ym' => $ym, 'count' => count((array)$rows), 'total' => $total]);

        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    public function wrpm_output_invoice_pdf($active_product_id) {
        if (!current_user_can(self::CAP_MANAGE)) {
            wp_die(esc_html__('Forbidden', self::TEXT_DOMAIN));
        }

        global $wpdb;
        $id = sanitize_text_field((string)$active_product_id);
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tbl_active()} WHERE id = %s",
            $id
        ), ARRAY_A);

        if (!$row) {
            wp_die(esc_html__('Invoice not found', self::TEXT_DOMAIN));
        }

        // Layout invoice (table style)
        $stream = $this->wrpm_build_invoice_pdf_stream($row);
        $pdf = $this->pdf_build_document_from_stream($stream);
        $filename = 'invoice-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$id) . '.pdf';

        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }
}
