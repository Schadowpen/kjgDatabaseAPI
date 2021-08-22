<?php


namespace pdf\document;

use pdf\object\PdfAbstractObject;
use pdf\object\PdfName;
use pdf\object\PdfString;

/**
 * Die Dokumenteninformation, die in dem Info-Eintrag im Trailer Dictionary zu finden sind.
 * @package pdf\document
 */
class DocumentInfo extends AbstractDocumentObject
{

    public static function objectType(): ?string
    {
        return null;
    }

    public static function objectSubtype(): ?string
    {
        return null;
    }

    public function getTitle(): PdfString {
        return $this->get("Title");
    }
    public function getAuthor(): PdfString {
        return $this->get("Author");
    }
    public function getSubject(): PdfString {
        return $this->get("Subject");
    }
    public function getKeywords(): PdfString {
        return $this->get("Keywords");
    }
    public function getCreator(): PdfString {
        return $this->get("Creator");
    }
    public function getProducer(): PdfString {
        return $this->get("Producer");
    }
    public function getCreationDate(): \DateTime {
        return PdfDate::parsePdfString($this->get("CreationDate"));
    }
    public function getModificationDate(): \DateTime {
        return PdfDate::parsePdfString($this->get("ModDate"));
    }
    public function getTrapped(): PdfName {
        return $this->get("Trapped");
    }

    public function setTitle(PdfAbstractObject $title) {
        $this->dictionary->setObject("Title", $title);
    }
    public function setProducer(PdfString $producer) {
        $this->dictionary->setObject("Producer", $producer);
    }
    public function setModificationDate(PdfString $lastModified) {
        $this->dictionary->setObject("ModDate", $lastModified);
    }
}