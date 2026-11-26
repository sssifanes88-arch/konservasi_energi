<?php
// Ekspor data ke PDF dan Excel (.xlsx) tanpa library tambahan (tidak perlu Composer).
//  - PDF   : ditulis langsung oleh kelas PdfSederhana (font Helvetica bawaan PDF).
//  - Excel : berkas .xlsx dibuat dengan ZipArchive (ekstensi "zip", aktif di XAMPP).
//            Jika ekstensi zip mati, otomatis dibuat berkas .xls (tabel HTML) sebagai cadangan.
require_once __DIR__ . '/functions.php';

function ekspor_bersih($v) { return $v === null ? '' : trim((string)$v); }

function nama_berkas($dasar, $ext) {
    return preg_replace('/[^A-Za-z0-9_-]+/', '_', $dasar) . '_' . date('Ymd_His') . '.' . $ext;
}

// Baris keterangan di bawah judul: siapa yang mencetak dan kapan (+ keterangan tambahan).
function ekspor_info(array $tambahan = []) {
    $u = user();
    $baris = ['Dicetak ' . tgl_id(date('Y-m-d')) . ' ' . date('H:i') . ' ' . date('T') . ' oleh ' . ($u['nama'] ?? '-') . ' (' . ($u['level'] ?? '-') . ')'];
    foreach ($tambahan as $t) $baris[] = $t;
    return $baris;
}

/**
 * $format : 'pdf' atau 'excel'
 * $nama   : dasar nama berkas, mis. 'data_siswa'
 * $kolom  : [['Judul kolom', bobot lebar], ...]
 * $baris  : array berisi array nilai, urutannya sama dengan $kolom
 */
function ekspor($format, $nama, $judul, array $info, array $kolom, array $baris, $landscape = false) {
    while (ob_get_level()) ob_end_clean();
    if ($format === 'pdf') {
        $pdf = new PdfSederhana($landscape);
        $pdf->tabel($judul, $info, $kolom, $baris);
        $pdf->keluarkan(nama_berkas($nama, 'pdf'));
    } else {
        ekspor_excel($nama, $judul, $info, $kolom, $baris);
    }
    exit;
}

// ======================================================================
//  EXCEL (.xlsx)
// ======================================================================

function xl_kol($i) {
    $s = '';
    $i++;
    while ($i > 0) {
        $m = ($i - 1) % 26;
        $s = chr(65 + $m) . $s;
        $i = (int)(($i - 1) / 26);
    }
    return $s;
}

function xl_teks($v) {
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$v);
    return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Sel: angka bulat (tanpa nol di depan) disimpan sebagai angka, selain itu sebagai teks.
function xl_sel($ref, $v, $gaya, $boleh_angka = false) {
    if ($boleh_angka && (is_int($v) || (is_string($v) && preg_match('/^(0|[1-9][0-9]{0,14})$/', $v)))) {
        return '<c r="' . $ref . '" s="' . $gaya . '"><v>' . $v . '</v></c>';
    }
    return '<c r="' . $ref . '" s="' . $gaya . '" t="inlineStr"><is><t xml:space="preserve">' . xl_teks($v) . '</t></is></c>';
}

