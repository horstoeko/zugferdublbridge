<?php

/**
 * This file is a part of horstoeko/zugferdublbridge.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\zugferdublbridge;

use DateTime;

/**
 * Class representing the converter from UBL syntax to CII syntax
 *
 * @category Zugferd-UBL-Bridge
 * @package  Zugferd-UBL-Bridge
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/zugferdublbridge
 */
class XmlConverterUblToCii extends XmlConverterBase
{
    /**
     * List of supported profiles
     *
     * @var string[]
     */
    private const SUPPORTED_PROFILES = [
        'urn:factur-x.eu:1p0:minimum',
        'urn:factur-x.eu:1p0:basicwl',
        'urn:cen.eu:en16931:2017#compliant#urn:factur-x.eu:1p0:basic',
        'urn:cen.eu:en16931:2017',
        'urn:cen.eu:en16931:2017#conformant#urn:factur-x.eu:1p0:extended',
        'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_1.2',
        'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.0',
        'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.1',
        'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.2',
        'urn:cen.eu:en16931:2017#compliant#urn:xoev-de:kosit:standard:xrechnung_2.3',
        'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0',
    ];

    /**
     * The UBL document root name
     *
     * @var string
     */
    private $ublRootName = 'ubl:Invoice';

    /**
     * The UBL invoice/creditnote line root name
     *
     * @var string
     */
    private $ublLineRootName = 'cac:InvoiceLine';

    /**
     * The UNL invoice/credítnote line quantity root name
     *
     * @var string
     */
    private $ublLineQuantityRootName = 'cbc:InvoicedQuantity';

    /**
     * @inheritDoc
     */
    protected function getDestinationRoot(): string
    {
        return "rsm:CrossIndustryInvoice";
    }

