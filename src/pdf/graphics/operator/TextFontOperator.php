<?php


namespace pdf\graphics\operator;

use pdf\document\ContentStream;
use pdf\document\Font;
use pdf\object\PdfName;
use pdf\object\PdfNumber;

/**
 * Operator zum Setzen der Schriftart und der Schriftgröße
 * @package pdf\graphics\operator
 */
class TextFontOperator extends AbstractOperator
{
    /**
     * Name, unter welchem die Schriftart im Resource Dictionary gefunden werden kann
     * @var PdfName
     */
    protected $fontName;
    /**
     * Objekt, welches die Schriftart beinhaltet.
     * @var Font
     */
    protected $fontResource;
    /**
     * Schriftgröße
     * @var PdfNumber
     */
    protected $fontSize;

    /**
     * TextFontOperator constructor.
     * @param PdfName $fontName Name, unter welchem die Schriftart im Resource Dictionary gefunden werden kann
     * @param Font $fontResource Objekt, welches die Schriftart beinhaltet.
     * @param PdfNumber $fontSize Schriftgröße
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(PdfName $fontName, Font $fontResource, PdfNumber $fontSize, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->fontName = $fontName;
        $this->fontResource = $fontResource;
        $this->fontSize = $fontSize;
    }

    /**
     * Erzeugt einen TextFontOperator, wobei die FontRessource aus dem ResourceDictionary eines ContentStreams ausgelesen wird.
     * @param PdfName $fontName Name, unter welchem die Schriftart im Resource Dictionary gefunden werden kann
     * @param ContentStream $contentStream ContentStream mit dem ResourceDictionary, aus welchem die FontRessource ausgelesen wird.
     * @param PdfNumber $fontSize Schriftgröße
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     * @return TextFontOperator
     * @throws \Exception Wenn der Font nicht bekommen werden kann
     */
    public static function constructFromContentStream(PdfName $fontName, ContentStream $contentStream, PdfNumber $fontSize, OperatorMetadata $operatorMetadata = null) : TextFontOperator {
        $fontResource = $contentStream->getResourceDictionary()->getFont($fontName->getValue());
        return new TextFontOperator($fontName, $fontResource, $fontSize, $operatorMetadata);
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "Tf";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     */
    function __toString(): string
    {
        return $this->fontName->toString() . " " . $this->fontSize->toString() . " Tf\n";
    }

    public function isGraphicsStateOperator(): bool
    {
        return true;
    }

    /**
     * @return PdfName
     */
    public function getFontName(): PdfName
    {
        return $this->fontName;
    }

    /**
     * @return Font
     */
    public function getFontResource()
    {
        return $this->fontResource;
    }

    /**
     * @return PdfNumber
     */
    public function getFontSize(): PdfNumber
    {
        return $this->fontSize;
    }
}