function ekspor_excel($nama, $judul, array $info, array $kolom, array $baris) {
    if (!class_exists('ZipArchive')) { ekspor_xls_html($nama, $judul, $info, $kolom, $baris); return; }

    $n     = count($kolom);
    $akhir = xl_kol($n - 1);
    $merge = [];
    $xml   = '';

    $r = 1;
    $xml .= '<row r="1" ht="24" customHeight="1">' . xl_sel('A1', $judul, 2) . '</row>';
    $merge[] = "A1:{$akhir}1";
    foreach ($info as $t) {
        $r++;
        $xml .= '<row r="' . $r . '">' . xl_sel('A' . $r, $t, 4) . '</row>';
        $merge[] = "A{$r}:{$akhir}{$r}";
    }

    $r  += 2;               // satu baris kosong
    $hdr = $r;
    $lebar = [];
    $xml .= '<row r="' . $r . '" ht="22" customHeight="1">';
    foreach ($kolom as $i => $k) {
        $xml .= xl_sel(xl_kol($i) . $r, $k[0], 1);
        $lebar[$i] = panjang($k[0]);
    }
    $xml .= '</row>';

    if (!$baris) {
        $r++;
        $xml .= '<row r="' . $r . '">' . xl_sel('A' . $r, 'Tidak ada data', 3) . '</row>';
    }
    foreach ($baris as $b) {
        $r++;
        $xml .= '<row r="' . $r . '">';
        foreach ($kolom as $i => $k) {
            $v = $b[$i] ?? '';
            if (is_string($v)) $v = trim($v);
            $xml .= xl_sel(xl_kol($i) . $r, $v, 3, true);
            foreach (preg_split('/\R/', (string)$v) as $ln) $lebar[$i] = max($lebar[$i], panjang($ln));
        }
        $xml .= '</row>';
    }

    $cols = '<cols>';
    foreach ($lebar as $i => $w) {
        $w = min(60, max(8, $w + 3));
        $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
    }
    $cols .= '</cols>';

    $mc = '<mergeCells count="' . count($merge) . '">';
    foreach ($merge as $m) $mc .= '<mergeCell ref="' . $m . '"/>';
    $mc .= '</mergeCells>';

    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<dimension ref="A1:' . $akhir . $r . '"/>'
        . '<sheetViews><sheetView workbookViewId="0" showGridLines="0">'
        . '<pane ySplit="' . $hdr . '" topLeftCell="A' . ($hdr + 1) . '" activePane="bottomLeft" state="frozen"/>'
        . '<selection pane="bottomLeft"/></sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="15"/>' . $cols
        . '<sheetData>' . $xml . '</sheetData>' . $mc
        . '<pageMargins left="0.5" right="0.5" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
        . '</worksheet>';

    $nm = trim(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $judul));
    $nm = substr($nm === '' ? 'Data' : $nm, 0, 31);

    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="4">'
        .   '<font><sz val="11"/><name val="Calibri"/></font>'
        .   '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
        .   '<font><b/><sz val="14"/><color rgb="FFBE185D"/><name val="Calibri"/></font>'
        .   '<font><i/><sz val="10"/><color rgb="FF6B7280"/><name val="Calibri"/></font>'
        . '</fonts>'
        . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
        .   '<fill><patternFill patternType="solid"><fgColor rgb="FFDB2777"/><bgColor indexed="64"/></patternFill></fill></fills>'
        . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
        .   '<border><left style="thin"><color rgb="FFD1D5DB"/></left><right style="thin"><color rgb="FFD1D5DB"/></right>'
        .   '<top style="thin"><color rgb="FFD1D5DB"/></top><bottom style="thin"><color rgb="FFD1D5DB"/></bottom><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="5">'
        .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        .   '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        .   '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
        .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>'
        .   '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';

    $tipe = 'application/vnd.openxmlformats-officedocument.spreadsheetml';
    $isi = [
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="' . $tipe . '.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="' . $tipe . '.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="' . $tipe . '.styles+xml"/>'
            . '</Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>',
        'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . xl_teks($nm) . '" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>',
        'xl/styles.xml'             => $styles,
        'xl/worksheets/sheet1.xml'  => $sheet,
    ];

    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        @unlink($tmp);
        exit('Gagal membuat berkas Excel.');
    }
    foreach ($isi as $path => $konten) $zip->addFromString($path, $konten);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . nama_berkas($nama, 'xlsx') . '"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: no-store');
    readfile($tmp);
    @unlink($tmp);
}

