<?php


namespace pdf\graphics\operator;


use pdf\object\PdfNumber;

/**
 * Operator zum Setzen des Zusätzlichen Platzes zwischen zwei Wörtern im TextState
 * @package pdf\graphics\operator
 */
class WordSpaceOperator extends AbstractOperator
{
    /**
     * Platz, der zu jedem Leerzeichen \x32 hinzugefügt werden soll, in Text Space Units
     * @var PdfNumber
     */
    protected $wordSpace;

    /**
     * WordSpaceOperator constructor.
     * @param PdfNumber $wordSpace Platz, der zu jedem Leerzeichen \x32 hinzugefügt werden soll, in Text Space Units
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $wordSpace, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->wordSpace = $wordSpace;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "Tw";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->wordSpace->toString() . " Tw\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfNumber
     */
    public function getWordSpace(): PdfNumber
    {
        return $this->wordSpace;
    }
}