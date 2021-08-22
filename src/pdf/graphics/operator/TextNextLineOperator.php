<?php


namespace pdf\graphics\operator;

/**
 * Operator zum Beginnen einer neuen Zeile in einem Text Objekt. Diese Zeile ist entsprechend dem Text Leading tiefer
 * @package pdf\graphics\operator
 */
class TextNextLineOperator extends AbstractOperator
{

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "T*";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return "T*\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }
}