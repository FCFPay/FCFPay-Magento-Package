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

use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Callback extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    /**
     * @var Curl
     */
    protected $curl;

    public function __construct(
        Curl $curl,
        Order $order,
        \Magento\Framework\App\Action\Context $context)
    {
        $this->curl = $curl;
        $this->_order = $order;
        return parent::__construct($context);
    }

    public function execute()
    {
        $inputJSON = file_get_contents('php://input');
        $response = json_decode($inputJSON);

        if ($response->success) {
           $callback = $this->checkStatus($response->data->order_id);
           if ($callback->success){
               $to_time = strtotime(date("Y-m-d H:i:s"));
               $order = $this->_order->loadByIncrementId($callback->data->order_id);//$objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($value->order_id);
               $from_time =strtotime($order->getCreatedAt());
               if (floatval($callback->data->total_fiat_amount)>0) {
                   $fiat_amount = floatval($callback->data->total_fiat_amount);
                   $def = $this->getDiff(floatval($order->getGrandTotal()), floatval($callback->data->total_fiat_amount));
                   if ($def) {
                       $orderState = Order::STATE_COMPLETE;
                       $order->setTotalPaid($fiat_amount);
                       $order->setBaseTotalPaid($fiat_amount);
                       $order->setState($orderState)->setStatus($orderState);
                       $order->save();
                   } else {
                       /* reset total_paid & base_total_paid of order */
                       if (round(abs($to_time - $from_time) / 60, 2) > 125 && $order->getBaseTotalPaid() != 0) {
                           $orderState = Order::STATE_HOLDED;
                           $order->setState($orderState)->setStatus($orderState);
                       }
                       $order->setTotalPaid($fiat_amount);
                       $order->setBaseTotalPaid($fiat_amount);
                       $order->save();
                   }
               } else {
                   if ((intval(round(abs($to_time - $from_time) / 60, 2)) > 125) && (floatval($order->getBaseTotalPaid()) == 0)) {
                       $orderState = Order::STATE_CANCELED;
                       $order->setState($orderState)->setStatus($orderState);
                       $order->save();
                   }

               }
           }


        }
    }

    /**
     * check payment differences
     *
     * @param float $amount
     * @param float $fiat_amount
     * @return boolean
     */

    public function getDiff($amount,$fiat_amount)
    {
        $percent_order_def = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue(
                'payment/fcfpay_checkout/percent_order_def',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

        $min_order_def = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue(
                'payment/fcfpay_checkout/min_order_def',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        $percent = (floatval($percent_order_def) / 100) * $amount;

        if ($percent > floatval($min_order_def)){
            $percent =  floatval($min_order_def);
        }

        if ($amount-$fiat_amount>$percent){
            return false;
        }
        return true;
    }
    /**
     * check order status
     *
     * @param integer $orderId
     * @return json
     */


    function checkStatus($orderId){
        $key = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue(
                'payment/fcfpay_checkout/shop_key',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        $mode = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue(
                'payment/fcfpay_checkout/test_mode',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        $modeVarchar =  "merchant";
        if ($mode){
            $modeVarchar =  "sandbox";
        }
        $this->curl->setOption(CURLOPT_URL , 'https://'.$modeVarchar.'.fcfpay.com/api/v2/check-order');
        $this->curl->setOption(CURLOPT_HEADER, 0);
        $this->curl->setOption(CURLOPT_TIMEOUT, 60);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1);
        $this->curl->setOption(CURLOPT_POST, 1);

        $this->curl->setOption(CURLOPT_HTTPHEADER , array(
            'Authorization: Bearer '.$key,
            'Content-Type: application/json'
        ));

        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->post('https://'.$modeVarchar.'.fcfpay.com/api/v2/check-order',json_encode(["order_id"=>$orderId]));
        $response = $this->curl->getBody();

        if ($response === false) {
            return false;
        } else {
            return json_decode($response);
        }

    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\Controller\Result\Json
     */
    protected function createResultJson()
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
    }
    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

}