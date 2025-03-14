<?php

use horstoeko\zugferdublbridge\XmlDocumentReader;

require __DIR__ . "/../vendor/autoload.php";

$doc = new XmlDocumentReader();
$doc
    ->addNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100')
    ->addNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100')
    ->addNamespace('qdt', 'urn:un:unece:uncefact:data:Standard:QualifiedDataType:100')
    ->addNamespace('udt', 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100')
    ->addNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance')
    ->loadFromXmlFile(__DIR__ . "/CII-Invoice-1.xml");

$nodeList = $doc->query('//rsm:CrossIndustryInvoice/rsm:ExchangedDocumentContext/ram:GuidelineSpecifiedDocumentContextParameter/ram:ID')->item(0)->nodeValue;
var_dump($nodeList);
