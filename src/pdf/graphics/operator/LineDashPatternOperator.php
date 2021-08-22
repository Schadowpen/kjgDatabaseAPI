<?php


namespace pdf\graphics\operator;

use pdf\object\PdfArray;
use pdf\object\PdfNumber;

/**
 * Operator zum setzen des Pattern, wie Linien gestrichelt sein sollen
 * @package pdf\graphics\operator
 */
class LineDashPatternOperator extends AbstractOperator
{
    /**
     * Pattern, wie Linien gestrichelt sein sollen
     * @var PdfArray
     */
    protected $dashArray;
    /**
     * Offset vom Start des Patterns zum Start, ab dem das Pattern angewandt werden soll
     * @var PdfNumber
     */
    protected $dashPhase;

    /**
     * LineDashPatternOperator constructor.
     * @param PdfArray $dashArray Pattern, wie Linien gestrichelt sein sollen
     * @param PdfNumber $dashPhase Offset vom Start des Patterns zum Start, ab dem das Pattern angewandt werden soll
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfArray $dashArray, PdfNumber $dashPhase, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->dashArray = $dashArray;
        $this->dashPhase = $dashPhase;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "d";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->dashArray->toString() . " " . $this->dashPhase->toString() . " d\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfArray
     */
    public function getDashArray(): PdfArray
    {
        return $this->dashArray;
    }

    /**
     * @return PdfNumber
     */
    public function getDashPhase(): PdfNumber
    {
        return $this->dashPhase;
    }
}