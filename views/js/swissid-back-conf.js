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
    // age verification switch input
    let name = ageVerificationInputName;
    // age verification optional switch input
    let nameOptional = ageVerificationOptionalInputName;
    // input specific prestashop switch name
    let nameOn = name + '_on';
    let nameOff = name + '_off';
    // input jQuery identifiers
    let inputOnSelector = 'input#' + nameOn;
    let inputOffSelector = 'input#' + nameOff;

    $(document).ready(function () {
        // age verification switch div
        let optionalDivSelector = $('div#conf_id_' + ageVerificationOptionalInputName);
        let textDivSelector = $('div#conf_id_' + ageVerificationTextInputName);
        let ageOverProductDivSelector = $('div#conf_id_' + ageOverProductInputName);
        // age verification switch parent div -> form-group
        let parentFormGroup = optionalDivSelector.parent('div.form-group');
        let parentFormGroup2 = textDivSelector.parent('div.form-group');
        let parentFormGroup3 = ageOverProductDivSelector.parent('div.form-group');
        // check whether age verification is active
        if (!$(inputOnSelector).prop('checked')) {
            // if active (checked) collapse age verification switch div
            parentFormGroup.addClass('collapse');
            parentFormGroup2.addClass('collapse');
            parentFormGroup3.addClass('collapse');
        }
        $(inputOnSelector).change(function () {
            toggleParentFormGroupVisibility();
        });
        $(inputOffSelector).change(function () {
            toggleParentFormGroupVisibility();
        });

        function toggleParentFormGroupVisibility() {
            if ($(parentFormGroup).hasClass('collapse')) {
                parentFormGroup.removeClass('collapse');
                parentFormGroup2.removeClass('collapse');
                parentFormGroup3.removeClass('collapse');
            } else {
                parentFormGroup.addClass('collapse');
                parentFormGroup2.addClass('collapse');
                parentFormGroup3.addClass('collapse');
            }
        }

        //
        $('#swissid-panel-toggle').click(function () {
            togglePanelHeadingIcon();
        });

        function togglePanelHeadingIcon() {
            let element = $('#swissid-panel-toggle span');
            let plusIcon = 'icon-plus';
            let minusIcon = 'icon-minus';
            if (element.hasClass(plusIcon)) {
                element.removeClass(plusIcon).addClass(minusIcon);
            } else {
                element.removeClass(minusIcon).addClass(plusIcon);
            }
        }
    });
})(jQuery);