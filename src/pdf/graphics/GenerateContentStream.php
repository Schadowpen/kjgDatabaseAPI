<?php
namespace pdf\graphics;


use pdf\document\ContentStream;
use pdf\document\ResourceDictionary;
use pdf\graphics\operator\AbstractOperator;
use pdf\graphics\state\GraphicsStateStack;
use pdf\indirectObject\PdfStream;
use pdf\object\PdfDictionary;
use pdf\PdfFile;

/**
 * Diese Klasse dient dazu, einen ContentStream zu generieren.
 * Dazu müssen mit der Funktion addOperator() neue Operatoren an das Ende des ContentStreams angehangen werden.
 * @package pdf\graphics
 */
class GenerateContentStream
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
     * Der String mit den Inhalten im ContentStream. Entspricht zwar $contentStream->getStream(), dürfte aber addOperator() dezent beschleunigen
     * @var string
     */
    protected $contentString;

    /**
     * GeneratedContentStream constructor.
     * @param GraphicsStateStack $initialGraphicsStateStack Initialer Wert des GraphicsStateStack für den ContentStream
     * @param ContentStream $contentStream ContentStream, dessen Ihnalt noch generiert werden wird.
     */
    public function __construct(GraphicsStateStack $initialGraphicsStateStack, ContentStream $contentStream)
    {
        $this->initialGraphicsStateStack = $initialGraphicsStateStack;
        $this->lastGraphicsStateStack = clone $initialGraphicsStateStack;
        $this->contentStream = $contentStream;
        $this->contentString = "";
    }

    /**
     * Erzeugt einen neuen, zu generierenden ContentStream.
     * Dabei werden zugehörige Objekte wie Stream oder CrossReferenceTableEntry ebenfalls neu erzeugt.
     * @param GraphicsStateStack $initialGraphicsStateStack Initialer Wert des GraphicsStateStack für den ContentStream
     * @param ResourceDictionary $resourceDictionary Resource Dictionary, welches für diesen ContentStream bereits gilt.
     * @param PdfFile $pdfFile PDF-Datei, in welcher der ContentStream vorkommen soll
     * @return GenerateContentStream Klasse zum generieren eines ContentStreams
     * @throws \Exception Wenn der ContentStream nicht erzeugt werden kann.
     */
    public static function generateNew(GraphicsStateStack $initialGraphicsStateStack, ResourceDictionary $resourceDictionary, PdfFile $pdfFile): GenerateContentStream {
        $crossReferenceTableEntry = $pdfFile->generateNewCrossReferenceTableEntry();
        $pdfStream = PdfStream::fromCrossReferenceTableEntry($crossReferenceTableEntry, $pdfFile);
        $contentStream = new ContentStream($pdfStream, $pdfFile, $resourceDictionary->clone());
        return new GenerateContentStream($initialGraphicsStateStack, $contentStream);
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
     * Liefert den GraphicsStateStack nach durchlaufen des ContentStreams beziehungsweise beim aktuellen Zustand des Generierten ContentStreams
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
     * Fügt einen neuen Operatoren an das Ende des Content Streams an.
     * @param AbstractOperator $operator
     * @throws \Exception Wenn der Operator den GraphicsState beeinflussen soll, dieser aber nichts damit anzufangen weiss.
     */
    public function addOperator(AbstractOperator $operator) {
        // Add to Content Stream
        $this->contentString .= $operator->__toString();
        $this->contentStream->setStream($this->contentString);

        // Add to Graphics State
        if ($operator->isGraphicsStateOperator())
            $this->lastGraphicsStateStack->reactToOperator($operator);
    }
}