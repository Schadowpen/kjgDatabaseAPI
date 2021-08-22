<?php


namespace pdf\document;

use pdf\graphics\Point;
use pdf\indirectObject\PdfStream;
use pdf\object\PdfAbstractObject;
use pdf\object\PdfArray;
use pdf\object\PdfBoolean;
use pdf\object\PdfDictionary;
use pdf\object\PdfName;
use pdf\object\PdfNull;
use pdf\object\PdfNumber;
use pdf\PdfFile;

/**
 * Ein externes Bild, welches in einen ContentStream eingebaut werden kann.
 * @package pdf\document
 */
class XObjectImage extends XObject
{
    public static function objectSubtype(): ?string
    {
        return "Image";
    }

    public function getWidth(): PdfNumber
    {
        return $this->get("Width");
    }

    public function getHeight(): PdfNumber
    {
        return $this->get("Height");
    }

    public function getColorSpace(): ?PdfAbstractObject
    {
        return $this->get("ColorSpace");
    }

    public function getBitsPerComponent(): ?PdfNumber
    {
        return $this->get("BitsPerComponent");
    }

    public function getIntent(): ?PdfName
    {
        return $this->get("Intent");
    }

    public function getImageMask(): ?PdfBoolean
    {
        return $this->get("ImageMask");
    }

    public function getDecode(): ?PdfArray
    {
        return $this->get("Decode");
    }

    public function getInterpolate(): ?PdfBoolean
    {
        return $this->get("Interpolate");
    }

    public function getSMask(): ?PdfStream
    {
        return $this->pdfFile->getIndirectObject($this->dictionary->getObject("SMask"));
    }

    public function getSMaskInData(): ?PdfNumber
    {
        return $this->get("SMaskInData");
    }

    /**
     * Liefert die Ecke links unten vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public function getLowerLeftCorner(): Point
    {
        return new Point(0, 0);
    }

    /**
     * Liefert die Ecke rechts unten vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public function getLowerRightCorner(): Point
    {
        return new Point(1, 0);
    }

    /**
     * Liefert die Ecke links oben vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public function getUpperLeftCorner(): Point
    {
        return new Point(0, 1);
    }

    /**
     * Liefert die Ecke rechts oben vom Externen Objekt im User Space des Do-Operators
     * @return Point
     */
    public function getUpperRightCorner(): Point
    {
        return new Point(1, 1);
    }

    /**
     * Erzeugt ein XObject aus einer PNG-Datei.
     * Dabei wird die Transparenz berücksichtigt.
     * @param string $fileName Name und Pfad zur PNG-Datei
     * @param PdfFile $pdfFile PDF-Datei, in welcher das XObjectImage eingefügt werden soll
     * @return XObjectImage Eingelesenes Bild als XObject
     * @throws \Exception Wenn die PNG-Datei nicht eingelesen oder konvertiert werden kann
     */
    public static function createFromPNG(string $fileName, PdfFile $pdfFile): XObjectImage
    {
        $image = @imagecreatefrompng($fileName);
        if ($image === false)
            throw new \Exception("Image {$fileName} could not be loaded");

        // Preparing Image
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        $imageDictionary = new PdfDictionary([
            "Type" => new PdfName("XObject"),
            "Subtype" => new PdfName("Image"),
            "Width" => new PdfNumber($imageWidth),
            "Height" => new PdfNumber($imageHeight),
            "ColorSpace" => new PdfName("DeviceRGB"),
            "BitsPerComponent" => new PdfNumber(8),
            // "SMask" wird später hinzugefügt
            "Filter" => new PdfName("FlateDecode")
        ]);
        $imageData = "";

        // Preparing Soft Mask (Alpha Channel)
        $softMaskDictionary = new PdfDictionary([
            "Type" => new PdfName("XObject"),
            "Subtype" => new PdfName("Image"),
            "Width" => new PdfNumber($imageWidth),
            "Height" => new PdfNumber($imageHeight),
            "ColorSpace" => new PdfName("DeviceGray"),
            "BitsPerComponent" => new PdfNumber(8),
            "Filter" => new PdfName("FlateDecode")
        ]);
        $softMaskData = "";

        // Alle Pixel durchgehen
        for ($y = 0; $y < $imageHeight; ++$y) {
            for ($x = 0; $x < $imageWidth; ++$x) {
                $pixelColor = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $imageData .= chr($pixelColor["red"]) . chr($pixelColor["green"]) . chr($pixelColor["blue"]);
                $softMaskData .= chr((127 - $pixelColor["alpha"]) << 1);
            }
        }

        // Speichere Soft Mask Stream
        $crossReferenceTableEntry = $pdfFile->generateNewCrossReferenceTableEntry();
        $softMaskStream = new PdfStream($crossReferenceTableEntry->getObjNumber(), $crossReferenceTableEntry->getGenerationNumber(), $softMaskDictionary, $pdfFile, $softMaskData);
        $crossReferenceTableEntry->setReferencedObject($softMaskStream);

        // Speichere Image Stream
        $imageDictionary->setObject("SMask", $softMaskStream->getIndirectReference());
        $crossReferenceTableEntry = $pdfFile->generateNewCrossReferenceTableEntry();
        $stream = new PdfStream($crossReferenceTableEntry->getObjNumber(), $crossReferenceTableEntry->getGenerationNumber(), $imageDictionary, $pdfFile, $imageData);
        $crossReferenceTableEntry->setReferencedObject($stream);

        // Gib XObject zurück
        return new XObjectImage($stream, $pdfFile);
    }