    /**
     * @inheritDoc
     */
    protected function getSourceNamespaces(): array
    {
        return [
            'ubl' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getDestinationNamespaces(): array
    {
        return [
            'rsm' => 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100',
            'ram' => 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100',
            'qdt' => 'urn:un:unece:uncefact:data:Standard:QualifiedDataType:100',
            'udt' => 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100',
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function checkValidSource()
    {
        $this->source->whenExists(
            '//ubl:CreditNote',
            null,
            function () {
                $this->ublRootName = 'ubl:CreditNote';
                $this->ublLineRootName = 'cac:CreditNoteLine';
                $this->ublLineQuantityRootName = 'cbc:CreditedQuantity';
            },
            function () {
                $this->source->whenExists(
                    '//ubl:Invoice',
                    null,
                    function () {
                        $this->ublRootName = 'ubl:Invoice';
                        $this->ublLineRootName = 'cac:InvoiceLine';
                        $this->ublLineQuantityRootName = 'cbc:InvoicedQuantity';
                    },
                    function () {
                        throw new \RuntimeException('The document is not a valid UBL document');
                    }
                );
            }
        );

        $rootElement = $this->source->query(sprintf('//%s', $this->ublRootName))->item(0);
        $submittedCustomizationID = $this->source->queryValue('./cbc:CustomizationID', $rootElement);

        if (!in_array($submittedCustomizationID, static::SUPPORTED_PROFILES)) {
            throw new \RuntimeException(sprintf('The submitted profile %s is not supported', $submittedCustomizationID));
        }
    }

    /**
     * @inheritDoc
     */
    protected function doConvert()
    {
        $this->convertExchangedDocumentContext();
        $this->convertExchangedDocument();
        $this->convertSupplyChainTradeTransaction();

        return $this;
    }

    /**
     * Convert system information
     *
     * @return void
     */
    private function convertExchangedDocumentContext(): void
    {
        $docRootElement = $this->source->query(sprintf('//%s', $this->ublRootName))->item(0);

        $this->destination->startElement('rsm:ExchangedDocumentContext');
        $this->source->whenExists(
            './cbc:ProfileID',
            $docRootElement,
            function ($profileIdNode) {
                $this->destination->startElement('ram:BusinessProcessSpecifiedDocumentContextParameter');
                $this->destination->element('ram:ID', $profileIdNode->nodeValue);
                $this->destination->endElement();
            }
        );
        $this->source->whenExists(
            './cbc:CustomizationID',
            $docRootElement,
            function ($customizationIdNode) {
                $this->destination->startElement('ram:GuidelineSpecifiedDocumentContextParameter');
                $this->destination->element('ram:ID', $customizationIdNode->nodeValue);
                $this->destination->endElement();
            }
        );
        $this->destination->endElement();
    }

    /**
     * Convert heading information
     *
     * @return void
     */
    private function convertExchangedDocument(): void
    {
        $docRootElement = $this->source->query(sprintf('//%s', $this->ublRootName))->item(0);

        $this->destination->startElement('rsm:ExchangedDocument');
        $this->destination->element('ram:ID', $this->source->queryValue('./cbc:ID', $docRootElement));
        $this->destination->element('ram:TypeCode', $this->source->queryValue('./cbc:InvoiceTypeCode', $docRootElement));
        $this->source->whenExists(
            './cbc:IssueDate',
            $docRootElement,
            function ($issueDateNode) {
                $this->destination->startElement('ram:IssueDateTime');
                $this->destination->elementWithAttribute('udt:DateTimeString', $this->convertDateTime($issueDateNode->nodeValue), 'format', '102');
                $this->destination->endElement();
            }
        );
        $this->source->queryAll('./cbc:Note', $docRootElement)->foreach(
            function ($noteNode) {
                $splittedNode = explode('#', $noteNode->nodeValue);
                if (count($splittedNode) > 2) {
                    $this->destination->startElement('ram:IncludedNote');
                    $this->destination->element('ram:Content', $splittedNode[2]);
                    $this->destination->element('ram:SubjectCode', $splittedNode[1]);
                    $this->destination->endElement();
                }
            }
        );
        $this->destination->endElement();
    }

    /**
     * Convert SupplyChainTradeTransaction Node
     *
     * @return void
     */
    private function convertSupplyChainTradeTransaction(): void
    {
        $this->destination->startElement('rsm:SupplyChainTradeTransaction');

        $this->convertIncludedSupplyChainTradeLineItem();
        $this->convertApplicableHeaderTradeAgreement();
        $this->convertApplicableHeaderTradeDelivery();
        $this->convertApplicableHeaderTradeSettlement();

        $this->destination->endElement();
    }

    /**
     * Convert all IncludedSupplyChainTradeLineItem
     *
     * @return void
     */
    private function convertIncludedSupplyChainTradeLineItem(): void
    {
        $docRootElement = $this->source->query(sprintf('//%s', $this->ublRootName))->item(0);

        $this->source->queryAll(sprintf('./%s', $this->ublLineRootName), $docRootElement)->forEach(
            function ($invoiceLineNode) {
                $this->destination->startElement('ram:IncludedSupplyChainTradeLineItem');

                $this->destination->startElement('ram:AssociatedDocumentLineDocument');
                $this->destination->element('ram:LineID', $this->source->queryValue('./cbc:ID', $invoiceLineNode));
                $this->source->whenExists(
                    './cbc:Note',
                    $invoiceLineNode,
                    function ($invoiceLineNoteNode) {
                        $this->destination->startElement('ram:IncludedNote');
                        $this->destination->element('ram:Content', $invoiceLineNoteNode->nodeValue);
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();

                $this->destination->startElement('ram:SpecifiedTradeProduct');
                $this->destination->element('ram:SellerAssignedID', $this->source->queryValue('./cac:Item/cac:SellersItemIdentification/cbc:ID', $invoiceLineNode));
                $this->destination->element('ram:BuyerAssignedID', $this->source->queryValue('./cac:Item/cac:BuyersItemIdentification/cbc:ID', $invoiceLineNode));
                $this->destination->elementWithAttribute('ram:GlobalID', $this->source->queryValue('./cac:Item/cac:StandardItemIdentification/cbc:ID', $invoiceLineNode), 'schemeID', $this->source->queryValue('./cac:Item/cac:StandardItemIdentification/cbc:ID/@schemeID'));
                $this->destination->element('ram:Name', $this->source->queryValue('./cac:Item/cbc:Name', $invoiceLineNode));
                $this->destination->element('ram:Description', $this->source->queryValue('./cac:Item/cbc:Description', $invoiceLineNode));
                $this->source->queryAll('./cac:Item/cac:CommodityClassification/cbc:ItemClassificationCode', $invoiceLineNode)->forEach(
                    function ($invoiceLineItemClassificationCode) {
                        $this->destination->elementWithMultipleAttributes('ram:ClassCode', $invoiceLineItemClassificationCode->nodeValue, ['listID' => $invoiceLineItemClassificationCode->getAttribute('listID'), 'listVersionID' => $invoiceLineItemClassificationCode->getAttribute('listVersionID')]);
                    },
                    function () {
                        // Do nothing here
                    },
                    function () {
                        // Do nothing here
                    },
                    function () {
                        $this->destination->startElement('ram:DesignatedProductClassification');
                    },
                    function () {
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();

                $this->destination->startElement('ram:SpecifiedLineTradeAgreement');
                $this->source->whenExists(
                    './cac:OrderLineReference/cbc:LineID',
                    $invoiceLineNode,
                    function ($invoiceLineOrderLineRefIdNode) {
                        $this->destination->startElement('ram:BuyerOrderReferencedDocument');
                        $this->destination->element('ram:LineID', $invoiceLineOrderLineRefIdNode->nodeValue);
                        $this->destination->endElement();
                    }
                );
                $this->destination->startElement('ram:NetPriceProductTradePrice');
                $this->destination->element('ram:ChargeAmount', $this->source->queryValue('./cac:Price/cbc:PriceAmount', $invoiceLineNode));
                $this->destination->elementWithAttribute('ram:BasisQuantity', $this->source->queryValue('./cac:Price/cbc:BaseQuantity', $invoiceLineNode), 'unitCode', $this->source->queryValue('./cac:Price/cbc:BaseQuantity/@unitCode', $invoiceLineNode));
                $this->destination->endElement();
                $this->destination->endElement();

                $this->destination->startElement('ram:SpecifiedLineTradeDelivery');
                $this->destination->elementWithAttribute('ram:BilledQuantity', $this->source->queryValue($this->ublLineQuantityRootName, $invoiceLineNode), 'unitCode', $this->source->queryValue('cbc:InvoicedQuantity/@unitCode', $invoiceLineNode));
                $this->destination->endElement();

                $this->destination->startElement('ram:SpecifiedLineTradeSettlement');
                $this->source->whenExists(
                    './cac:Item/cac:ClassifiedTaxCategory',
                    $invoiceLineNode,
                    function ($invoiceLineTaxCategoryNode) {
                        $this->destination->startElement('ram:ApplicableTradeTax');
                        $this->destination->element('ram:TypeCode', $this->source->queryValue('./cac:TaxScheme/cbc:ID', $invoiceLineTaxCategoryNode));
                        $this->destination->element('ram:CategoryCode', $this->source->queryValue('./cbc:ID', $invoiceLineTaxCategoryNode));
                        $this->destination->element('ram:RateApplicablePercent', $this->source->queryValue('./cbc:Percent', $invoiceLineTaxCategoryNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:InvoicePeriod',
                    $invoiceLineNode,
                    function ($invoiceLineInvoicePeriodNode) {
                        $this->destination->startElement('ram:BillingSpecifiedPeriod');
                        $this->source->whenExists(
                            './cbc:StartDate',
                            $invoiceLineInvoicePeriodNode,
                            function ($invoiceLineInvoicePeriodStartNode) {
                                $this->destination->startElement('ram:StartDateTime');
                                $this->destination->elementWithAttribute('udt:DateTimeString', $this->convertDateTime($invoiceLineInvoicePeriodStartNode->nodeValue), 'format', '102');
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './cbc:EndDate',
                            $invoiceLineInvoicePeriodNode,
                            function ($invoiceLineInvoicePeriodEndNode) {
                                $this->destination->startElement('ram:EndDateTime');
                                $this->destination->elementWithAttribute('udt:DateTimeString', $this->convertDateTime($invoiceLineInvoicePeriodEndNode->nodeValue), 'format', '102');
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
                $this->destination->startElement('ram:SpecifiedTradeSettlementLineMonetarySummation');
                $this->destination->element('ram:LineTotalAmount', $this->source->queryValue('cbc:LineExtensionAmount', $invoiceLineNode));
                $this->destination->endElement();
                $this->destination->endElement();

                $this->destination->endElement();
            }
        );
    }

    /**
     * Convert ApplicableHeaderTradeAgreement
     *
     * @return void
     */
    private function convertApplicableHeaderTradeAgreement(): void
    {
        $docRootElement = $this->source->query(sprintf('//%s', $this->ublRootName))->item(0);

        $this->destination->startElement('ram:ApplicableHeaderTradeAgreement');

        $this->destination->element('ram:BuyerReference', $this->source->queryValue('./cbc:BuyerReference', $docRootElement));

        $this->source->whenExists(
            './cac:AccountingSupplierParty/cac:Party',
            $docRootElement,
            function ($invoiceAccountingSupplierPartyNode) {
                $this->destination->startElement('ram:SellerTradeParty');
                $this->source->queryAll('./cac:PartyIdentification/cbc:ID[not(@schemeID)]', $invoiceAccountingSupplierPartyNode)->forEach(
                    function ($invoiceAccountingSupplierPartyIdNode) {
                        $this->destination->element('ram:ID', $invoiceAccountingSupplierPartyIdNode->nodeValue);
                    }
                );
                $this->source->queryAll('./cac:PartyIdentification/cbc:ID[@schemeID != \'\' and @schemeID != \'SEPA\']', $invoiceAccountingSupplierPartyNode)->forEach(
                    function ($invoiceAccountingSupplierPartyIdNode) {
                        //if (strcasecmp($invoiceAccountingSupplierPartyIdNode->getAttribute('schemeID'), 'SEPA') !== 0) {
                            $this->destination->elementWithAttribute('ram:GlobalID', $invoiceAccountingSupplierPartyIdNode->nodeValue, 'schemeID', $invoiceAccountingSupplierPartyIdNode->getAttribute('schemeID'));
                        //}
                    }
                );
                $this->source->whenExists(
                    './cac:PartyName/cbc:Name',
                    $invoiceAccountingSupplierPartyNode,
                    function ($invoiceAccountingSupplierPartyNameNode) {
                        $this->destination->element('ram:Name', $invoiceAccountingSupplierPartyNameNode->nodeValue);
                    },
                    function () use ($invoiceAccountingSupplierPartyNode) {
                        $this->destination->element('ram:Name', $this->source->queryValue('./cac:PartyLegalEntity/cbc:RegistrationName', $invoiceAccountingSupplierPartyNode));
                    }
                );
                $this->source->whenExists(
                    './cac:PartyLegalEntity/cbc:CompanyLegalForm',
                    $invoiceAccountingSupplierPartyNode,
                    function ($invoiceAccountingSupplierPartyLegalEntityNode) {
                        $this->destination->element('ram:Description', $invoiceAccountingSupplierPartyLegalEntityNode->nodeValue);
                    }
                );
                $this->source->whenExists(
                    './cac:PartyLegalEntity',
                    $invoiceAccountingSupplierPartyNode,
                    function ($invoiceAccountingSupplierPartyLegalEntityNode) {
                        $this->destination->startElement('ram:SpecifiedLegalOrganization');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('./cbc:CompanyID', $invoiceAccountingSupplierPartyLegalEntityNode), 'schemeID', $this->source->queryValue('./cbc:CompanyID/@schemeID', $invoiceAccountingSupplierPartyLegalEntityNode));
                        $this->destination->element('ram:TradingBusinessName', $this->source->queryValue('./cbc:RegistrationName', $invoiceAccountingSupplierPartyLegalEntityNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:Contact',
                    $invoiceAccountingSupplierPartyNode,
                    function ($invoiceAccountingSupplierPartyContactNode) {
                        $this->destination->startElement('ram:DefinedTradeContact');
                        $this->destination->element('ram:PersonName', $this->source->queryValue('./cbc:Name', $invoiceAccountingSupplierPartyContactNode));
                        $this->source->whenExists(
                            './cbc:Telephone',
                            $invoiceAccountingSupplierPartyContactNode,
                            function ($invoiceAccountingSupplierPartyContactPhoneNode) {
                                $this->destination->startElement('ram:TelephoneUniversalCommunication');
                                $this->destination->element('ram:CompleteNumber', $invoiceAccountingSupplierPartyContactPhoneNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './cbc:ElectronicMail',
                            $invoiceAccountingSupplierPartyContactNode,
                            function ($invoiceAccountingSupplierPartyContactMailNode) {
                                $this->destination->startElement('ram:EmailURIUniversalCommunication');
                                $this->destination->element('ram:URIID', $invoiceAccountingSupplierPartyContactMailNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PostalAddress',
                    $invoiceAccountingSupplierPartyNode,
                    function ($invoiceAccountingSupplierPartyPostalAddressNode) {
                        $this->destination->startElement('ram:PostalTradeAddress');
                        $this->destination->element('ram:PostcodeCode', $this->source->queryValue('./cbc:PostalZone', $invoiceAccountingSupplierPartyPostalAddressNode));
                        $this->destination->element('ram:LineOne', $this->source->queryValue('./cbc:StreetName', $invoiceAccountingSupplierPartyPostalAddressNode));
                        $this->destination->element('ram:LineTwo', $this->source->queryValue('./cbc:AdditionalStreetName', $invoiceAccountingSupplierPartyPostalAddressNode));
                        $this->destination->element('ram:LineThree', $this->source->queryValue('./cac:AddressLine/cbc:Line', $invoiceAccountingSupplierPartyPostalAddressNode));
                        $this->destination->element('ram:CityName', $this->source->queryValue('./cbc:CityName', $invoiceAccountingSupplierPartyPostalAddressNode));
                        $this->destination->element('ram:CountryID', $this->source->queryValue('./cac:Country/cbc:IdentificationCode', $invoiceAccountingSupplierPartyPostalAddressNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cbc:EndpointID[@schemeID=\'EM\']',
                    $invoiceAccountingSupplierPartyNode,
                    function ($invoiceAccountingSupplierPartyEndpointNode) {
                        $this->destination->startElement('ram:URIUniversalCommunication');
                        $this->destination->elementWithAttribute('ram:URIID', $invoiceAccountingSupplierPartyEndpointNode->nodeValue, 'schemeID', 'EM');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'VAT\']',
                    $invoiceAccountingSupplierPartyNode,
                    function ($invoiceAccountingSupplierPartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceAccountingSupplierPartyTaxSchemeNode), 'schemeID', 'VA');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'FC\']',
                    $invoiceAccountingSupplierPartyNode,
                    function ($invoiceAccountingSupplierPartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceAccountingSupplierPartyTaxSchemeNode), 'schemeID', 'FC');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'???\']',
                    $invoiceAccountingSupplierPartyNode,
                    function ($invoiceAccountingSupplierPartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceAccountingSupplierPartyTaxSchemeNode), 'schemeID', 'FC');
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
            }
        );

        $this->source->whenExists(
            './cac:AccountingCustomerParty/cac:Party',
            $docRootElement,
            function ($invoiceAccountingCustomerPartyNode) {
                $this->destination->startElement('ram:BuyerTradeParty');
                $this->source->queryAll('./cac:PartyIdentification/cbc:ID[not(@schemeID)]', $invoiceAccountingCustomerPartyNode)->forEach(
                    function ($invoiceAccountingCustomerPartyIdNode) {
                        $this->destination->element('ram:ID', $invoiceAccountingCustomerPartyIdNode->nodeValue);
                    }
                );
                $this->source->queryAll('./cac:PartyIdentification/cbc:ID[@schemeID]', $invoiceAccountingCustomerPartyNode)->forEach(
                    function ($invoiceAccountingCustomerPartyIdNode) {
                        $this->destination->elementWithAttribute('ram:GlobalID', $invoiceAccountingCustomerPartyIdNode->nodeValue, 'schemeID', $invoiceAccountingCustomerPartyIdNode->getAttribute('schemeID'));
                    }
                );
                $this->source->whenExists(
                    './cac:PartyName/cbc:Name',
                    $invoiceAccountingCustomerPartyNode,
                    function ($invoiceAccountingCustomerPartyNameNode) {
                        $this->destination->element('ram:Name', $invoiceAccountingCustomerPartyNameNode->nodeValue);
                    },
                    function () use ($invoiceAccountingCustomerPartyNode) {
                        $this->destination->element('ram:Name', $this->source->queryValue('./cac:PartyLegalEntity/cbc:RegistrationName', $invoiceAccountingCustomerPartyNode));
                    }
                );
                $this->source->whenExists(
                    './cac:PartyLegalEntity/cbc:CompanyLegalForm',
                    $invoiceAccountingCustomerPartyNode,
                    function ($invoiceAccountingCustomerPartyLegalEntityNode) {
                        $this->destination->element('ram:Description', $invoiceAccountingCustomerPartyLegalEntityNode->nodeValue);
                    }
                );
                $this->source->whenExists(
                    './cac:PartyLegalEntity',
                    $invoiceAccountingCustomerPartyNode,
                    function ($invoiceAccountingCustomerPartyLegalEntityNode) {
                        $this->destination->startElement('ram:SpecifiedLegalOrganization');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('./cbc:CompanyID', $invoiceAccountingCustomerPartyLegalEntityNode), 'schemeID', $this->source->queryValue('./cbc:CompanyID/@schemeID', $invoiceAccountingCustomerPartyLegalEntityNode));
                        $this->destination->element('ram:TradingBusinessName', $this->source->queryValue('./cbc:RegistrationName', $invoiceAccountingCustomerPartyLegalEntityNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:Contact',
                    $invoiceAccountingCustomerPartyNode,
                    function ($invoiceAccountingCustomerPartyContactNode) {
                        $this->destination->startElement('ram:DefinedTradeContact');
                        $this->destination->element('ram:PersonName', $this->source->queryValue('./cbc:Name', $invoiceAccountingCustomerPartyContactNode));
                        $this->source->whenExists(
                            './cbc:Telephone',
                            $invoiceAccountingCustomerPartyContactNode,
                            function ($invoiceAccountingCustomerPartyContactPhoneNode) {
                                $this->destination->startElement('ram:TelephoneUniversalCommunication');
                                $this->destination->element('ram:CompleteNumber', $invoiceAccountingCustomerPartyContactPhoneNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './cbc:ElectronicMail',
                            $invoiceAccountingCustomerPartyContactNode,
                            function ($invoiceAccountingCustomerPartyContactMailNode) {
                                $this->destination->startElement('ram:EmailURIUniversalCommunication');
                                $this->destination->element('ram:URIID', $invoiceAccountingCustomerPartyContactMailNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PostalAddress',
                    $invoiceAccountingCustomerPartyNode,
                    function ($invoiceAccountingCustomerPartyPostalAddressNode) {
                        $this->destination->startElement('ram:PostalTradeAddress');
                        $this->destination->element('ram:PostcodeCode', $this->source->queryValue('./cbc:PostalZone', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->element('ram:LineOne', $this->source->queryValue('./cbc:StreetName', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->element('ram:LineTwo', $this->source->queryValue('./cbc:AdditionalStreetName', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->element('ram:LineThree', $this->source->queryValue('./cac:AddressLine/cbc:Line', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->element('ram:CityName', $this->source->queryValue('./cbc:CityName', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->element('ram:CountryID', $this->source->queryValue('./cac:Country/cbc:IdentificationCode', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cbc:EndpointID[@schemeID=\'EM\']',
                    $invoiceAccountingCustomerPartyNode,
                    function ($invoiceAccountingCustomerPartyEndpointNode) {
                        $this->destination->startElement('ram:URIUniversalCommunication');
                        $this->destination->elementWithAttribute('ram:URIID', $invoiceAccountingCustomerPartyEndpointNode->nodeValue, 'schemeID', 'EM');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'VAT\']',
                    $invoiceAccountingCustomerPartyNode,
                    function ($invoiceAccountingCustomerPartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceAccountingCustomerPartyTaxSchemeNode), 'schemeID', 'VA');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'FC\']',
                    $invoiceAccountingCustomerPartyNode,
                    function ($invoiceAccountingCustomerPartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceAccountingCustomerPartyTaxSchemeNode), 'schemeID', 'FC');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'???\']',
                    $invoiceAccountingCustomerPartyNode,
                    function ($invoiceAccountingCustomerPartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceAccountingCustomerPartyTaxSchemeNode), 'schemeID', 'FC');
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
            }
        );

        $this->source->whenExists(
            './cac:TaxRepresentativeParty',
            $docRootElement,
            function ($invoiceTaxRepresentativePartyNode) {
                $this->destination->startElement('ram:SellerTaxRepresentativeTradeParty');
                $this->source->queryAll('./cac:PartyIdentification/cbc:ID[not(@schemeID)]', $invoiceTaxRepresentativePartyNode)->forEach(
                    function ($invoiceAccountingCustomerPartyIdNode) {
                        $this->destination->element('ram:ID', $invoiceAccountingCustomerPartyIdNode->nodeValue);
                    }
                );
                $this->source->queryAll('./cac:PartyIdentification/cbc:ID[@schemeID]', $invoiceTaxRepresentativePartyNode)->forEach(
                    function ($invoiceAccountingCustomerPartyIdNode) {
                        $this->destination->elementWithAttribute('ram:GlobalID', $invoiceAccountingCustomerPartyIdNode->nodeValue, 'schemeID', $invoiceAccountingCustomerPartyIdNode->getAttribute('schemeID'));
                    }
                );
                $this->destination->element('ram:Name', $this->source->queryValue('./cac:PartyName/cbc:Name', $invoiceTaxRepresentativePartyNode));
                $this->source->whenExists(
                    './cac:PostalAddress',
                    $invoiceTaxRepresentativePartyNode,
                    function ($invoiceTaxRepresentativePartyPostalAddressNode) {
                        $this->destination->startElement('ram:PostalTradeAddress');
                        $this->destination->element('ram:PostcodeCode', $this->source->queryValue('./cbc:PostalZone', $invoiceTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('ram:LineOne', $this->source->queryValue('./cbc:StreetName', $invoiceTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('ram:LineTwo', $this->source->queryValue('./cbc:AdditionalStreetName', $invoiceTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('ram:LineThree', $this->source->queryValue('./cac:AddressLine/cbc:Line', $invoiceTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('ram:CityName', $this->source->queryValue('./cbc:CityName', $invoiceTaxRepresentativePartyPostalAddressNode));
                        $this->destination->element('ram:CountryID', $this->source->queryValue('./cac:Country/cbc:IdentificationCode', $invoiceTaxRepresentativePartyPostalAddressNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'VAT\']',
                    $invoiceTaxRepresentativePartyNode,
                    function ($invoiceTaxRepresentativePartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceTaxRepresentativePartyTaxSchemeNode), 'schemeID', 'VA');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'FC\']',
                    $invoiceTaxRepresentativePartyNode,
                    function ($invoiceTaxRepresentativePartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceTaxRepresentativePartyTaxSchemeNode), 'schemeID', 'FC');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'???\']',
                    $invoiceTaxRepresentativePartyNode,
                    function ($invoiceTaxRepresentativePartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceTaxRepresentativePartyTaxSchemeNode), 'schemeID', 'FC');
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
            }
        );

        $this->source->whenExists(
            './cac:OrderReference/cbc:SalesOrderID',
            $docRootElement,
            function ($orderReferenceSalesOrderNode) {
                $this->destination->startElement('ram:SellerOrderReferencedDocument');
                $this->destination->element('ram:IssuerAssignedID', trim($orderReferenceSalesOrderNode->nodeValue));
                $this->destination->endElement();
            }
        );

        $this->source->whenExists(
            './cac:OrderReference/cbc:ID',
            $docRootElement,
            function ($orderReferenceSalesOrderNode) {
                $this->destination->startElement('ram:BuyerOrderReferencedDocument');
                $this->destination->element('ram:IssuerAssignedID', trim($orderReferenceSalesOrderNode->nodeValue));
                $this->destination->endElement();
            }
        );

        $this->source->queryAll('./cac:ContractDocumentReference', $docRootElement)->forEach(
            function ($contractReferenceDocumenntNode) {
                $this->destination->startElement('ram:ContractReferencedDocument');
                $this->destination->element('ram:IssuerAssignedID', trim($contractReferenceDocumenntNode->nodeValue));
                $this->destination->endElement();
            }
        );

        $this->source->queryAll('./cac:AdditionalDocumentReference', $docRootElement)->forEach(
            function ($additionalDocumentReferenceNode) {
                $this->destination->startElement('ram:AdditionalReferencedDocument');
                $this->destination->element('ram:IssuerAssignedID', $this->source->queryValue('./cbc:ID', $additionalDocumentReferenceNode));
                $this->destination->element('ram:TypeCode', '916');
                $this->destination->element('ram:Name', $this->source->queryValue('./cbc:DocumentDescription', $additionalDocumentReferenceNode));
                $this->source->whenExists(
                    './cac:Attachment/cbc:EmbeddedDocumentBinaryObject',
                    $additionalDocumentReferenceNode,
                    function ($additionalDocumentReferenceAttNode) {
                        $this->destination->startElement('ram:AttachmentBinaryObject', $additionalDocumentReferenceAttNode->nodeValue);
                        $this->destination->attribute('mimeCode', $additionalDocumentReferenceAttNode->getAttribute('mimeCode'));
                        $this->destination->attribute('filename', $additionalDocumentReferenceAttNode->getAttribute('filename'));
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
            }
        );

        $this->source->queryAll('./cac:OriginatorDocumentReference', $docRootElement)->forEach(
            function ($additionalDocumentReferenceNode) {
                $this->destination->startElement('ram:AdditionalReferencedDocument');
                $this->destination->element('ram:IssuerAssignedID', $this->source->queryValue('./cbc:ID', $additionalDocumentReferenceNode));
                $this->destination->element('ram:TypeCode', '50');
                $this->destination->element('ram:Name', $this->source->queryValue('./cbc:DocumentDescription', $additionalDocumentReferenceNode));
                $this->destination->endElement();
            }
        );

        $this->source->queryAll('./cac:ProjectReference', $docRootElement)->forEach(
            function ($additionalDocumentReferenceNode) {
                $this->destination->startElement('ram:SpecifiedProcuringProject');
                $this->destination->element('ram:ID', $this->source->queryValue('./cbc:ID', $additionalDocumentReferenceNode));
                $this->destination->element('ram:Name', 'Project Reference');
                $this->destination->endElement();
            }
        );

        $this->destination->endElement();
    }

    /**
     * Convert ApplicableHeaderTradeDelivery
     *
     * @return void
     */
    private function convertApplicableHeaderTradeDelivery(): void
    {
        $docRootElement = $this->source->query(sprintf('//%s', $this->ublRootName))->item(0);

        $this->destination->startElement('ram:ApplicableHeaderTradeDelivery');

        $this->source->whenExists(
            './cac:Delivery/cac:DeliveryLocation',
            $docRootElement,
            function ($deliveryLocationNode, $deliveryNode) {
                $this->destination->startElement('ram:ShipToTradeParty');
                $this->destination->element('ram:ID', $this->source->queryValue('./cbc:ID', $deliveryLocationNode));
                $this->destination->element('ram:Name', $this->source->queryValue('./cac:DeliveryParty/cac:PartyName/cbc:Name', $deliveryNode));
                $this->source->whenExists(
                    './cac:Address',
                    $deliveryLocationNode,
                    function ($deliveryLocationAddressNode) {
                        $this->destination->startElement('ram:PostalTradeAddress');
                        $this->destination->element('ram:PostcodeCode', $this->source->queryValue('./cbc:PostalZone', $deliveryLocationAddressNode));
                        $this->destination->element('ram:LineOne', $this->source->queryValue('./cbc:StreetName', $deliveryLocationAddressNode));
                        $this->destination->element('ram:LineTwo', $this->source->queryValue('./cbc:AdditionalStreetName', $deliveryLocationAddressNode));
                        $this->destination->element('ram:CityName', $this->source->queryValue('./cbc:CityName', $deliveryLocationAddressNode));
                        $this->destination->element('ram:CountryID', $this->source->queryValue('./cac:Country/cbc:IdentificationCode', $deliveryLocationAddressNode));
                        $this->destination->element('ram:CountrySubDivisionName', $this->source->queryValue('./cbc:CountrySubentity', $deliveryLocationAddressNode));
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
            }
        );

        $this->source->whenExists(
            './cac:Delivery/cbc:ActualDeliveryDate',
            $docRootElement,
            function ($actualDeliveryDateNode) {
                $this->destination->startElement('ram:ActualDeliverySupplyChainEvent');
                $this->destination->startElement('ram:OccurrenceDateTime');
                $this->destination->elementWithAttribute('udt:DateTimeString', $this->convertDateTime($actualDeliveryDateNode->nodeValue), 'format', '102');
                $this->destination->endElement();
                $this->destination->endElement();
            }
        );

        $this->destination->endElement();
    }

    /**
     * Convert ApplicableHeaderTradeSettlement
     *
     * @return void
     */
    private function convertApplicableHeaderTradeSettlement(): void
    {
        $docRootElement = $this->source->query(sprintf('//%s', $this->ublRootName))->item(0);

        $this->destination->startElement('ram:ApplicableHeaderTradeSettlement');

        $this->source->whenExists(
            './cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID[@schemeID=\'SEPA\']', $docRootElement, function ($CreditorReferenceNode) {
                $this->destination->element('ram:CreditorReferenceID', $CreditorReferenceNode->nodeValue);
            }
        );

        $this->source->whenExists(
            './cac:PaymentMeans',
            $docRootElement,
            function ($paymentMeansNode) {
                $this->destination->element('ram:PaymentReference', $this->source->queryValue('./cbc:PaymentID', $paymentMeansNode));
            }
        );
        $this->source->whenExists(
            './cbc:TaxCurrencyCode',
            $docRootElement,
            function ($documentCUrrencyNode) {
                $this->destination->element('ram:TaxCurrencyCode', $documentCUrrencyNode->nodeValue);
            }
        );
        $this->source->whenExists(
            './cbc:DocumentCurrencyCode',
            $docRootElement,
            function ($documentCUrrencyNode) {
                $this->destination->element('ram:InvoiceCurrencyCode', $documentCUrrencyNode->nodeValue);
            }
        );
        $this->source->whenExists(
            './cac:PayeeParty',
            $docRootElement,
            function ($invoicePayeePartyNode) {
                $this->destination->startElement('ram:PayeeTradeParty');
                $this->source->queryAll('./cac:PartyIdentification/cbc:ID[not(@schemeID)]', $invoicePayeePartyNode)->forEach(
                    function ($invoiceAccountingCustomerPartyIdNode) {
                        $this->destination->element('ram:ID', $invoiceAccountingCustomerPartyIdNode->nodeValue);
                    }
                );
                $this->source->queryAll('./cac:PartyIdentification/cbc:ID[@schemeID]', $invoicePayeePartyNode)->forEach(
                    function ($invoiceAccountingCustomerPartyIdNode) {
                        $this->destination->elementWithAttribute('ram:GlobalID', $invoiceAccountingCustomerPartyIdNode->nodeValue, 'schemeID', $invoiceAccountingCustomerPartyIdNode->getAttribute('schemeID'));
                    }
                );
                $this->destination->element('ram:Name', $this->source->queryValue('./cac:PartyName/cbc:Name', $invoicePayeePartyNode));
                $this->source->whenExists(
                    './cac:Contact',
                    $invoicePayeePartyNode,
                    function ($invoiceAccountingCustomerPartyContactNode) {
                        $this->destination->startElement('ram:DefinedTradeContact');
                        $this->destination->element('ram:PersonName', $this->source->queryValue('./cbc:Name', $invoiceAccountingCustomerPartyContactNode));
                        $this->source->whenExists(
                            './cbc:Telephone',
                            $invoiceAccountingCustomerPartyContactNode,
                            function ($invoiceAccountingCustomerPartyContactPhoneNode) {
                                $this->destination->startElement('ram:TelephoneUniversalCommunication');
                                $this->destination->element('ram:CompleteNumber', $invoiceAccountingCustomerPartyContactPhoneNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->source->whenExists(
                            './cbc:ElectronicMail',
                            $invoiceAccountingCustomerPartyContactNode,
                            function ($invoiceAccountingCustomerPartyContactMailNode) {
                                $this->destination->startElement('ram:EmailURIUniversalCommunication');
                                $this->destination->element('ram:URIID', $invoiceAccountingCustomerPartyContactMailNode->nodeValue);
                                $this->destination->endElement();
                            }
                        );
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PostalAddress',
                    $invoicePayeePartyNode,
                    function ($invoiceAccountingCustomerPartyPostalAddressNode) {
                        $this->destination->startElement('ram:PostalTradeAddress');
                        $this->destination->element('ram:PostcodeCode', $this->source->queryValue('./cbc:PostalZone', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->element('ram:LineOne', $this->source->queryValue('./cbc:StreetName', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->element('ram:LineTwo', $this->source->queryValue('./cbc:AdditionalStreetName', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->element('ram:LineThree', $this->source->queryValue('./cac:AddressLine/cbc:Line', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->element('ram:CityName', $this->source->queryValue('./cbc:CityName', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->element('ram:CountryID', $this->source->queryValue('./cac:Country/cbc:IdentificationCode', $invoiceAccountingCustomerPartyPostalAddressNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cbc:EndpointID[@schemeID=\'EM\']',
                    $invoicePayeePartyNode,
                    function ($invoiceAccountingCustomerPartyEndpointNode) {
                        $this->destination->startElement('ram:URIUniversalCommunication');
                        $this->destination->elementWithAttribute('ram:URIID', $invoiceAccountingCustomerPartyEndpointNode->nodeValue, 'schemeID', 'EM');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'VAT\']',
                    $invoicePayeePartyNode,
                    function ($invoiceAccountingCustomerPartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceAccountingCustomerPartyTaxSchemeNode), 'schemeID', 'VA');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'FC\']',
                    $invoicePayeePartyNode,
                    function ($invoiceAccountingCustomerPartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceAccountingCustomerPartyTaxSchemeNode), 'schemeID', 'FC');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'???\']',
                    $invoicePayeePartyNode,
                    function ($invoiceAccountingCustomerPartyTaxSchemeNode) {
                        $this->destination->startElement('ram:SpecifiedTaxRegistration');
                        $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceAccountingCustomerPartyTaxSchemeNode), 'schemeID', 'FC');
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
            }
        );
        $this->source->whenExists(
            './cac:PaymentMeans',
            $docRootElement,
            function ($paymentMeansNode) {
                $this->destination->startElement('ram:SpecifiedTradeSettlementPaymentMeans');
                $this->destination->element('ram:TypeCode', $this->source->queryValue('./cbc:PaymentMeansCode', $paymentMeansNode));
                $this->source->whenExists(
                    './cac:CardAccount',
                    $paymentMeansNode,
                    function ($payeeCardAccountNode) {
                        $this->destination->startElement('ram:ApplicableTradeSettlementFinancialCard');
                        $this->destination->element('ram:ID', $this->source->queryValue('./cbc:PrimaryAccountNumberID', $payeeCardAccountNode));
                        $this->destination->element('ram:CardholderName', $this->source->queryValue('./cbc:HolderName', $payeeCardAccountNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PaymentMandate',
                    $paymentMeansNode,
                    function ($paymentMandateNode) {
                        $this->destination->startElement('ram:PayerPartyDebtorFinancialAccount');
                        $this->destination->element('ram:IBANID', $this->source->queryValue('./cac:PayerFinancialAccount/cbc:ID', $paymentMandateNode));
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cac:PayeeFinancialAccount',
                    $paymentMeansNode,
                    function ($payeeFinancialAccountNode) {
                        $this->destination->startElement('ram:PayeePartyCreditorFinancialAccount');
                        $this->destination->element('ram:IBANID', $this->source->queryValue('./cbc:ID', $payeeFinancialAccountNode));
                        $this->destination->element('ram:AccountName', $this->source->queryValue('./cbc:Name', $payeeFinancialAccountNode));
                        $this->destination->endElement();
                        $this->source->whenExists(
                            './cac:FinancialInstitutionBranch',
                            $payeeFinancialAccountNode,
                            function ($financialInstitutionBranchNode) {
                                $this->destination->startElement('ram:PayeeSpecifiedCreditorFinancialInstitution');
                                $this->destination->element('ram:BICID', trim($financialInstitutionBranchNode->nodeValue));
                                $this->destination->endElement();
                            }
                        );
                    }
                );
                $this->destination->endElement();
            }
        );

        $this->source->queryAll('./cac:TaxTotal/cac:TaxSubtotal', $docRootElement)->forEach(
            function ($taxSubtotalNode) use ($docRootElement) {
                $this->destination->startElement('ram:ApplicableTradeTax');
                $this->destination->element('ram:CalculatedAmount', $this->source->queryValue('./cbc:TaxAmount', $taxSubtotalNode));
                $this->destination->element('ram:TypeCode', $this->source->queryValue('./cac:TaxCategory/cac:TaxScheme/cbc:ID', $taxSubtotalNode));
                $this->destination->element('ram:BasisAmount', $this->source->queryValue('./cbc:TaxableAmount', $taxSubtotalNode));
                $this->destination->element('ram:CategoryCode', $this->source->queryValue('./cac:TaxCategory/cbc:ID', $taxSubtotalNode));
                $this->destination->element('ram:ExemptionReasonCode', $this->source->queryValue('./cac:TaxCategory/cbc:TaxExemptionReasonCode', $taxSubtotalNode));
                $this->destination->element('ram:RateApplicablePercent', $this->source->queryValue('./cac:TaxCategory/cbc:Percent', $taxSubtotalNode));
                $this->destination->endElement();
            }
        );

        $this->source->whenExists(
            './cac:InvoicePeriod',
            $docRootElement,
            function ($invoicePeriodNode) {
                $this->destination->startElement('ram:BillingSpecifiedPeriod');
                $this->source->whenExists(
                    './cbc:StartDate',
                    $invoicePeriodNode,
                    function ($invoicePeriodStartDateNode) {
                        $this->destination->startElement('ram:StartDateTime');
                        $this->destination->elementWithAttribute('udt:DateTimeString', $this->convertDateTime($invoicePeriodStartDateNode->nodeValue), 'format', '102');
                        $this->destination->endElement();
                    }
                );
                $this->source->whenExists(
                    './cbc:EndDate',
                    $invoicePeriodNode,
                    function ($invoicePeriodStartDateNode) {
                        $this->destination->startElement('ram:EndDateTime');
                        $this->destination->elementWithAttribute('udt:DateTimeString', $this->convertDateTime($invoicePeriodStartDateNode->nodeValue), 'format', '102');
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
            }
        );

        $this->source->queryAll('./cac:AllowanceCharge', $docRootElement)->forEach(
            function ($allowanceChargeNode) {
                $this->destination->startElement('ram:SpecifiedTradeAllowanceCharge');
                $this->source->whenExists(
                    './cbc:ChargeIndicator',
                    $allowanceChargeNode,
                    function ($chargeIndicatorNode) {
                        $this->destination->startElement('ram:ChargeIndicator');
                        $this->destination->element('udt:Indicator', $chargeIndicatorNode->nodeValue);
                        $this->destination->endElement();
                    }
                );
                $this->destination->element('ram:CalculationPercent', $this->source->queryValue('./cbc:MultiplierFactorNumeric', $allowanceChargeNode));
                $this->destination->element('ram:BasisAmount', $this->source->queryValue('./cbc:BaseAmount', $allowanceChargeNode));
                $this->destination->element('ram:ActualAmount', $this->source->queryValue('./cbc:Amount', $allowanceChargeNode));
                $this->destination->element('ram:ReasonCode', $this->source->queryValue('./cbc:AllowanceChargeReasonCode', $allowanceChargeNode));
                $this->destination->element('ram:Reason', $this->source->queryValue('./cbc:AllowanceChargeReason', $allowanceChargeNode));
                $this->source->whenExists(
                    './cac:TaxCategory',
                    $allowanceChargeNode,
                    function ($AllowanceChargeTaxNode) {
                        $this->destination->startElement('ram:CategoryTradeTax');
                        $this->destination->element('ram:TypeCode', $this->source->queryValue('./cac:TaxScheme/cbc:ID', $AllowanceChargeTaxNode));
                        $this->destination->element('ram:CategoryCode', $this->source->queryValue('./cbc:ID', $AllowanceChargeTaxNode));
                        $this->destination->element('ram:RateApplicablePercent', $this->source->queryValue('./cbc:Percent', $AllowanceChargeTaxNode));
                        $this->destination->endElement();
                    }
                );
                $this->destination->endElement();
            }
        );

        $this->source->whenExists(
            './cbc:DueDate',
            $docRootElement,
            function ($invoiceDueDateNode) use ($docRootElement) {
                $this->destination->startElement('ram:SpecifiedTradePaymentTerms');
                $this->destination->element('ram:Description', $this->source->queryValue('./cac:PaymentTerms/cbc:Note', $docRootElement));
                $this->destination->startElement('ram:DueDateDateTime');
                $this->destination->elementWithAttribute('udt:DateTimeString', $this->convertDateTime($invoiceDueDateNode->nodeValue), "format", "102");
                $this->destination->endElement();
                $this->destination->element('ram:DirectDebitMandateID', $this->source->queryValue('./cac:PaymentMeans/cac:PaymentMandate/cbc:ID'));
                $this->destination->endElement();
            },
            function () use ($docRootElement) {
                $this->source->whenExists(
                    './cac:PaymentTerms/cbc:Note', $docRootElement, function ($paymentTermaNoteNode) {
                        $this->destination->startElement('ram:SpecifiedTradePaymentTerms');
                        $this->destination->element('ram:Description', $paymentTermaNoteNode->nodeValue);
                        $this->destination->element('ram:DirectDebitMandateID', $this->source->queryValue('./cac:PaymentMeans/cac:PaymentMandate/cbc:ID'));
                        $this->destination->endElement();
                    }
                );
            }
        );

        $this->source->whenExists(
            './cac:LegalMonetaryTotal',
            $docRootElement,
            function ($invoiceMoneraryTotalNode) use ($docRootElement) {
                $invoiceCurrencyCode = $this->source->queryValue('./cbc:DocumentCurrencyCode', $docRootElement);
                $taxCurrencyCode = $this->source->queryValue('./cbc:TaxCurrencyCode', $docRootElement);
                $this->destination->startElement('ram:SpecifiedTradeSettlementHeaderMonetarySummation');
                $this->destination->element('ram:LineTotalAmount', $this->source->queryValue('./cbc:LineExtensionAmount', $invoiceMoneraryTotalNode));
                $this->destination->element('ram:ChargeTotalAmount', $this->source->queryValue('./cbc:ChargeTotalAmount', $invoiceMoneraryTotalNode));
                $this->destination->element('ram:AllowanceTotalAmount', $this->source->queryValue('./cbc:AllowanceTotalAmount', $invoiceMoneraryTotalNode));
                $this->destination->element('ram:TaxBasisTotalAmount', $this->source->queryValue('./cbc:TaxExclusiveAmount', $invoiceMoneraryTotalNode));
                $this->destination->elementWithAttribute('ram:TaxTotalAmount', $this->source->queryValue(sprintf('./cac:TaxTotal/cbc:TaxAmount[@currencyID=\'%s\']', $invoiceCurrencyCode), $docRootElement), 'currencyID', $invoiceCurrencyCode);
                $this->source->whenExists(
                    sprintf('./cac:TaxTotal/cbc:TaxAmount[@currencyID=\'%s\']', $taxCurrencyCode),
                    $docRootElement,
                    function ($diffTaxNode) use ($taxCurrencyCode) {
                        $this->destination->elementWithAttribute('ram:TaxTotalAmount', $diffTaxNode->nodeValue, 'currencyID', $taxCurrencyCode);
                    }
                );
                $this->source->whenExists(
                    './cbc:PayableRoundingAmount',
                    $invoiceMoneraryTotalNode,
                    function ($payableRoundingAmountNode) {
                        $this->destination->element('ram:RoundingAmount', $payableRoundingAmountNode->nodeValue);
                    },
                    function () {
                        $this->destination->element('ram:RoundingAmount', '0');
                    }
                );
                $this->destination->element('ram:GrandTotalAmount', $this->source->queryValue('./cbc:TaxInclusiveAmount', $invoiceMoneraryTotalNode));
                $this->destination->element('ram:TotalPrepaidAmount', $this->source->queryValue('./cbc:PrepaidAmount', $invoiceMoneraryTotalNode));
                $this->destination->element('ram:DuePayableAmount', $this->source->queryValue('./cbc:PayableAmount', $invoiceMoneraryTotalNode));
                $this->destination->endElement();
            }
        );

        $this->destination->endElement();
    }

    /**
     * Convert UBL date to CII date
     *
     * @param  string|null $dateTimeString
     * @return string
     */
    private function convertDateTime(?string $dateTimeString): string
    {
        if (is_null($dateTimeString)) {
            return null;
        }

        $dt = DateTime::createFromFormat("Y-m-d", $dateTimeString);

        if ($dt === false) {
            return null;
        }

        return $dt->format('Ymd');
    }
}
