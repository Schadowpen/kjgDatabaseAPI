<?php


namespace generation;


use database\DatabaseConnection;
use misc\StringReader;
use pdf\document\FontType0;
use pdf\document\Page;
use pdf\PdfDocument;
use pdf\PdfFile;

/**
 * Class KartenVorlageAnalyzer
 * @package templates
 */
class KartenVorlageAnalyzer
{
    /**
     * Eingelesene Theaterkarten Vorlage
     * @var PdfDocument
     */
    protected $pdfDocument;

    /**
     * KartenVorlageAnalyzer constructor.
     * @param DatabaseConnection $databaseConnection Verbindung zur Datenbank, um die Kartenvorlage zu erhalten. Es muss mindestens Leseberechtigung vorliegen
     * @throws \Exception Wenn die Kartenvorlage nicht als PDF gelesen oder als Vorlage genutzt werden kann.
     */
    public function __construct(DatabaseConnection $databaseConnection)
    {
        $kartenVorlageString = $databaseConnection->getKartenVorlageString();
        if ($kartenVorlageString === false)
            throw new \Exception("Could not find kartenVorlage in database");

        $this->pdfDocument = new PdfDocument(new PdfFile(new StringReader($kartenVorlageString)));
        if ($this->pdfDocument->getPageList()->getPageCount() !== 1)
            throw new \Exception("Only PDF Documents with one Page can be used as Template");
    }

    /**
     * Liefert alle Schriftarten, die in der Vorlagen-PDF beim Generieren einer neuen Theaterkarte problemlos nutzbar wären.
     * @return string[] Namen der verfügbaren Schriftarten.
     */
    public function getAvailableFonts() {
        // Immer unterstützte Fonts aus den Standard 14 Fonts
        $fonts = [
            "Courier",
            "Courier-Bold",
            "Courier-Oblique",
            "Courier-BoldOblique",
            "Helvetica",
            "Helvetica-Bold",
            "Helvetica-Oblique",
            "Helvetica-BoldOblique",
            "Times-Roman",
            "Times-Bold",
            "Times-Italic",
            "Times-BoldItalic"
        ];

        // Schriftarten aus den Resource Dictionarys hinzuzählen, die unterstützt werden
        $pageCount = $this->pdfDocument->getPageList()->getPageCount();
        for ($i = 0; $i < $pageCount; ++$i) {
            foreach ($this->pdfDocument->getPageList()->getPage($i)->getResources()->getAllFonts() as $font) {
                if ($font instanceof FontType0)
                    continue; // Diese Schriftarten werden nicht unterstützt

                if (!in_array($font->getBaseFontName(), $fonts))
                    array_push($fonts, $font->getBaseFontName());
            }
        }

        // Rückgabe
        return $fonts;
    }

    /**
     * @return Page Die einzige Seite in der PDF-Datei, die als Vorlage für die Theaterkarten dient
     */
    public function getTemplatePage(): Page {
        return $this->pdfDocument->getPageList()->getPage(0);
    }
}