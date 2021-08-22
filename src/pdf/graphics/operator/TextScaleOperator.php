<?php


namespace pdf\graphics\operator;

use pdf\object\PdfNumber;

/**
 * Operator zum Setzen der horizontalen Skalierung in Prozent
 * @package pdf\graphics\operator
 */
class TextScaleOperator extends AbstractOperator
{
    /**
     * Horizontale Skalierung des Textes in Prozent
     * @var PdfNumber
     */
    protected $scale;

    /**
     * TextScaleOperator constructor.
     * @param PdfNumber $scale Horizontale Skalierung des Textes in Prozent
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $scale, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->scale = $scale;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "Tz";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->scale->toString() . " Tz\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfNumber
     */
    public function getScale(): PdfNumber
    {
        return $this->scale;
    }
}