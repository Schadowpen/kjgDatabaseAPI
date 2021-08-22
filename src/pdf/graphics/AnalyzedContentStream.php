<?php

namespace pdf\graphics;

use misc\Range;
use misc\StringReader;
use pdf\document\ContentStream;
use pdf\graphics\operator\AbstractImageOperator;
use pdf\graphics\operator\AbstractOperator;
use pdf\graphics\operator\AbstractTextOperator;
use pdf\graphics\operator\BeginCompatibilityOperator;
use pdf\graphics\operator\BeginTextObjectOperator;
use pdf\graphics\operator\CharacterSpaceOperator;
use pdf\graphics\operator\ClippingPathEvenOddOperator;
use pdf\graphics\operator\ClippingPathNonzeroOperator;
use pdf\graphics\operator\CloseAndStrokePathOperator;
use pdf\graphics\operator\CloseFillAndStrokePathEvenOddOperator;
use pdf\graphics\operator\CloseFillAndStrokePathNonzeroOperator;
use pdf\graphics\operator\ColorFillingOperator;
use pdf\graphics\operator\ColorRenderingIntentOperator;
use pdf\graphics\operator\ColorRGBFillingOperator;
use pdf\graphics\operator\ColorRGBStrokingOperator;
use pdf\graphics\operator\ColorStrokingOperator;
use pdf\graphics\operator\ComplexTextOperator;
use pdf\graphics\operator\EndCompatibilityOperator;
use pdf\graphics\operator\EndTextObjectOperator;
use pdf\graphics\operator\ExternalGraphicsStateOperator;
use pdf\graphics\operator\ExternalObjectOperator;
use pdf\graphics\operator\FillAndStrokePathEvenOddOperator;
use pdf\graphics\operator\FillAndStrokePathNonzeroOperator;
use pdf\graphics\operator\FillPathEvenOddOperator;
use pdf\graphics\operator\FillPathNonzeroOperator;
use pdf\graphics\operator\FlatnessOperator;
use pdf\graphics\operator\InlineImageOperator;
use pdf\graphics\operator\LineCapOperator;
use pdf\graphics\operator\LineDashPatternOperator;
use pdf\graphics\operator\LineJoinOperator;
use pdf\graphics\operator\LineWidthOperator;
use pdf\graphics\operator\MarkedContentOperator;
use pdf\graphics\operator\MiterLimitOperator;
use pdf\graphics\operator\ModifyTransformationMatrixOperator;
use pdf\graphics\operator\OperatorMetadata;
use pdf\graphics\operator\PathBeginOperator;
use pdf\graphics\operator\PathBezierOperator;
use pdf\graphics\operator\PathCloseOperator;
use pdf\graphics\operator\PathConstructionOperator;
use pdf\graphics\operator\PathEndingOperator;
use pdf\graphics\operator\PathLineOperator;
use pdf\graphics\operator\PathPaintingOperator;
use pdf\graphics\operator\PathRectangleOperator;
use pdf\graphics\operator\PopGraphicsStateOperator;
use pdf\graphics\operator\PushGraphicsStateOperator;
use pdf\graphics\operator\ShadingOperator;
use pdf\graphics\operator\StrokePathOperator;
use pdf\graphics\operator\TextFontOperator;
use pdf\graphics\operator\TextInNewLineOperator;
use pdf\graphics\operator\TextLeadingOperator;
use pdf\graphics\operator\TextMatrixOperator;
use pdf\graphics\operator\TextNewLineAndLeadingOperator;
use pdf\graphics\operator\TextNewLineOperator;
use pdf\graphics\operator\TextNextLineOperator;
use pdf\graphics\operator\TextOperator;
use pdf\graphics\operator\TextRenderModeOperator;
use pdf\graphics\operator\TextRiseOperator;
use pdf\graphics\operator\TextScaleOperator;
use pdf\graphics\operator\TextWithSpacesOperator;
use pdf\graphics\operator\UnknownOperator;
use pdf\graphics\operator\WordSpaceOperator;
use pdf\graphics\state\GraphicsStateStack;
use pdf\object\ObjectParser;
use pdf\object\PdfAbstractObject;
use pdf\object\PdfNumber;
use pdf\object\PdfToken;

/**
 * Ein Content Stream, dessen Grafische Anweisungen analysiert wurden
 * @package pdf\graphics
 */
