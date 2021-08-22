<?php
namespace pdf\document;

use pdf\graphics\Point;
use pdf\object\PdfArray;

/**
 * Ein Rectangle aus einem PDF-Dokument
 * @package pdf\document
 */
class PdfRectangle
{
    private $lowerLeftX;
    private $lowerLeftY;
    private $upperRightX;
    private $upperRightY;

    /**
     * Erzeugt ein neues Rectangle mit den angegebenen Koordinaten
     * @param float $lowerLeftX
     * @param float $lowerLeftY
     * @param float $upperRightX
     * @param float $upperRightY
     */
    public function __construct(float $lowerLeftX, float $lowerLeftY, float $upperRightX, float $upperRightY)
    {
        $this->lowerLeftX = $lowerLeftX;
        $this->lowerLeftY = $lowerLeftY;
        $this->upperRightX = $upperRightX;
        $this->upperRightY = $upperRightY;
    }

    /**
     * Erzeugt ein PdfRectangle aus dem angegebenen Array.
     * Es wird nicht überprüft, ob das Array tatsächlich ein Rectangle ist
     * @param PdfArray $pdfArray
     * @return PdfRectangle
     */
    public static function parsePdfArray(PdfArray $pdfArray) : PdfRectangle {
        return new PdfRectangle(
            min($pdfArray->getObject(0)->getValue(), $pdfArray->getObject(2)->getValue()),
            min($pdfArray->getObject(1)->getValue(), $pdfArray->getObject(3)->getValue()),
            max($pdfArray->getObject(0)->getValue(), $pdfArray->getObject(2)->getValue()),
            max($pdfArray->getObject(1)->getValue(), $pdfArray->getObject(3)->getValue())
        );
    }


    /**
     * @return float
     */
    public function getLowerLeftX(): float
    {
        return $this->lowerLeftX;
    }

    /**
     * @param float $lowerLeftX
     */
    public function setLowerLeftX(float $lowerLeftX): void
    {
        $this->lowerLeftX = $lowerLeftX;
    }

    /**
     * @return float
     */
    public function getLowerLeftY(): float
    {
        return $this->lowerLeftY;
    }

    /**
     * @param float $lowerLeftY
     */
    public function setLowerLeftY(float $lowerLeftY): void
    {
        $this->lowerLeftY = $lowerLeftY;
    }

    /**
     * @return float
     */
    public function getUpperRightX(): float
    {
        return $this->upperRightX;
    }

    /**
     * @param float $upperRightX
     */
    public function setUpperRightX(float $upperRightX): void
    {
        $this->upperRightX = $upperRightX;
    }

    /**
     * @return float
     */
    public function getUpperRightY(): float
    {
        return $this->upperRightY;
    }

    /**
     * @param float $upperRightY
     */
    public function setUpperRightY(float $upperRightY): void
    {
        $this->upperRightY = $upperRightY;
    }

    /**
     * @return Point
     */
    public function getLowerLeftPoint() : Point
    {
        return new Point($this->lowerLeftX, $this->lowerLeftY);
    }

    /**
     * @return Point
     */
    public function getLowerRightPoint() : Point
    {
        return new Point($this->upperRightX, $this->lowerLeftY);
    }

    /**
     * @return Point
     */
    public function getUpperLeftPoint() : Point
    {
        return new Point($this->lowerLeftX, $this->upperRightY);
    }

    /**
     * @return Point
     */
    public function getUpperRightPoint() : Point
    {
        return new Point($this->upperRightX, $this->upperRightY);
    }
}