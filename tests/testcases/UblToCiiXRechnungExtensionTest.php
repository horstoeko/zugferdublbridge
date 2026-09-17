<?php

namespace horstoeko\zugferdublbridge\tests\testcases;

use horstoeko\zugferdublbridge\tests\TestCase;
use horstoeko\zugferdublbridge\tests\traits\HandlesXmlTests;
use horstoeko\zugferdublbridge\XmlConverterUblToCii;

class UblToCiiXRechnungExtensionTest extends TestCase
{
    use HandlesXmlTests;

    /**
     * The CustomizationID of the XRechnung 2.3 extension
     */
    private const PROFILE_XRECHNUNG_23_EXTENSION = 'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.3#conformant#urn:xoev-de:kosit:extension:xrechnung_2.3';

    /**
     * The CustomizationID of the XRechnung 3.0 extension
     */
    private const PROFILE_XRECHNUNG_30_EXTENSION = 'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0#conformant#urn:xeinkauf.de:kosit:extension:xrechnung_3.0';

    public function testExtensionProfilesAreSupported(): void
    {
        $converter = XmlConverterUblToCii::fromString($this->getUblWithCustomizationId(self::PROFILE_XRECHNUNG_30_EXTENSION));

        $this->assertTrue($converter->isSupportedProfile(self::PROFILE_XRECHNUNG_23_EXTENSION));
        $this->assertTrue($converter->isSupportedProfile(self::PROFILE_XRECHNUNG_30_EXTENSION));
        $this->assertFalse($converter->isSupportedProfile('urn:cen.eu:en16931:2017#conformant#urn:unknown:extension:1.0'));
    }

    public function testLoadAndConvertXRechnung23Extension(): void
    {
        self::$document = XmlConverterUblToCii::fromString($this->getUblWithCustomizationId(self::PROFILE_XRECHNUNG_23_EXTENSION))->convert();

        $this->assertNotNull(self::$document);
        $this->assertXPathValueWithIndex('/rsm:CrossIndustryInvoice/rsm:ExchangedDocument/ram:ID', 0, 'Snippet1');
        $this->assertXPathValueWithIndex('/rsm:CrossIndustryInvoice/rsm:ExchangedDocumentContext/ram:GuidelineSpecifiedDocumentContextParameter/ram:ID', 0, 'urn:cen.eu:en16931:2017');
    }

    public function testLoadAndConvertXRechnung30Extension(): void
    {
        self::$document = XmlConverterUblToCii::fromString($this->getUblWithCustomizationId(self::PROFILE_XRECHNUNG_30_EXTENSION))->convert();

        $this->assertNotNull(self::$document);
        $this->assertXPathValueWithIndex('/rsm:CrossIndustryInvoice/rsm:ExchangedDocument/ram:ID', 0, 'Snippet1');
        $this->assertXPathValueWithIndex('/rsm:CrossIndustryInvoice/rsm:ExchangedDocumentContext/ram:GuidelineSpecifiedDocumentContextParameter/ram:ID', 0, 'urn:cen.eu:en16931:2017');
    }

    /**
     * Returns the content of the simple UBL test file with an exchanged CustomizationID
     *
     * @param  string $customizationId
     * @return string
     */
    private function getUblWithCustomizationId(string $customizationId): string
    {
        $ublContent = file_get_contents(__DIR__ . "/../assets/ubl/1_ubl_simple.xml");

        return preg_replace(
            '/<cbc:CustomizationID>.*?<\/cbc:CustomizationID>/',
            sprintf('<cbc:CustomizationID>%s</cbc:CustomizationID>', $customizationId),
            $ublContent,
            1
        );
    }
}