class AnalyzedContentStream
{
    /**
     * GraphicsStateStack, mit welchem dieser Content Stream begonnen wurde
     * @var GraphicsStateStack
     */
    protected $initialGraphicsStateStack;
    /**
     * GraphicsStateStack, mit welchem dieser Content Stream geendet ist
     * @var GraphicsStateStack
     */
    protected $lastGraphicsStateStack;
    /**
     * Referenz auf den ContentStream, dessen Inhalt hier analysiert und bearbeitet wird.
     * @var ContentStream
     */
    protected $contentStream;
    /**
     * Array mit allen Operatoren in dem ContentStream in korrekter Reihenfolge
     * @var AbstractOperator[]
     */
    protected $operators;
    /**
     * Array mit den Operatoren, die ein Bild zeichnen.
     * @var AbstractImageOperator[]
     */
    protected $imageOperators;
    /**
     * Array mit den Operatoren, die Text zeichnen.
     * @var AbstractTextOperator[]
     */
    protected $textOperators;

    /**
     * AnalyzedContentStream constructor.
     * @param GraphicsStateStack $initialGraphicsStateStack Initialer Wert des GraphicsStateStack
     * @param ContentStream $contentStream ContentStream, dessen Inhalt hier analysiert und bearbeitet wird. Es wird davon ausgegangen, dass dessen Inhalt nicht verändert wird.
     * @throws \Exception Wenn der Content Stream nicht analysiert werden kann
     */
    public function __construct(GraphicsStateStack $initialGraphicsStateStack, ContentStream $contentStream)
    {
        $this->initialGraphicsStateStack = $initialGraphicsStateStack;
        $this->lastGraphicsStateStack = clone $initialGraphicsStateStack;
        $this->contentStream = $contentStream;
        $this->operators = [];
        $this->imageOperators = [];
        $this->textOperators = [];
        $operatorCount = 0;

        // Gehe Content Stream durch
        $stringReader = new StringReader($contentStream->getStream());
        $objectParser = new ObjectParser($stringReader);
        $operatorStartBytePos = 0;
        /** @var PdfAbstractObject[] $operands */
        $operands = [];
        while (!$stringReader->isAtEndOfString()) {
            try {
                $pdfObject = $objectParser->parseObject(true);
            } catch (\Exception $exception) {
                continue;
            }
            if ($pdfObject instanceof PdfToken) {
                $operatorMetadata = new OperatorMetadata($this->lastGraphicsStateStack->getGraphicsState(), $operatorCount, $operatorStartBytePos);

                switch ($pdfObject->getValue()) {
                    // GraphicsState Operatoren
                    case "q":
                        $operator = new PushGraphicsStateOperator($operatorMetadata);
                        break;
                    case "Q":
                        $operator = new PopGraphicsStateOperator($operatorMetadata);
                        break;
                    case "cm":
                        $operator = new ModifyTransformationMatrixOperator(new TransformationMatrix($operands[0]->getValue(), $operands[1]->getValue(), $operands[2]->getValue(), $operands[3]->getValue(), $operands[4]->getValue(), $operands[5]->getValue()), $operatorMetadata);
                        break;
                    case "w":
                        $operator = new LineWidthOperator($operands[0], $operatorMetadata);
                        break;
                    case "J":
                        $operator = new LineCapOperator($operands[0], $operatorMetadata);
                        break;
                    case "j":
                        $operator = new LineJoinOperator($operands[0], $operatorMetadata);
                        break;
                    case "M":
                        $operator = new MiterLimitOperator($operands[0], $operatorMetadata);
                        break;
                    case "d":
                        $operator = new LineDashPatternOperator($operands[0], $operands[1], $operatorMetadata);
                        break;
                    case "ri":
                        $operator = new ColorRenderingIntentOperator($operands[0], $operatorMetadata);
                        break;
                    case "i":
                        $operator = new FlatnessOperator($operands[0], $operatorMetadata);
                        break;
                    case "gs":
                        $operator = ExternalGraphicsStateOperator::constructFromContentStream($operands[0], $this->contentStream, $operatorMetadata);
                        break;

                    // TextState Operatoren
                    case "Tc":
                        $operator = new CharacterSpaceOperator($operands[0], $operatorMetadata);
                        break;
                    case "Tw":
                        $operator = new WordSpaceOperator($operands[0], $operatorMetadata);
                        break;
                    case "Tz":
                        $operator = new TextScaleOperator($operands[0], $operatorMetadata);
                        break;
                    case "TL":
                        $operator = new TextLeadingOperator($operands[0], $operatorMetadata);
                        break;
                    case "Tf":
                        $operator = TextFontOperator::constructFromContentStream($operands[0], $this->contentStream, $operands[1], $operatorMetadata);
                        break;
                    case "Tr":
                        $operator = new TextRenderModeOperator($operands[0], $operatorMetadata);
                        break;
                    case "Ts":
                        $operator = new TextRiseOperator($operands[0], $operatorMetadata);
                        break;

                    // Path Construction
                    case "m":
                        $operator = new PathBeginOperator($operands[0], $operands[1], $operatorMetadata);
                        break;
                    case "l":
                        $operator = new PathLineOperator($operands[0], $operands[1], $operatorMetadata);
                        break;
                    case "c":
                        $operator = new PathBezierOperator($operands[0], $operands[1], $operands[2], $operands[3], $operands[4], $operands[5], $operatorMetadata);
                        break;
                    case "v":
                        $lastPoint = $this->getLastGraphicsStateStack()->getGraphicsState()->getPathConstructionState()->getCurrentPoint();
                        if ($lastPoint === null)
                            throw new \Exception("A Path Construction Operator was found in a Content Stream with no Path to be added to");
                        $operator = new PathBezierOperator(new PdfNumber($lastPoint->x), new PdfNumber($lastPoint->y), $operands[0], $operands[1], $operands[2], $operands[3], $operatorMetadata);
                        break;
                    case "y":
                        $operator = new PathBezierOperator($operands[0], $operands[1], $operands[2], $operands[3], $operands[2], $operands[3], $operatorMetadata);
                        break;
                    case "h":
                        $operator = new PathCloseOperator($operatorMetadata);
                        break;
                    case "re":
                        $operator = new PathRectangleOperator($operands[0], $operands[1], $operands[2], $operands[3], $operatorMetadata);
                        break;

                    // Path Painting
                    case "S":
                        $operator = new StrokePathOperator($operatorMetadata);
                        break;
                    case "s":
                        $operator = new CloseAndStrokePathOperator($operatorMetadata);
                        break;
                    case "f":
                    case "F":
                        $operator = new FillPathNonzeroOperator($operatorMetadata);
                        break;
                    case "f*":
                        $operator = new FillPathEvenOddOperator($operatorMetadata);
                        break;
                    case "B":
                        $operator = new FillAndStrokePathNonzeroOperator($operatorMetadata);
                        break;
                    case "B*":
                        $operator = new FillAndStrokePathEvenOddOperator($operatorMetadata);
                        break;
                    case "b":
                        $operator = new CloseFillAndStrokePathNonzeroOperator($operatorMetadata);
                        break;
                    case "b*":
                        $operator = new CloseFillAndStrokePathEvenOddOperator($operatorMetadata);
                        break;
                    case "n":
                        $operator = new PathEndingOperator($operatorMetadata);
                        break;

                    // Clipping
                    case "W":
                        $operator = new ClippingPathNonzeroOperator($operatorMetadata);
                        break;
                    case "W*":
                        $operator = new ClippingPathEvenOddOperator($operatorMetadata);
                        break;

                    // Color
                    case "CS":
                    case "SC":
                    case "SCN":
                    case "G":
                    case "K":
                        $operator = new ColorStrokingOperator($operands, $pdfObject->getValue(), $operatorMetadata);
                        break;
                    case "RG":
                        $operator = new ColorRGBStrokingOperator(new ColorRGB($operands[0]->getValue(), $operands[1]->getValue(), $operands[2]->getValue()), $operatorMetadata);
                        break;
                    case "cs":
                    case "sc":
                    case "scn":
                    case "g":
                    case "k":
                        $operator = new ColorFillingOperator($operands, $pdfObject->getValue(), $operatorMetadata);
                        break;
                    case "rg":
                        $operator = new ColorRGBFillingOperator(new ColorRGB($operands[0]->getValue(), $operands[1]->getValue(), $operands[2]->getValue()), $operatorMetadata);
                        break;

                    // External Object
                    case "Do":
                        $operator = ExternalObjectOperator::constructFromContentStream($operands[0], $this->contentStream, $operatorMetadata);
                        array_push($this->imageOperators, $operator);
                        break;

                    // Inline Image
                    case "BI":
                        $operator = InlineImageOperator::parse($objectParser, $operatorMetadata);
                        array_push($this->imageOperators, $operator);
                        break;

                    // Shading
                    case "sh":
                        $operator = new ShadingOperator($operands[0], $operatorMetadata);
                        break;

                    // Compatibility
                    case "BX":
                        $operator = new BeginCompatibilityOperator($operatorMetadata);
                        break;
                    case "EX":
                        $operator = new EndCompatibilityOperator($operatorMetadata);
                        break;

                    // Text Objects
                    case "BT":
                        $operator = new BeginTextObjectOperator($operatorMetadata);
                        break;
                    case "ET":
                        $operator = new EndTextObjectOperator($operatorMetadata);
                        break;

                    // Text Positioning
                    case "Td":
                        $operator = new TextNewLineOperator($operands[0], $operands[1], $operatorMetadata);
                        break;
                    case "TD":
                        $operator = new TextNewLineAndLeadingOperator($operands[0], $operands[1], $operatorMetadata);
                        break;
                    case "Tm":
                        $operator = new TextMatrixOperator(new TransformationMatrix($operands[0]->getValue(), $operands[1]->getValue(), $operands[2]->getValue(), $operands[3]->getValue(), $operands[4]->getValue(), $operands[5]->getValue()), $operatorMetadata);
                        break;
                    case "T*":
                        $operator = new TextNextLineOperator($operatorMetadata);
                        break;

                    // Text Showing
                    case "Tj":
                        $operator = new TextOperator($operands[0], $operatorMetadata);
                        array_push($this->textOperators, $operator);
                        break;
                    case "TJ":
                        $operator = new TextWithSpacesOperator($operands[0], $operatorMetadata);
                        array_push($this->textOperators, $operator);
                        break;
                    case "'":
                        $operator = new TextInNewLineOperator($operands[0], $operatorMetadata);
                        array_push($this->textOperators, $operator);
                        break;
                    case "\"":
                        $operator = new ComplexTextOperator($operands[0], $operands[1], $operands[2], $operatorMetadata);
                        array_push($this->textOperators, $operator);
                        break;

                    // Marked Content
                    case "MP":
                    case "DP":
                    case "BMC":
                    case "BDC":
                    case "EMC":
                        $operator = new MarkedContentOperator($operands, $pdfObject->getValue(), $operatorMetadata);
                        break;

                    // Type 3 Font Operatoren kommen nur in Content Streams vor, die einen Glyphen dieses Fonts beschreiben
                    case "d0":
                    case "d1":

                    default:
                        $operator = new UnknownOperator($operands, $pdfObject->getValue(), $operatorMetadata);
                        break;
                }
                if ($operator->isGraphicsStateOperator())
                    $this->lastGraphicsStateStack->reactToOperator($operator);

                $this->operators[$operatorCount] = $operator;
                $operatorStartBytePos = $stringReader->getReaderPos();
                ++$operatorCount;
                $operands = [];

            } else {
                array_push($operands, $pdfObject);
            }
        }
        // Kein weiteres PdfObjekt gefunden
        if ($operands !== [])
            throw new \Exception("Operands without Operator left at End of ContentStream");
    }

