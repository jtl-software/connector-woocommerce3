<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Order;

use jtl\Connector\Model\CustomerOrder as CustomerOrderModel;
use jtl\Connector\Model\CustomerOrderAttr;
use jtl\Connector\Model\CustomerOrderPaymentInfo;
use jtl\Connector\Model\Identity;
use jtl\Connector\Payment\PaymentTypes;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Controllers\Payment;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Integrations\Plugins\Germanized\Germanized;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use TheIconic\NameParser\Parser;

class CustomerOrder extends BaseController
{
    use PullTrait, StatsTrait;
    
    /** Order received (unpaid) */
    const STATUS_PENDING = 'pending';
    /** Payment received – the order is awaiting fulfillment */
    const STATUS_PROCESSING = 'processing';
    /** Order fulfilled and complete */
    const STATUS_COMPLETED = 'completed';
    /** Awaiting payment – stock is reduced, but you need to confirm payment */
    const STATUS_ON_HOLD = 'on-hold';
    /** Payment failed or was declined (unpaid) */
    const STATUS_FAILED = 'failed';
    /** Cancelled by an admin or the customer */
    const STATUS_CANCELLED = 'cancelled';
    /** Already paid */
    const STATUS_REFUNDED = 'refunded';
    
    const BILLING_ID_PREFIX = 'b_';
    const SHIPPING_ID_PREFIX = 's_';
    
