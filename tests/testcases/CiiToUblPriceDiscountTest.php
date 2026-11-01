<?php

declare(strict_types=1);

namespace horstoeko\zugferdublbridge\tests\testcases;

use horstoeko\zugferdublbridge\tests\TestCase;
use horstoeko\zugferdublbridge\tests\traits\HandlesXmlTests;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;
use horstoeko\zugferdublbridge\XmlConverterUblToCii;

final class CiiToUblPriceDiscountTest extends TestCase
{
    use HandlesXmlTests;

    public function testInvoicePriceDiscount(): void
    {
        self::$document = XmlConverterCiiToUbl::fromFile(__DIR__ . '/../assets/cii/5_cii_price_discount_invoice.xml')->enableAutomaticMode()->convert();

        $this->assertPriceDiscount('/ubl:Invoice/cac:InvoiceLine[1]/cac:Price', '400.0000', '401.5000', '1.5000');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine[1]/cac:Price/cbc:BaseQuantity', 0, '1', 'unitCode', 'DAY');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine[1]/cac:AllowanceCharge', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine[2]/cac:Price/cac:AllowanceCharge', 0);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine[2]/cac:Price/cbc:PriceAmount', 0, '500.0000', 'currencyID', 'EUR');
    }

    public function testMultiplePriceDiscounts(): void
    {
        self::$document = XmlConverterCiiToUbl::fromFile(__DIR__ . '/../assets/cii/5_cii_price_discount_multiple.xml')->enableAutomaticMode()->convert();

        $this->assertPriceDiscount('/ubl:Invoice/cac:InvoiceLine[1]/cac:Price', '400.0000', '401.5000', '1.5000');
        $this->assertPriceDiscount('/ubl:Invoice/cac:InvoiceLine[2]/cac:Price', '500.0000', '510.0000', '10.0000');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:AllowanceCharge', 0);
    }

    public function testCreditNotePriceDiscount(): void
    {
        self::$document = XmlConverterCiiToUbl::fromFile(__DIR__ . '/../assets/cii/5_cii_price_discount_creditnote.xml')->enableAutomaticMode()->convert();

        $this->assertXPathValueWithIndex('/ubl:CreditNote/cbc:CreditNoteTypeCode', 0, '381');
        $this->assertPriceDiscount('/ubl:Invoice/cac:InvoiceLine[1]/cac:Price', '400.0000', '401.5000', '1.5000');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine[2]/cac:Price/cac:AllowanceCharge', 0);
    }

    public function testInvoiceWithoutPriceDiscount(): void
    {
        self::$document = XmlConverterCiiToUbl::fromFile(__DIR__ . '/../assets/cii/5_cii_price_discount_without.xml')->enableAutomaticMode()->convert();

        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine/cac:Price/cac:AllowanceCharge', 0);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine[1]/cac:Price/cbc:PriceAmount', 0, '400.0000', 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine[2]/cac:Price/cbc:PriceAmount', 0, '500.0000', 'currencyID', 'EUR');
    }

    public function testCiiToUblToCiiToUblPriceDiscount(): void
    {
        $ublContent = XmlConverterCiiToUbl::fromFile(__DIR__ . '/../assets/cii/5_cii_price_discount_invoice.xml')->enableAutomaticMode()->convert()->saveXmlString();
        $ciiContent = XmlConverterUblToCii::fromString($ublContent)->convert()->saveXmlString();

        self::$document = XmlConverterCiiToUbl::fromString($ciiContent)->enableAutomaticMode()->convert();

        $this->assertPriceDiscount('/ubl:Invoice/cac:InvoiceLine[1]/cac:Price', '400.0000', '401.5000', '1.5000');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:InvoiceLine[2]/cac:Price/cac:AllowanceCharge', 0);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:InvoiceLine[2]/cac:Price/cbc:PriceAmount', 0, '500.0000', 'currencyID', 'EUR');
    }

    /**
     * Assert the UBL net price, price discount, and gross price of a line.
     *
     * @param  string $pricePath
     * @param  string $netPrice
     * @param  string $grossPrice
     * @param  string $discountAmount
     * @return void
     */
    private function assertPriceDiscount(string $pricePath, string $netPrice, string $grossPrice, string $discountAmount): void
    {
        $this->assertXPathValueWithIndexAndAttribute($pricePath . '/cbc:PriceAmount', 0, $netPrice, 'currencyID', 'EUR');
        $this->assertXPathValueWithIndex($pricePath . '/cac:AllowanceCharge/cbc:ChargeIndicator', 0, 'false');
        $this->assertXPathValueWithIndexAndAttribute($pricePath . '/cac:AllowanceCharge/cbc:Amount', 0, $discountAmount, 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute($pricePath . '/cac:AllowanceCharge/cbc:BaseAmount', 0, $grossPrice, 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex($pricePath . '/cac:AllowanceCharge', 1);
    }
}