    /**
     * Liefert den Initialen Wert des GraphicsStateStack
     * @return GraphicsStateStack
     */
    public function getInitialGraphicsStateStack(): GraphicsStateStack
    {
        return $this->initialGraphicsStateStack;
    }

    /**
     * Liefert den GraphicsStateStack nach durchlaufen des ContentStreams
     * @return GraphicsStateStack
     */
    public function getLastGraphicsStateStack(): GraphicsStateStack
    {
        return $this->lastGraphicsStateStack;
    }

    /**
     * Liefert den analysierten ContentStream
     * @return ContentStream
     */
    public function getContentStream(): ContentStream
    {
        return $this->contentStream;
    }

    /**
     * Liefert den Operatoren an dem Index
     * @param int $index Zählnummer des Operators im ContentStream
     * @return AbstractOperator
     */
    public function getOperator(int $index): AbstractOperator
    {
        return $this->operators[$index];
    }

    /**
     * Liefert die Anzahl der definierten Operatoren in diesem ContentStream
     * @return int
     */
    public function getOperatorCount(): int
    {
        return count($this->operators);
    }

    /**
     * Liefert alle Operatoren, die ein Bild zeichnen.
     * @return AbstractImageOperator[]
     */
    public function getImageOperators(): array
    {
        return $this->imageOperators;
    }

