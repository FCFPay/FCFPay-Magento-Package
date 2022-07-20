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


namespace fcfpay\PaymentGateway\Model\Config\Source\Order\Status;

/**
 * Order Statuses
 * Class Status
 * @package fcfpay\PaymentGateway\Model\Config\Source\Order\Status
 */
class Status extends \Magento\Sales\Model\Config\Source\Order\Status
{
    /**
     * @var string
     */
    protected $_stateStatuses = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
}
