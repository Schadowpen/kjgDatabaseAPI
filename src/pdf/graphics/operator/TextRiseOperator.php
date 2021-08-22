<?php


namespace pdf\graphics\operator;

use pdf\object\PdfNumber;

/**
 * Operator zum setzen des Höher- oder Tieferstellens von Text
 * @package pdf\graphics\operator
 */
class TextRiseOperator extends AbstractOperator
{
    /**
     * Wie weit der Text höher- oder tiefergestellt werden soll, in Text Space Units
     * @var PdfNumber
     */
    protected $textRise;

    /**
     * TextRiseOperator constructor.
     * @param PdfNumber $textRise Wie weit der Text höher- oder tiefergestellt werden soll, in Text Space Units
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $textRise, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->textRise = $textRise;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "Ts";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->textRise->toString() . " Ts\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfNumber
     */
    public function getTextRise(): PdfNumber
    {
        return $this->textRise;
    }
}