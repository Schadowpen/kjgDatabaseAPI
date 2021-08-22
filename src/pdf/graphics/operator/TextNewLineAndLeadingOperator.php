<?php


namespace pdf\graphics\operator;

use pdf\object\PdfNumber;

/**
 * Operator zum Beginnen einer neuen Zeile in einem Text Objekt. Zusätzlich wird der TextLeading im GraphicsState gesetzt.
 * Die Position neue Textzeile ist relativ zur letzten Textzeile angegeben.
 * @package pdf\graphics\operator
 */
class TextNewLineAndLeadingOperator extends AbstractOperator
{
    /**
     * X-Abstand der neuen Textzeile zur Vorherigen
     * @var PdfNumber
     */
    protected $tx;
    /**
     * Y-Abstand der  neuen Textzeile zur Vorherigen, sowie neuer Wert für textLeading im GraphicsState
     * @var PdfNumber
     */
    protected $ty;

    /**
     * TextNewLineOperator constructor.
     * @param PdfNumber $tx X-Abstand der neuen Textzeile zur Vorherigen
     * @param PdfNumber $ty Y-Abstand der neuen Textzeile zur Vorherigen, sowie neuer Wert für textLeading im GraphicsState
     * @param OperatorMetadata $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfNumber $tx, PdfNumber $ty, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->tx = $tx;
        $this->ty = $ty;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "TD";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->tx->toString() . " " . $this->ty->toString() . " TD\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfNumber
     */
    public function getTx(): PdfNumber
    {
        return $this->tx;
    }

    /**
     * @return PdfNumber
     */
    public function getTy(): PdfNumber
    {
        return $this->ty;
    }
}