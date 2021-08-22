<?php


namespace pdf\graphics\operator;

use pdf\graphics\Point;
use pdf\object\PdfNumber;

/**
 * Operator zum Zeichnen einer Linie vom letzten Punkt des Unterpfades zum angegebenen Punkt
 * @package pdf\graphics\operator
 */
class PathLineOperator extends PathConstructionOperator
{
    /**
     * x-Position des Linienendes in User Space
     * @var PdfNumber
     */
    protected $x;
    /**
     * y-Position des Linienendes in User Space
     * @var PdfNumber
     */
    protected $y;

    /**
     * PathLineOperator constructor.
     * @param PdfNumber $x x-Position des Linienendes in User Space
     * @param PdfNumber $y y-Position des Linienendes in User Space
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $x, PdfNumber $y, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "l";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt.
     * Dieser Beinhaltet auch einen EOL-Marker am Ende des Operatoren
     * @return string
     */
    function __toString(): string
    {
        return "{$this->x->toString()} {$this->y->toString()} l\n";
    }

    /**
     * Liefert den letzten Punkt des Operators, der dann als Startpunkt für den nächsten Operatoren genutzt wird.
     * Sollte der Operator einen Unterpfad beenden, wird null zurückgeliefert
     * @return null|Point
     */
    public function getLastPoint(): ?Point
    {
        return new Point($this->x->getValue(), $this->y->getValue());
    }

    /**
     * @return PdfNumber
     */
    public function getX(): PdfNumber
    {
        return $this->x;
    }

    /**
     * @return PdfNumber
     */
    public function getY(): PdfNumber
    {
        return $this->y;
    }
}