// Cadangan bila ekstensi zip PHP tidak aktif: tabel HTML yang bisa dibuka Excel.
function ekspor_xls_html($nama, $judul, array $info, array $kolom, array $baris) {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . nama_berkas($nama, 'xls') . '"');
    echo "\xEF\xBB\xBF<html><head><meta charset=\"utf-8\"></head><body>";
    echo '<h3>' . e($judul) . '</h3>';
    foreach ($info as $t) echo '<div>' . e($t) . '</div>';
    echo '<br><table border="1" cellspacing="0" cellpadding="4"><tr>';
    foreach ($kolom as $k) echo '<th style="background:#db2777;color:#fff">' . e($k[0]) . '</th>';
    echo '</tr>';
    foreach ($baris as $b) {
        echo '<tr>';
        foreach ($kolom as $i => $k) echo '<td style="mso-number-format:\'\\@\'">' . e($b[$i] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
}

// ======================================================================
//  PDF (penulis PDF sederhana: tabel dengan header berulang, nomor halaman)
// ======================================================================

class PdfSederhana
{
    private $hal = [];          // isi tiap halaman yang sudah selesai
    private $isi = '';          // isi halaman yang sedang dibuat
    private $pw;
    private $ph;
    private $m = 36;            // margin (pt)
    private $y;                 // posisi vertikal (dari atas halaman)
    private $w  = [];           // lebar tiap kolom
    private $kj = [];           // judul kolom

    // Lebar karakter Helvetica (ASCII 32..126), satuan 1/1000 em
    private static $lc = [
        278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,
        556,556,556,556,556,556,556,556,556,556,
        278,278,584,584,584,556,1015,
        667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,
        278,278,278,469,556,333,
        556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,
        334,260,334,584,
    ];

    public function __construct($landscape = false)
    {
        $this->pw = $landscape ? 841.89 : 595.28;
        $this->ph = $landscape ? 595.28 : 841.89;
    }

    // UTF-8 -> Windows-1252 (agar huruf beraksen/tanda baca umum tetap tampil)
    public static function enc($s)
    {
        $s = (string)$s;
        if (function_exists('iconv')) {
            $r = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
            if ($r !== false) return $r;
        }
        if (function_exists('mb_convert_encoding')) return mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
        return preg_replace('/[^\x20-\x7E\r\n\t]/', '?', $s);
    }

    private function lebar($s, $size, $bold)
    {
        $t = 0;
        $n = strlen($s);
        for ($i = 0; $i < $n; $i++) {
            $o = ord($s[$i]);
            $t += ($o >= 32 && $o <= 126) ? self::$lc[$o - 32] : 556;
        }
        return $t / 1000 * $size * ($bold ? 1.06 : 1);
    }

    // Memecah teks menjadi baris-baris yang muat dalam lebar $maks
    private function bungkus($s, $maks, $size, $bold)
    {
        $s   = str_replace(["\r\n", "\r", "\t"], ["\n", "\n", ' '], $s);
        $out = [];
        foreach (explode("\n", $s) as $para) {
            $line = '';
            foreach (preg_split('/ +/', $para) as $kata) {
                if ($kata === '') continue;
                while ($this->lebar($kata, $size, $bold) > $maks && strlen($kata) > 1) {
                    for ($k = strlen($kata) - 1; $k > 1; $k--) {
                        if ($this->lebar(substr($kata, 0, $k), $size, $bold) <= $maks) break;
                    }
                    if ($line !== '') { $out[] = $line; $line = ''; }
                    $out[] = substr($kata, 0, $k);
                    $kata = substr($kata, $k);
                }
                $coba = $line === '' ? $kata : $line . ' ' . $kata;
                if ($line === '' || $this->lebar($coba, $size, $bold) <= $maks) {
                    $line = $coba;
                } else {
                    $out[] = $line;
                    $line  = $kata;
                }
            }
            $out[] = $line;
        }
        return $out ?: [''];
    }

    private function teks($s, $x, $yTop, $size, $bold, $rgb = [0, 0, 0])
    {
        $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        $this->isi .= sprintf("BT %.3F %.3F %.3F rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n",
            $rgb[0], $rgb[1], $rgb[2], $bold ? 'F2' : 'F1', $size, $x, $this->ph - $yTop, $s);
    }

    private function kotak($x, $yTop, $w, $h, $rgb)
    {
        $this->isi .= sprintf("%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f\n",
            $rgb[0], $rgb[1], $rgb[2], $x, $this->ph - $yTop - $h, $w, $h);
    }

    private function bingkai($x, $yTop, $w, $h)
    {
        $this->isi .= sprintf("0.82 0.84 0.86 RG 0.5 w %.2F %.2F %.2F %.2F re S\n", $x, $this->ph - $yTop - $h, $w, $h);
    }

    private function halamanBaru()
    {
        $this->hal[] = $this->isi;
        $this->isi   = '';
        $this->y     = $this->m;
    }

    private function baris(array $sel, $header, $zebra)
    {
        $size  = $header ? 9 : 8.5;
        $lh    = $size * 1.3;
        $pad   = 4;
        $lines = [];
        $maxL  = 1;
        foreach ($sel as $i => $v) {
            $lines[$i] = $this->bungkus(self::enc($v), $this->w[$i] - 2 * $pad, $size, $header);
            $maxL = max($maxL, count($lines[$i]));
        }
        $h = $maxL * $lh + 2 * $pad;

        if (!$header && $this->y + $h > $this->ph - $this->m) {
            $this->halamanBaru();
            $this->baris($this->kj, true, false);   // ulangi header di halaman baru
        }

        $x = $this->m;
        foreach ($lines as $i => $l) {
            if ($header)      $this->kotak($x, $this->y, $this->w[$i], $h, [0.859, 0.153, 0.467]);
            elseif ($zebra)   $this->kotak($x, $this->y, $this->w[$i], $h, [1, 0.953, 0.973]);
            $this->bingkai($x, $this->y, $this->w[$i], $h);
            $ty = $this->y + $pad + $size;
            foreach ($l as $ln) {
                $this->teks($ln, $x + $pad, $ty, $size, $header, $header ? [1, 1, 1] : [0.1, 0.1, 0.1]);
                $ty += $lh;
            }
            $x += $this->w[$i];
        }
        $this->y += $h;
    }

    public function tabel($judul, array $info, array $kolom, array $baris)
    {
        $tot = 0;
        foreach ($kolom as $k) $tot += $k[1];
        $avail = $this->pw - 2 * $this->m;
        foreach ($kolom as $i => $k) {
            $this->w[$i]  = $avail * $k[1] / $tot;
            $this->kj[$i] = $k[0];
        }

        $this->y = $this->m;
        $this->teks(self::enc($judul), $this->m, $this->y + 15, 16, true, [0.745, 0.094, 0.365]);
        $this->y += 24;
        foreach ($info as $t) {
            $this->teks(self::enc($t), $this->m, $this->y + 9, 9, false, [0.35, 0.35, 0.35]);
            $this->y += 13;
        }
        $this->y += 8;

        $this->baris($this->kj, true, false);
        if (!$baris) {
            $kosong = ['Tidak ada data'];
            for ($i = 1; $i < count($kolom); $i++) $kosong[] = '';
            $this->baris($kosong, false, false);
        }
        $z = false;
        foreach ($baris as $b) {
            $sel = [];
            foreach ($kolom as $i => $k) $sel[$i] = ekspor_bersih($b[$i] ?? '');
            $this->baris($sel, false, $z);
            $z = !$z;
        }
    }

    public function keluarkan($namaBerkas)
    {
        $this->hal[] = $this->isi;
        $n = count($this->hal);
        foreach ($this->hal as $i => $c) {         // footer + nomor halaman
            $this->isi = '';
            $fy = $this->ph - $this->m + 14;
            $this->teks('Konservasi Energi', $this->m, $fy, 8, false, [0.5, 0.5, 0.5]);
            $t = 'Halaman ' . ($i + 1) . ' dari ' . $n;
            $this->teks($t, $this->pw - $this->m - $this->lebar($t, 8, false), $fy, 8, false, [0.5, 0.5, 0.5]);
            $this->hal[$i] = $c . $this->isi;
        }

        $o = [];
        $o[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $o[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $o[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids = [];
        $no   = 5;
        foreach ($this->hal as $c) {
            $o[$no]     = '<< /Length ' . strlen($c) . " >>\nstream\n" . $c . 'endstream';
            $o[$no + 1] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                $this->pw, $this->ph, $no);
            $kids[] = ($no + 1) . ' 0 R';
            $no += 2;
        }
        $o[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($o);

        $out = "%PDF-1.4\n";
        $off = [];
        foreach ($o as $i => $isi) {
            $off[$i] = strlen($out);
            $out .= $i . " 0 obj\n" . $isi . "\nendobj\n";
        }
        $xref = strlen($out);
        $out .= "xref\n0 " . (count($o) + 1) . "\n0000000000 65535 f \n";
        foreach ($off as $p) $out .= sprintf("%010d 00000 n \n", $p);
        $out .= "trailer\n<< /Size " . (count($o) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $namaBerkas . '"');
        header('Content-Length: ' . strlen($out));
        header('Cache-Control: no-store');
        echo $out;
    }
}
