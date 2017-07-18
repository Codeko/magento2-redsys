require([
    'jquery',
    'mage/translate',
    'jquery/validate'],
        function ($) {
            $.validator.addMethod(
                    'validate-32-length', function (v) {
                        return (v.length === 32);
                    }, $.mage.__('Field must have length of 32'));
        }
);


