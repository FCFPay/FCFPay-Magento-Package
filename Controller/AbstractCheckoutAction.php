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
namespace fcfpay\PaymentGateway\Controller;

/**
 * Base Checkout Controller Class
 * Class AbstractCheckoutAction
 * @package fcfpay\PaymentGateway\Controller
 */
abstract class AbstractCheckoutAction extends \fcfpay\PaymentGateway\Controller\AbstractAction
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        parent::__construct($context, $logger);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
    }

    /**
     * Get an Instance of the Magento Checkout Session
     * @return \Magento\Checkout\Model\Session
     */
    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * Get an Instance of the Magento Order Factory
     * It can be used to instantiate an order
     * @return \Magento\Sales\Model\OrderFactory
     */
    protected function getOrderFactory()
    {
        return $this->_orderFactory;
    }

    /**
     * Get an Instance of the current Checkout Order Object
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        $orderId = $this->getCheckoutSession()->getLastRealOrderId();

        if (!isset($orderId)) {
            return null;
        }

        $order = $this->getOrderFactory()->create()->loadByIncrementId(
            $orderId
        );

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    /**
     * Does a redirect to the Checkout Payment Page
     * @return void
     */
    protected function redirectToCheckoutFragmentPayment()
    {
        $this->_redirect('checkout', ['_fragment' => 'payment']);
    }

    /**
     * Does a redirect to the Checkout Success Page
     * @return void
     */
    protected function redirectToCheckoutOnePageSuccess()
    {
        $this->_redirect('checkout/onepage/success');
    }

    /**
     * Does a redirect to the Checkout Cart Page
     * @return void
     */
    protected function redirectToCheckoutCart()
    {
        $this->_redirect('checkout/cart');
    }
}
