<?php
namespace pdf\graphics\operator;

/**
 * Befehl, um eine Kopie des aktuellen Graphics State auf den GraphicsStateStack zu pushen
 * @package pdf\graphics\operator
 */
class PushGraphicsStateOperator extends AbstractOperator
{

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "q";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return "q\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }
}