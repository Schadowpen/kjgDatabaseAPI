<?php

namespace pdf\document;


use pdf\indirectObject\PdfIndirectObject;
use pdf\object\PdfArray;
use pdf\object\PdfDictionary;
use pdf\object\PdfNumber;
use pdf\PdfFile;

/**
 * Baum (oder eher Liste) mit allen Seiten in einem Pdf-Dokument.
 * @package pdf\document
 */
class PageTree extends AbstractDocumentObject
{
    /**
     * Eine Liste mit allen Seiten, die sich in diesem PageTree befinden.
     * @var Page[]
     */
    private $pages = [];

    /**
     * Liefert den Type dieser Klasse an Dokument Objekten.
     * Er muss in einem zugehörigen Dictionary immer unter Type enthalten sein.
     * @return string
     */
    public static function objectType(): string
    {
        return "Pages";
    }

    /**
     * Liefert den Subtype dieser Klasse an Dokument Objekten.
     * Sollte für die Dokumentklasse kein SubType benötigt werden, wird null zurückgeliefert.
     * @return string|null
     */
    public static function objectSubtype(): ?string
    {
        return null;
    }

    /**
     * Erzeugt einen PageTree aus einem entsprechenden IndirectObject.
     *
     * @param PdfIndirectObject $pdfObject
     * @param PdfFile $pdfFile
     * @throws \Exception
     */
    public function __construct($pdfObject, PdfFile $pdfFile)
    {
        parent::__construct($pdfObject, $pdfFile);

        // Um in den Pages nicht dauernd überprüfen zu müssen, ob ein Attribut vererbt wurde, reiche diese erstmal an die Pages weiter.
        $this->downpassInheritedAttributes();

        // Theoretisch kann dieser Page Tree ein balancierter Baum sein.
        // Der Einfachheit halber und um Dateigröße zu sparen, wird dieser in eine flache Hierarchie umgewandelt
        /** @var PdfArray $kids */
        $kids = $this->getKids();
        for ($i = 0; $i < $kids->getArraySize(); ++$i) {

            // Wenn Kid ein PageTree ist, muss dieser eingebunden werden.
            $kid = $this->pdfFile->getIndirectObject($kids->getObject($i));
            if (PageTree::matchesType($kid->getContainingObject())) {

                // Erstelle ein PageTree-Objekt für dieses Kind, auch um rekursiv den Baum aufzulösen
                $kidPageTree = new PageTree($kid, $pdfFile);

                // Passe Attribute daran an, dass der kid-PageTree aufgelöst wird
                foreach ($kidPageTree->getKids()->getValue() as $kidKid) {
                    /** @var PdfDictionary $kidKidDictionary */
                    $kidKidDictionary = $this->pdfFile->parseReference($kidKid);
                    $kidKidDictionary->setObject("Parent", $this->getIndirectReference());
                }

                // Ersetze Eintrag $i durch den KidPageTree
                $kidsArray = $kids->getValue();
                array_splice($kidsArray, $i, 1, $kidPageTree->getKids()->getValue());
                $kids = new PdfArray($kidsArray);
                $this->set("Kids", $kids);

                // Füge die Pages des KidPageTree an die aktuellen Pages an
                $this->pages = array_merge($this->pages, $kidPageTree->pages);
                $i += $kidPageTree->getPageCount() - 1; // $i anpassen, um NACH dem eingefügten PageTree weiterzuarbeiten. Nicht vergessen, dass $i durch die Schleife noch um 1 erhöht wird

            } else {
                $this->pages[$i] = new Page($kid, $this->pdfFile);
            }
        }
    }

    /**
     * Gibt die Attribute, die nicht zum PageTree gehören, sondern an die Pages weitervererbt werden, direkt an diese weiter
     */
    public function downpassInheritedAttributes()
    {
        // finde Schlüssel, die als Attribute an Kinder weitergegeben werden
        $dictionaryKeys = $this->dictionary->getKeys();
        $dictionaryKeys = array_intersect($dictionaryKeys, Page::inheritableAttributes);
        foreach ($dictionaryKeys as $inheritedKey) {

            // gebe jedes Attribut an jedes Kind weiter, sofern dieses es nicht überschreibt
            $kids = $this->getKids();
            $kidsCount = $kids->getArraySize();
            for ($i = 0; $i < $kidsCount; ++$i) {
                /** @var PdfDictionary $kid */
                $kid = $this->pdfFile->parseReference($kids->getObject($i));
                if (!$kid->hasObject($inheritedKey))
                    $kid->setObject($inheritedKey, $this->dictionary->getObject($inheritedKey));
            }
            // Lösche das weitergegebene Attribut aus dem Page Tree
            $this->remove($inheritedKey);
        }
    }

    /**
     * Holt alle Attribute, die vom PageTree aus an die Pages weitervererbbar sind und sich in allen Pages gleichen, in den PageTree.
     * Dies sollte nur gemacht werden, wenn die Pages diese Attribute nicht mehr brauchen, sprich kurz vor dem Abspeichern der neuen PDF.
     */
    public function upliftInheritableAttributes()
    {
        if (count($this->pages) === 0)
            return; // Keine Seiten -> nichts zum upliften

        foreach (Page::inheritableAttributes as $attribute) {
            $attributeValue = $this->pages[0]->get($attribute);
            if ($attributeValue === null)
                continue; // Wenn der Wert bereits im Referenzobjekt null ist, kann das Attribut übersprungen werden

            $equalInAllPages = true;
            foreach ($this->pages as $page) {
                if (!$attributeValue->equals($page->get($attribute)))
                    $equalInAllPages = false;
            }

            if ($equalInAllPages) {
                foreach ($this->pages as $page)
                    $page->remove($attribute);
                $this->set($attribute, $attributeValue);
            }
        }
    }

    /**
     * Liefert die Anzahl der Seiten in der PDF
     * @return int
     */
    public function getPageCount(): int
    {
        return $this->get("Count")->getValue();
    }

    protected function getKids(): PdfArray
    {
        return $this->get("Kids");
    }

    /**
     * Liefert die x-te Seite in der PDF-Datei
     * @param int $index Index der Seite, beginnend bei 0
     * @return Page
     */
    public function getPage(int $index)
    {
        return $this->pages[$index];
    }

    /**
     * Fügt eine neue Seite am Ende des Dokumentes hinzu.
     * Ist diese noch nicht in der PdfFile vorhanden, wird ein neues IndirectObject erzeugt.
     * @param Page $page Neue hinzuzufügende Seite
     */
    public function addPage(Page $page)
    {
        $page->generateIndirectObjectIfNotExists();
        $page->setParent($this->indirectObject->getIndirectReference());
        $this->getKids()->addObject($page->getIndirectReference());
        $this->pages[] = $page;
        $this->set("Count", new PdfNumber($this->getPageCount() + 1));
    }

    /**
     * Entfernt die x-te Seite in der PDF-Datei
     * @param int $index Index der Seite, beginnend bei 0
     */
    public function removePage(int $index)
    {
        $this->getKids()->removeObject($index);
        array_splice($this->pages, $index, 1);
        $this->set("Count", new PdfNumber($this->getPageCount() - 1));
    }
}