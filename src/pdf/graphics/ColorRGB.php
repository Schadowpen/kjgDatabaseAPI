<?php


namespace pdf\graphics;

/**
 * Farbe im Farbraum mit Rot, Grün und Blau
 * @package pdf\graphics
 */
class ColorRGB extends Color
{
    /**
     * Rotwert im Bereich 0 bis 1
     * @var float
     */
    protected $red;
    /**
     * Grünwert im Bereich 0 bis 1
     * @var float
     */
    protected $green;
    /**
     * Blauwert im Bereich 0 bis 1
     * @var float
     */
    protected $blue;

    /**
     * ColorRGB constructor.
     * @param float $red Rotwert im Bereich 0 bis 1
     * @param float $green Grünwert im Bereich 0 bis 1
     * @param float $blue Blauwert im Bereich 0 bis 1
     */
    public function __construct(float $red, float $green, float $blue)
    {
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
    }

    /**
     * Erzeugt eine Farbe aus einer Hexadezimalen Darstellung der Form #rrggbb
     * @param string $hex Hexadezimaler String von #000000 bis #ffffff
     * @return ColorRGB Farbe entsprechend dem übergebenen String
     */
    public static function fromHex(string $hex) : ColorRGB
    {
        $dec = hexdec(substr($hex, 1));
        $r = ($dec >> 16) & 0xFF;
        $g = ($dec >> 8) & 0xFF;
        $b = $dec & 0xFF;
        return new ColorRGB($r / 255.0, $g / 255.0, $b / 255.0);
    }

    /**
     * @return float
     */
    public function getRed() : float
    {
        return $this->red;
    }

    /**
     * @return float
     */
    public function getGreen() : float
    {
        return $this->green;
    }

    /**
     * @return float
     */
    public function getBlue() : float
    {
        return $this->blue;
    }
}