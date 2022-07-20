<?php
/*
 * Copyright (C) 2017 FCF Pay
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      FCF Pay
 * @copyright   2017 FCF Pay
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace fcfpay\PaymentGateway\Model\Method;
use Magento\Framework\HTTP\Client\Curl;
/**
 * Checkout Payment Method Model Class
 * Class Checkout
 * @package fcfpay\PaymentGateway\Model\Method
 */
class Checkout extends \Magento\Payment\Model\Method\AbstractMethod
{
    use \fcfpay\PaymentGateway\Model\Traits\OnlinePaymentMethod;
    use \fcfpay\PaymentGateway\Model\Traits\Logger;

//    const CODE = 'fcfpay_payment_gateway_checkout';
    const CODE = 'fcfpay_checkout';
    /**
     * Checkout Method Code
     */
    protected $_code = self::CODE;

    protected $_canOrder                    = true;
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canCancelInvoice            = true;
    protected $_canVoid                     = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canAuthorize                = true;
    protected $_isInitializeNeeded          = false;
    protected $curl;

    /**
     * Get Instance of the Magento Code Logger
     * @return \Monolog\Logger
     */
    protected function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Checkout constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\App\Action\Context $actionContext
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \fcfpay\PaymentGateway\Helper\Data $moduleHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Action\Context $actionContext,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger  $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \fcfpay\PaymentGateway\Helper\Data $moduleHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        Curl $curl,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_actionContext = $actionContext;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_moduleHelper = $moduleHelper;
        $this->_logger = $logger;
        $this->curl = $curl;
        $this->_configHelper =
            $this->getModuleHelper()->getMethodConfig(
                $this->getCode()
            );
    }


    /**
     * Get Default Payment Action On Payment Complete Action
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return \Magento\Payment\Model\Method\AbstractMethod::ACTION_ORDER;
    }

    /**
     * Get Available Checkout Transaction Types
     * @return array
     */
    public function getCheckoutTransactionTypes()
    {
        $selected_types = $this->getConfigHelper()->getTransactionTypes();

        return $selected_types;
    }

    /**
     * Get Available Checkout Payment Method Types
     * @return array
     */
    public function getCheckoutPaymentMethodTypes()
    {
        $selected_types = $this->getConfigHelper()->getPaymentMethodTypes();

        return $selected_types;
    }

    /**
     * Create a Web-Payment Form Instance
     * @param array $data
     * @return \stdClass
     * @throws \Magento\Framework\Webapi\Exception
     */
    protected function checkout($data)
    {
            return $this->createOrder($data);
    }

    /**
     * Order Payment
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();

        $orderId = $order->getIncrementId();//ltrim($order->getIncrementId(), '0');

        $data = [
            'tracking_id' =>
                $this->getModuleHelper()->genTransactionId(
                  $orderId
                ),
            'transaction_types' =>
                $this->getConfigHelper()->getTransactionTypes(),
            'order' => [
                'increment_id' => $orderId,
                'currency' => $order->getBaseCurrencyCode(),
                'language' => $this->getModuleHelper()->getLocale(),
                'amount' => $amount,
                'usage' => $this->getModuleHelper()->buildOrderUsage(),
                'description' => __('Order # %1 payment', $orderId),
                'customer' => [
                    'email' => $this->getCheckoutSession()->getQuote()->getCustomerEmail(),
                ],
                'billing' =>
                    $order->getBillingAddress(),
                'shipping' =>
                    $order->getShippingAddress()
            ],
            'erip' => [
              'service_no' => $this->getConfigHelper()->getValue('erip_service_no'),
              'service_info' => array(
                __('Order # %1 payment', $orderId),
                $this->getModuleHelper()->buildOrderDescriptionText(
                  $order
                ),
              ),
            ],
            'urls' => [
                'notify' =>
                    $this->getModuleHelper()->getNotificationUrl(
                        $this->getCode()
                    ),
                'return_success' =>
                    $this->getModuleHelper()->getReturnUrl(
                        $this->getCode(),
                        'success'
                    ),
                'return_cancel'  =>
                    $this->getModuleHelper()->getReturnUrl(
                        $this->getCode(),
                        'cancel'
                    ),
                'return_failure' =>
                    $this->getModuleHelper()->getReturnUrl(
                        $this->getCode(),
                        'failure'
                    ),
            ]
        ];
        $this->getConfigHelper()->initGatewayClient();
        try {
            $responseObject = $this->createOrder($data);
            $isSuccessful = true;
                //$responseObject->isSuccess() && !empty($responseObject->getRedirectUrl());
            if (!$isSuccessful) {
                $errorMessage = false;//$responseObject->getMessage();
                $this->getCheckoutSession()->setFcfGatewayLastCheckoutError(
                    $errorMessage
                );
                $this->getModuleHelper()->throwWebApiException($errorMessage);
            }
            $payment->setTransactionId($orderId);//$responseObject->getToken()
            $payment->setIsTransactionPending(true);
            $payment->setIsTransactionClosed(false);
//            $this->getModuleHelper()->setPaymentTransactionAdditionalInfo(
//                $payment,
//                $responseObject
//            );
//          print_r($responseObject);die();
            $this->getCheckoutSession()->setFcfGatewayCheckoutRedirectUrl(
                $responseObject->data->checkout_page_url
            );
            $this->_writeDebugData();
            return $this;
        } catch (\Exception $e) {
            $this->_addDebugData('exception', json_encode($responseObject).$e->getMessage());

            $this->getCheckoutSession()->setFcfGatewayLastCheckoutError(
                $e->getMessage()
            );
            $this->_writeDebugData();
            $this->getModuleHelper()->maskException($e);
        }
    }

    /**
     * Payment Capturing
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->_addDebugData('capture_process', 'Capture transaction for order #' . $order->getIncrementId());

        $authTransaction = $this->getModuleHelper()->lookUpAuthorizationTransaction(
            $payment
        );
        if (!isset($authTransaction)) {
            $errorMessage = __('Capture transaction for order # %1 cannot be finished (No Authorize Transaction exists)',
                $order->getIncrementId()
            );
            $this->_addDebugData('capture_error', $errorMessage);
            $this->_writeDebugData();

            $this->getModuleHelper()->throwWebApiException(
                $errorMessage
            );
        }
        try {
            $this->doCapture($payment, $amount, $authTransaction);
        } catch (\Exception $e) {
            $this->_addDebugData('exception', $e->getMessage());
            $this->_writeDebugData();
            $this->getModuleHelper()->maskException($e);
        }
        $this->_writeDebugData();

        return $this;
    }

    /**
     * Payment refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->_addDebugData('refund_process', 'Refund transaction for order #' . $order->getIncrementId());

        $captureTransaction = $this->getModuleHelper()->lookUpCaptureTransaction(
            $payment
        );

        if (!isset($captureTransaction)) {
            $errorMessage = __('Refund transaction for order # %1 cannot be finished (No Capture Transaction exists)',
                $order->getIncrementId()
            );

            $this->_addDebugData('refund_error', $errorMessage);
            $this->_writeDebugData();

            $this->getMessageManager()->addError($errorMessage);

            $this->getModuleHelper()->throwWebApiException(
                $errorMessage
            );
        }

        try {
            $this->doRefund($payment, $amount, $captureTransaction);
        } catch (\Exception $e) {
            $this->_addDebugData('exception', $e->getMessage());
            $this->_writeDebugData();

            $this->getMessageManager()->addError(
                $e->getMessage()
            );

            $this->getModuleHelper()->maskException($e);
        }
        $this->_writeDebugData();

        return $this;
    }

    /**
     * Payment Cancel
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->void($payment);

        return $this;
    }

    /**
     * Void Payment
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order $order */

        $order = $payment->getOrder();

        $this->_addDebugData('void_process', 'Void transaction for order #' . $order->getIncrementId());

        $referenceTransaction = $this->getModuleHelper()->lookUpVoidReferenceTransaction(
            $payment
        );

        if ($referenceTransaction->getTxnType() == \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH) {
            $authTransaction = $referenceTransaction;
        } else {
            $authTransaction = $this->getModuleHelper()->lookUpAuthorizationTransaction(
                $payment
            );
        }

        if (!isset($authTransaction) || !isset($referenceTransaction)) {
            $errorMessage = __('Void transaction for order # %1 cannot be finished (No Authorize / Capture Transaction exists)',
                            $order->getIncrementId()
            );
            $this->_addDebugData('void_error', $errorMessage);
            $this->_writeDebugData();

            $this->getModuleHelper()->throwWebApiException($errorMessage);
        }

        try {
            $this->doVoid($payment, $authTransaction, $referenceTransaction);
        } catch (\Exception $e) {
            $this->_addDebugData('exception', $e->getMessage());
            $this->_writeDebugData();
            $this->getModuleHelper()->maskException($e);
        }
        $this->_writeDebugData();

        return $this;
    }

    /**
     * Determines method's availability based on config data and quote amount
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote) &&
            $this->getConfigHelper()->isMethodAvailable();
    }

    /**
     * Checks base currency against the allowed currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return $this->getModuleHelper()->isCurrencyAllowed(
            $this->getCode(),
            $currencyCode
        );
    }


    protected function _getDebugMessage() {
        return $this->_debugData;
      }


    /**
     * Payment request
     *
     * @param array $data
     * @return object
     */

    public function createOrder($data){

        $storeId = $this->_storeManager->getDefaultStoreView()->getStoreId();
        $url = $this->_storeManager->getStore($storeId)->getUrl("fcfpay/ipn/index");
        $parse = parse_url($url);
        $domain =  $parse['host'];
        $this->curl->setOption(CURLOPT_HEADER, 0);
        $this->curl->setOption(CURLOPT_TIMEOUT, 60);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1);
        $this->curl->setOption(CURLOPT_POST, 1);

        $this->curl->setOption(CURLOPT_HTTPHEADER , array(
            'Authorization: Bearer '.$this->getConfigHelper()->getShopKey(),
            'Content-Type: application/json'
        ));
        $this->curl->setOption(CURLOPT_POSTFIELDS, CURLOPT_POSTFIELDS ,'{
                "domain": "'.$domain.'",
                "order_id": "'.$data['order']['increment_id'].'",
                "user_id": "1",
                "amount": "'.$data['order']['amount'].'",
                "currency_name": "'.$data['order']['currency'].'",
                "currency_code": "840",
                "order_date": "'.date("Y-m-d").'",
                "redirect_url": "'.$url.'"
                
            }');
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->post('https://'.$this->getConfigHelper()->getModeVarchar().'.fcfpay.com/api/v1/create-order');
        $response = $this->curl->getBody();

        if ($response === false) {
            return false;
        } else {
            return json_decode($response);
        }

    }


}
