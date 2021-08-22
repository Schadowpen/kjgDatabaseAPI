<?php

namespace pdf;

use pdf\document\DocumentCatalog;
use pdf\document\DocumentInfo;
use pdf\document\PageTree;

/**
 * Klasse zum Analysieren und Bearbeiten einer PDF auf Dokumentenebene
 * @package pdf
 */
class PdfDocument
{
    /**
     * Referenz auf die zugehörige PdfFile mit den genutzten Objekten.
     * @var PdfFile
     */
    private $pdfFile;

    /**
     * Der Dokument Katalog der PDF-Datei
     * @var DocumentCatalog
     */
    private $documentCatalog;

    /**
     * Metadaten zu dem Dokument
     * @var DocumentInfo
     */
    private $documentInfo;

    /**
     * Die Liste mit allen Seiten, die in dem PDF-Dokument enthalten sind
     * @var PageTree
     */
    private $pageList;

    /**
     * Erzeugt ein Objekt zum Analysieren und Bearbeiten der gegebenen PDF auf Dokumentenebene
     * @param PdfFile $pdfFile PDF-Datei, mit der das PDF-Dokument arbeitet
     * @throws \Exception Wenn das Dokument nicht initialisiert werden kann
     */
    public function __construct(PdfFile $pdfFile)
    {
        $this->pdfFile = $pdfFile;
        $this->documentCatalog = new DocumentCatalog($pdfFile->getIndirectObject($this->pdfFile->getTrailerDictionary()->getObject("Root")), $pdfFile);
        $this->documentInfo = new DocumentInfo($pdfFile->getTrailerDictionary()->getObject("Info"), $pdfFile);
        $this->pageList = new PageTree($pdfFile->getIndirectObject($this->documentCatalog->getPages()), $pdfFile);
    }

    /**
     * Erzeugt aus dem PdfDocument eine PDF-Datei.
     * Vorher werden einige Optimierungen bez. Größe der PDF-Datei durchgeführt.<br/>
     * <b>Nach Aufruf dieser Funktion sollte das PdfDocument nicht mehr verwendet werden!<b/>
     * @param bool $allowDataCompression Ob erlaubt werden soll, die Daten in Object Streams zu komprimieren, Standardwert true
     * @return string Inhalt der PDF-Datei
     * @throws \Exception Wenn die Datei nicht gespeichert werden konnte
     */
    public function generatePdfFile (bool $allowDataCompression = true) {
        // Optimierung
        $this->documentCatalog->setDefaults();
        $pageCount = $this->pageList->getPageCount();
        for ($i = 0; $i < $pageCount; ++$i)
            $this->pageList->getPage($i)->setDefaults();

        $this->pageList->upliftInheritableAttributes();
        $this->pdfFile->removeUnusedObjects();

        // Speichern
        return $this->pdfFile->savePdfFile($allowDataCompression);
    }

    /**
     * Liefert die im Hintergrund genutzte PdfFile
     * @return PdfFile
     */
    public function getPdfFile(): PdfFile
    {
        return $this->pdfFile;
    }

    /**
     * Liefert den Dokument Catalog der PDF-Datei
     * @return DocumentCatalog
     */
    public function getDocumentCatalog(): DocumentCatalog
    {
        return $this->documentCatalog;
    }

    /**
     * Liefert die Metadaten zu der PDF-Datei
     * @return DocumentInfo
     */
    public function getDocumentInfo(): DocumentInfo {
        return $this->documentInfo;
    }

    /**
     * Liefert die Liste mit allen Seiten der PDF
     * @return PageTree
     */
    public function getPageList(): PageTree
    {
        return $this->pageList;
    }
}