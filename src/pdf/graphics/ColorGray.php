<?php


namespace pdf\graphics;

/**
 * Farbe in Graustufen
 * @package pdf\graphics
 */
class ColorGray extends Color
{
    /**
     * Grauwert im Bereich 0 bis 1
     * @var float
     */
    protected $gray;

    /**
     * ColorGray constructor.
     * @param float $gray Grauwert im Bereich 0 bis 1
     */
    public function __construct(float $gray)
    {
        $this->gray = $gray;
    }

    /**
     * @return float
     */
    public function getGray(): float
    {
        return $this->gray;
    }
}