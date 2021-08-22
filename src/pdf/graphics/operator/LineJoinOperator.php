<?php


namespace pdf\graphics\operator;


use pdf\object\PdfNumber;

/**
 * Operator, welcher den Style, wie Linien zusammengefügt werden, im GraphicsState setzt
 * @package pdf\graphics\operator
 */
class LineJoinOperator extends AbstractOperator
{
    /**
     * Linien werden mit einer spitzen Ecke zusammengefügt.
     * Der MiterLimit beschreibt, wie lang die spizte Ecke maximal sein darf
     */
    public const miterJoin = 0;
    /**
     * Linien werden mit einer Runden Ecke zusammengefügt
     */
    public const roundJoin = 1;
    /**
     * Linien werden mit einer abgeschnittenen Ecke zusammengefügt
     */
    public const bevelJoin = 2;

    /**
     * Der neue Style, wie Linien zusammengefügt werden
     * @var int
     */
    protected $lineJoin;

    /**
     * LineJoinOperator constructor.
     * @param PdfNumber $lineJoin Der neue Style, wie Linien zusammengefügt werden
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $lineJoin, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->lineJoin = $lineJoin->getValue();
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "j";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->lineJoin . " J\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return int
     */
    public function getLineJoin(): int
    {
        return $this->lineJoin;
    }
}