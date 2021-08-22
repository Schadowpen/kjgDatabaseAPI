<?php
namespace pdf\graphics\operator;


use pdf\object\PdfNumber;

/**
 * Operator zum Setzen der Linienbreite im GraphicsState
 * @package pdf\graphics\operator
 */
class LineWidthOperator extends AbstractOperator
{
    /**
     * Neue Linienbreite im GraphicsState
     * @var PdfNumber
     */
    protected $lineWidth;

    /**
     * LineWidthOperator constructor.
     * @param PdfNumber $lineWidth Neue Linienbreite im GraphicsState
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $lineWidth, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->lineWidth = $lineWidth;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "w";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->lineWidth->toString() . " w\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfNumber
     */
    public function getLineWidth(): PdfNumber
    {
        return $this->lineWidth;
    }
}