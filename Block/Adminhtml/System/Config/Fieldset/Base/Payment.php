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

namespace fcfpay\PaymentGateway\Block\Adminhtml\System\Config\Fieldset\Base;

/**
 * Base Renderer for fcfpay Checkout Panel in System Configuration
 *
 * Class Payment
 * @package fcfpay\PaymentGateway\Block\Adminhtml\System\Config\Fieldset\Base
 */
abstract class Payment extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Config\Model\Config
     */
    protected $_backendConfig;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Magento\Config\Model\Config $backendConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Config\Model\Config $backendConfig,
        array $data = []
    ) {
        $this->_backendConfig = $backendConfig;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * Retrieves the Module Panel Css Class
     * @return string
     */
    abstract protected function getBlockHeadCssClass();

    /**
     * Add custom css class
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getFrontendClass($element)
    {
        return parent::_getFrontendClass($element) . ' with-button';
    }

    /**
     * Return header title part of html for payment solution
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getHeaderTitleHtml($element)
    {
        $html = "<div class=\"config-heading {$this->getBlockHeadCssClass()}\" ><div class=\"headingGateway\"><strong>" . $element->getLegend();

        $html .= '</strong>';

        if ($element->getComment()) {
            $html .= '<span class="heading-intro">' . $element->getComment() . '</span>';
        }
        $html .= '</div>';

        $htmlId = $element->getHtmlId();
        $html .= '<div class="button-container"><button type="button"' .
            ' class="button action-configure' .
            '" id="' .
            $htmlId .
            '-head" onclick="gatewayToggleSolution.call(this, \'' .
            $htmlId . '\'); return false;"><span class="state-closed">' . __(
                'Configure'
            ) . '</span><span class="state-opened">' . __(
                'Collapse'
            ) . '</span></button>';

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Return header comment part of html for payment solution
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getHeaderCommentHtml($element)
    {
        return '';
    }

    /**
     * Get collapsed state on-load
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _isCollapseState($element)
    {
        return false;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getExtraJs($element)
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.gatewayToggleSolution = function (id, url) {
                Fieldset.toggleCollapse(id, url);
            }
        });";

        return $this->_jsHelper->getScript($script);
    }
}
