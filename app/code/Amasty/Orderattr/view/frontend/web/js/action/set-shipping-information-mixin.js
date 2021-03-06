define([
    'jquery',
    'mage/utils/wrapper',
    'uiRegistry'
], function ($, wrapper, registry) {
    'use strict';

    return function (setShippingInformation) {
        return wrapper.wrap(setShippingInformation,
            function (originalAction) {
                var source = registry.get('checkoutProvider');
                source.set('params.invalid', false);
                if (source.get('shippingAddress.custom_attributes')) {
                    source.trigger('shippingAddress.custom_attributes.data.validate');
                };

                if (source.get('params.invalid')) {
                    if ($('#checkout-step-shipping div.field._error[name^="shippingAddress.custom_attributes"').length){
                        throw new Error('Form is invalid');
                    }
                }
                return originalAction();
            }
        );
    };

});
