<?php

namespace Malik12tree\ZATCA\Utils;

use Malik12tree\ZATCA\Invoice\Enums\InvoiceCode;
use Malik12tree\ZATCA\Invoice\Enums\InvoicePaymentMethod;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceType;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceVATCategory;
use Webmozart\Assert\Assert;

/**
 * Webmozart\Assert is the only validation library I found convenient.
 * TODO: Use a Zod like alternative.
 * TODO: Human readable messages.
 */
class Validation
{
    public const VATS = [
        0.0, 0.05, 0.15,
    ];

    /**
     * An exact copy of Assert::uuid() from Webmozart/Assert
     * fixing issue [#307](https://github.com/webmozarts/assert/issues/307).
     *
     * @param string $value
     * @param string $message
     *
     * @throws \InvalidArgumentException
     */
    public static function uuid($value, $message = '')
    {
        Assert::string($value, $message ?: 'Value %s is not a valid UUID.');

        if (!\preg_match('/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$/D', $value)) {
            throw new \InvalidArgumentException(\sprintf(
                $message ?: 'Value %s is not a valid UUID.',
                Assert::valueToString($value)
            ));
        }
    }

    // BR-KSA-40
    public static function vatNumber($value)
    {
        $message = '';
        Assert::string($value);
        Assert::integerish($value);
        Assert::length($value, 15);
        Assert::startsWith($value, '3');
        Assert::endsWith($value, '3');
    }

    public static function commonName($value)
    {
        Assert::string($value);
        Assert::maxLength($value, 255);
    }

    public static function location($location)
    {
        if (null === $location) {
            return;
        }

        Assert::isArray($location);
        Assert::stringNotEmpty(@$location['city']);
        Assert::stringNotEmpty(@$location['street']);
        Assert::integerish(@$location['building']);
        Assert::nullOrString(@$location['city_subdivision']);
        Assert::nullOrString(@$location['plot_identification']);
        Assert::nullOrString(@$location['postal_zone']);
    }

    public static function egs($egs)
    {
        Assert::isArray($egs);
        Assert::integerish(@$egs['crn_number']);
        Assert::stringNotEmpty(@$egs['model']);
        Assert::stringNotEmpty(@$egs['vat_name']);
        Assert::stringNotEmpty(@$egs['branch_name']);
        Assert::stringNotEmpty(@$egs['branch_industry']);
        Validation::uuid(@$egs['uuid']);
        Validation::commonName(@$egs['common_name']);
        Validation::vatNumber(@$egs['vat_number']);
        Validation::location(@$egs['location']);
    }

    public static function customer($customer)
    {
        Assert::stringNotEmpty(@$customer['buyer_name']);
        Assert::nullOrString(@$customer['crn_number']);
        Assert::nullOrString(@$customer['vat_number']);

        if (null !== @$customer['crn_number']) {
            Assert::integerish(@$customer['crn_number']);
        }
        if (null !== @$customer['vat_number']) {
            Validation::vatNumber(@$customer['vat_number']);
        }

        // Customer is a superset of location
        Validation::location(@$customer);
    }

    public static function invoice($invoice)
    {
        Assert::isArray($invoice);
        Assert::notNull(@$invoice['invoice_counter_number']);
        Assert::stringNotEmpty(@$invoice['invoice_serial_number']);
        Assert::stringNotEmpty(@$invoice['issue_date']);
        Assert::stringNotEmpty(@$invoice['issue_time']);
        Assert::stringNotEmpty(@$invoice['previous_invoice_hash']);
        Assert::nullOrString(@$invoice['actual_delivery_date']);
        Assert::nullOrString(@$invoice['latest_delivery_date']);
        Validation::enum7(@$invoice['invoice_type'], InvoiceType::class);
        Validation::enum7(@$invoice['invoice_code'], InvoiceCode::class);
        Validation::nullOrEnum7(@$invoice['payment_method'], InvoicePaymentMethod::class);

        Validation::dateFormat(@$invoice['issue_date']);
        Validation::timeFormat(@$invoice['issue_time']);

        if (null !== @$invoice['actual_delivery_date']) {
            Validation::dateFormat(@$invoice['actual_delivery_date']);
        }
        if (null !== @$invoice['latest_delivery_date']) {
            Validation::dateFormat(@$invoice['latest_delivery_date']);
        }

        if (isset($invoice['cancellation'])) {
            Validation::cancellation($invoice['cancellation']);
        }

        if (InvoiceCode::TAX === $invoice['invoice_code']) {
            Validation::customer(@$invoice['customer_info']);
        } else {
            Assert::null(@$invoice['customer_info']);
        }

        Validation::items(@$invoice['line_items']);
    }

    public static function cancellation($cancellation)
    {
        Assert::stringNotEmpty(@$cancellation['invoice_serial_number']);
        Assert::stringNotEmpty(@$cancellation['reason']);
        Validation::enum7(@$cancellation['payment_method'], InvoicePaymentMethod::class);
    }

    public static function items($items)
    {
        Assert::isArray($items);

        $currentStandardVAT = null;
        foreach ($items as $item) {
            Validation::item($item);

            if (0.00 == $item['vat_percent']) {
                continue;
            }
            if (null === $currentStandardVAT) {
                $currentStandardVAT = $item['vat_percent'];

                continue;
            }

            if ($item['vat_percent'] !== $currentStandardVAT) {
                throw new \InvalidArgumentException(
                    'Invalid VAT Percent. Cannot mix 15% and 5% standard VATs in the same invoice.'
                );
            }
        }
    }

    public static function item($item)
    {
        Assert::stringNotEmpty(@$item['id']);
        Assert::stringNotEmpty(@$item['name']);
        Assert::float(@$item['quantity']);
        Assert::float(@$item['unit_price']);

        if (false === array_search(@$item['vat_percent'], Validation::VATS, true)) {
            throw new \InvalidArgumentException('Invalid VAT percent');
        }

        if (0 === $item['vat_percent']) {
            Validation::vatCategory(@$item['vat_category']);
        }

        if (null !== @$item['discounts']) {
            Assert::isArray(@$item['discounts']);

            foreach ($item['discounts'] as $discount) {
                Assert::float(@$discount['amount']);
                Assert::stringNotEmpty(@$discount['reason']);
            }
        }
    }

    public static function vatCategory($category)
    {
        Validation::enum7(@$category['category'], InvoiceVATCategory::class);
        Assert::stringNotEmpty(@$category['reason']);
        Assert::stringNotEmpty(@$category['reason_code']);
    }

    public static function timeFormat($date)
    {
        Assert::stringNotEmpty(@$date);
        Assert::regex(@$date, '/^\d{2}\:\d{2}\:\d{2}$/D');
    }

    public static function dateFormat($date)
    {
        Assert::stringNotEmpty(@$date);
        Assert::regex(@$date, '/^\d{4}-\d{2}-\d{2}$/D');
    }

    /**
     * @param mixed  $value
     * @param string $enum
     */
    private static function nullOrEnum7($value, $enum)
    {
        if (null !== $value) {
            Validation::enum7($value, $enum);
        }
    }

    /**
     * @param mixed  $value
     * @param string $enum
     */
    private static function enum7($value, $enum)
    {
        if (!$enum::isValidValue($value)) {
            throw new \InvalidArgumentException(\sprintf('Invalid value %s for enum %s', $value, $enum));
        }
    }
}
