(function ($) {
    $(document).ready(function () {
        $('a.sendmail').click(function () {
            $(this).closest('tr').find('td').each(function (i, v) {
                if (isValidEmailAddress($(v).html().toString().trim())) {
                    let mail = $(v).html().toString().trim();
                    console.log(mail);
                    // TODO: mail works. Setup an action and do an AJAX call to send an emal
                }
            });
        });

        function isValidEmailAddress(emailAddress) {
            let pattern = new RegExp(/^(("[\w-+\s]+")|([\w-+]+(?:\.[\w-+]+)*)|("[\w-+\s]+")([\w-+]+(?:\.[\w-+]+)*))(@((?:[\w-+]+\.)*\w[\w-+]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][\d]\.|1[\d]{2}\.|[\d]{1,2}\.))((25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\.){2}(25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})]?$)/i);
            return pattern.test(emailAddress);
        }
    });
})(jQuery);