<?php
/*
 * Copyright (C) 2021 FCFPAY
 *
 * FCF Pay provides merchants with an easy-to-use solution to accept cryptocurrency payments. Stay ahead of the competition with FCF Pay.
 * FCF Pay is a safe and secure payment processing solution. FCF Pay is housed on a dedicated server which uses innovative encrypting technology to ensure that all of your information remains secure and private.
 * @author      The FCF Inc
 * @copyright   2021 The FCF Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace fcfpay\PaymentGateway\Block\Adminhtml\System\Config\Fieldset;

/**
 * Renderer for fcfpay Checkout Panel in System Configuration
 *
 * Class CheckoutPayment
 * @package fcfpay\PaymentGateway\Block\Adminhtml\System\Config\Fieldset
 */
class CheckoutPayment extends \fcfpay\PaymentGateway\Block\Adminhtml\System\Config\Fieldset\Base\Payment
{
    /**
     * Retrieves the Module Panel Css Class
     * @return string
     */
    protected function getBlockHeadCssClass()
    {
        return "fcfpayCheckout";
    }
}
