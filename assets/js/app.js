// Global variable for our select
let select = '.variantPlacementOptions select'

function setSelects() {
    $(select).each(function() {
        let val = this.value;
        $(select).not(this).find('option').filter(function() {
            return this.value === val;
        }, this).prop('disabled', true);
    });
}

function updateSelects() {
    $('.variantPlacementOptions').on('change', 'select', function() {
        $(select + ' option').prop('disabled', false)
        setSelects();
    });
}

$(document).on('shown.oc.popup', function() {
    setSelects();
    updateSelects();
    $(document).on('ajaxDone', function (){
        setSelects();
        updateSelects();
        if ($(select).data('select2')) {
            // set the select. Prevents error in the console on ajaxDone
            $(select).select2();
            // stops a scrolling bug by closing the select before destroying it.
            $(select).select2('close');
            $(select).select2('destroy');
            $(select).select2();
        }
    });
});

// Bind change event to document so it stays with us regardless of changes in DOM
$(document).on('change', '.variantPlacementOptions select', function() {
    setSelects();
    updateSelects();
    if ($(select).data('select2')) {
        $(select).select2('close');
        $(select).select2('destroy');
        $(select).select2();
    }
});