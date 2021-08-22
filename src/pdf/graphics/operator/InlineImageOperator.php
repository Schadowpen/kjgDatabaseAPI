<?php
namespace pdf\graphics\operator;

use Exception;
use pdf\graphics\Point;
use pdf\object\ObjectParser;
use pdf\object\PdfAbstractObject;
use pdf\object\PdfArray;
use pdf\object\PdfBoolean;
use pdf\object\PdfName;
use pdf\object\PdfNumber;
use pdf\object\PdfToken;
use pdf\object\Tokenizer;

class InlineImageOperator extends AbstractImageOperator
{
    /**
     * Daten zu dem InlineImage, gespeichert zwischen BI und ID
     * @var PdfAbstractObject[]
     */
    protected $imageConfig;
    /**
     * Das Image in Rohdaten, nicht dekomprimiert, gespeichert zwischen ID und EI
     * @var string
     */
    protected $imageData;

    /**
     * InlineImageOperator constructor.
     * @param array $imageConfig Daten zu dem InlineImage, gespeichert zwischen BI und ID
     * @param string $imageData Das Image in Rohdaten, nicht dekomprimiert, gespeichert zwischen ID und EI
     * @param OperatorMetadata|null $operatorMetadata Metadaten zu einem Operatoren, wenn ein ContentStream analysiert wird. Wird nicht benötigt für einen neu generierten ContentStream.
     */
    public function __construct(array $imageConfig, string $imageData, OperatorMetadata $operatorMetadata = null)
    {
        parent::__construct($operatorMetadata);
        $this->imageConfig = $imageConfig;
        $this->imageData = $imageData;
    }

    /**
     * Parst ein Inline Image aus einem Content Stream.
     * Es wird angenommen, dass der BI-Operator bereits geparst wurde.
     * @param ObjectParser $objectParser ObjectParser, welcher am Ende des BI-Operators steht zum weiteren auslesen. Wird nach Beendigung der Methode hinter dem EI-Operator stehen.
     * @param OperatorMetadata $operatorMetadata
     * @return InlineImageOperator
     * @throws \Exception
     */
    public static function parse(ObjectParser $objectParser, OperatorMetadata $operatorMetadata)
    {
        $imageConfig = [];
        $parsedObject = $objectParser->parseObject(true);

        while (!($parsedObject instanceof PdfToken)) {
            $key = $parsedObject->getValue();
            $value = $objectParser->parseObject();
            $imageConfig[$key] = $value;
            // Parse nächstes Objekt zur Überprüfung, ob ID Operator bereits erreicht
            $parsedObject = $objectParser->parseObject(true);
        }
        if ($parsedObject->getValue() !== "ID")
            throw new Exception("Operator for Image Data not found!");

        $objectParser->getStringReader()->skipByte(); // Skip single White Space
        $imageData = $objectParser->getStringReader()->readUntilMask(Tokenizer::whiteSpaceChars);
        if ($objectParser->getTokenizer()->getToken() !== "EI")
            throw new Exception("Operator for End Image not found!");

        return new InlineImageOperator($imageConfig, $imageData, $operatorMetadata);
    }

    /**
     * Erzeugt ein InlineImage aus einem QR-Code
     * @param string[] $qrCode Der QR-Code, wobei jeder String eine Pixelreihe bedeutet und aus den Zeichen "0" und "1" besteht.
     * @return InlineImageOperator Operator, der den QR-Code als Bild zeichnet.
     * @throws Exception Wird praktisch nie geschmissen
     */
    public static function getFromQRCode(array $qrCode) : InlineImageOperator {
        $imageSize = count($qrCode);
        $imageConfig = [
            "BPC" => new PdfNumber(1),
            "CS" => new PdfName("DeviceGray"),
            "W" => new PdfNumber($imageSize),
            "H" => new PdfNumber($imageSize),
            "I" => new PdfBoolean(false)
        ];
        $imageData = [];
        for ($y = 0; $y < $imageSize; ++$y) {
            $imageRow = array_fill(0, ($imageSize+7) / 8, 0);
            for ($x = 0; $x < $imageSize; ++$x) {
                if ($qrCode[$y][$x] === "0") {
                    $imageRow[$x / 8] += 128 >> ($x % 8);
                }
            }
            $imageData = array_merge($imageData, $imageRow);
        }
        $bytes = count($imageData);
        $imageDataString = str_repeat("\x00", $bytes);
        for ($i = 0; $i < $bytes; ++$i) {
            $imageDataString[$i] = chr($imageData[$i]);
        }
        return new InlineImageOperator($imageConfig, $imageDataString);
    }

