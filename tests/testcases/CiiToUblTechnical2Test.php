<?php

namespace horstoeko\zugferdublbridge\tests\testcases;

use horstoeko\zugferdublbridge\tests\TestCase;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;
use \horstoeko\zugferdublbridge\tests\traits\HandlesXmlTests;

class CiiToUblTechnical2Test extends TestCase
{
    use HandlesXmlTests;

    public function testLoadAndConvert(): void
    {
        self::$document = XmlConverterCiiToUbl::fromFile(dirname(__FILE__) . "/../assets/cii/3_cii_technical_2.xml")->enableAutomaticMode()->convert();
        $this->assertNotNull(self::$document);
    }

    public function testDocumentGeneral(): void
    {
        $this->assertXPathValue('/ubl:Invoice/cbc:CustomizationID', "urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0");
        $this->assertXPathValue('/ubl:Invoice/cbc:ProfileID', "urn:fdc:peppol.eu:2017:poacc:billing:01:1.0");
        $this->assertXPathValue('/ubl:Invoice/cbc:ID', "0000123456");
        $this->assertXPathValue('/ubl:Invoice/cbc:IssueDate', "2017-12-11");
        $this->assertXPathValue('/ubl:Invoice/cbc:DueDate', '2018-01-10');
        $this->assertXPathValue('/ubl:Invoice/cbc:InvoiceTypeCode', "380");
        $this->assertXPathNotExists('/ubl:Invoice/cbc:CreditNoteTypeCode');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cbc:Note', 0, "#ADU#[Invoice note]");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cbc:Note', 1);
        $this->assertXPathNotExists('/ubl:Invoice/cbc:TaxPointDate');
        $this->assertXPathValue('/ubl:Invoice/cbc:DocumentCurrencyCode', "EUR");
        $this->assertXPathNotExists('/ubl:Invoice/cbc:TaxCurrencyCode');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:AccountingCost');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cbc:BuyerReference', 0, '11002002-98765-14');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:StartDate');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:EndDate');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:OrderReference/cbc:ID', 0, '10520');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:OrderReference/cbc:SalesOrderID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:BillingReference/cac:InvoiceDocumentReference/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:BillingReference/cac:InvoiceDocumentReference/cbc:IssueDate', 0);
        $this->assertXPathNotExists('/ubl:Invoice/cac:DespatchDocumentReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:ReceiptDocumentReference/cbc:ID');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:ID', 0, '01_15_Anhang_01');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentTypeCode', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentDescription', 0, 'Aufschlüsselung der einzelnen Leistungspositionen');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:EmbeddedDocumentBinaryObject', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:ID', 1, '01_15_Anhang_02');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentTypeCode', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentDescription', 1, 'Gesamtübersicht der Leistungspositionen');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:EmbeddedDocumentBinaryObject', 1);
        $this->assertXPathNotExists('/ubl:Invoice/cac:OriginatorDocumentReference/cbc:ID');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:ProjectReference/cbc:ID', 0, 'Entwicklung System');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:ProjectReference/cbc:ID', 1);
    }

    public function testAccountingSupplierParty(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cbc:EndpointID', 0, 'seller@email.de', 'schemeID', 'EM');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name', 0, "[Seller name]");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:StreetName', 0, "[Seller street]");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:AdditionalStreetName', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:CityName', 0, "[Seller city]");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:PostalZone', 0, "10623");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode', 0, "DE");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 0, "DE123456789");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cac:TaxScheme/cbc:ID', 0, "VAT");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName', 0, "[Seller name]");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyLegalForm', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyID', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Name', 0, '[Seller contact person]');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Telephone', 0, '1234567890');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:ElectronicMail', 0, 'contact@seller.de');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact', 1);
    }

    public function testAccountingCustomerParty(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cbc:EndpointID', 0, 'buyer@info.de', 'schemeID', 'EM');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyName/cbc:Name', 0, "[Buyer name]");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:StreetName', 0, "[Buyer street]");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:AdditionalStreetName', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:CityName', 0, "[Buyer city]");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:PostalZone', 0, "12345");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode', 0, "DE");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName', 0, "[Buyer name]");
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyLegalForm');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyID');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:Name', 0, '[Buyer contact person]');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:Telephone', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:ElectronicMail', 0, 'buyer@contact.com');
    }

    public function testPayeeParty(): void
    {
        $this->assertXPathNotExists('/ubl:Invoice/cac:PayeeParty');
    }

    public function testTaxRepresentativeParty(): void
    {
        $this->assertXPathNotExists('/ubl:Invoice/cac:TaxRepresentativeParty');
    }

    public function testDelivery(): void
    {
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cbc:ActualDeliveryDate', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cbc:ActualDeliveryDate', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:StreetName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:AdditionalStreetName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:AddressLine', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:CityName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:PostalZone', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:CountrySubentity', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:Country', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:Country/cbc:IdentificationCode', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryParty', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryParty/cac:PartyName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryParty/cac:PartyName/cbc:Name', 0);
    }

    public function testPaymentMeans(): void
    {
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode', 0, '48');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode/@name', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentID', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:PrimaryAccountNumberID', 0, '41234');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:NetworkID', 0, 'mapped-from-cii');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:HolderName', 0, '[Payment card holder name]');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:Name', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount/cbc:ID', 0);

        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode/@name', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:PrimaryAccountNumberID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:NetworkID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:HolderName', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:Name', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount/cbc:ID', 1);
    }

}