<?php
require "autoload.php";

// lese POST Body aus
$kartenConfig = json_decode(file_get_contents("php://input"));
$kartenConfig = deleteUnnecessaryAttributes($kartenConfig, array("qrCodeConfig", "sitzplanConfig", "dateTextConfig", "timeTextConfig", "blockTextConfig", "reiheTextConfig", "platzTextConfig", "preisTextConfig", "bezahlstatusTextConfig", "vorgangsNummerTextConfig"));

if (!keyValid(true))
    exit;
if (!checkDatabaseUsageAllowed(false, false, true))
    exit;


// verbinde mit Datenbank
$dbo = new \database\DatabaseOverview();
$dbc = $dbo->getTemplateDatabaseConnection($_GET["template"]);
if ($dbc == false)
    exit;
if (!$dbc->writelockDatabase())
    exit;

try {
    $kartenVorlageAnalyzer = new generation\KartenVorlageAnalyzer($dbc);
    $availableFonts = $kartenVorlageAnalyzer->getAvailableFonts();
    $templatePage = $kartenVorlageAnalyzer->getTemplatePage();
    $templatePageContentStream = new pdf\graphics\AnalyzedContentStream(
        new pdf\graphics\state\GraphicsStateStack(new pdf\graphics\TransformationMatrix(), $templatePage->getCropBox()),
        $templatePage->getContents()
    );
    $correct = true;

    // Funktionen zum Prüfen von Korrektheit
    function checkImageConfig(string $imageConfigName)
    {
        global $kartenConfig;
        global $templatePageContentStream;
        global $correct;

        if (!isset($kartenConfig->$imageConfigName))
            return; // Konfiguration nicht angegeben, ist nicht schlimm
        $imageConfig =& $kartenConfig->$imageConfigName;

        if (!isset($imageConfig->operatorNumber)) {
            unset($imageConfig->operatorName);
            unset($imageConfig->resourceDeletable);
            unset($imageConfig->deleteStartIndex);
            unset($imageConfig->deleteEndIndex);
        } else {
            if (!is_int($imageConfig->operatorNumber)) {
                echo "Error: Falscher Datentyp von {$imageConfigName}->operatorNumber \n";
                $correct = false;
            } elseif ($imageConfig->operatorNumber < 0 || $imageConfig->operatorNumber >= $templatePageContentStream->getOperatorCount()) {
                echo "Error: {$imageConfigName}->operatorNumber gilt für keinen gültigen Operatoren \n";
                $correct = false;
            } elseif (!$templatePageContentStream->getOperator($imageConfig->operatorNumber) instanceof pdf\graphics\operator\AbstractImageOperator) {
                echo "Error: {$imageConfigName}->operatorNumber ist kein Bildoperator \n";
                $correct = false;
            } else {
                /** @var \pdf\graphics\operator\AbstractImageOperator $imageOperator */
                $imageOperator = $templatePageContentStream->getOperator($imageConfig->operatorNumber);

                // Berechnung weiterer Konfiguration aus dem Operatoren
                $imageConfig->operatorName = $imageOperator->getName();
                $imageConfig->resourceDeletable = $imageOperator->getName() !== "Inline Image"
                    && (generation\AutoConfig::countImageOperatorsWithName($templatePageContentStream->getImageOperators(), count($templatePageContentStream->getImageOperators()), $imageOperator->getName()) === 1);
                $deletionRange = $templatePageContentStream->getDeletableRangeForOperator($imageOperator->getOperatorNumber());
                $imageConfig->deleteStartIndex = $deletionRange->getStartIndex();
                $imageConfig->deleteEndIndex = $deletionRange->getEndIndex();
            }
        }

        if (!isset($imageConfig->lowerLeftCorner)) {
            echo "Error: {$imageConfigName}->lowerLeftCorner nicht angegeben \n";
            $correct = false;
        } elseif (!is_object($imageConfig->lowerLeftCorner)) {
            echo "Error: {$imageConfigName}->lowerLeftCorner ist kein JSON-Objekt \n";
            $correct = false;
        } else {
            $imageConfig->lowerLeftCorner = deleteUnnecessaryAttributes($imageConfig->lowerLeftCorner, ["x", "y"]);
            if (!isset($imageConfig->lowerLeftCorner->x)) {
                echo "Error: {$imageConfigName}->lowerLeftCorner->x nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($imageConfig->lowerLeftCorner->x)) {
                echo "Error: Falscher Datentyp von {$imageConfigName}->lowerLeftCorner->x \n";
                $correct = false;
            }
            if (!isset($imageConfig->lowerLeftCorner->y)) {
                echo "Error: {$imageConfigName}->lowerLeftCorner->y nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($imageConfig->lowerLeftCorner->y)) {
                echo "Error: Falscher Datentyp von {$imageConfigName}->lowerLeftCorner->y \n";
                $correct = false;
            }
        }

        if (!isset($imageConfig->lowerRightCorner)) {
            echo "Error: {$imageConfigName}->lowerRightCorner nicht angegeben \n";
            $correct = false;
        } elseif (!is_object($imageConfig->lowerRightCorner)) {
            echo "Error: {$imageConfigName}->lowerRightCorner ist kein JSON-Objekt \n";
            $correct = false;
        } else {
            $imageConfig->lowerRightCorner = deleteUnnecessaryAttributes($imageConfig->lowerRightCorner, ["x", "y"]);
            if (!isset($imageConfig->lowerRightCorner->x)) {
                echo "Error: {$imageConfigName}->lowerRightCorner->x nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($imageConfig->lowerRightCorner->x)) {
                echo "Error: Falscher Datentyp von {$imageConfigName}->lowerRightCorner->x \n";
                $correct = false;
            }
            if (!isset($imageConfig->lowerRightCorner->y)) {
                echo "Error: {$imageConfigName}->lowerRightCorner->y nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($imageConfig->lowerRightCorner->y)) {
                echo "Error: Falscher Datentyp von {$imageConfigName}->lowerRightCorner->y \n";
                $correct = false;
            }
        }

        if (!isset($imageConfig->upperLeftCorner)) {
            echo "Error: {$imageConfigName}->upperLeftCorner nicht angegeben \n";
            $correct = false;
        } elseif (!is_object($imageConfig->upperLeftCorner)) {
            echo "Error: {$imageConfigName}->upperLeftCorner ist kein JSON-Objekt \n";
            $correct = false;
        } else {
            $imageConfig->upperLeftCorner = deleteUnnecessaryAttributes($imageConfig->upperLeftCorner, ["x", "y"]);
            if (!isset($imageConfig->upperLeftCorner->x)) {
                echo "Error: {$imageConfigName}->upperLeftCorner->x nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($imageConfig->upperLeftCorner->x)) {
                echo "Error: Falscher Datentyp von {$imageConfigName}->upperLeftCorner->x \n";
                $correct = false;
            }
            if (!isset($imageConfig->upperLeftCorner->y)) {
                echo "Error: {$imageConfigName}->upperLeftCorner->y nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($imageConfig->upperLeftCorner->y)) {
                echo "Error: Falscher Datentyp von {$imageConfigName}->upperLeftCorner->y \n";
                $correct = false;
            }
        }

        if (!isset($imageConfig->upperRightCorner)) {
            echo "Error: {$imageConfigName}->upperRightCorner nicht angegeben \n";
            $correct = false;
        } elseif (!is_object($imageConfig->upperRightCorner)) {
            echo "Error: {$imageConfigName}->upperRightCorner ist kein JSON-Objekt \n";
            $correct = false;
        } else {
            $imageConfig->upperRightCorner = deleteUnnecessaryAttributes($imageConfig->upperRightCorner, ["x", "y"]);
            if (!isset($imageConfig->upperRightCorner->x)) {
                echo "Error: {$imageConfigName}->upperRightCorner->x nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($imageConfig->upperRightCorner->x)) {
                echo "Error: Falscher Datentyp von {$imageConfigName}->upperRightCorner->x \n";
                $correct = false;
            }
            if (!isset($imageConfig->upperRightCorner->y)) {
                echo "Error: {$imageConfigName}->upperRightCorner->y nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($imageConfig->upperRightCorner->y)) {
                echo "Error: Falscher Datentyp von {$imageConfigName}->upperRightCorner->y \n";
                $correct = false;
            }
        }
    }

    function checkTextConfig(string $textConfigName)
    {
        global $kartenConfig;
        global $availableFonts;
        global $correct;

        if (!isset($kartenConfig->$textConfigName))
            return; // Konfiguration nicht angegeben, ist nicht schlimm

        // Unnötige Werte Entfernen
        $kartenConfig->$textConfigName = deleteUnnecessaryAttributes($kartenConfig->$textConfigName, array("position", "alignment", "font", "fontSize", "color"));
        $textConfig =& $kartenConfig->$textConfigName;

        if (!isset($textConfig->position)) {
            echo "Error: {$textConfigName}->position nicht angegeben \n";
            $correct = false;
        } elseif (!is_object($textConfig->position)) {
            echo "Error: {$textConfigName}->position ist kein JSON-Objekt \n";
            $correct = false;
        } else {
            $textConfig->position = deleteUnnecessaryAttributes($textConfig->position, ["x", "y"]);
            if (!isset($textConfig->position->x)) {
                echo "Error: {$textConfigName}->position->x nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($textConfig->position->x)) {
                echo "Error: Falscher Datentyp von {$textConfigName}->position->x \n";
                $correct = false;
            }
            if (!isset($textConfig->position->y)) {
                echo "Error: {$textConfigName}->position->y nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($textConfig->position->y)) {
                echo "Error: Falscher Datentyp von {$textConfigName}->position->y \n";
                $correct = false;
            }
        }

        if (!isset($textConfig->alignment)) {
            echo "Error: {$textConfigName}->alignment nicht angegeben \n";
            $correct = false;
        } elseif ($textConfig->alignment !== 0 && $textConfig->alignment !== 1 && $textConfig->alignment !== 2) {
            echo "Error: {$textConfigName}->alignment darf nur die Werte 0, 1 und 2 annehmen \n";
            $correct = false;
        }

        if (!isset($textConfig->font)) {
            echo "Error: {$textConfigName}->font nicht angegeben \n";
            $correct = false;
        } elseif (!is_string($textConfig->font)) {
            echo "Error: Falscher Datentyp von {$textConfigName}->font \n";
            $correct = false;
        } else if (!in_array($textConfig->font, $availableFonts)) {
            echo "Error: {$textConfigName}->font ist keine unterstützte Schriftart! \n";
            $correct = false;
        }

        if (!isset($textConfig->fontSize)) {
            echo "Error: {$textConfigName}->fontSize nicht angegeben \n";
            $correct = false;
        } elseif (!is_number($textConfig->fontSize)) {
            echo "Error: Falscher Datentyp von {$textConfigName}->fontSize \n";
            $correct = false;
        }

        if (!isset($textConfig->color)) {
            echo "Error: {$textConfigName}->color nicht angegeben \n";
            $correct = false;
        } elseif (!is_object($textConfig->color)) {
            echo "Error: {$textConfigName}->color ist kein JSON-Objekt \n";
            $correct = false;
        } else {
            $textConfig->color = deleteUnnecessaryAttributes($textConfig->color, ["r", "g", "b"]);
            if (!isset($textConfig->color->r)) {
                echo "Error: {$textConfigName}->color->r nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($textConfig->color->r)) {
                echo "Error: Falscher Datentyp von {$textConfigName}->color->r \n";
                $correct = false;
            } elseif ($textConfig->color->r < 0 || $textConfig->color->r > 1) {
                echo "Error: {$textConfigName}->color->r darf nur zwischen 0 und 1 liegen \n";
                $correct = false;
            }
            if (!isset($textConfig->color->g)) {
                echo "Error: {$textConfigName}->color->g nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($textConfig->color->g)) {
                echo "Error: Falscher Datentyp von {$textConfigName}->color->g \n";
                $correct = false;
            } elseif ($textConfig->color->g < 0 || $textConfig->color->g > 1) {
                echo "Error: {$textConfigName}->color->g darf nur zwischen 0 und 1 liegen \n";
                $correct = false;
            }
            if (!isset($textConfig->color->b)) {
                echo "Error: {$textConfigName}->color->b nicht angegeben \n";
                $correct = false;
            } elseif (!is_number($textConfig->color->b)) {
                echo "Error: Falscher Datentyp von {$textConfigName}->color->b \n";
                $correct = false;
            } elseif ($textConfig->color->b < 0 || $textConfig->color->b > 1) {
                echo "Error: {$textConfigName}->color->b darf nur zwischen 0 und 1 liegen \n";
                $correct = false;
            }
        }
    }

    // überprüfe Input auf Korrektheit
    checkImageConfig("qrCodeConfig");
    if (isset($kartenConfig->qrCodeConfig))
        $kartenConfig->qrCodeConfig = deleteUnnecessaryAttributes($kartenConfig->qrCodeConfig, ["operatorNumber", "operatorName", "resourceDeletable", "deleteStartIndex", "deleteEndIndex", "lowerLeftCorner", "lowerRightCorner", "upperLeftCorner", "upperRightCorner"]);
    checkImageConfig("sitzplanConfig");
    if (isset($kartenConfig->sitzplanConfig)) {
        $kartenConfig->sitzplanConfig = deleteUnnecessaryAttributes($kartenConfig->sitzplanConfig, ["operatorNumber", "operatorName", "resourceDeletable", "deleteStartIndex", "deleteEndIndex", "lowerLeftCorner", "lowerRightCorner", "upperLeftCorner", "upperRightCorner", "font", "fontSize", "seatNumbersVisible", "lineWidth", "connectEntranceArrows"]);

        if (!isset($kartenConfig->sitzplanConfig->font)) {
            echo "Error: sitzplanConfig->font nicht angegeben \n";
            $correct = false;
        } elseif (!is_string($kartenConfig->sitzplanConfig->font)) {
            echo "Error: Falscher Datentyp von sitzplanConfig->font \n";
            $correct = false;
        } else if (!in_array($kartenConfig->sitzplanConfig->font, $availableFonts)) {
            echo "Error: sitzplanConfig->font ist keine unterstützte Schriftart! \n";
            $correct = false;
        }

        if (!isset($kartenConfig->sitzplanConfig->fontSize)) {
            echo "Error: sitzplanConfig->fontSize nicht angegeben \n";
            $correct = false;
        } elseif (!is_number($kartenConfig->sitzplanConfig->fontSize)) {
            echo "Error: Falscher Datentyp von sitzplanConfig->fontSize \n";
            $correct = false;
        }

        if (!isset($kartenConfig->sitzplanConfig->seatNumbersVisible)) {
            echo "Error: sitzplanConfig->seatNumbersVisible nicht angegeben \n";
            $correct = false;
        } elseif (!is_bool($kartenConfig->sitzplanConfig->seatNumbersVisible)) {
            echo "Error: Falscher Datentyp von sitzplanConfig->seatNumbersVisible \n";
            $correct = false;
        }

        if (!isset($kartenConfig->sitzplanConfig->lineWidth)) {
            echo "Error: sitzplanConfig->lineWidth nicht angegeben \n";
            $correct = false;
        } elseif (!is_number($kartenConfig->sitzplanConfig->lineWidth)) {
            echo "Error: Falscher Datentyp von sitzplanConfig->lineWidth \n";
            $correct = false;
        }

        if (!isset($kartenConfig->sitzplanConfig->connectEntranceArrows)) {
            echo "Error: sitzplanConfig->connectEntranceArrows nicht angegeben \n";
            $correct = false;
        } elseif (!is_bool($kartenConfig->sitzplanConfig->connectEntranceArrows)) {
            echo "Error: Falscher Datentyp von sitzplanConfig->connectEntranceArrows \n";
            $correct = false;
        }
    }

    checkTextConfig("dateTextConfig");
    checkTextConfig("timeTextConfig");
    checkTextConfig("blockTextConfig");
    checkTextConfig("reiheTextConfig");
    checkTextConfig("platzTextConfig");
    checkTextConfig("preisTextConfig");
    checkTextConfig("bezahlstatusTextConfig");
    checkTextConfig("vorgangsNummerTextConfig");

    if (!$correct)
        exit;


    // schreibe Datenbank
    $dbc->setKartenConfigJson($kartenConfig);

    $dbc->unlockDatabase();

    // gib gespeicherten Status zurück
    header("Content-Type: application/json");
    echo json_encode($kartenConfig);

} catch (Throwable $exception) {
    $dbc->unlockDatabase();
    echo "Error: " . $exception->getMessage() . "\n" . $exception->getTraceAsString() . "\n";
    exit;
}