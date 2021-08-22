<?php


namespace pdf\graphics\operator;

use pdf\object\PdfNumber;

/**
 * Operator zum Setzen des zusätzlichen Platzes zwischen zwei Zeichen im TextState
 * @package pdf\graphics\operator
 */
class CharacterSpaceOperator extends AbstractOperator
{
    /**
     * Platz zwischen zwei Zeichen im Text in Text Space Einheiten
     * @var PdfNumber
     */
    protected $charSpace;

    /**
     * CharacterSpaceOperator constructor.
     * @param PdfNumber $charSpace Platz zwischen zwei Zeichen im Text in Text Space Einheiten
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $charSpace, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->charSpace = $charSpace;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "Tc";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->charSpace->toString() . " Tc\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfNumber
     */
    public function getCharSpace(): PdfNumber
    {
        return $this->charSpace;
    }
}