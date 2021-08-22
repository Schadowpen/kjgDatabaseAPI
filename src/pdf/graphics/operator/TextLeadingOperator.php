<?php


namespace pdf\graphics\operator;

use pdf\object\PdfNumber;

/**
 * Operator zum setzen des Zeilenabstandes in Text Space Einheiten.
 * @package pdf\graphics\operator
 */
class TextLeadingOperator extends AbstractOperator
{
    /**
     * Zeilenabstand zwischen zwei Zeilen in Text Space Units
     * @var PdfNumber
     */
    protected $leading;

    /**
     * TextLeadingOperator constructor.
     * @param PdfNumber $leading Zeilenabstand zwischen zwei Zeilen in Text Space Units
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $leading, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->leading = $leading;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "TL";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->leading->toString() . " TL\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfNumber
     */
    public function getLeading(): PdfNumber
    {
        return $this->leading;
    }
}