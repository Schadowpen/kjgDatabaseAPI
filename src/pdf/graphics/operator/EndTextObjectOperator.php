<?php


namespace pdf\graphics\operator;

/**
 * Operator zum Beginnen eines Text Objektes. Nur innerhalb eines Text Objektes darf Text gezeichnet werden.
 * Er entfernt den TextObjectState.
 * @package pdf\graphics\operator
 */
class EndTextObjectOperator extends AbstractOperator
{

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "ET";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return "ET\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }
}