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
        $this->source->whenExists(
            './cbc:Note',
            $docRootElement,
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
                $this->source->queryAll('./cac:PartyIdentification/cbc:ID[@schemeID]', $invoiceAccountingSupplierPartyNode)->forEach(
                    function ($invoiceAccountingSupplierPartyIdNode) {
                        $this->destination->elementWithAttribute('ram:GlobalID', $invoiceAccountingSupplierPartyIdNode->nodeValue, 'schemeID', $invoiceAccountingSupplierPartyIdNode->getAttribute('schemeID'));
                    }
                );
                $this->source->whenExists(
                    './cac:PartyLegalEntity/cbc:RegistrationName',
                    $invoiceAccountingSupplierPartyNode,
                    function ($invoiceAccountingSupplierPartyLegalEntityNode) {
                        $this->destination->element('ram:Name', $invoiceAccountingSupplierPartyLegalEntityNode->nodeValue);
                    },
                    function () use ($invoiceAccountingSupplierPartyNode) {
                        $this->destination->element('ram:Name', $this->source->queryValue('./cac:PartyName/cbc:Name', $invoiceAccountingSupplierPartyNode));
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
                    './cac:PartyLegalEntity/cbc:CompanyID',
                    $invoiceAccountingSupplierPartyNode,
                    function ($invoiceAccountingSupplierPartyLegalEntityCompanyIdNode, $invoiceAccountingSupplierPartyLegalEntityNode) use ($invoiceAccountingSupplierPartyNode) {
                        $this->destination->startElement('ram:SpecifiedLegalOrganization');
                        $this->destination->elementWithAttribute('ram:ID', $invoiceAccountingSupplierPartyLegalEntityCompanyIdNode->nodeValue, 'schemeID', $invoiceAccountingSupplierPartyLegalEntityCompanyIdNode->getAttribute('schemeID'));
                        $this->source->whenExists(
                            './cac:PartyName/cbc:Name',
                            $invoiceAccountingSupplierPartyNode,
                            function ($invoiceAccountingSupplierPartyNameNode) {
                                $this->destination->element('ram:TradingBusinessName', $invoiceAccountingSupplierPartyNameNode->nodeValue);
                            },
                            function () use ($invoiceAccountingSupplierPartyLegalEntityNode) {
                                $this->source->whenExists(
                                    './cbc:RegistrationName',
                                    $invoiceAccountingSupplierPartyLegalEntityNode,
                                    function ($invoiceAccountingSupplierPartyLegalEntityRegNameNode) {
                                        $this->destination->element('ram:TradingBusinessName', $invoiceAccountingSupplierPartyLegalEntityRegNameNode->nodeValue);
                                    }
                                );
                            }
                        );
                        $this->destination->endElement();
                    },
                    function () {
                        //TODO: Implement or delete
                    }
                );
                $this->source->whenExists('./cac:Contact', $invoiceAccountingSupplierPartyNode, function ($invoiceAccountingSupplierPartyContactNode) {
                    $this->destination->startElement('ram:DefinedTradeContact');
                    $this->destination->element('ram:PersonName', $this->source->queryValue('./cbc:Name', $invoiceAccountingSupplierPartyContactNode));
                    $this->source->whenExists('./cbc:Telephone', $invoiceAccountingSupplierPartyContactNode, function ($invoiceAccountingSupplierPartyContactPhoneNode) {
                        $this->destination->startElement('ram:TelephoneUniversalCommunication');
                        $this->destination->element('ram:CompleteNumber', $invoiceAccountingSupplierPartyContactPhoneNode->nodeValue);
                        $this->destination->endElement();
                    });
                    $this->source->whenExists('./cbc:ElectronicMail', $invoiceAccountingSupplierPartyContactNode, function ($invoiceAccountingSupplierPartyContactMailNode) {
                        $this->destination->startElement('ram:EmailURIUniversalCommunication');
                        $this->destination->element('ram:URIID', $invoiceAccountingSupplierPartyContactMailNode->nodeValue);
                        $this->destination->endElement();
                    });
                    $this->destination->endElement();
                });
                $this->source->whenExists('./cac:PostalAddress', $invoiceAccountingSupplierPartyNode, function ($invoiceAccountingSupplierPartyPostalAddressNode) {
                    $this->destination->startElement('ram:PostalTradeAddress');
                    $this->destination->element('ram:PostcodeCode', $this->source->queryValue('./cbc:PostalZone', $invoiceAccountingSupplierPartyPostalAddressNode));
                    $this->destination->element('ram:LineOne', $this->source->queryValue('./cbc:StreetName', $invoiceAccountingSupplierPartyPostalAddressNode));
                    $this->destination->element('ram:LineTwo', $this->source->queryValue('./cbc:AdditionalStreetName', $invoiceAccountingSupplierPartyPostalAddressNode));
                    $this->destination->element('ram:CityName', $this->source->queryValue('./cbc:CityName', $invoiceAccountingSupplierPartyPostalAddressNode));
                    $this->destination->element('ram:CountryID', $this->source->queryValue('./cac:Country/cbc:IdentificationCode', $invoiceAccountingSupplierPartyPostalAddressNode));
                    $this->destination->endElement();
                });
                $this->source->whenExists('./cbc:EndpointID[@schemeID=\'EM\']', $invoiceAccountingSupplierPartyNode, function($invoiceAccountingSupplierPartyEndpointNode) {
                    $this->destination->startElement('ram:URIUniversalCommunication');
                    $this->destination->elementWithAttribute('ram:URIID', $invoiceAccountingSupplierPartyEndpointNode->nodeValue, 'schemeID', 'EM');
                    $this->destination->endElement();
                });
                $this->source->whenExists('./cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'VAT\']', $invoiceAccountingSupplierPartyNode, function($invoiceAccountingSupplierPartyTaxSchemeNode) {
                    $this->destination->startElement('ram:SpecifiedTaxRegistration');
                    $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceAccountingSupplierPartyTaxSchemeNode), 'schemeID', 'VA');
                    $this->destination->endElement();
                });
                $this->source->whenExists('./cac:PartyTaxScheme/cac:TaxScheme/cbc:ID[text() = \'FC\']', $invoiceAccountingSupplierPartyNode, function($invoiceAccountingSupplierPartyTaxSchemeNode) {
                    $this->destination->startElement('ram:SpecifiedTaxRegistration');
                    $this->destination->elementWithAttribute('ram:ID', $this->source->queryValue('../../cbc:CompanyID', $invoiceAccountingSupplierPartyTaxSchemeNode), 'schemeID', 'FC');
                    $this->destination->endElement();
                });
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
    }

    /**
     * Convert ApplicableHeaderTradeSettlement
     *
     * @return void
     */
    private function convertApplicableHeaderTradeSettlement(): void
    {
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
