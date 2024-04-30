jQuery(document).ready(function($) {
    var quantityInput = $(".quantity input.qty");
    quantityInput.attr("min", script_data.min_quantity);
    quantityInput.attr("max", script_data.max_quantity);
    quantityInput.attr("step", script_data.group_of);
});


jQuery(document).ready(function($) {
    $(".user-roles-select").change(function() {
        var selectedRoles = $(this).val();
        var variationId = $(this).attr("id").replace("user_roles_", ""); // Get the variation ID
        $(".user-role-box").each(function() {
            var boxId = $(this).attr("id");
            var roleId = boxId.replace("user-role-box-" + variationId + "-", "");
            if (selectedRoles !== null && selectedRoles.includes(roleId)) {
                $(this).show(); // Show user-role box for the selected role
                $("#title-" + roleId).show(); // Show title for the selected role

                // Pre-populate quantities for selected user-role and variation
                $("#min-quantity-" + variationId + "-" + roleId).val(script_data[variationId][roleId]['min_quantity']);
                $("#max-quantity-" + variationId + "-" + roleId).val(script_data[variationId][roleId]['max_quantity']);
                $("#group-of-" + variationId + "-" + roleId).val(script_data[variationId][roleId]['group_of']);
            } else {
                $(this).hide(); // Hide user-role box for the unselected role
                $("#title-" + roleId).hide(); // Hide title for the unselected role
            }
        });
    });
});

jQuery(document).ready(function($) {
    // Listen for changes in the quantity input field
    $('input.qty').on('change', function() {
        var currentQty = parseInt($(this).val()); // Get the current quantity
        var groupOf = parseInt($(this).attr('group-of')); // Get the group size

        // If the quantity is not a multiple of the group size, adjust it
        if (currentQty % groupOf !== 0) {
            var newQty = Math.ceil(currentQty / groupOf) * groupOf; // Round up to the nearest multiple
            $(this).val(newQty); // Update the quantity input field
        }
    });
});

jQuery(document).ready(function($) {
    // Listen for click events on the quantity increment and decrement buttons
    $('.quantity').on('click', 'button', function() {
        // Get the associated quantity input field
        var qtyInput = $(this).siblings('input.qty');

        // Use a short delay to allow the quantity input value to update before enforcing the group size
        setTimeout(function() {
            var currentQty = parseInt(qtyInput.val()); // Get the current quantity
            var groupOf = parseInt(qtyInput.attr('group-of')); // Get the group size

            // If the quantity is not a multiple of the group size, adjust it
            if (currentQty % groupOf !== 0) {
                var newQty = Math.ceil(currentQty / groupOf) * groupOf; // Round up to the nearest multiple
                qtyInput.val(newQty); // Update the quantity input field
            }
        }, 1);
    });
});

