// automatically disable fields that are already accepted in the
$(document).ready(function () {
    $(document).on('shown.oc.popup', function() {
        // Get form and check if fields are selected, and disable if they are
        $('.variantPlacementOptions').on('change', 'select', function() {
            console.log('did it work?')
            $('.variantPlacementOptions select option').prop('disabled', false);
            $('.variantPlacementOptions select').each(function() {
                let val = this.value;
                $('.variantPlacementOptions select').not(this).find('option').filter(function() {
                    return this.value === val;
                }, this).prop('disabled', true);
            });
        }).change();

        $('.variantPlacementOptions select').on('change', function() {
            // console.log('did it work?')
            $('.variantPlacementOptions select option').prop('disabled', false);
            $('.variantPlacementOptions select').each(function() {
                let val = this.value;
                $('.variantPlacementOptions select').not(this).find('option').filter(function() {
                    return this.value === val;
                }, this).prop('disabled', true);
            });
        }).change();

        // Get form and check if fields are selected, and disable if they are (on ajaxDone)
        $(document).on('ajaxDone', function (){
            $('.variantPlacementOptions').on('change', 'select', function() {
                $('.variantPlacementOptions select option').prop('disabled', false);
                $('.variantPlacementOptions select').each(function() {
                    let val = this.value;
                    $('.variantPlacementOptions select').not(this).find('option').filter(function() {
                        return this.value === val;
                    }, this).prop('disabled', true);
                });
            }).change();

            $('.variantPlacementOptions select').on('change', function() {
                $('.variantPlacementOptions select option').prop('disabled', false);
                $('.variantPlacementOptions select').each(function() {
                    let val = this.value;
                    $('.variantPlacementOptions select').not(this).find('option').filter(function() {
                        return this.value === val;
                    }, this).prop('disabled', true);
                });
            }).change();
        })
    });
})
