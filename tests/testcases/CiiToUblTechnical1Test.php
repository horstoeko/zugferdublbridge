<?php

namespace horstoeko\zugferdublbridge\tests\testcases;

use horstoeko\zugferdublbridge\tests\TestCase;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;
use \horstoeko\zugferdublbridge\tests\traits\HandlesXmlTests;

class CiiToUblTechnical1Test extends TestCase
{
    use HandlesXmlTests;

    public function testLoadAndConvert(): void
    {
        self::$document = XmlConverterCiiToUbl::fromFile(dirname(__FILE__) . "/../assets/cii/3_cii_technical_1.xml")->enableAutomaticMode()->convert();
        $this->assertNotNull(self::$document);
    }

    public function testDocumentGeneral(): void
    {
        $this->assertXPathValue('/ubl:Invoice/cbc:CustomizationID', "urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0");
        $this->assertXPathValue('/ubl:Invoice/cbc:ProfileID', "urn:fdc:peppol.eu:2017:poacc:billing:01:1.0");
        $this->assertXPathValue('/ubl:Invoice/cbc:ID', "PRG1502112");
        $this->assertXPathValue('/ubl:Invoice/cbc:IssueDate', "2015-04-24");
        $this->assertXPathNotExists('/ubl:Invoice/cbc:DueDate');
        $this->assertXPathValue('/ubl:Invoice/cbc:InvoiceTypeCode', "384");
        $this->assertXPathNotExists('/ubl:Invoice/cbc:CreditNoteTypeCode');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cbc:Note', 0, "#ADU#Trainer: Herr […]");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cbc:Note', 1);
        $this->assertXPathNotExists('/ubl:Invoice/cbc:TaxPointDate');
        $this->assertXPathValue('/ubl:Invoice/cbc:DocumentCurrencyCode', "EUR");
        $this->assertXPathNotExists('/ubl:Invoice/cbc:TaxCurrencyCode');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:AccountingCost');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cbc:BuyerReference', 0, '99000000-18188-18');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:StartDate');
        $this->assertXPathNotExists('/ubl:Invoice/cbc:EndDate');
        $this->assertXPathNotExists('/ubl:Invoice/cac:OrderReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:OrderReference/cbc:SalesOrderID');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:BillingReference/cac:InvoiceDocumentReference/cbc:ID', 0, 'PRG1502168');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:BillingReference/cac:InvoiceDocumentReference/cbc:IssueDate', 0);
        $this->assertXPathNotExists('/ubl:Invoice/cac:DespatchDocumentReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:ReceiptDocumentReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:ID');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentTypeCode');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:DocumentDescription');
        $this->assertXPathNotExists('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:EmbeddedDocumentBinaryObject');
        $this->assertXPathNotExists('/ubl:Invoice/cac:OriginatorDocumentReference/cbc:ID');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:ProjectReference/cbc:ID', 0, 'PR456789');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:ProjectReference/cbc:ID', 1);
    }

    public function testAccountingSupplierParty(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cbc:EndpointID', 0, 'seller@email.de', 'schemeID', 'EM');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name', 0, "[Seller name]");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:StreetName', 0, "[Seller address line 1]");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:AdditionalStreetName', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:CityName', 0, "[Seller city]");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:PostalZone', 0, "12345");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode', 0, "DE");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 0, "DE123456789");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cac:TaxScheme/cbc:ID', 0, "VAT");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName', 0, "[Seller name]");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyLegalForm', 0, 'Sitz der Gesellschaft […], Registergericht […], Amtsgericht […]');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyID', 0, 'HRB 123456');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Name', 0, '[Seller contact person]');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Telephone', 0, '+49 123456789');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:ElectronicMail', 0, 'xxx@schulung.de');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact', 1);
    }

    public function testAccountingCustomerParty(): void
    {
        $this->assertXPathValueWithIndexAndAttribute('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cbc:EndpointID', 0, 'buyer@info.de', 'schemeID', 'EM');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID', 0, "BI123456");
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyName/cbc:Name', 0, "[Buyer name]");
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:StreetName', 0, "[Buyer address line 1]");
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
        $this->assertXPathNotExists('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact');
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
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:StreetName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:AdditionalStreetName', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:AddressLine', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:AddressLine/cbc:Line', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:CityName', 0, 'Schulungsort');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:PostalZone', 0, '12345');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:CountrySubentity', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:Country/cbc:IdentificationCode', 0, 'DE');
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:Delivery/cac:DeliveryParty/cac:PartyName/cbc:Name', 0, 'Schulungsanbieter');
    }

    public function testPaymentMeans(): void
    {
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode', 0, '58');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode/@name', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:PrimaryAccountNumberID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:NetworkID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:HolderName', 0);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID', 0, 'DE75512108001245126199');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:Name', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cbc:ID', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount', 0);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount/cbc:ID', 0);

        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode', 1, '58');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode/@name', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:PrimaryAccountNumberID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:NetworkID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:HolderName', 1);
        $this->assertXPathValueWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID', 1, 'DE12500105170648489890');
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:Name', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cbc:ID', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount', 1);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount/cbc:ID', 1);

        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode/@name', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:PrimaryAccountNumberID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:NetworkID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:HolderName', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:Name', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cbc:ID', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount', 2);
        $this->assertXPathNotExistsWithIndex('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount/cbc:ID', 2);
    }

}