<?php


namespace pdf\graphics\operator;

/**
 * Operator zum Beenden eines Pfades, ohne ihn zu zeichnen.
 * @package pdf\graphics\operator
 */
class PathEndingOperator extends AbstractOperator
{

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "n";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return "n\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }
}