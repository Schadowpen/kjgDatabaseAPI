<?php


namespace pdf\graphics\operator;

use pdf\graphics\Point;
use pdf\object\PdfNumber;

/**
 * Operator zum Beginnen eines Pfades oder Unterpfades mit einem Startpunkt
 * @package pdf\graphics\operator
 */
class PathBeginOperator extends PathConstructionOperator
{
    /**
     * X-Position in User Space
     * @var PdfNumber
     */
    protected $x;
    /**
     * Y-Position in User Space
     * @var PdfNumber
     */
    protected $y;

    /**
     * PathBeginOperator constructor.
     * @param PdfNumber $x X-Position in User Space
     * @param PdfNumber $y Y-Position in User Space
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
        return "m";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return "{$this->x->toString()} {$this->y->toString()} m\n";
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

    public function isGraphicsStateOperator(): bool
    {
        return true;
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