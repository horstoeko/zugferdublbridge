<?php

use horstoeko\stringmanagement\PathUtils;
use horstoeko\stringmanagement\StringUtils;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;

require dirname(__FILE__) . "/../vendor/autoload.php";

$xmlFilenames = glob(dirname(__FILE__) . "/*uncefact*.xml");

if ($xmlFilenames === false) {
    die();
}

foreach ($xmlFilenames as $xmlFilename) {
    $xmlFilePathInfo = pathinfo($xmlFilename);

    $newXmlPath = PathUtils::combineAllPaths($xmlFilePathInfo['dirname'], "ubl");
    $newXmlFilename = PathUtils::combinePathWithFile($newXmlPath, str_replace('uncefact', 'ubl', $xmlFilePathInfo['basename']));

    echo "Converting..." . PHP_EOL;
    echo " - Source ... $xmlFilename" . PHP_EOL;
    echo " - Dest ..... $newXmlFilename" . PHP_EOL;

    XmlConverterCiiToUbl::fromFile($xmlFilename)->convert()->saveXmlFile($newXmlFilename);
}
