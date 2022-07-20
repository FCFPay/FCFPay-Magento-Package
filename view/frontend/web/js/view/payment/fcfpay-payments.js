define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'fcfpay_checkout',
                component: 'fcfpay_PaymentGateway/js/view/payment/method-renderer/checkout-method'
            },
            {
                type: 'fcfpay_direct',
                component: 'fcfpay_PaymentGateway/js/view/payment/method-renderer/direct-method'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
