<?php


namespace pdf\graphics\operator;

/**
 * Operator zum Beenden einer Kompabilitätszone, in welcher Fehler abgefangen werden und nur dazu führen, dass die nicht erkannten Operatoren nicht ausgeführt werden.
 * @package pdf\graphics\operator
 */
class EndCompatibilityOperator extends AbstractOperator
{

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "EX";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return "EX\n";
    }
}