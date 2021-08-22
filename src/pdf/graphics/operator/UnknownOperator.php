<?php
namespace pdf\graphics\operator;

use pdf\graphics\state\GraphicsState;
use pdf\object\PdfAbstractObject;

/**
 * Unbekannter oder nicht extra definierter Operator
 * @package pdf\graphics
 */
class UnknownOperator extends AbstractOperator
{
    /**
     * Array mit allen Operanden
     * @var PdfAbstractObject[]
     */
    private $operands;
    /**
     * Operator, wie er im ContentStream vorkommt
     * @var string
     */
    private $operator;

    /**
     * Erzeugt einen Unbekannten oder nicht extra definierten Operatoren
     * @param PdfAbstractObject[] $operands Operanden dieses Operators
     * @param string $operator Operator im ContentStream
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(array $operands, string $operator, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->operands = $operands;
        $this->operator = $operator;
    }

    /**
     * Da wir nicht wissen, ob der Operator etwas zeichnet, wird dieser Wert als true angenommen
     * @return bool
     */
    public function isRenderingOperator(): bool
    {
        return true;
    }


    /**
     * Liefert die für den Operator genutzten Operanden
     * @return PdfAbstractObject[]
     */
    public function getOperands() : array {
        return $this->operands;
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        $string = "";
        foreach ($this->operands as $operand)
            $string .= $operand->toString() . " ";
        return $string . $this->operator . "\n";
    }
}