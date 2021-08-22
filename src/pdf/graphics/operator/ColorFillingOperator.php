<?php


namespace pdf\graphics\operator;

/**
 * Operator zum Setzen des Farbraums und/oder Farbe für Flächen zeichnen
 * @package pdf\graphics\operator
 */
class ColorFillingOperator extends UnknownOperator
{
    public function isGraphicsStateOperator(): bool
    {
        return true;
    }
    public function isRenderingOperator(): bool
    {
        return false;
    }
}