<?php

namespace App\Support;

/**
 * Generador de PDF mínimo, sin librerías externas (solo PHP).
 * Usa las fuentes estándar Helvetica / Helvetica-Bold con codificación WinAnsi,
 * así se ven bien las tildes y la ñ. Lo usa CU20 Generar reportes.
 *
 * Coordenadas en puntos (1 pt = 1/72 pulgada); el origen (0,0) está arriba a la izquierda.
 */
class PdfSimple
{
    /** Ancho de los caracteres 32..255 (milésimas de em) – Helvetica y Helvetica-Bold. */
    private const ANCHO_N = [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584,750,556,556,556,556,556,1000,556,556,556,556,667,556,556,556,611,556,556,556,556,556,556,556,556,1000,556,556,500,556,556,556,500,667,278,333,556,556,556,556,260,556,333,737,370,556,584,0,737,552,400,549,333,333,333,576,537,333,333,333,365,556,834,834,834,611,667,667,667,667,667,667,1000,722,667,667,667,667,278,278,278,278,722,722,778,778,778,778,778,584,778,722,722,722,722,667,667,611,556,556,556,556,556,556,889,500,556,556,556,556,278,278,278,278,556,556,556,556,556,556,556,549,611,556,556,556,556,500,556,500];
    private const ANCHO_B = [278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584,750,556,611,556,556,556,1000,556,556,556,556,667,556,556,611,611,611,611,556,556,556,556,556,556,1000,556,556,556,556,556,611,500,667,278,333,556,556,556,556,280,556,333,737,370,556,584,0,737,552,400,549,333,333,333,576,556,333,333,333,365,556,834,834,834,611,722,722,722,722,722,722,1000,722,667,667,667,667,278,278,278,278,722,722,778,778,778,778,778,584,778,722,722,722,722,667,667,611,556,556,556,556,556,556,889,556,556,556,556,556,278,278,278,278,611,611,611,611,611,611,611,549,611,611,611,611,611,556,611,556];

    private array $paginas = [];
    private string $actual = '';
    public float $ancho;
    public float $alto;

    public function __construct(bool $horizontal = false)
    {
        [$this->ancho, $this->alto] = $horizontal ? [841.89, 595.28] : [595.28, 841.89];   // A4
    }

    public function nuevaPagina(): void
    {
        if ($this->actual !== '') {
            $this->paginas[] = $this->actual;
        }
        $this->actual = "0.2 w\n";
    }

    private static function cp1252(string $t): string
    {
        $c = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $t);

        return $c === false ? preg_replace('/[^\x20-\x7E]/', '?', $t) : $c;
    }

    public function medir(string $texto, float $tam, bool $negrita = false): float
    {
        $tabla = $negrita ? self::ANCHO_B : self::ANCHO_N;
        $w = 0;
        foreach (str_split(self::cp1252($texto)) as $ch) {
            $o = ord($ch);
            $w += $o >= 32 ? $tabla[$o - 32] : 556;
        }

        return $w * $tam / 1000;
    }

    /** Recorta el texto con «…» para que entre en el ancho dado. */
    public function ajustar(string $texto, float $max, float $tam, bool $negrita = false): string
    {
        if ($this->medir($texto, $tam, $negrita) <= $max) {
            return $texto;
        }
        while (mb_strlen($texto) > 1 && $this->medir($texto . '...', $tam, $negrita) > $max) {
            $texto = mb_substr($texto, 0, -1);
        }

        return rtrim($texto) . '...';
    }

    public function texto(float $x, float $y, string $texto, float $tam = 9, bool $negrita = false, array $color = [0, 0, 0], string $alinear = 'L', float $ancho = 0): void
    {
        if ($alinear === 'R') {
            $x += $ancho - $this->medir($texto, $tam, $negrita);
        } elseif ($alinear === 'C') {
            $x += ($ancho - $this->medir($texto, $tam, $negrita)) / 2;
        }
        $t = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], self::cp1252($texto));
        $this->actual .= sprintf("BT %.3f %.3f %.3f rg /%s %.1f Tf %.2f %.2f Td (%s) Tj ET\n",
            $color[0], $color[1], $color[2], $negrita ? 'F2' : 'F1', $tam, $x, $this->alto - $y, $t);
    }

    public function rect(float $x, float $y, float $w, float $h, ?array $relleno = null, ?array $borde = [0.75, 0.75, 0.75]): void
    {
        $op = '';
        if ($relleno) {
            $op .= sprintf('%.3f %.3f %.3f rg ', ...$relleno);
        }
        if ($borde) {
            $op .= sprintf('%.3f %.3f %.3f RG ', ...$borde);
        }
        $op .= sprintf('%.2f %.2f %.2f %.2f re %s', $x, $this->alto - $y - $h, $w, $h, $relleno && $borde ? 'B' : ($relleno ? 'f' : 'S'));
        $this->actual .= $op . "\n";
    }

    public function linea(float $x1, float $y1, float $x2, float $y2, array $color = [0.6, 0.6, 0.6]): void
    {
        $this->actual .= sprintf("%.3f %.3f %.3f RG %.2f %.2f m %.2f %.2f l S\n", ...$color, ...[$x1, $this->alto - $y1, $x2, $this->alto - $y2]);
    }

    public function salida(): string
    {
        if ($this->actual !== '') {
            $this->paginas[] = $this->actual;
            $this->actual = '';
        }
        $obj = [];
        $obj[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $obj[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $obj[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids = [];
        $n = 5;
        foreach ($this->paginas as $contenido) {
            $obj[$n] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                $this->ancho, $this->alto, $n + 1);
            $obj[$n + 1] = '<< /Length ' . strlen($contenido) . " >>\nstream\n" . $contenido . "endstream";
            $kids[] = "$n 0 R";
            $n += 2;
        }
        $obj[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($obj);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $pos = [];
        foreach ($obj as $i => $o) {
            $pos[$i] = strlen($pdf);
            $pdf .= "$i 0 obj\n$o\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($obj) + 1) . "\n0000000000 65535 f \n";
        foreach ($pos as $p) {
            $pdf .= sprintf("%010d 00000 n \n", $p);
        }
        $pdf .= "trailer\n<< /Size " . (count($obj) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%EOF";

        return $pdf;
    }
}
