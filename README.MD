<h1 align="center">
  <code>malik12tree/zatca</code>
  <br/>
  <!-- ZATCA Phase 2 -->
  <img src="https://img.shields.io/badge/💸_ZATCA-Phase_2-00b694" />
  <!-- E-Invoicing -->
  <img src="https://img.shields.io/badge/📃_ZATCA-E--Invoicing-fc5f08">
  <!-- Encrypted -->
  <img src="https://img.shields.io/badge/🔐_ZATCA-Encrypted-f75394">
  <br/>
  <!-- XML Complaint -->
  <img src="https://img.shields.io/badge/📝_XML-Complaint-ff5555">
  <!-- PDF/A-3 Complaint -->
  <img src="https://img.shields.io/badge/📕_PDF%2FA--3-Complaint-ff5555">
  <!-- QR Code -->
  <img src="https://img.shields.io/badge/🔋_QR_Codes-Included-5793ff">
  <br/>
  <img src="https://img.shields.io/badge/php-7.4-777BB4?logo=php" />
  <img src="https://img.shields.io/github/license/Malik12tree/zatca?color=orange">
  <img src="https://img.shields.io/badge/version-0.1.0-blue.svg" />
</h1>

PHP implementation of ZATCA E-Invoicing standards.

## Installation

### Composer

```bash
composer require malik12tree/zatca
```

## Glossary

| Term | Definition                      |
| ---- | ------------------------------- |
| EGS  | E-Invoicing Generation Solution |

## Usage

### Table of Contents

- [Register EGS](#register-egs)
- [Saving/Loading EGS](#savingloading-egs)
- [Invoicing](#invoicing)
- [PDF/A-3 Invoice](#pdfa-3-invoice)

### Register EGS

```php
use Malik12tree\ZATCA;
use Malik12tree\ZATCA\EGS;
use Malik12tree\ZATCA\Utils\Encoding\Crypto;

$egs = new EGS([
    // EGS Serial Number
    // You should generate a unique UUID for each EGS
    // Use Crypto::uuid4() to generate a secure UUID
    'uuid' => '00000000-0000-0000-0000-000000000000',

    'common_name' => 'Cashier #1',
    'model' => 'Windows Vista',

    // Known as CRN Number, License Number or Contract Number
    'crn_number' => '1234567890',
    // Known as VAT Name or Taxpayer Name
    'vat_name' => 'My Totally Existing Company',
    // Known as VAT Registration Number
    // Should be a valid 15 digits number starting and ending with "3"
    'vat_number' => '300001234500003',
    'branch_name' => 'My Really Profound Branch',
    'branch_industry' => 'Trading',

    'location' => [
        'building' => 'Al Yamama Tower',
        'street' => 'King Fahd Rd',
        'city_subdivision' => 'Al Olaya',
        'city' => 'Riyadh',
        'plot_identification' => '0000',
        'postal_zone' => '00000',
    ],
]);

// Obtain an OTP (One-Time Password) from the Fatoora portal https://fatoora.zatca.gov.sa/onboard-solution for each EGS registration.
$otp = '123345';
$solutionName = 'My POS System\'s Name';

try {
    $egs->register($solutionName, $otp);
} catch (ZATCA\Exceptions\APIException $e) {
    // Handle API Exceptions gracefully
    // If you believe a bug occurred, please report it at https://github.com/Malik12tree/zatca/issues
    // $e->getCode()
    // $e->getMessage()
    // $e->getResponse()
    throw $e;
}

// ...
```

### Saving/Loading EGS

```php
use Malik12tree\ZATCA\EGSDatabases\EGSFileDatabase;

// Store the EGS object and its credentials for later use.
//  Be mindful to locate the EGS database to a safe place

$database = new EGSFileDatabase(__DIR__.'/private-secure-storage/solutions');

// Option 1
$database->save($egs);
// Option 2
$egs->setDatabase($database);
$egs->save();

// Retrieve a previously saved EGS using its UUID
$loadedEgs = $database->load('00000000-0000-0000-0000-000000000000');

// ...
```

### Invoicing

```php
use Malik12tree\ZATCA\Invoice;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceCode;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceType;

$invoice = $egs->invoice([
    'invoice_code' => InvoiceCode::TAX,
    'invoice_type' => InvoiceType::INVOICE,

    // To your heart's desire
    // Even uuids are acceptable
    'invoice_serial_number' => 'INV-0000001',
    'invoice_counter_number' => 0,

    'issue_date' => date('Y-m-d'),
    'issue_time' => date('H:i:s'),
    'actual_delivery_date' => date('Y-m-d', strtotime('tomorrow')),

    'previous_invoice_hash' => Invoice::INITIAL_PREVIOUS_HASH,

    'customer_info' => [
        'vat_number' => '300000000000003',
        'buyer_name' => 'Mahmoud 3gwa',

        'building' => 'Juice Stand',
        'street' => 'Abaseya Tunnel',
        'city_subdivision' => '',
        'city' => 'Dammam',
        'country_sub_entity' => 'Eastern Province',
        'plot_identification' => '0000',
        'postal_zone' => '00000',
    ],

    'line_items' => [
        [
            'id' => "sugarcane_juicer_metal",
            'name' => 'Metal Sugarcane Juicer',
            'quantity' => 1.0,
            'tax_exclusive_price' => 525.0,
            'vat_percent' => 0.15,
            'discounts' => [
                [
                    'amount' => 100.0,
                    'reason' => 'Special Customer',
                ],
            ],
        ],
    ],
]);

try {
    $signedInvoice = $egs->signInvoice($invoice);

    // Throws an exception if the EGS is not compliant
    // Even if the EGS is compliant, $response may contain warnings
    $response = $egs->checkInvoiceCompliance($signedInvoice);

    $response = $egs->reportInvoice($signedInvoice);
} catch (ZATCA\Exceptions\APIException $e) {
    // Handle API Exceptions gracefully
    // If you believe a bug occurred, please report it at https://github.com/Malik12tree/zatca/issues
    // $e->getCode()
    // $e->getMessage()
    // $e->getResponse()
    throw $e;
} catch (Exception $e) {
    // Handle Exceptions gracefully
    // If you believe a bug occurred, please report it at https://github.com/Malik12tree/zatca/issues
    throw $e;
}

// ...
```

### PDF/A-3 Invoice

ZATCA rules that each invoice must be converted into a
standardized PDF/A-3 format containing all essential information.
Additionally, a signed XML invoice attachment must be included within the PDF.

Fortunately, this library simplifies this process significantly by utilizing a built-in PDF builder.

```php

$pdfInvoice = $signedInvoice->toPDF([
  // Optional Logo
  "logo" => __DIR__ . "/assets"
]);

// Save at directory.
// saveAt(...) automatically appends the PDF name according to
// ZATCA's naming convention.
$pdfInvoice->saveAt(__DIR__ . "/private-secure-storage/invoices");

// Alternatively, You can store the binary data.
$binaryData = $pdfInvoice->getPDF();
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details

## Acknowledgments

Special thanks to [zatca-xml-js](https://github.com/Repzo/zatca-xml-js).

## Non-Affiliation Disclaimer

This library is not affiliated with ZATCA or any of its subsidiaries or affiliates. The library's development and maintenance are independent of ZATCA. Any references to ZATCA or its standards are for informational purposes only and do not imply endorsement or sponsorship by ZATCA.
