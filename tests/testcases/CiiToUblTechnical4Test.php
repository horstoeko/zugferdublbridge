<?php

namespace horstoeko\zugferdublbridge\tests\testcases;

use horstoeko\zugferdublbridge\tests\TestCase;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;
use \horstoeko\zugferdublbridge\tests\traits\HandlesXmlTests;

class CiiToUblTechnical4Test extends TestCase
{
    use HandlesXmlTests;

    public function testLoadAndConvert(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The submitted profile urn:cen.eu:en16931:2017#compliant#unknownprofile is not supported');
        self::$document = XmlConverterCiiToUbl::fromFile(dirname(__FILE__) . "/../assets/cii/3_cii_technical_4.xml")->enableAutomaticMode()->convert();
        $this->assertNull(self::$document);
    }
}