    /**
     * Liefert alle Operatoren, die Text zeichnen.
     * @return AbstractTextOperator[]
     */
    public function getTextOperators(): array
    {
        return $this->textOperators;
    }

    /**
     * Liefert den Bereich um einen Operatoren, der zusammen mit dem Operatoren gelöscht werden kann
     * @param int $operatorNumber Nummer des zu löschenden Operators, sollte ein existierender Operator sein
     * @return Range Bereich um den zu löschenden Operatoren, enthält mindestens den Operatoren selbst
     */
    public function getDeletableRangeForOperator(int $operatorNumber): Range
    {
        $range = new Range($operatorNumber, $operatorNumber + 1);
        $this->expandDeletableRange($range);
        return $range;
    }

    /**
     * Erweitert den deletableRange um die davor liegenden Operatoren zum verändern des GraphicsState.
     * Dies passiert nur, wenn direkt hinter dem Range ein Q-Operator kommt, um einen Einfluss durch das Löschen komplett ausschließen zu können.
     * Ist der vorherige nicht-reiner-GraphicsState Operator ein RenderingOperator oder ein weiterer Q-Operator, wird der Bereich bis direkt vor diesen ausgeweitet.
     * Ist dieser ein q-Operator, werden q- und Q-Operator (am Ende) mit in den Range eingefügt und die Funktion rekursiv nochmal aufgerufen.
     * @param Range $range Range-Objekt, welches erweitert wird.
     * @see AnalyzedContentStream::getDeletableRangeForOperator() Helferfunktion für
     */
    private function expandDeletableRange(Range & $range)
    {
        // Überprüfe ob dahinter ein Q Operator ist
        if (count($this->operators) === $range->getEndIndex() || !($this->operators[$range->getEndIndex()] instanceof PopGraphicsStateOperator))
            return; // kein Q dahinter

        // Überprüfe ob davor gelöscht werden kann
        do {
            $prevOperator = $this->operators[$range->getStartIndex() - 1];
            if ($prevOperator->isRenderingOperator() || $prevOperator instanceof PopGraphicsStateOperator)
                return; // Bereich zwischen dem letzten vorherigen RenderingOperator oder Q und Q wird gelöscht
            $range->decreaseStartIndex();
        } while (!($prevOperator instanceof PushGraphicsStateOperator));
        // q am Anfang und Q am Ende werden auch entfernt, Überprüfung ob noch ein Bereich entfernt werden kann.
        $range->increaseEndIndex();
        $this->expandDeletableRange($range);
    }