    /**
     * Liefert den Operatoren, wie er im ContentStream vorkommt
     * @return string
     */
    function getOperator(): string
    {
        return "BI";
    }

    /**
     * Parst den Operatoren zu einem String, wie er in einem ContentStream vorkommt
     * @return string
     * @throws \Exception Sollte theoretisch nicht passieren
     */
    function __toString(): string
    {
        $string = "BI\n";
        foreach ($this->imageConfig as $key => $value) {
            $string .= (new PdfName($key))->toString() . " " . $value->toString() . "\n";
        }
        $string .= "ID\n" . $this->imageData . "\nEI\n";
        return $string;
    }

    public function getBitsPerComponent(): ?PdfNumber
    {
        return @$this->imageConfig["BPC"];
    }

    public function getColorSpace(): ?PdfName
    {
        return $this->decodeAbbreviation(@$this->imageConfig["CS"]);
    }

    public function getDecode(): ?PdfArray
    {
        return @$this->imageConfig["D"];
    }

    public function getDecodeParms(): ?PdfAbstractObject
    {
        return @$this->imageConfig["DP"];
    }

    public function getFilter(): ?PdfAbstractObject
    {
        $abbreviatedFilters = @$this->imageConfig["F"];
        if ($abbreviatedFilters instanceof PdfArray) {
            for ($i = 0; $i < $abbreviatedFilters->getArraySize(); ++$i)
                $abbreviatedFilters->setObject($i, $this->decodeAbbreviation($abbreviatedFilters->getObject($i)));
            return $abbreviatedFilters;
        }
        return $this->decodeAbbreviation($abbreviatedFilters);
    }

    public function getHeight(): PdfNumber
    {
        return $this->imageConfig["H"];
    }

    public function getImageMask(): ?PdfBoolean
    {
        return @$this->imageConfig["IM"];
    }

    public function getIntent(): ?PdfName
    {
        return @$this->imageConfig["Intent"];
    }

    public function getInterpolate(): ?PdfBoolean
    {
        return @$this->imageConfig["I"];
    }

    public function getWidth(): PdfNumber
    {
        return $this->imageConfig["W"];
    }

    public function decodeAbbreviation(?PdfName $abbreviated): ?PdfName
    {
        if ($abbreviated === null)
            return null;
        switch ($abbreviated->getValue()) {
            case "G":
                return new PdfName("DeviceGray");
            case "RGB":
                return new PdfName("DeviceRGB");
            case "CMYK":
                return new PdfName("DeviceCMYK");
            case "I":
                return new PdfName("Indexed");
            case "AHx":
                return new PdfName("ASCIIHexDecode");
            case "A85":
                return new PdfName("ASCII85Decode");
            case "LZW":
                return new PdfName("LZWDecode");
            case "Fl":
                return new PdfName("FlateDecode");
            case "RL":
                return new PdfName("RunLengthDecode");
            case "CCF":
                return new PdfName("CCITTFaxDecode");
            case "DCT":
                return new PdfName("DCTDecode");
            default:
                return $abbreviated;
        }
    }

    /**
     * Liefert den Punkt unten links in Device Space
     * @return Point
     */
    function getLowerLeftCorner(): Point
    {
        return new Point(0, 0);
    }

    /**
     * Liefert den Punkt unten rechts in Device Space
     * @return Point
     */
    function getLowerRightCorner(): Point
    {
        return new Point(1, 0);
    }

    /**
     * Liefert den Punkt oben links in Device Space
     * @return Point
     */
    function getUpperLeftCorner(): Point
    {
        return new Point(0, 1);
    }

    /**
     * Liefert den Punkt oben rechts in Device Space
     * @return Point
     */
    function getUpperRightCorner(): Point
    {
        return new Point(1, 1);
    }

    /**
     * Liefert den Namen des Bildes. Namen müssen in einem Content Stream nicht einzigartig sein.
     * @return string
     */
    function getName(): string
    {
        return "Inline Image";
    }
}