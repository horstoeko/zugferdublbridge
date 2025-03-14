<?php

namespace horstoeko\zugferdublbridge\tests\testcases;

use horstoeko\zugferdublbridge\tests\TestCase;
use horstoeko\zugferdublbridge\tests\traits\HandlesXmlTests;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;

class CiiToUblTechnical5Test extends TestCase
{
    use HandlesXmlTests;

    public function testLoadAndConvert(): void
    {
        self::$document = XmlConverterCiiToUbl::fromFile(__DIR__ . "/../assets/cii/3_cii_technical_5.xml")->enableAutomaticMode()->convert();
        $this->assertNotNull(self::$document);
    }

    public function testDocumentGeneral(): void
    {
        $this->assertXPathValue('/ubl:CreditNote/cbc:CustomizationID', "urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0");
        $this->assertXPathValue('/ubl:CreditNote/cbc:ProfileID', "urn:fdc:peppol.eu:2017:poacc:billing:01:1.0");
        $this->assertXPathValue('/ubl:CreditNote/cbc:ID', "0000123456");
        $this->assertXPathValue('/ubl:CreditNote/cbc:IssueDate', "2017-12-11");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cbc:DueDate', 0);
        $this->assertXPathNotExists('/ubl:CreditNote/cbc:InvoiceTypeCode');
        $this->assertXPathValue('/ubl:CreditNote/cbc:CreditNoteTypeCode', "381");
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cbc:Note', 0, "#ADU#[Invoice note]");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cbc:Note', 1);
        $this->assertXPathNotExists('/ubl:CreditNote/cbc:TaxPointDate');
        $this->assertXPathValue('/ubl:CreditNote/cbc:DocumentCurrencyCode', "EUR");
        $this->assertXPathNotExists('/ubl:CreditNote/cbc:TaxCurrencyCode');
        $this->assertXPathNotExists('/ubl:CreditNote/cbc:AccountingCost');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cbc:BuyerReference', 0, '11002002-98765-14');
        $this->assertXPathNotExists('/ubl:CreditNote/cbc:StartDate');
        $this->assertXPathNotExists('/ubl:CreditNote/cbc:EndDate');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:OrderReference/cbc:ID', 0, '10520');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:OrderReference/cbc:SalesOrderID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:BillingReference/cac:InvoiceDocumentReference/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:BillingReference/cac:InvoiceDocumentReference/cbc:IssueDate', 0);
        $this->assertXPathNotExists('/ubl:CreditNote/cac:DespatchDocumentReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:CreditNote/cac:ReceiptDocumentReference/cbc:ID');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AdditionalDocumentReference/cbc:ID', 0, '01_15_Anhang_01');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AdditionalDocumentReference/cbc:DocumentTypeCode', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AdditionalDocumentReference/cbc:DocumentDescription', 0, 'Aufschlüsselung der einzelnen Leistungspositionen');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AdditionalDocumentReference/cbc:EmbeddedDocumentBinaryObject', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AdditionalDocumentReference/cbc:ID', 1, '01_15_Anhang_02');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AdditionalDocumentReference/cbc:DocumentTypeCode', 1);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AdditionalDocumentReference/cbc:DocumentDescription', 1, 'Gesamtübersicht der Leistungspositionen');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AdditionalDocumentReference/cbc:EmbeddedDocumentBinaryObject', 1);
        $this->assertXPathNotExists('/ubl:CreditNote/cac:OriginatorDocumentReference/cbc:ID');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:ProjectReference/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:ProjectReference/cbc:ID', 1);
    }

    public function testAccountingSupplierParty(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cbc:EndpointID', 0, 'seller@email.de', 'schemeID', 'EM');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:StreetName', 0, "[Seller street]");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:AdditionalStreetName', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:CityName', 0, "[Seller city]");
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:PostalZone', 0, "10623");
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode', 0, "DE");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 0, "DE123456789");
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cac:TaxScheme/cbc:ID', 0, "VAT");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 1);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName', 0, "[Seller name]");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyLegalForm', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyID', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Name', 0, '[Seller contact person]');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Telephone', 0, '1234567890');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:ElectronicMail', 0, 'contact@seller.de');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingSupplierParty/cac:Party/cac:Contact', 1);
    }

    public function testAccountingCustomerParty(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cbc:EndpointID', 0, 'buyer@info.de', 'schemeID', 'EM');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PartyName/cbc:Name', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:StreetName', 0, "[Buyer street]");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:AdditionalStreetName', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:CityName', 0, "[Buyer city]");
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:PostalZone', 0, "12345");
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode', 0, "DE");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 1);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName', 0, "[Buyer name]");
        $this->assertXPathNotExists('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyLegalForm');
        $this->assertXPathNotExists('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyID');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:Name', 0, '[Buyer contact person]');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:Telephone', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:ElectronicMail', 0, 'buyer@contact.com');
    }

    public function testPayeeParty(): void
    {
        $this->assertXPathNotExists('/ubl:CreditNote/cac:PayeeParty');
    }

    public function testTaxRepresentativeParty(): void
    {
        $this->assertXPathNotExists('/ubl:CreditNote/cac:TaxRepresentativeParty');
    }

    public function testDelivery(): void
    {
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cbc:ActualDeliveryDate', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cbc:ActualDeliveryDate', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation/cac:Address', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:StreetName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:AdditionalStreetName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:AddressLine', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:CityName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:PostalZone', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:CountrySubentity', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:Country', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:Country/cbc:IdentificationCode', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryParty', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryParty/cac:PartyName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:Delivery/cac:DeliveryParty/cac:PartyName/cbc:Name', 0);
    }

    public function testPaymentMeans(): void
    {
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:PaymentMeans/cbc:PaymentMeansCode', 0, '48');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cbc:PaymentMeansCode/@name', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cbc:PaymentID', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:CardAccount/cbc:PrimaryAccountNumberID', 0, '41234');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:CardAccount/cbc:NetworkID', 0, 'mapped-from-cii');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:CardAccount/cbc:HolderName', 0, '[Payment card holder name]');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:Name', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PaymentMandate', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PaymentMandate/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount/cbc:ID', 0);

        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cbc:PaymentMeansCode', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cbc:PaymentMeansCode/@name', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cbc:PaymentID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:CardAccount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:CardAccount/cbc:PrimaryAccountNumberID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:CardAccount/cbc:NetworkID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:CardAccount/cbc:HolderName', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PayeeFinancialAccount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:Name', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PaymentMandate', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PaymentMandate/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount/cbc:ID', 1);
    }

    public function testPaymentTerms(): void
    {
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:PaymentTerms/cbc:Note', 0, 'Bei Zahlungen binnen 14 Tagen, 2% Skonto');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:PaymentTerms/cbc:Note', 1);
    }

    public function testAllowanceCharge(): void
    {
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge/cbc:ChargeIndicator', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge/cbc:AllowanceChargeReason', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge/cbc:Amount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge/cbc:BaseAmount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge/cac:TaxCategory', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge/cac:TaxCategory/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge/cac:TaxCategory/cbc:Percent', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge/cac:TaxCategory/cac:TaxScheme', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:AllowanceCharge/cac:TaxCategory/cac:TaxScheme/cbc:ID', 0);
    }

    public function testTaxTotal(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:TaxTotal/cbc:TaxAmount', 0, "1706.2", 'currencyID', 'EUR');

        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxableAmount', 0, "8980", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxAmount', 0, "1706.2", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:ID', 0, "S");
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:Percent', 0, "19.00");
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cac:TaxScheme/cbc:ID', 0, "VAT");

        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxableAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:Percent', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cac:TaxScheme/cbc:ID', 1);
    }

    public function testLegalMonetaryTotal(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:LineExtensionAmount', 0, "8980", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount', 0, "8980", 'currencyID', 'EUR');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount', 0, "10686.2", 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:AllowanceTotalAmount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:ChargeTotalAmount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:PrepaidAmount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:PayableRoundingAmount', 0);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:PayableAmount', 0, "10686.2", 'currencyID', 'EUR');

        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:LineExtensionAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:AllowanceTotalAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:ChargeTotalAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:ChargeTotalAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:PrepaidAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:PayableRoundingAmount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:LegalMonetaryTotal/cbc:PayableAmount', 1);
    }

    public function testInvoiceLine(): void
    {
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:ID', 0, "0");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:Note', 0);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cbc:CreditedQuantity', 0, "1", "unitCode", "XPP");
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cbc:LineExtensionAmount', 0, "850", "currencyID", "EUR");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:AccountingCost', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod/cbc:StartDate', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod/cbc:EndDate', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:OrderLineReference', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:OrderLineReference/cbc:LineID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:DocumentReference', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:DocumentReference/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:ChargeIndicator', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:AllowanceChargeReason', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:Amount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:BaseAmount', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cbc:Description', 0, 'Anforderungmanagament');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cbc:Name', 0, 'Anforderungsaufnahme');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:BuyersItemIdentification', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:BuyersItemIdentification/cbc:ID', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:SellersItemIdentification/cbc:ID', 0, '1034');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:StandardItemIdentification/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:OriginCountry', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:OriginCountry/cbc:IdentificationCode', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:CommodityClassification', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', 0);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cbc:ID', 0, 'S');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', 0, '19.00');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cac:TaxScheme/cbc:ID', 0, 'VAT');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/cbc:PriceAmount', 0, '850', 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/cbc:BaseQuantity', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:ChargeIndicator', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:Amount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:BaseAmount', 0);

        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:ID', 1, "1");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:Note', 1);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cbc:CreditedQuantity', 1, "1", "unitCode", "XPP");
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cbc:LineExtensionAmount', 1, "2986", "currencyID", "EUR");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:AccountingCost', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod/cbc:StartDate', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod/cbc:EndDate', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:OrderLineReference', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:OrderLineReference/cbc:LineID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:DocumentReference', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:DocumentReference/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:ChargeIndicator', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:AllowanceChargeReason', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:Amount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:BaseAmount', 1);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cbc:Description', 1, 'Erstellung Lastenheft bis Abnahme');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cbc:Name', 1, 'Lastenheft');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:BuyersItemIdentification', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:BuyersItemIdentification/cbc:ID', 1);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:SellersItemIdentification/cbc:ID', 1, '1035');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:StandardItemIdentification/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:OriginCountry', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:OriginCountry/cbc:IdentificationCode', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:CommodityClassification', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', 1);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cbc:ID', 1, 'S');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', 1, '19.00');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cac:TaxScheme/cbc:ID', 1, 'VAT');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/cbc:PriceAmount', 1, '2986', 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/cbc:BaseQuantity', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:ChargeIndicator', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:Amount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:BaseAmount', 1);

        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:ID', 2, "2");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:Note', 2);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cbc:CreditedQuantity', 2, "1", "unitCode", "XPP");
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cbc:LineExtensionAmount', 2, "2344", "currencyID", "EUR");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:AccountingCost', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod/cbc:StartDate', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod/cbc:EndDate', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:OrderLineReference', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:OrderLineReference/cbc:LineID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:DocumentReference', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:DocumentReference/cbc:ID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:ChargeIndicator', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:AllowanceChargeReason', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:Amount', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:BaseAmount', 2);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cbc:Description', 2, 'Erstellung Pflichtenheft bis Abnahme');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cbc:Name', 2, 'Pflichtenheft');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:BuyersItemIdentification', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:BuyersItemIdentification/cbc:ID', 2);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:SellersItemIdentification/cbc:ID', 2, '1036');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:StandardItemIdentification/cbc:ID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:OriginCountry', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:OriginCountry/cbc:IdentificationCode', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:CommodityClassification', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', 2);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cbc:ID', 2, 'S');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', 2, '19.00');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cac:TaxScheme/cbc:ID', 2, 'VAT');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/cbc:PriceAmount', 2, '2344', 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/cbc:BaseQuantity', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:ChargeIndicator', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:Amount', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:BaseAmount', 2);

        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:ID', 3, "3");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:Note', 3);
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cbc:CreditedQuantity', 3, "1", "unitCode", "XPP");
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cbc:LineExtensionAmount', 3, "2800", "currencyID", "EUR");
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cbc:AccountingCost', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod/cbc:StartDate', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:InvoicePeriod/cbc:EndDate', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:OrderLineReference', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:OrderLineReference/cbc:LineID', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:DocumentReference', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:DocumentReference/cbc:ID', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:ChargeIndicator', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:AllowanceChargeReason', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:MultiplierFactorNumeric', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:Amount', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:AllowanceCharge/cbc:BaseAmount', 3);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cbc:Description', 3, 'Entwicklung System bis Implementierung');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cbc:Name', 3, 'Entwicklung');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:BuyersItemIdentification', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:BuyersItemIdentification/cbc:ID', 3);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:SellersItemIdentification/cbc:ID', 3, '1037');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:StandardItemIdentification/cbc:ID', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:OriginCountry', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:OriginCountry/cbc:IdentificationCode', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:CommodityClassification', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', 3);
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cbc:ID', 3, 'S');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', 3, '19.00');
        $this->assertXPathValueWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Item/cac:ClassifiedTaxCategory/cac:TaxScheme/cbc:ID', 3, 'VAT');
        $this->assertXPathValueWithIndexAndAttribute('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/cbc:PriceAmount', 3, '2800', 'currencyID', 'EUR');
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/cbc:BaseQuantity', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:ChargeIndicator', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:Amount', 3);
        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine/cac:Price/AllowanceCharge/cbc:BaseAmount', 3);

        $this->assertXPathNotExistsWithIndex('/ubl:CreditNote/cac:CreditNoteLine', 4);
    }
}
