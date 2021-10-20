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