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
namespace fcfpay\PaymentGateway\Controller\Ipn;



/**
 * Unified IPN controller for all supported fcfpay Payment Methods
 * Class Index
 * @package fcfpay\PaymentGateway\Controller\Ipn
 */
class Index extends \Magento\Framework\App\Action\Action
{


    protected $resultRedirect;

    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->resultRedirect = $context->getResultFactory();
        parent::__construct($context);
    }
    /**
     * Instantiate IPN model and pass IPN request to it
     *
     * @return void
     */
    public function execute()
    {

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/onepage/success');
        return $resultRedirect;

    }
    /**
     * @inheritDoc
     */


}