    public function pullData($limit)
    {
        $orders = [];
        
        $orderIds = $this->database->queryList(SqlHelper::customerOrderPull($limit));
        
        foreach ($orderIds as $orderId) {
            $order = \wc_get_order($orderId);
            
            if (!$order instanceof \WC_Order) {
                continue;
            }

            $total = $order->get_total();
            $totalTax = $order->get_total_tax();
            $totalSum = $total - $totalTax;
                
            $customerOrder = (new CustomerOrderModel())
                ->setId(new Identity($order->get_id()))
                ->setCreationDate($order->get_date_created())
                ->setCurrencyIso($order->get_currency())
                ->setNote($order->get_customer_note())
                ->setCustomerId($order->get_customer_id() === 0
                    ? new Identity(Id::link([Id::GUEST_PREFIX, $order->get_id()]))
                    : new Identity($order->get_customer_id())
                )
                ->setOrderNumber($order->get_order_number())
                ->setShippingMethodName($order->get_shipping_method())
                ->setPaymentModuleCode(Util::getInstance()->mapPaymentModuleCode($order))
                ->setPaymentStatus($this->paymentStatus($order))
                ->setStatus($this->status($order))
                ->setTotalSum((float)$totalSum);
            
            $customerOrder
                ->setItems(CustomerOrderItem::getInstance()->pullData($order))
                ->setBillingAddress(CustomerOrderBillingAddress::getInstance()->pullData($order))
                ->setShippingAddress(CustomerOrderShippingAddress::getInstance()->pullData($order));

            if ($this->wpml->canBeUsed() && !empty($wpmlLanguage = $order->get_meta('wpml_language'))) {
                $customerOrder->setLanguageISO($this->wpml->convertLanguageToWawi($wpmlLanguage));
            }

            if ($order->is_paid()) {
                $customerOrder->setPaymentDate($order->get_date_paid());
            }

            if ($customerOrder->getPaymentModuleCode() === PaymentTypes::TYPE_PAYPAL_PLUS) {
                $this->setPayPalPlusPaymentInfo($order, $customerOrder);
            }
            
            if ($this->getPluginsManager()->get(Germanized::class)->canBeUsed()) {
                $this->setGermanizedPaymentInfo($customerOrder);
            }
            
            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                $this->setGermanMarketPaymentInfo($customerOrder);
            }

            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_DHL_FOR_WOOCOMMERCE)) {

                $dhlPreferredDeliveryOptions = get_post_meta( $orderId, '_pr_shipment_dhl_label_items', true );

                if (is_array($dhlPreferredDeliveryOptions)) {
                    $this->setPreferredDeliveryOptions($customerOrder, $dhlPreferredDeliveryOptions);
                }
            }
            
            $orders[] = $customerOrder;
        }
        
        return $orders;
    }

    protected function setPayPalPlusPaymentInfo(\WC_Order $order, CustomerOrderModel $customerOrder)
    {
        $instructionType = $order->get_meta('instruction_type');

        if ($instructionType === Payment::PAY_UPON_INVOICE) {
            $payPalPlusSettings = get_option('woocommerce_paypal_plus_settings', []);

            $pui = $payPalPlusSettings['pay_upon_invoice_instructions'] ?? '';
            if (empty($pui)) {
                $orderMetaData = $order->get_meta_data();
                $pui = (sprintf(
                    'Bitte überweisen Sie %s %s bis %s an folgendes Konto: %s Verwendungszweck: %s',
                    number_format((float)$customerOrder->getTotalSumGross(), 2),
                    $customerOrder->getCurrencyIso(),
                    $orderMetaData['payment_due_date'] ?? '',
                    sprintf(
                        'Empfänger: %s, Bank: %s, IBAN: %s, BIC: %s',
                        $orderMetaData['account_holder_name'] ?? '',
                        $orderMetaData['bank_name'] ?? '',
                        $orderMetaData['international_bank_account_number'] ?? '',
                        $orderMetaData['bank_identifier_code'] ?? ''
                    ),
                    $orderMetaData['reference_number']
                ));
            }

            $customerOrder->setPui($pui);
        }
    }
    
    protected function paymentStatus(\WC_Order $order)
    {
        if ($order->has_status(self::STATUS_COMPLETED)) {
            return CustomerOrderModel::PAYMENT_STATUS_COMPLETED;
        }
        
        return CustomerOrderModel::PAYMENT_STATUS_UNPAID;
    }
    
    protected function status(\WC_Order $order)
    {
        if ($order->has_status(self::STATUS_COMPLETED)) {
            return CustomerOrderModel::STATUS_SHIPPED;
        } elseif ($order->has_status([self::STATUS_CANCELLED, self::STATUS_REFUNDED])) {
            return CustomerOrderModel::STATUS_CANCELLED;
        }
        
        return CustomerOrderModel::STATUS_NEW;
    }
    
    protected function setGermanizedPaymentInfo(CustomerOrderModel &$customerOrder)
    {
        $directDebitGateway = new \WC_GZD_Gateway_Direct_Debit();
        
        if ($customerOrder->getPaymentModuleCode() === PaymentTypes::TYPE_DIRECT_DEBIT) {
            $orderId = $customerOrder->getId()->getEndpoint();
            
            $bic = $directDebitGateway->maybe_decrypt(\get_post_meta($orderId, '_direct_debit_bic', true));
            $iban = $directDebitGateway->maybe_decrypt(\get_post_meta($orderId, '_direct_debit_iban', true));
            
            $paymentInfo = (new CustomerOrderPaymentInfo())
                ->setBic($bic)
                ->setIban($iban)
                ->setAccountHolder(\get_post_meta($orderId, '_direct_debit_holder', true));
            
            $customerOrder->setPaymentInfo($paymentInfo);
            
        } elseif ($customerOrder->getPaymentModuleCode() === PaymentTypes::TYPE_INVOICE) {
            $settings = \get_option('woocommerce_invoice_settings');
            
            if (!empty($settings) && isset($settings['instructions'])) {
                $customerOrder->setPui($settings['instructions']);
            }
        }
    }

    protected function setPreferredDeliveryOptions(CustomerOrderModel &$customerOrder,$dhlPreferredDeliveryOptions = [])
    {
        $customerOrder->addAttribute(
            (new CustomerOrderAttr())
                ->setKey('dhl_wunschpaket_feeder_system')
                ->setValue('woocommerce')
        );

        //foreach each item mach
        foreach($dhlPreferredDeliveryOptions as $optionName=>$optionValue){
            switch($optionName){
                case 'pr_dhl_preferred_day':
                    $customerOrder->addAttribute(
                        (new CustomerOrderAttr())
                            ->setKey('dhl_wunschpaket_day')
                            ->setValue($optionValue)
                    );
                    break;
                case 'pr_dhl_preferred_location':
                    $customerOrder->addAttribute(
                        (new CustomerOrderAttr())
                            ->setKey('dhl_wunschpaket_location')
                            ->setValue($optionValue)
                    );
                    break;
                case 'pr_dhl_preferred_time':
                    $customerOrder->addAttribute(
                        (new CustomerOrderAttr())
                            ->setKey('dhl_wunschpaket_time')
                            ->setValue($optionValue)
                    );
                    break;
                case 'pr_dhl_preferred_neighbour_address':
                    $parts = array_map('trim', explode(',', $optionValue, 2));
                    $streetParts = [];
                    $pattern = '/^(?P<street>\d*\D+[^A-Z]) (?P<number>[^a-z]?\D*\d+.*)$/';
                    preg_match($pattern, $parts[0], $streetParts);

                    if (isset($streetParts['street'])) {
                        $customerOrder->addAttribute(
                            (new CustomerOrderAttr())
                                ->setKey('dhl_wunschpaket_neighbour_street')
                                ->setValue($streetParts['street'])
                        );
                    }
                    if (isset($streetParts['number'])) {
                        $customerOrder->addAttribute(
                            (new CustomerOrderAttr())
                                ->setKey('dhl_wunschpaket_neighbour_house_number')
                                ->setValue($streetParts['number'])
                        );
                    }

                    $addressAddition = sprintf('%s %s',
                        $customerOrder->getShippingAddress()->getZipCode(),
                        $customerOrder->getShippingAddress()->getCity()
                    );

                    if (isset($parts[1])) {
                        $addressAddition = $parts[1];
                    }

                    $customerOrder->addAttribute(
                        (new CustomerOrderAttr())
                            ->setKey('dhl_wunschpaket_neighbour_address_addition')
                            ->setValue($addressAddition)
                    );

                    break;
                case 'pr_dhl_preferred_neighbour_name':
                    $name = (new Parser())->parse($optionValue);

                    $salutation = $name->getSalutation();
                    $firstName  = $name->getFirstname();

                    if(preg_match("/(herr|frau)/i",$firstName)){
                        $salutation = ucfirst(mb_strtolower($firstName));
                        $firstName = $name->getMiddlename();
                    }
                    $salutation = trim($salutation);
                    if (empty($salutation)) {
                        $salutation = 'Herr';
                    }

                    $customerOrder->addAttribute(
                        (new CustomerOrderAttr())
                            ->setKey('dhl_wunschpaket_neighbour_salutation')
                            ->setValue($salutation)
                    );
                    $customerOrder->addAttribute(
                        (new CustomerOrderAttr())
                            ->setKey('dhl_wunschpaket_neighbour_first_name')
                            ->setValue($firstName)
                    );
                    $customerOrder->addAttribute(
                        (new CustomerOrderAttr())
                            ->setKey('dhl_wunschpaket_neighbour_last_name')
                            ->setValue($name->getLastname())
                    );
                    break;
            }
        }


    }
    
    protected function setGermanMarketPaymentInfo(CustomerOrderModel &$customerOrder)
    {
        $orderId = $customerOrder->getId()->getEndpoint();
        
        if ($customerOrder->getPaymentModuleCode() === PaymentTypes::TYPE_DIRECT_DEBIT) {
            $instance = new \WGM_Gateway_Sepa_Direct_Debit();
            $gmSettings = $instance->settings;
            $bic = \get_post_meta($orderId, '_german_market_sepa_bic', true);
            $iban = \get_post_meta($orderId, '_german_market_sepa_iban', true);
            $accountHolder = \get_post_meta($orderId, '_german_market_sepa_holder', true);
            $settingsKeys = [
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
            $pui = array_key_exists('direct_debit_mandate', $gmSettings) ? $gmSettings['direct_debit_mandate'] : '';
            
            foreach ($settingsKeys as $key => $formValue) {
                switch ($formValue) {
                    case '[creditor_information]':
                        $value = array_key_exists('creditor_information',
                            $gmSettings) ? $gmSettings['creditor_information'] : '';
                        break;
                    case '[creditor_identifier]':
                        $value = array_key_exists('creditor_identifier',
                            $gmSettings) ? $gmSettings['creditor_identifier'] : '';
                        break;
                    case '[creditor_account_holder]':
                        $value = array_key_exists('creditor_account_holder',
                            $gmSettings) ? $gmSettings['creditor_account_holder'] : '';
                        break;
                    case '[creditor_iban]':
                        $value = array_key_exists('iban', $gmSettings) ? $gmSettings['iban'] : '';
                        break;
                    case '[creditor_bic]':
                        $value = array_key_exists('bic', $gmSettings) ? $gmSettings['bic'] : '';
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
                
                $pui = str_replace(
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
            
        } elseif ($customerOrder->getPaymentModuleCode() === PaymentTypes::TYPE_INVOICE) {
            $instance = new \WGM_Gateway_Purchase_On_Account();
            $gmSettings = $instance->settings;
    
            if(array_key_exists('direct_debit_mandate', $gmSettings) && $gmSettings['direct_debit_mandate'] !== ''){
                $customerOrder->setPui($gmSettings['direct_debit_mandate']);
            }
        }
    }
    
    public function getStats()
    {
        return $this->database->queryOne(SqlHelper::customerOrderPull(null));
    }
}
