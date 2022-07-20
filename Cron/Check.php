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

namespace fcfpay\PaymentGateway\Cron;
use Magento\Sales\Model\Order;
use Magento\Cron\Model\Config\Source\Frequency;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\HTTP\Client\Curl;
class Check
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var ValueFactory
     */
    protected $_configValueFactory;
    /**
     * @var mixed|string
     */
    protected $_runModelPath = '';
    protected $_resourceCollection = '';
    protected $_order = '';
    protected $orderRepository;
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;
    /**
     * CronConfig constructor.
     *
     */

    public function __construct(
        InvoiceSender $invoiceSender,
        Transaction $transaction,
        InvoiceService $invoiceService,
        OrderRepositoryInterface $orderRepository,
        ValueFactory $configValueFactory,
        AbstractResource $resource = null,
        ResourceConnection $resourceCollection,
        Order $order,
        $runModelPath = '',
        Curl $curl
    ) {
        $this->_runModelPath = $runModelPath;
        $this->_resourceCollection = $resourceCollection;
        $this->_order = $order;
        $this->_configValueFactory = $configValueFactory;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->curl = $curl;

    }

    public function execute()
    {
        $db = $this->_resourceCollection->getConnection();
        $select = $db->select()->from(['o' => "sales_order_payment"])
            ->join(
                ['soa' => 'sales_order'],
                'soa.entity_id=o.entity_id'
            )
            ->where("soa.status='payment_review'")
            ->where("o.method='fcfpay_checkout'")
            ->order('o.entity_id', 'ASC')
            ->limit(5);

        $rows = $db->fetchAll($select);
        $ordersStr = '[';
        foreach ($rows as $row) {
            $order = $this->_order->load($row['entity_id']);
            $ordersStr.='"'.$order->getIncrementId().'",';
        }

        $ordersStr = substr_replace($ordersStr, "", -1).']';

        $response = $this->checkStatus($ordersStr);

        $to_time = strtotime(date("Y-m-d H:i:s"));


        if ($response->success===true) {
            foreach ($response->data as $key => $value) {

                $order = $this->_order->loadByIncrementId($value->order_id);//$objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($value->order_id);
                $from_time =strtotime($order->getCreatedAt());
                if (floatval($value->total_fiat_amount)>0) {

                    $fiat_amount = floatval($value->total_fiat_amount);
                    $def = $this->getDiff(floatval($order->getGrandTotal()), floatval($value->total_fiat_amount));
                    if ($def) {
                        $orderState = Order::STATE_COMPLETE;


                        $order->setTotalPaid($fiat_amount);
                        $order->setBaseTotalPaid($fiat_amount);
                        $order->setState($orderState)->setStatus($orderState);
                        $order->save();
                        $this->createInvoice($order->getId());
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

        return $this;
    }
    /**
     * create Invoice
     *
     * @param float $orderId
     * @return boolean
     */


    public function createInvoice($orderId)
    {
        $order = $this->orderRepository->get($orderId);
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();

            $transactionSave =
                $this->transaction
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
            $transactionSave->save();
            $this->invoiceSender->send($invoice);

            $order->addCommentToStatusHistory(
                __('Notified customer about invoice creation #%1.', $invoice->getId())
            )->setIsCustomerNotified(true)->save();
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
     * check payment request status
     *
     * @param integer $orderId
     * @return object
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
        $this->curl->setOption(CURLOPT_URL , 'https://'.$modeVarchar.'.fcfpay.com/api/v2/check-orders');
        $this->curl->setOption(CURLOPT_HEADER, 0);
        $this->curl->setOption(CURLOPT_TIMEOUT, 60);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1);
        $this->curl->setOption(CURLOPT_POST, 1);

        $this->curl->setOption(CURLOPT_HTTPHEADER , array(
            'Authorization: Bearer '.$key,
            'Content-Type: application/json'
        ));
        $this->curl->setOption(CURLOPT_POSTFIELDS, '{
                      "order_ids": '.$orderId.'
                }');
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->post('https://'.$modeVarchar.'.fcfpay.com/api/v2/check-orders',[]);
        $response = $this->curl->getBody();

        if ($response === false) {
            return false;
        } else {
            return json_decode($response);
        }

    }
}
