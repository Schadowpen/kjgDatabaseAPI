<?php


namespace pdf\graphics\operator;


use pdf\graphics\Point;
use pdf\object\PdfNumber;

/**
 * Operator zum Zeichnen einer kubischen Bezier-Kurve. Der erste der vier Bezier-Punkte ist der Punkt aus dem letzten Path Construction Operator
 * @package pdf\graphics\operator
 */
class PathBezierOperator extends PathConstructionOperator
{
    /**
     * x-Position von P1 der Bezier-Kurve
     * @var PdfNumber
     */
    protected $x1;
    /**
     * y-Position von P1 der Bezier-Kurve
     * @var PdfNumber
     */
    protected $y1;
    /**
     * x-Position von P2 der Bezier-Kurve
     * @var PdfNumber
     */
    protected $x2;
    /**
     * y-Position von P2 der Bezier-Kurve
     * @var PdfNumber
     */
    protected $y2;
    /**
     * x-Position von P3 der Bezier-Kurve
     * @var PdfNumber
     */
    protected $x3;
    /**
     * y-Position von P3 der Bezier-Kurve
     * @var PdfNumber
     */
    protected $y3;

    /**
     * PathBezierOperator constructor.
     * @param PdfNumber $x1 x-Position von P1 der Bezier-Kurve
     * @param PdfNumber $y1 y-Position von P1 der Bezier-Kurve
     * @param PdfNumber $x2 x-Position von P2 der Bezier-Kurve
     * @param PdfNumber $y2 y-Position von P2 der Bezier-Kurve
     * @param PdfNumber $x3 x-Position von P3 der Bezier-Kurve
     * @param PdfNumber $y3 y-Position von P3 der Bezier-Kurve
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $x1, PdfNumber $y1, PdfNumber $x2, PdfNumber $y2, PdfNumber $x3, PdfNumber $y3, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->x1 = $x1;
        $this->y1 = $y1;
        $this->x2 = $x2;
        $this->y2 = $y2;
        $this->x3 = $x3;
        $this->y3 = $y3;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "c";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt.
     * Dieser Beinhaltet auch einen EOL-Marker am Ende des Operatoren
     * @return string
     */
    function __toString(): string
    {
        return $this->x1->toString() . " "
            . $this->y1->toString() . " "
            . $this->x2->toString() . " "
            . $this->y2->toString() . " "
            . $this->x3->toString() . " "
            . $this->y3->toString() . " c\n";
    }

    /**
     * Liefert den letzten Punkt des Operators, der dann als Startpunkt für den nächsten Operatoren genutzt wird.
     * Sollte der Operator einen Unterpfad beenden, wird null zurückgeliefert
     * @return null|Point
     */
    public function getLastPoint(): ?Point
    {
        return new Point($this->x3->getValue(), $this->y3->getValue());
    }

    /**
     * @return PdfNumber
     */
    public function getX1(): PdfNumber
    {
        return $this->x1;
    }

    /**
     * @return PdfNumber
     */
    public function getY1(): PdfNumber
    {
        return $this->y1;
    }

    /**
     * @return PdfNumber
     */
    public function getX2(): PdfNumber
    {
        return $this->x2;
    }

    /**
     * @return PdfNumber
     */
    public function getY2(): PdfNumber
    {
        return $this->y2;
    }

    /**
     * @return PdfNumber
     */
    public function getX3(): PdfNumber
    {
        return $this->x3;
    }

    /**
     * @return PdfNumber
     */
    public function getY3(): PdfNumber
    {
        return $this->y3;
    }
}