    /*
    public static function createFromPNG(string $fileName, PdfFile $pdfFile): XObjectImage
    {
        $image = @imagecreatefrompng($fileName);
        if ($image === false)
            throw new \Exception("Image {$fileName} could not be loaded");

        // Preparing Image
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        $imageDictionary = new PdfDictionary([
            "Type" => new PdfName("XObject"),
            "Subtype" => new PdfName("Image"),
            "Width" => new PdfNumber($imageWidth),
            "Height" => new PdfNumber($imageHeight),
            "ColorSpace" => new PdfName("DeviceRGB"),
            "BitsPerComponent" => new PdfNumber(8),
            // SMask wird später hinzugefügt
            "Filter" => new PdfName("FlateDecode")
        ]);
        $imageData = "";

        // Preparing Soft Mask (Alpha Channel)
        $softMaskDictionary = new PdfDictionary([
            "Type" => new PdfName("XObject"),
            "Subtype" => new PdfName("Image"),
            "Width" => new PdfNumber($imageWidth),
            "Height" => new PdfNumber($imageHeight),
            "BitsPerComponent" => new PdfNumber(1),
            "ImageMask" => new PdfBoolean(true),
            "Filter" => new PdfName("FlateDecode")
        ]);
        $softMaskData = [];

        // Alle Pixel durchgehen
        for ($y = 0; $y < $imageHeight; ++$y) {
            $softMaskRow = array_fill(0, ($imageWidth+7) / 8, 0);
            for ($x = 0; $x < $imageWidth; ++$x) {
                $pixelColor = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $imageData .= chr($pixelColor["red"]) . chr($pixelColor["green"]) . chr($pixelColor["blue"]);
                if ($pixelColor["alpha"] >= 64)
                    $softMaskRow[$x / 8] += 128 >> ($x % 8);
            }
            $softMaskData = array_merge($softMaskData, $softMaskRow);
        }

        // Speichere Soft Mask Stream
        $bytes = count($softMaskData);
        $softMaskDataString = str_repeat("\x00", $bytes);
        for ($i = 0; $i < $bytes; ++$i) {
            $softMaskDataString[$i] = chr($softMaskData[$i]);
        }
        $crossReferenceTableEntry = $pdfFile->generateNewCrossReferenceTableEntry();
        $softMaskStream = new PdfStream($crossReferenceTableEntry->getObjNumber(), $crossReferenceTableEntry->getGenerationNumber(), $softMaskDictionary, $pdfFile, $softMaskDataString);
        $crossReferenceTableEntry->setReferencedObject($softMaskStream);

        // Speichere Image Stream
        $imageDictionary->setObject("Mask", $softMaskStream->getIndirectReference());
        $crossReferenceTableEntry = $pdfFile->generateNewCrossReferenceTableEntry();
        $stream = new PdfStream($crossReferenceTableEntry->getObjNumber(), $crossReferenceTableEntry->getGenerationNumber(), $imageDictionary, $pdfFile, $imageData);
        $crossReferenceTableEntry->setReferencedObject($stream);

        // Gib XObject zurück
        return new XObjectImage($stream, $pdfFile);
    }
    //*/
}