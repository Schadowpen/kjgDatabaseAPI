<?php


namespace pdf\graphics\operator;


use pdf\object\PdfNumber;

/**
 * Operator, welcher den Style, wie Linien beendet werden, im GraphicsState setzt
 * @package pdf\graphics\operator
 */
class LineCapOperator extends AbstractOperator
{
    /**
     * Linien werden am Ende abgeschnitten
     */
    public const buttCap = 0;
    /**
     * Linien werden am Ende mit runden Ecken abgeschlossen
     */
    public const roundCap = 1;
    /**
     * Linien werden am Ende quadratisch abgeschlossen.
     */
    public const squareCap = 2;

    /**
     * Der neue Style, wie Linien beendet werden
     * @var int
     */
    protected $lineCap;

    /**
     * LineJoinOperator constructor.
     * @param PdfNumber $lineCap Der neue Style, wie Linien beendet werden
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $lineCap, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->lineCap = $lineCap->getValue();
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "J";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->lineCap . " J\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return int
     */
    public function getLineCap(): int
    {
        return $this->lineCap;
    }
}