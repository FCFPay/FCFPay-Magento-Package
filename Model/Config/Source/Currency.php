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

namespace fcfpay\PaymentGateway\Model\Config\Source;

/**
 * Locale currency source
 * Class Currency
 * @package fcfpay\PaymentGateway\Model\Config\Source\Locale\Currency
 */
class Currency extends \Magento\Config\Model\Config\Source\Locale\Currency
{
    /**
     * @var \fcfpay\PaymentGateway\Helper\Data
     */
    protected $_moduleHelper;

    /**
     * @param \Magento\Framework\Locale\ListsInterface $localeLists
     * @param \fcfpay\PaymentGateway\Helper\Data $moduleHelper
     */
    public function __construct(
        \Magento\Framework\Locale\ListsInterface $localeLists,
        \fcfpay\PaymentGateway\Helper\Data $moduleHelper
    ) {
        parent::__construct($localeLists);
        $this->_moduleHelper = $moduleHelper;
    }

    /**
     * Get an Instance of the Module Helper
     * @return \fcfpay\PaymentGateway\Helper\Data
     */
    protected function getModuleHelper()
    {
        return $this->_moduleHelper;
    }

    /**
     * Builds the options array for the MultiSelect control in the Admin Zone
     * @return array
     */
    public function toOptionArray()
    {
        $options = parent::toOptionArray();

        return $this->getModuleHelper()->getGlobalAllowedCurrenciesOptions($options);
    }
}
