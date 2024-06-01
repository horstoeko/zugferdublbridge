<?php

use horstoeko\zugferdublbridge\XmlDocumentWriter;

require dirname(__FILE__) . "/../vendor/autoload.php";

$doc = new XmlDocumentWriter("rsm:CrossIndustryInvoice");

$doc
    ->addNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100')
    ->addNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100')
    ->addNamespace('qdt', 'urn:un:unece:uncefact:data:Standard:QualifiedDataType:100')
    ->addNamespace('udt', 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100')
    ->addNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance')
    ->startElement('rsm:ExchangedDocumentContext', '')
        ->startElement('ram:GuidelineSpecifiedDocumentContextParameter', '')
            ->startElement('ram:ID', 'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.0')
            ->endElement()
        ->endElement()
    ->endElement()
    ->startElement('rsm:ExchangedDocument', '')
        ->startElement('ram:ID', '4711')
        ->endElement()
        ->startElement('ramx:TypeCode', '380')
            ->attribute('listID', '1001')
            ->attribute('listVersionID', 'D16A')
        ->endElement()
    ->endElement();

echo $doc->saveXmlString();
