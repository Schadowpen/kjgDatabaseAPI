<?php


namespace pdf\graphics\operator;

use pdf\object\PdfNumber;

/**
 * Operator zum setzen des Miter Limit, wird für Miter Line Join benutzt.
 * Wenn Linien spitz zusammenlaufen beschreibt dieser Wert, wie weit die Linie maximal über die Ecke hinausragen darf.
 * @package pdf\graphics\operator
 */
class MiterLimitOperator extends AbstractOperator
{
    /**
     * Doppelte Länge, wie weit die Ecke maximal über das Linienende hinausragen darf
     * @var PdfNumber
     */
    protected $miterLimit;

    /**
     * MiterLimitOperator constructor.
     * @param PdfNumber $miterLimit Maximale Länge des Miters beim spitzen zusammenlaufen von Linien
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $miterLimit, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->miterLimit = $miterLimit;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "M";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->miterLimit->toString() . " M\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfNumber
     */
    public function getMiterLimit(): PdfNumber
    {
        return $this->miterLimit;
    }
}