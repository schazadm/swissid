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
        // age verification switch parent div -> form-group
        let parentFormGroup = optionalDivSelector.parent('div.form-group');
        // check whether age verification is active
        if (!$(inputOnSelector).prop('checked')) {
            // if active (checked) collapse age verification switch div
            parentFormGroup.addClass('collapse');
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
            } else {
                parentFormGroup.addClass('collapse');
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