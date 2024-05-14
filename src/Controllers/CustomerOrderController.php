<?php

namespace JtlWooCommerceConnector\Controllers;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use InvalidArgumentException;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Model\CustomerOrder as CustomerOrderModel;
use Jtl\Connector\Core\Model\CustomerOrderPaymentInfo;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Definition\PaymentType;
use Jtl\Connector\Core\Model\KeyValueAttribute;
use Jtl\Connector\Core\Model\QueryFilter;
use JtlWooCommerceConnector\Controllers\Order\CustomerOrderBillingAddressController;
use JtlWooCommerceConnector\Controllers\Order\CustomerOrderItemController;
use JtlWooCommerceConnector\Controllers\Order\CustomerOrderShippingAddressController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use TheIconic\NameParser\Parser;

class CustomerOrderController extends AbstractBaseController implements PullInterface, StatisticInterface
{
    /** Order received (unpaid) */
    public const
        STATUS_PENDING = 'pending',
        /** Payment received – the order is awaiting fulfillment */
        STATUS_PROCESSING = 'processing',
        /** Order fulfilled and complete */
        STATUS_COMPLETED = 'completed',
        /** Awaiting payment – stock is reduced, but you need to confirm payment */
        STATUS_ON_HOLD = 'on-hold',
        /** Payment failed or was declined (unpaid) */
        STATUS_FAILED = 'failed',
        /** Cancelled by an admin or the customer */
        STATUS_CANCELLED = 'cancelled',
        /** Already paid */
        STATUS_REFUNDED = 'refunded';

    public const BILLING_ID_PREFIX  = 'b_';
    public const SHIPPING_ID_PREFIX = 's_';

    /**
     * @param QueryFilter $query
     * @return array<int|CustomerOrderController>
     * @throws InvalidArgumentException
     * @throws \WC_Data_Exception
     * @throws \Exception
     */
    public function pull(QueryFilter $query): array
    {
        $orders = [];

        $orderIds = $this->db->queryList(SqlHelper::customerOrderPull($query->getLimit()));

        foreach ($orderIds as $orderId) {
            $order = \wc_get_order($orderId);

            if (!$order instanceof \WC_Order) {
                continue;
            }

            $total    = $order->get_total();
            $totalTax = $order->get_total_tax();
            $totalSum = $total - $totalTax;

            $customerOrder = (new CustomerOrderModel())
                ->setId(new Identity($order->get_id()))
                ->setCreationDate($order->get_date_created())
                ->setCurrencyIso($order->get_currency())
                ->setNote($order->get_customer_note())
                ->setCustomerId($order->get_customer_id() === 0
                    ? new Identity(Id::link([Id::GUEST_PREFIX, $order->get_id()]))
                    : new Identity($order->get_customer_id()))
                ->setOrderNumber($order->get_order_number())
                ->setShippingMethodName($order->get_shipping_method())
                ->setPaymentModuleCode($this->util->mapPaymentModuleCode($order))
                ->setPaymentStatus(CustomerOrderModel::PAYMENT_STATUS_UNPAID)
                ->setStatus($this->status($order))
                ->setTotalSum((float)$totalSum);

            $customerOrder
                ->setItems(...(new CustomerOrderItemController($this->db, $this->util))->pull($order))
                ->setBillingAddress((new CustomerOrderBillingAddressController($this->db, $this->util))->pull($order))
                ->setShippingAddress(
                    (new CustomerOrderShippingAddressController($this->db, $this->util))->pull($order)
                );

            if ($this->wpml->canBeUsed() && !empty($wpmlLanguage = $order->get_meta('wpml_language'))) {
                $customerOrder->setLanguageISO($this->wpml->convertLanguageToWawi($wpmlLanguage));
            }

            if ($order->is_paid()) {
                $customerOrder->setPaymentDate($order->get_date_paid());
            }

            if ($customerOrder->getPaymentModuleCode() === PaymentType::AMAPAY) {
                $amazonChargePermissionId = $order->get_meta('amazon_charge_permission_id');
                if (!empty($amazonChargePermissionId)) {
                    $customerOrder->addAttribute(
                        (new KeyValueAttribute())
                            ->setKey('AmazonPay-Referenz')
                            ->setValue($amazonChargePermissionId)
                    );
                }
            }

            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_CHECKOUT_FIELD_EDITOR_FOR_WOOCOMMERCE)) {
                foreach ($order->get_meta_data() as $metaData) {
                    if (
                        \in_array(
                            $metaData->get_data()['key'],
                            \explode(',', Config::get(Config::OPTIONS_CUSTOM_CHECKOUT_FIELDS))
                        )
                    ) {
                        $customerOrder->addAttribute(
                            (new KeyValueAttribute())
                                ->setKey($metaData->get_data()['key'])
                                ->setValue($metaData->get_data()['value'])
                        );
                    }
                }
            }

