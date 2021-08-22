<?php

namespace pdf\graphics;

use pdf\object\PdfArray;

/**
 * Transformations Matrix zum Umrechnen von Koordinaten im User Space zu Device Space.<br/>
 * Eine Transformationsmatrix hat folgende Form:<br/>
 * a b 0 <br/>
 * c d 0 <br/>
 * e f 1 <br/>
 * Koordinaten in Device Space umrechnen:  Xd = Xu * M <br/>
 * Zusätzliche Transformation VOR ausführung dieser Transformation: Mn * M <br/>
 * @package pdf\graphics
 */
class TransformationMatrix
{
    protected $a;
    protected $b;
    protected $c;
    protected $d;
    protected $e;
    protected $f;

    /**
     * Erzeugt eine neue Transformationsmatrix. Sind keine Werte angegeben, wird eine Einheitsmatrix erzeugt.
     * @param float $a
     * @param float $b
     * @param float $c
     * @param float $d
     * @param float $e
     * @param float $f
     */
    public function __construct(float $a = 1, float $b = 0, float $c = 0, float $d = 1, float $e = 0, float $f = 0)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
        $this->d = $d;
        $this->e = $e;
        $this->f = $f;
    }

    /**
     * Erzeugt eine Transformationsmatrix, die eine Translation durchführt
     * @param float $tx Translation in X-Richtung
     * @param float $ty Translation in Y-Richtung
     * @return TransformationMatrix
     */
    public static function translation(float $dx, float $dy): TransformationMatrix
    {
        return new TransformationMatrix(1, 0, 0, 1, $dx, $dy);
    }

    /**
     * Erzeugt eine Transformationsmatrix, die eine Skalierung durchführt
     * @param float $sx Skalierung in X-Richtung
     * @param float $sy Skalierung in Y-Richtung
     * @return TransformationMatrix
     */
    public static function scaling(float $sx, float $sy): TransformationMatrix
    {
        return new TransformationMatrix($sx, 0, 0, $sy);
    }

    /**
     * Erzeugt eine Transformationsmatrix, die eine Rotation durchführt
     * @param float $radians Rotation gegen den Uhrzeigersinn in Radians
     * @return TransformationMatrix
     */
    public static function rotation(float $radians): TransformationMatrix
    {
        return new TransformationMatrix(cos($radians), sin($radians), -sin($radians), cos($radians));
    }

    /**
     * Erzeugt eine Transformationsmatrix, die eine Abschrägung durchführt
     * @param float $xSkewRadians Abschrägung gegenüber der X-Achse in Radians
     * @param float $ySkewRadians Abschrägung gegenüber der Y-Achse in Radians
     * @return TransformationMatrix
     */
    public static function skew(float $xSkewRadians, float $ySkewRadians)
    {
        return new TransformationMatrix(1, tan($xSkewRadians), tan($ySkewRadians), 1);
    }

    /**
     * Erzeugt eine Transformationsmatrix aus einem PdfArray
     * @param PdfArray $pdfArray Array mit 6 PdfNumber-Werten
     * @return TransformationMatrix
     */
    public static function parsePdfArray(PdfArray $pdfArray)
    {
        return new TransformationMatrix(
            $pdfArray->getObject(0)->getValue(),
            $pdfArray->getObject(1)->getValue(),
            $pdfArray->getObject(2)->getValue(),
            $pdfArray->getObject(3)->getValue(),
            $pdfArray->getObject(4)->getValue(),
            $pdfArray->getObject(5)->getValue()
        );
    }

    /**
     * Transformiert einen Punkt.<br/>
     * Entspricht <b>return $p * $this</b>
     * @param Point $p Punkt im User Space
     * @return Point Punkt im Device Space
     */
    public function transformPoint(Point $p): Point
    {
        return new Point(
            $this->a * $p->x + $this->c * $p->y + $this->e,
            $this->b * $p->x + $this->d * $p->y + $this->f
        );
    }

    /**
     * Fügt eine Transformation am User Space Ende dieser Transformation hinzu und gibt die neue Transformation zurück.<br/>
     * Entspricht <b>return $matrix * $this</b>
     * @param TransformationMatrix $matrix Anzuwendende Transformation
     * @return TransformationMatrix neue zusammengeführte Transformation
     */
    public function addTransformation(TransformationMatrix $matrix): TransformationMatrix
    {
        return new TransformationMatrix(
            $matrix->a * $this->a + $matrix->b * $this->c,
            $matrix->a * $this->b + $matrix->b * $this->d,
            $matrix->c * $this->a + $matrix->d * $this->c,
            $matrix->c * $this->b + $matrix->d * $this->d,
            $matrix->e * $this->a + $matrix->f * $this->c + $this->e,
            $matrix->e * $this->b + $matrix->f * $this->d + $this->f
        );
    }

    /**
     * Berechnet die Inverse Matrix zur Transformationsmatrix.
     * Diese Matrix kann dan verwendet werden, um einen Punkt in Device Space in einen Punkt im User Space umzurechnen.
     * @return TransformationMatrix Invertierte Matrix
     */
    public function invers(): TransformationMatrix
    {
        $determinante = $this->a * $this->d - $this->b * $this->c;
        return new TransformationMatrix(
            $this->d / $determinante,
            -$this->b / $determinante,
            -$this->c / $determinante,
            $this->a / $determinante,
            ($this->c * $this->f - $this->d * $this->e) / $determinante,
            ($this->b * $this->e - $this->a * $this->f) / $determinante
        );
    }

    /**
     * @return float
     */
    public function getA(): float
    {
        return $this->a;
    }

    /**
     * @return float
     */
    public function getB(): float
    {
        return $this->b;
    }

    /**
     * @return float
     */
    public function getC(): float
    {
        return $this->c;
    }

    /**
     * @return float
     */
    public function getD(): float
    {
        return $this->d;
    }

    /**
     * @return float
     */
    public function getE(): float
    {
        return $this->e;
    }

    /**
     * @return float
     */
    public function getF(): float
    {
        return $this->f;
    }
}