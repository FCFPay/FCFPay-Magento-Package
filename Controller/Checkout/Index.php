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

namespace fcfpay\PaymentGateway\Controller\Checkout;

/**
 * Front Controller for Checkout Method
 * it does a redirect to checkout
 * Class Index
 * @package fcfpay\PaymentGateway\Controller\Checkout
 */
class Index extends \fcfpay\PaymentGateway\Controller\AbstractCheckoutAction
{
    /**
     * Redirect to checkout
     *
     * @return void
     */
    public function execute()
    {

        $order = $this->getOrder();

        if (isset($order)) {
            $redirectUrl = $this->getCheckoutSession()->getFcfGatewayCheckoutRedirectUrl();


            if (isset($redirectUrl)) {
                $this->getResponse()->setRedirect($redirectUrl);
            } else {
                $this->redirectToCheckoutFragmentPayment();
            }
        }
    }
}