    /**
     * Löscht alle Operatoren in einem bestimmten Bereich.
     * Dabei werden die Indexe der Operatoren erhalten, die Positionen im Content Stream jedoch neu ermittelt.
     * Beachte, dass nach Aufruf dieser Funktion die Arrayposition in $operators und $operator->getOperatorNumber() nicht mehr übereinstimmen.<br/>
     * <b>Achtung!</b> Es wird nicht überprüft, ob das Löschen Auswirkungen auf den GraphicsState hätte! Es wird angenommen, dass der Bereich von getDeletableRangeForOperator() ermittelt wurde!
     * @param int $startIndex Start des Bereiches zu löschender Operatoren (inklusive)
     * @param int $endIndex Ende des Bereiches zu löschender Operatoren (exklusive)
     * @throws \Exception Wenn die Operatoren nicht gelöscht werden können
     */
    public function deleteOperators(int $startIndex, int $endIndex)
    {
        $contentStream = $this->contentStream->getStream();
        $operatorCount = count($this->operators);
        // Finde Operatoren für startIndex und endIndex
        $i = 0;
        while ($this->operators[$i]->getOperatorNumber() < $startIndex) {
            ++$i;
            if ($i === $operatorCount)
                throw new \Exception("reached End of Content Stream without finding Operator for deleting");
        }
        $startOperator = $i;
        $startBytePos = $this->operators[$i]->getBytePositionInStream();
        while ($i < $operatorCount && $this->operators[$i]->getOperatorNumber() < $endIndex) {
            ++$i;
        }
        $endOperator = $i;
        // Passe Content Stream und Bytepositionen der folgenden Operatoren an.
        if ($endOperator < $operatorCount) {
            $endBytePos = $this->operators[$endOperator]->getBytePositionInStream();
            $deletedBytesLength = $endBytePos - $startBytePos;
            for (; $i < $operatorCount; ++$i)
                $this->operators[$i]->setBytePositionInStream($this->operators[$i]->getBytePositionInStream() - $deletedBytesLength);
            $contentStream = substr($contentStream, 0, $startBytePos) . substr($contentStream, $endBytePos);

        } else {
            $contentStream = substr($contentStream, 0, $startBytePos);
        }
        // Entferne Operatoren von Content Stream und Arrays
        $this->contentStream->setStream($contentStream);
        $deletedOperators = array_splice($this->operators, $startOperator, $endOperator - $startOperator);
        $this->imageOperators = array_diff($this->imageOperators, $deletedOperators);
        $this->textOperators = array_diff($this->textOperators, $deletedOperators);
    }
}