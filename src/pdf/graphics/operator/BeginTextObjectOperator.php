<?php


namespace pdf\graphics\operator;

/**
 * Operator zum Beginnen eines Text Objektes. Nur innerhalb eines Text Objektes darf Text gezeichnet werden.
 * Er initialisiert den TextObjectState.
 * @package pdf\graphics\operator
 */
class BeginTextObjectOperator extends AbstractOperator
{

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "BT";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return "BT\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }
}