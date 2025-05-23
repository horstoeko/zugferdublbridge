<?php

use horstoeko\stringmanagement\PathUtils;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;

require __DIR__ . "/../vendor/autoload.php";

$xmlFilenames = glob(__DIR__ . "/*uncefact*.xml");

if ($xmlFilenames === false) {
    die();
}

foreach ($xmlFilenames as $xmlFilename) {
    $xmlFilePathInfo = pathinfo($xmlFilename);

    $newXmlPath = PathUtils::combineAllPaths($xmlFilePathInfo['dirname'], "ubl");
    $newXmlFilename = PathUtils::combinePathWithFile($newXmlPath, str_replace('uncefact', 'ubl', $xmlFilePathInfo['basename']));

    echo "Converting..." . PHP_EOL;
    echo ' - Source ... ' . $xmlFilename . PHP_EOL;
    echo ' - Dest ..... ' . $newXmlFilename . PHP_EOL;

    XmlConverterCiiToUbl::fromFile($xmlFilename)->enableAutomaticMode()->convert()->saveXmlFile($newXmlFilename);
}
