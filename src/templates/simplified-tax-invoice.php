<?php

// BT-110

use Malik12tree\ZATCA\Utils\Rendering\Template;

$totalTaxes = 0;
$totalSubtotal = 0;

$lineItemsRender = '';

foreach ($LINE_ITEMS as $lineItem) {

  list($lineItemRender, $lineItemTotals) = Template::render('@simplified-tax-invoice/line-item', [
    'LINE_ITEM' => $lineItem
  ], true);

  $totalTaxes += $lineItemTotals['taxes_total'];
  $totalSubtotal += (float)$lineItemTotals['subtotal'];

  $lineItemsRender .= $lineItemRender;
  $lineItemsRender .= "\n";
}

list($taxTotalRender, $totalTax) = Template::render('@simplified-tax-invoice/tax-total', [
  'LINE_ITEMS' => $LINE_ITEMS
], true);
list($legalMonetaryTotalRender, $total) = Template::render('@simplified-tax-invoice/legal-monetary-total', [
  'TOTAL_SUBTOTAL' => $totalSubtotal,
  'TOTAL_TAXES' => $totalTaxes
], true);

?>
<?= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" ?>

<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
  <ext:UBLExtensions>%UBL_EXTENSIONS_STRING%</ext:UBLExtensions>

  <cbc:ProfileID>reporting:1.0</cbc:ProfileID>
  <cbc:ID><?= $INVOICE_SERIAL_NUMBER ?></cbc:ID>
  <cbc:UUID><?= $EGS_INFO["uuid"] ?></cbc:UUID>
  <cbc:IssueDate><?= $ISSUE_DATE ?></cbc:IssueDate>
  <cbc:IssueTime><?= $ISSUE_TIME ?></cbc:IssueTime>
  <cbc:InvoiceTypeCode name="<?= $INVOICE_CODE ?>"><?= $INVOICE_TYPE ?></cbc:InvoiceTypeCode>
  <cbc:DocumentCurrencyCode>SAR</cbc:DocumentCurrencyCode>
  <cbc:TaxCurrencyCode>SAR</cbc:TaxCurrencyCode>
  <?php if ($CANCELLATION): ?>
    <cac:BillingReference>
      <cac:InvoiceDocumentReference>
        <cbc:ID><?= $CANCELLATION["canceled_serial_invoice_number"] ?></cbc:ID>
      </cac:InvoiceDocumentReference>
    </cac:BillingReference>
  <?php endif ?>
  <cac:AdditionalDocumentReference>
    <cbc:ID>ICV</cbc:ID>
    <cbc:UUID><?= $INVOICE_COUNTER_NUMBER ?></cbc:UUID>
  </cac:AdditionalDocumentReference>
  <cac:AdditionalDocumentReference>
    <cbc:ID>PIH</cbc:ID>
    <cac:Attachment>
      <cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain"><?= $PREVIOUS_INVOICE_HASH ?></cbc:EmbeddedDocumentBinaryObject>
    </cac:Attachment>
  </cac:AdditionalDocumentReference>
  <cac:AdditionalDocumentReference>
    <cbc:ID>QR</cbc:ID>
    <cac:Attachment>
      <cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain">%QR_CODE_DATA%</cbc:EmbeddedDocumentBinaryObject>
    </cac:Attachment>
  </cac:AdditionalDocumentReference>
  <cac:Signature>
    <cbc:ID>urn:oasis:names:specification:ubl:signature:Invoice</cbc:ID>
    <cbc:SignatureMethod>urn:oasis:names:specification:ubl:dsig:enveloped:xades</cbc:SignatureMethod>
  </cac:Signature>
  <cac:AccountingSupplierParty>
    <cac:Party>
      <cac:PartyIdentification>
        <cbc:ID schemeID="CRN"><?= $EGS_INFO["crn_number"] ?></cbc:ID>
      </cac:PartyIdentification>
      <cac:PostalAddress>
        <?php if (isset($EGS_INFO["location"]["street"])): ?>
          <cbc:StreetName><?= $EGS_INFO["location"]["street"] ?></cbc:StreetName>
        <?php endif ?>
        <?php if (isset($EGS_INFO["location"]["building"])): ?>
          <cbc:BuildingNumber><?= $EGS_INFO["location"]["building"] ?></cbc:BuildingNumber>
        <?php endif ?>
        <?php if (isset($EGS_INFO["location"]["plot_identification"])): ?>
          <cbc:PlotIdentification><?= $EGS_INFO["location"]["plot_identification"] ?></cbc:PlotIdentification>
        <?php endif ?>
        <?php if (isset($EGS_INFO["location"]["city_subdivision"])): ?>
          <cbc:CitySubdivisionName><?= $EGS_INFO["location"]["city_subdivision"] ?></cbc:CitySubdivisionName>
        <?php endif ?>
        <?php if (isset($EGS_INFO["location"]["city"])): ?>
          <cbc:CityName><?= $EGS_INFO["location"]["city"] ?></cbc:CityName>
        <?php endif ?>
        <?php if (isset($EGS_INFO["location"]["postal_zone"])): ?>
          <cbc:PostalZone><?= $EGS_INFO["location"]["postal_zone"] ?></cbc:PostalZone>
        <?php endif ?>
        <cac:Country>
          <cbc:IdentificationCode>SA</cbc:IdentificationCode>
        </cac:Country>
      </cac:PostalAddress>
      <cac:PartyTaxScheme>
        <cbc:CompanyID><?= $EGS_INFO["vat_number"] ?></cbc:CompanyID>
        <cac:TaxScheme>
          <cbc:ID>VAT</cbc:ID>
        </cac:TaxScheme>
      </cac:PartyTaxScheme>
      <cac:PartyLegalEntity>
        <cbc:RegistrationName><?= $EGS_INFO["vat_name"] ?></cbc:RegistrationName>
      </cac:PartyLegalEntity>
    </cac:Party>
  </cac:AccountingSupplierParty>
  <cac:AccountingCustomerParty>
    <?php if (isset($EGS_INFO["customer_info"])): ?>
      <cac:Party>
        <cac:PartyIdentification>
          <cbc:ID schemeID="CRN"><?= isset($EGS_INFO["customer_info"]["crn_number"]) ? $EGS_INFO["customer_info"]["crn_number"] : '' ?></cbc:ID>
        </cac:PartyIdentification>
        <cac:PostalAddress>
          <?php if (isset($EGS_INFO["customer_info"]["street"])): ?>
            <cbc:StreetName><?= $EGS_INFO["customer_info"]["street"] ?></cbc:StreetName>
          <?php endif ?>
          <?php if (isset($EGS_INFO["customer_info"]["additional_street"])): ?>
            <cbc:AdditionalStreetName><?= $EGS_INFO["customer_info"]["additional_street"] ?></cbc:AdditionalStreetName>
          <?php endif ?>
          <?php if (isset($EGS_INFO["customer_info"]["building"])): ?>
            <cbc:BuildingNumber><?= $EGS_INFO["customer_info"]["building"] ?></cbc:BuildingNumber>
          <?php endif ?>
          <?php if (isset($EGS_INFO["customer_info"]["plot_identification"])): ?>
            <cbc:PlotIdentification><?= $EGS_INFO["customer_info"]["plot_identification"] ?></cbc:PlotIdentification>
          <?php endif ?>
          <?php if (isset($EGS_INFO["customer_info"]["city_subdivision"])): ?>
            <cbc:CitySubdivisionName><?= $EGS_INFO["customer_info"]["city_subdivision"] ?></cbc:CitySubdivisionName>
          <?php endif ?>
          <?php if (isset($EGS_INFO["customer_info"]["city"])): ?>
            <cbc:CityName><?= $EGS_INFO["customer_info"]["city"] ?></cbc:CityName>
          <?php endif ?>
          <?php if (isset($EGS_INFO["customer_info"]["postal_zone"])): ?>
            <cbc:PostalZone><?= $EGS_INFO["customer_info"]["postal_zone"] ?></cbc:PostalZone>
          <?php endif ?>
          <?php if (isset($EGS_INFO["customer_info"]["country_sub_entity"])): ?>
            <cbc:CountrySubentity><?= $EGS_INFO["customer_info"]["country_sub_entity"] ?></cbc:CountrySubentity>
          <?php endif ?>
          <cac:Country>
            <cbc:IdentificationCode>SA</cbc:IdentificationCode>
          </cac:Country>
        </cac:PostalAddress>
        <?php if (isset($EGS_INFO["customer_info"]["vat_number"])): ?>
          <cac:PartyTaxScheme>
            <cbc:CompanyID><?= $EGS_INFO["customer_info"]["vat_number"] ?></cbc:CompanyID>
            <cac:TaxScheme>
              <cbc:ID>VAT</cbc:ID>
            </cac:TaxScheme>
          </cac:PartyTaxScheme>
        <?php endif ?>
        <cac:PartyLegalEntity>
          <cbc:RegistrationName><?= $EGS_INFO["customer_info"]["buyer_name"] ?></cbc:RegistrationName>
        </cac:PartyLegalEntity>
      </cac:Party>
    <?php endif ?>
  </cac:AccountingCustomerParty>
  <?php if (isset($ACTUAL_DELIVERY_DATE)): ?>
    <cac:Delivery>
      <cbc:ActualDeliveryDate><?= $ACTUAL_DELIVERY_DATE ?></cbc:ActualDeliveryDate>
      <?php if (isset($LATEST_DELIVERY_DATE)): ?>
        <cbc:LatestDeliveryDate><?= $LATEST_DELIVERY_DATE ?></cbc:LatestDeliveryDate>
      <?php endif ?>
    </cac:Delivery>
  <?php endif ?>

  <?php if (!isset($CANCELLATION)): ?>
    <?php if (isset($PAYMENT_METHOD)): ?>
      <cac:PaymentMeans>
        <cbc:PaymentMeansCode><?= $PAYMENT_METHOD ?></cbc:PaymentMeansCode>
      </cac:PaymentMeans>
    <?php endif ?>
  <?php elseif ($INVOICE_TYPE == 381 || $INVOICE_TYPE == 383): ?>
    <cac:PaymentMeans>
      <cbc:PaymentMeansCode><?= $CANCELLATION["payment_method"] ?></cbc:PaymentMeansCode>
      <cbc:InstructionNote><?= $CANCELLATION["reason"] ?? 'No note Specified' ?></cbc:InstructionNote>
    </cac:PaymentMeans>
  <?php endif ?>

  <?= $taxTotalRender ?>
  <?= $legalMonetaryTotalRender ?>
  <?= $lineItemsRender ?>
</Invoice>

<?php return ["total" => $total, "totalTax" => $totalTax] ?>