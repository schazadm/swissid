/** ====================================================================
 *
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code.
 *
 * @author             Online Services Rieder GmbH
 * @copyright          Online Services Rieder GmbH
 * @license            Check at: https://www.os-rieder.ch/
 * @date:              22.10.2021
 * @version:           1.0.0
 * @name:              SwissID
 * @description        Provides the possibility for a customer to log in with his SwissID.
 * @website            https://www.os-rieder.ch/
 *
 ================================================================== **/

(function ($) {
    $(document).ready(function () {
        $('a.sendmail').click(function () {
            $(this).closest('tr').find('td').each(function (i, v) {
                if (isValidEmailAddress($(v).html().toString().trim())) {
                    let mail = $(v).html().toString().trim();
                    $.ajax(swissidNonCustomerController, {
                            data: {
                                'ajax': 1,
                                'action': 'sendMail',
                                'non_swissid_customer_email': mail
                            },
                            success: function (response) {
                                let jsonResponse = JSON.parse(response);
                                if (jsonResponse.status === 'success') {
                                    showSuccessMessage(jsonResponse.message);
                                } else {
                                    showErrorMessage(jsonResponse.message);
                                }
                            },
                            error: function (response) {
                                showErrorMessage("An error occurred while handling your request.");
                            }
                        }
                    )
                }
            });
        });

        function isValidEmailAddress(emailAddress) {
            let pattern = new RegExp(/^(("[\w-+\s]+")|([\w-+]+(?:\.[\w-+]+)*)|("[\w-+\s]+")([\w-+]+(?:\.[\w-+]+)*))(@((?:[\w-+]+\.)*\w[\w-+]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][\d]\.|1[\d]{2}\.|[\d]{1,2}\.))((25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\.){2}(25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})]?$)/i);
            return pattern.test(emailAddress);
        }
    });
})(jQuery);