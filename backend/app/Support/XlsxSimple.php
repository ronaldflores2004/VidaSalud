<?php

namespace App\Support;

/**
 * Generador de archivos Excel (.xlsx) mínimo, sin librerías ni extensiones:
 * arma el XML de la hoja y lo empaqueta en un ZIP sin compresión (solo usa crc32 de PHP).
 * Lo usa CU20 Generar reportes. Soporta varias hojas, encabezado en negrita y anchos de columna.
 */
class XlsxSimple
{
    /** @var array<int, array{nombre:string, filas:array, anchos:array, titulos:int}> */
    private array $hojas = [];

    /**
     * @param  array<int, array<int, string|int|float|null>>  $filas  la primera fila con títulos va en negrita
     * @param  int  $filasTitulo  cuántas filas iniciales van en negrita
     */
    public function hoja(string $nombre, array $filas, array $anchos = [], int $filasTitulo = 1): self
    {
        $this->hojas[] = ['nombre' => mb_substr(preg_replace('/[\[\]\*\?\/\\\\:]/', '', $nombre), 0, 31), 'filas' => $filas, 'anchos' => $anchos, 'titulos' => $filasTitulo];

        return $this;
    }

    private static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }

        return $s;
    }

    private static function x(string $t): string
    {
        return htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xmlHoja(array $h): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        if ($h['anchos']) {
            $xml .= '<cols>';
            foreach ($h['anchos'] as $i => $w) {
                $xml .= sprintf('<col min="%d" max="%d" width="%s" customWidth="1"/>', $i + 1, $i + 1, $w);
            }
            $xml .= '</cols>';
        }
        $xml .= '<sheetData>';
        foreach ($h['filas'] as $r => $fila) {
            $xml .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($fila) as $c => $v) {
                $ref = self::col($c) . ($r + 1);
                $estilo = $r < $h['titulos'] ? ' s="1"' : '';
                if ($v === null || $v === '') {
                    $xml .= "<c r=\"$ref\"$estilo/>";
                } elseif (is_int($v) || is_float($v)) {
                    $xml .= "<c r=\"$ref\"$estilo><v>$v</v></c>";
                } else {
                    $xml .= "<c r=\"$ref\" t=\"inlineStr\"$estilo><is><t xml:space=\"preserve\">" . self::x((string) $v) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }

    public function salida(): string
    {
        $ns = 'http://schemas.openxmlformats.org';
        $archivos = [];
        $tipos = '';
        $hojasXml = '';
        $rels = '';
        foreach ($this->hojas as $i => $h) {
            $n = $i + 1;
            $archivos["xl/worksheets/sheet$n.xml"] = $this->xmlHoja($h);
            $tipos .= "<Override PartName=\"/xl/worksheets/sheet$n.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>";
            $hojasXml .= '<sheet name="' . self::x($h['nombre']) . "\" sheetId=\"$n\" r:id=\"rId$n\"/>";
            $rels .= "<Relationship Id=\"rId$n\" Type=\"$ns/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet$n.xml\"/>";
        }
        $k = count($this->hojas) + 1;
        $archivos['[Content_Types].xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="' . $ns . '/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $tipos . '</Types>';
        $archivos['_rels/.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="' . $ns . '/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="' . $ns . '/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $archivos['xl/workbook.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="' . $ns . '/spreadsheetml/2006/main" xmlns:r="' . $ns . '/officeDocument/2006/relationships"><sheets>' . $hojasXml . '</sheets></workbook>';
        $archivos['xl/_rels/workbook.xml.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="' . $ns . '/package/2006/relationships">' . $rels
            . "<Relationship Id=\"rId$k\" Type=\"$ns/officeDocument/2006/relationships/styles\" Target=\"styles.xml\"/></Relationships>";
        $archivos['xl/styles.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="' . $ns . '/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F4E79"/></patternFill></fill></fills>'
            . '<borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2"><xf/><xf fontId="1" fillId="2" applyFont="1" applyFill="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

        return self::zip($archivos);
    }

    /** ZIP sin compresión (método "store"). */
    private static function zip(array $archivos): string
    {
        $datos = '';
        $central = '';
        foreach ($archivos as $nombre => $contenido) {
            $crc = crc32($contenido);
            $len = strlen($contenido);
            $offset = strlen($datos);
            $cab = pack('vvvvvVVVvv', 20, 0x0800, 0, 0, 0x21, $crc, $len, $len, strlen($nombre), 0);
            $datos .= "PK\x03\x04" . $cab . $nombre . $contenido;
            $central .= "PK\x01\x02" . pack('vvvvvvVVVvvvvvVV', 20, 20, 0x0800, 0, 0, 0x21, $crc, $len, $len, strlen($nombre), 0, 0, 0, 0, 0, $offset) . $nombre;
        }
        $fin = "PK\x05\x06" . pack('vvvvVVv', 0, 0, count($archivos), count($archivos), strlen($central), strlen($datos), 0);

        return $datos . $central . $fin;
    }
}
