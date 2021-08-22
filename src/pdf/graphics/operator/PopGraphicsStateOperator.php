<?php
namespace pdf\graphics\operator;

/**
 * Befehl, um den obersten Graphics State auf dem GraphicsStateStack zu entfernen.
 * @package pdf\graphics\operator
 */
class PopGraphicsStateOperator extends AbstractOperator
{

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "Q";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return "Q\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }
}