            if ($customerOrder->getPaymentModuleCode() === PaymentType::PAYPAL_PLUS) {
                $this->setPayPalPlusPaymentInfo($order, $customerOrder);
            }

            if (
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
            ) {
                $this->setGermanizedPaymentInfo($customerOrder);
            }

            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                $this->setGermanMarketPaymentInfo($customerOrder);
            }

            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_DHL_FOR_WOOCOMMERCE)) {
                $dhlPreferredDeliveryOptions = \get_post_meta($orderId, '_pr_shipment_dhl_label_items', true);

                if (\is_array($dhlPreferredDeliveryOptions)) {
                    $this->setPreferredDeliveryOptions($customerOrder, $dhlPreferredDeliveryOptions);
                }
            }

            $orders[] = $customerOrder;
        }

        return $orders;
    }

    /**
     * @param \WC_Order $order
     * @param CustomerOrderModel $customerOrder
     * @return void
     */
    protected function setPayPalPlusPaymentInfo(\WC_Order $order, CustomerOrderModel $customerOrder): void
    {
        $instructionType = $order->get_meta('instruction_type');

        if ($instructionType === PaymentController::PAY_UPON_INVOICE) {
            $payPalPlusSettings = \get_option('woocommerce_paypal_plus_settings', []);

            $pui = $payPalPlusSettings['pay_upon_invoice_instructions'] ?? '';
            if (empty($pui)) {
                $orderMetaData = $order->get_meta('_payment_instruction_result');
                if (
                    !empty($orderMetaData)
                    && $orderMetaData['instruction_type'] === PaymentController::PAY_UPON_INVOICE
                ) {
                    $bankData = $orderMetaData['recipient_banking_instruction'] ?? '';
                    if (!empty($bankData)) {
                        $pui = (\sprintf(
                            'Bitte überweisen Sie %s %s bis %s an folgendes Konto: %s Verwendungszweck: %s',
                            \number_format((float)$order->get_total(), 2),
                            $customerOrder->getCurrencyIso(),
                            $order->get_meta('payment_due_date') ?? '',
                            \sprintf(
                                'Empfänger: %s, Bank: %s, IBAN: %s, BIC: %s',
                                $bankData['account_holder_name'] ?? '',
                                $bankData['bank_name'] ?? '',
                                $bankData['international_bank_account_number'] ?? '',
                                $bankData['bank_identifier_code'] ?? ''
                            ),
                            $orderMetaData['reference_number']
                        ));
                    }
                }
            }

            $customerOrder->setPui($pui);
        }
    }

    /**
     * @param \WC_Order $order
     * @return string
     */
    protected function status(\WC_Order $order): string
    {
        if ($order->has_status(self::STATUS_COMPLETED)) {
            return CustomerOrderModel::STATUS_SHIPPED;
        } elseif ($order->has_status([self::STATUS_CANCELLED, self::STATUS_REFUNDED])) {
            return CustomerOrderModel::STATUS_CANCELLED;
        }

        return CustomerOrderModel::STATUS_NEW;
    }

    /**
     * @param CustomerOrderModel $customerOrder
     * @return void
     * @throws EnvironmentIsBrokenException
     * @throws \TypeError
     */
    protected function setGermanizedPaymentInfo(CustomerOrderModel &$customerOrder): void
    {
        $directDebitGateway = new \WC_GZD_Gateway_Direct_Debit();

        if ($customerOrder->getPaymentModuleCode() === PaymentType::DIRECT_DEBIT) {
            $orderId = $customerOrder->getId()->getEndpoint();

            $bic  = $directDebitGateway->maybe_decrypt(\get_post_meta($orderId, '_direct_debit_bic', true));
            $iban = $directDebitGateway->maybe_decrypt(\get_post_meta($orderId, '_direct_debit_iban', true));

            $paymentInfo = (new CustomerOrderPaymentInfo())
                ->setBic($bic)
                ->setIban($iban)
                ->setAccountHolder(\get_post_meta($orderId, '_direct_debit_holder', true));

            $customerOrder->setPaymentInfo($paymentInfo);
        } elseif ($customerOrder->getPaymentModuleCode() === PaymentType::INVOICE) {
            $settings = \get_option('woocommerce_invoice_settings');

            if (!empty($settings) && isset($settings['instructions'])) {
                $customerOrder->setPui($settings['instructions']);
            }
        }
    }

    /**
     * @param CustomerOrderModel $customerOrder
     * @param array $dhlPreferredDeliveryOptions
     * @return void
     */
    protected function setPreferredDeliveryOptions(
        CustomerOrderModel &$customerOrder,
        array $dhlPreferredDeliveryOptions = []
    ): void {
        $customerOrder->addAttribute(
            (new KeyValueAttribute())
                ->setKey('dhl_wunschpaket_feeder_system')
                ->setValue('wooc')
        );

        //foreach each item mach
        foreach ($dhlPreferredDeliveryOptions as $optionName => $optionValue) {
            switch ($optionName) {
                case 'pr_dhl_preferred_day':
                    $customerOrder->addAttribute(
                        (new KeyValueAttribute())
                            ->setKey('dhl_wunschpaket_day')
                            ->setValue($optionValue)
                    );
                    break;
                case 'pr_dhl_preferred_location':
                    $customerOrder->addAttribute(
                        (new KeyValueAttribute())
                            ->setKey('dhl_wunschpaket_location')
                            ->setValue($optionValue)
                    );
                    break;
                case 'pr_dhl_preferred_time':
                    $customerOrder->addAttribute(
                        (new KeyValueAttribute())
                            ->setKey('dhl_wunschpaket_time')
                            ->setValue($optionValue)
                    );
                    break;
                case 'pr_dhl_preferred_neighbour_address':
                    $parts       = \array_map('trim', \explode(',', $optionValue, 2));
                    $streetParts = [];
                    $pattern     = '/^(?P<street>\d*\D+[^A-Z]) (?P<number>[^a-z]?\D*\d+.*)$/';
                    \preg_match($pattern, $parts[0], $streetParts);

                    if (isset($streetParts['street'])) {
                        $customerOrder->addAttribute(
                            (new KeyValueAttribute())
                                ->setKey('dhl_wunschpaket_neighbour_street')
                                ->setValue($streetParts['street'])
                        );
                    }
                    if (isset($streetParts['number'])) {
                        $customerOrder->addAttribute(
                            (new KeyValueAttribute())
                                ->setKey('dhl_wunschpaket_neighbour_house_number')
                                ->setValue($streetParts['number'])
                        );
                    }

                    $addressAddition = \sprintf(
                        '%s %s',
                        $customerOrder->getShippingAddress()->getZipCode(),
                        $customerOrder->getShippingAddress()->getCity()
                    );

                    if (isset($parts[1])) {
                        $addressAddition = $parts[1];
                    }

                    $customerOrder->addAttribute(
                        (new KeyValueAttribute())
                            ->setKey('dhl_wunschpaket_neighbour_address_addition')
                            ->setValue($addressAddition)
                    );

                    break;
                case 'pr_dhl_preferred_neighbour_name':
                    $name = (new Parser())->parse($optionValue);

                    $salutation = $name->getSalutation();
                    $firstName  = $name->getFirstname();

                    if (\preg_match("/(herr|frau)/i", $firstName)) {
                        $salutation = \ucfirst(\mb_strtolower($firstName));
                        $firstName  = $name->getMiddlename();
                    }
                    $salutation = \trim($salutation);
                    if (empty($salutation)) {
                        $salutation = 'Herr';
                    }

                    $customerOrder->addAttribute(
                        (new KeyValueAttribute())
                            ->setKey('dhl_wunschpaket_neighbour_salutation')
                            ->setValue($salutation)
                    );
                    $customerOrder->addAttribute(
                        (new KeyValueAttribute())
                            ->setKey('dhl_wunschpaket_neighbour_first_name')
                            ->setValue($firstName)
                    );
                    $customerOrder->addAttribute(
                        (new KeyValueAttribute())
                            ->setKey('dhl_wunschpaket_neighbour_last_name')
                            ->setValue($name->getLastname())
                    );
                    break;
            }
        }
    }

    /**
     * @param CustomerOrderModel $customerOrder
     * @return void
     */
    protected function setGermanMarketPaymentInfo(CustomerOrderModel &$customerOrder): void
    {
        $orderId = $customerOrder->getId()->getEndpoint();

        if ($customerOrder->getPaymentModuleCode() === PaymentType::DIRECT_DEBIT) {
            $instance      = new \WGM_Gateway_Sepa_Direct_Debit();
            $gmSettings    = $instance->settings;
            $bic           = \get_post_meta($orderId, '_german_market_sepa_bic', true);
            $iban          = \get_post_meta($orderId, '_german_market_sepa_iban', true);
            $accountHolder = \get_post_meta($orderId, '_german_market_sepa_holder', true);
            $settingsKeys  = [
                '[creditor_information]',
                '[creditor_identifier]',
                '[creditor_account_holder]',
                '[creditor_iban]',
                '[creditor_bic]',
                '[mandate_id]',
                '[street]',
                '[city]',
                '[postcode]',
                '[country]',
                '[date]',
                '[account_holder]',
                '[account_iban]',
                '[account_bic]',
                '[amount]',
            ];
            $pui           = \array_key_exists('direct_debit_mandate', $gmSettings)
                ? $gmSettings['direct_debit_mandate']
                : '';

            foreach ($settingsKeys as $key => $formValue) {
                switch ($formValue) {
                    case '[creditor_information]':
                        $value = \array_key_exists(
                            'creditor_information',
                            $gmSettings
                        ) ? $gmSettings['creditor_information'] : '';
                        break;
                    case '[creditor_identifier]':
                        $value = \array_key_exists(
                            'creditor_identifier',
                            $gmSettings
                        ) ? $gmSettings['creditor_identifier'] : '';
                        break;
                    case '[creditor_account_holder]':
                        $value = \array_key_exists(
                            'creditor_account_holder',
                            $gmSettings
                        ) ? $gmSettings['creditor_account_holder'] : '';
                        break;
                    case '[creditor_iban]':
                        $value = \array_key_exists('iban', $gmSettings) ? $gmSettings['iban'] : '';
                        break;
                    case '[creditor_bic]':
                        $value = \array_key_exists('bic', $gmSettings) ? $gmSettings['bic'] : '';
                        break;
                    case '[mandate_id]':
                        $value = \get_post_meta($orderId, '_german_market_sepa_mandate_reference', true);
                        break;
                    case '[street]':
                        $value = $customerOrder->getBillingAddress()->getStreet();
                        break;
                    case '[city]':
                        $value = $customerOrder->getBillingAddress()->getCity();
                        break;
                    case '[postcode]':
                        $value = $customerOrder->getBillingAddress()->getZipCode();
                        break;
                    case '[country]':
                        $value = $customerOrder->getBillingAddress()->getCountryIso();
                        break;
                    case '[date]':
                        $value = $customerOrder->getPaymentDate()->getTimestamp();
                        break;
                    case '[account_holder]':
                        $value = $accountHolder;
                        break;
                    case '[account_iban]':
                        $value = $iban;
                        break;
                    case '[account_bic]':
                        $value = $bic;
                        break;
                    case '[amount]':
                        $value = $customerOrder->getTotalSum();
                        break;
                    default:
                        $value = '';
                        break;
                }

                $pui = \str_replace(
                    $formValue,
                    $value,
                    $pui
                );
            }

            $paymentInfo = (new CustomerOrderPaymentInfo())
                ->setBic($bic)
                ->setIban($iban)
                ->setAccountHolder($accountHolder);

            $customerOrder->setPui($pui);
            $customerOrder->setPaymentInfo($paymentInfo);
        } elseif ($customerOrder->getPaymentModuleCode() === PaymentType::INVOICE) {
            $instance   = new \WGM_Gateway_Purchase_On_Account();
            $gmSettings = $instance->settings;

            if (
                \array_key_exists('direct_debit_mandate', $gmSettings)
                && $gmSettings['direct_debit_mandate'] !== ''
            ) {
                $customerOrder->setPui($gmSettings['direct_debit_mandate']);
            }
        }
    }

    /**
     * @param QueryFilter $query
     * @return int
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function statistic(QueryFilter $query): int
    {
        return $this->db->queryOne(SqlHelper::customerOrderPull(null));
    }
}
