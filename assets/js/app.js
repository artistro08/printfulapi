function setSelects() {
    $('.variantPlacementOptions select').each(function() {
        let val = this.value;
        $('.variantPlacementOptions select').not(this).find('option').filter(function() {
            return this.value === val;
        }, this).prop('disabled', true);
    });
}

function updateSelects() {
    $('.variantPlacementOptions').on('change', 'select', function() {
        $('.variantPlacementOptions select option').prop('disabled', false)
        setSelects();
    }).change();
}
$(document).on('shown.oc.popup', function() {
    setSelects();
    updateSelects();
    $(document).on('ajaxDone', function (){
        setSelects();
        updateSelects()
    }).change();
});
