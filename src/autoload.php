<?php
/*
 * Binde diese Datei ein, um alle Klassen automatisch Laden zu können.
 * Dazu muss der Namespace einer Klasse und das Verzeichnis, in welcher sie liegt, übereinstimmen.
 */

spl_autoload_extensions(".php");
spl_autoload_register(function($class) {
    require __DIR__ . "/" . str_replace("\\", "/", $class) . ".php";
});
//*/

// Lade config, da dies keine Klasse ist
require_once "config/config.php";
// Lade securityFunctions, da dies keine Klasse ist
require_once "security/securityFunctions.php";