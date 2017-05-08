<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Order;

use jtl\Connector\Model\CustomerOrder as CustomerOrderModel;
use jtl\Connector\Model\CustomerOrderPaymentInfo;
use jtl\Connector\Payment\PaymentTypes;

class CustomerOrderGermanized extends CustomerOrder
{
    private $directDebitGateway;

    public function __construct()
    {
        parent::__construct();

        $this->directDebitGateway = new \WC_GZD_Gateway_Direct_Debit();
    }

    protected function onCustomerOrderMapped(CustomerOrderModel &$customerOrder)
    {
        if ($customerOrder->getPaymentModuleCode() === PaymentTypes::TYPE_DIRECT_DEBIT) {
            $orderId = $customerOrder->getId()->getEndpoint();

            $bic = $this->directDebitGateway->maybe_decrypt(\get_post_meta($orderId, '_direct_debit_bic', true));
            $iban = $this->directDebitGateway->maybe_decrypt(\get_post_meta($orderId, '_direct_debit_iban', true));

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
}
