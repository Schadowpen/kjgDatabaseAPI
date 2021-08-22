<?php
namespace pdf\document;

use pdf\indirectObject\PdfStream;
use pdf\object\PdfDictionary;
use pdf\object\PdfIndirectReference;
use pdf\PdfFile;

/**
 * Kapselt einen einzelnen Content Stream.
 * @package pdf\document
 */
class ContentStream extends AbstractDocumentStream
{
    /**
     * Ressourcen, die von dem ContentStream genutzt werden
     * @var ResourceDictionary
     */
    protected $resourceDictionary;

    public static function objectType(): ?string
    {
        return null;
    }

    public static function objectSubtype(): ?string
    {
        return null;
    }

    /**
     * ContentStream constructor.
     * @param PdfIndirectReference|PdfStream $pdfObject Referenz auf oder Stream mit dem Content
     * @param PdfFile $pdfFile PdfFile, in welchem der ContentStream genutzt wird
     * @param ResourceDictionary $resourceDictionary Ressourcen, die im Content Stream genutzt werden
     * @throws \Exception Wenn die übergebenen Parameter keinen ContentStream beschreiben
     */
    public function __construct($pdfObject, PdfFile $pdfFile, ResourceDictionary $resourceDictionary)
    {
        parent::__construct($pdfObject, $pdfFile);
        $this->resourceDictionary = $resourceDictionary;
    }

    /**
     * @return ResourceDictionary
     */
    public function getResourceDictionary(): ResourceDictionary
    {
        return $this->resourceDictionary;
    }
}