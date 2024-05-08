<?php

/**
 * Storefront automatically loads the core CSS even if using a child theme as it is more efficient
 * than @importing it in the child theme style.css file.
 *
 * Uncomment the line below if you'd like to disable the Storefront Core CSS.
 *
 * If you don't plan to dequeue the Storefront Core CSS you can remove the subsequent line and as well
 * as the sf_child_theme_dequeue_style() function declaration.
 */
//add_action( 'wp_enqueue_scripts', 'sf_child_theme_dequeue_style', 999 );

/**
 * Dequeue the Storefront Parent theme core CSS
 */
function sf_child_theme_dequeue_style() {
    wp_dequeue_style( 'storefront-style' );
    wp_dequeue_style( 'storefront-woocommerce-style' );
}

/**
 * Note: DO NOT! alter or remove the code above this text and only add your custom PHP functions below this text.
 */

 add_action('woocommerce_product_options_general_product_data', 'add_user_roles_select_box_for_simple_products');
function add_user_roles_select_box_for_simple_products() {
    global $post;
    $all_roles = get_editable_roles();
    ?>
    <div class="options_group">
        <p class="form-field">
            <label for="user_roles">User Roles</label>
            <select multiple id="user_roles" name="user_roles[]" class="user-roles-select">
                <?php
                foreach ($all_roles as $role_name => $role_info) {
                    if (isset($role_info['name']) && is_string($role_info['name'])) {
                        ?>
                        <option value="<?php echo $role_name; ?>"><?php echo $role_info['name']; ?></option>
                        <?php
                    }
                }
                ?>
            </select>
        </p>

        <div class="user-role-fields-container">
            <?php
            // Loop through selected roles to display input fields
            foreach ($all_roles as $role_name => $role_info) {
                ?>
                <div class="role-fields <?php echo $role_name; ?>">
                    <h4><?php echo $role_info['name']; ?></h4>
                    <p class="form-field minimum_allowed_quantity_field">
                        <label for="minimum_allowed_quantity_<?php echo $role_name; ?>">Minimum quantity</label>
                        <input type="number" class="short" name="minimum_allowed_quantity_<?php echo $role_name; ?>" id="minimum_allowed_quantity_<?php echo $role_name; ?>" placeholder="" min="0" step="1" value="<?php echo get_post_meta($post->ID, '_min_quantity_' . $role_name, true); ?>">
                    </p>
                    <p class="form-field maximum_allowed_quantity_field">
                        <label for="maximum_allowed_quantity_<?php echo $role_name; ?>">Maximum quantity</label>
                        <input type="number" class="short" name="maximum_allowed_quantity_<?php echo $role_name; ?>" id="maximum_allowed_quantity_<?php echo $role_name; ?>" placeholder="" min="0" step="1" value="<?php echo get_post_meta($post->ID, '_max_quantity_' . $role_name, true); ?>">
                    </p>
                    <p class="form-field group_of_quantity_field">
                        <label for="group_of_quantity_<?php echo $role_name; ?>">Group of</label>
                        <input type="number" class="short" name="group_of_quantity_<?php echo $role_name; ?>" id="group_of_quantity_<?php echo $role_name; ?>" placeholder="" min="0" step="1" value="<?php echo get_post_meta($post->ID, '_group_of_' . $role_name, true); ?>">
                    </p>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <button class="button save" type="submit"><?php esc_html_e('Save', 'woocommerce'); ?></button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    var userRolesSelect = document.getElementById('user_roles');
    var roleFieldsContainers = document.querySelectorAll('.role-fields');

    // Hide role fields initially
    roleFieldsContainers.forEach(function(container) {
        container.style.display = 'none';
    });

    userRolesSelect.addEventListener('change', function() {
        var selectedRoles = this.selectedOptions;

        roleFieldsContainers.forEach(function(container) {
            container.style.display = 'none';
        });

        for (var i = 0; i < selectedRoles.length; i++) {
            var selectedRole = selectedRoles[i].value;
            var roleFieldsContainer = document.querySelector('.role-fields.' + selectedRole);
            if (roleFieldsContainer) {
                roleFieldsContainer.style.display = 'block';
            }
        }
    });
});
    </script>
    <?php
}

 
// Save simple product user role fields data
add_action('woocommerce_process_product_meta_simple', 'save_simple_product_user_roles_data');
function save_simple_product_user_roles_data($product_id) {
    if (isset($_POST['user_roles'])) {
        $user_roles = $_POST['user_roles'];
        foreach ($user_roles as $role_name) {
            update_post_meta($product_id, '_min_quantity_' . $role_name, sanitize_text_field($_POST['minimum_allowed_quantity_' . $role_name]));
            update_post_meta($product_id, '_max_quantity_' . $role_name, sanitize_text_field($_POST['maximum_allowed_quantity_' . $role_name]));
            update_post_meta($product_id, '_group_of_' . $role_name, sanitize_text_field($_POST['group_of_quantity_' . $role_name]));
        }
    }
}
add_action('woocommerce_before_add_to_cart_button', 'custom_display_quantity_fields_on_single_product');
function custom_display_quantity_fields_on_single_product() {
    global $product;

    // Check if the product is simple
    if ($product->is_type('simple')) {
        // Get the current user
        $user = wp_get_current_user();
        $user_roles = $user->roles;

        // Initialize variables to store minimum, maximum, and group quantity
        $min_quantity = $max_quantity = $group_of_quantity = '';

        // Loop through user roles
        foreach ($user_roles as $role_name) {
            // Get quantity rules for the role
            $min_quantity = get_post_meta($product->get_id(), '_min_quantity_' . $role_name, true);
            $max_quantity = get_post_meta($product->get_id(), '_max_quantity_' . $role_name, true);
            $group_of_quantity = get_post_meta($product->get_id(), '_group_of_' . $role_name, true);

            // If any of the quantities is found, break the loop
            if ($min_quantity || $max_quantity || $group_of_quantity) {
                break;
            }
        }

        // Modify the add to cart button to enforce quantity rules
        ?>
        <script>
            jQuery(document).ready(function($) {
                // Find the quantity input field
                var quantityInput = $('input[name="quantity"]');
                
                // Set minimum quantity
                quantityInput.attr('min', <?php echo intval($min_quantity); ?>);

                // Set maximum quantity
                quantityInput.attr('max', <?php echo intval($max_quantity) ? intval($max_quantity) : '""'; ?>);

                // Set step to group of quantity
                quantityInput.attr('step', <?php echo intval($group_of_quantity); ?>);
            });
        </script>
        <?php
    }
}

//hides previous quantity inputs
add_action('woocommerce_product_options_general_product_data', 'hide_quantity_rules_fields');
function hide_quantity_rules_fields() {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select the elements to hide
            var minimumQuantityField = document.querySelector('.minimum_allowed_quantity_field');
            var maximumQuantityField = document.querySelector('.maximum_allowed_quantity_field');
            var groupOfQuantityField = document.querySelector('.group_of_quantity_field');
            var allowCombinationField = document.querySelector('.allow_combination_field');

            // Hide the elements
            if (minimumQuantityField) {
                minimumQuantityField.style.display = 'none';
            }
            if (maximumQuantityField) {
                maximumQuantityField.style.display = 'none';
            }
            if (groupOfQuantityField) {
                groupOfQuantityField.style.display = 'none';
            }
            if (allowCombinationField) {
                allowCombinationField.style.display = 'none';
            }
        });
    </script>
    <?php
}


//////////// VARIABLE PRODUCTS ////////////

// Add JavaScript function to handle user role fields dynamically
add_action('admin_footer', 'add_custom_js_script');
function add_custom_js_script() {
    ?>
    <script>
function showUserRoleFields(select, variationId) {
    var selectedRoles = Array.from(select.selectedOptions).map(option => option.value);
    var fieldsContainer = document.getElementById('user_role_fields_' + variationId);
    if (!fieldsContainer) {
        fieldsContainer = document.createElement('div');
        fieldsContainer.id = 'user_role_fields_' + variationId;
        fieldsContainer.classList.add('user-role-fields-container');
        select.parentNode.parentNode.appendChild(fieldsContainer);
    }
    fieldsContainer.innerHTML = '';

    var allowedRoles = selectedRoles; // Display fields for all selected roles

    if (allowedRoles.length > 0) {
        fieldsContainer.style.display = 'block';

        allowedRoles.forEach(function(role_name) {
            var fieldName = role_name + '_fields_' + variationId;
            var fieldElement = document.createElement('div');
            fieldElement.id = fieldName;
            fieldElement.classList.add('user-role-fields');
            fieldsContainer.appendChild(fieldElement);
            fieldElement.innerHTML = `
                <h4>${role_name}</h4>
                <div class="user-role-field">
                    <label for="${fieldName}_min_qty">Minimum Quantity</label>
                    <input type="number" id="${fieldName}_min_qty" name="user_role_fields[${variationId}][${role_name}][min_qty]" class="input-text" value="" step="1" min="0">
                </div>
                <div class="user-role-field">
                    <label for="${fieldName}_max_qty">Maximum Quantity</label>
                    <input type="number" id="${fieldName}_max_qty" name="user_role_fields[${variationId}][${role_name}][max_qty]" class="input-text" value="" step="1" min="0">
                </div>
                <div class="user-role-field">
                    <label for="${fieldName}_group_of">Group of</label>
                    <input type="number" id="${fieldName}_group_of" name="user_role_fields[${variationId}][${role_name}][group_of]" class="input-text" value="" step="1" min="0">
                </div>
            `;
        });
    } else {
        fieldsContainer.style.display = 'none';
    }
}
    </script>
    <?php
}
// Add user roles select box for variations
add_action('woocommerce_variation_options_pricing', 'generate_variation_user_roles_select_box', 10, 3);
function generate_variation_user_roles_select_box($loop, $variation_data, $variation) {
    $all_roles = get_editable_roles();

    if (!empty($all_roles)) {
        ?>
        <div class="options_group">
            <p class="form-field">
                <label for="user_roles_<?php echo $variation->ID; ?>">User Roles</label>
                <select multiple id="user_roles_<?php echo $variation->ID; ?>" name="user_roles[<?php echo $variation->ID; ?>][]" class="user-roles-select" onchange="showUserRoleFields(this, <?php echo $variation->ID; ?>)">
                    <?php foreach ($all_roles as $role_name => $role_info) {
                        if (isset($role_info['name']) && is_string($role_info['name'])) {
                            ?>
                            <option value="<?php echo $role_name; ?>"><?php echo $role_info['name']; ?></option>
                            <?php
                        }
                    } ?>
                </select>
            </p>
            <div id="saved_role_values">
                <?php foreach ($all_roles as $role_name => $role_info) {
                    // Define the roles for which the fields should be displayed
                    $allowedRoles = ['Administrator', 'Author', 'Subscriber', 'Customer'];

                    // Check if the current role is in the allowedRoles array
                    if (in_array($role_name, $allowedRoles)) {
                        $min_quantity = get_post_meta($variation->ID, '_min_quantity_' . $role_name, true);
                        $max_quantity = get_post_meta($variation->ID, '_max_quantity_' . $role_name, true);
                        $group_of_quantity = get_post_meta($variation->ID, '_group_of_' . $role_name, true);
                        ?>
                        <div id="<?php echo $role_name . '_fields'; ?>">
                            <h4><?php echo $role_info['name']; ?></h4>
                            <div class="user-role-field">
                                <label for="<?php echo $role_name . '_min_qty'; ?>">Minimum Quantity</label>
                                <input type="number" id="<?php echo $role_name . '_min_qty'; ?>" name="user_role_fields[<?php echo $variation->ID; ?>][<?php echo $role_name; ?>][min_qty]" class="input-text" value="<?php echo $min_quantity; ?>" step="1" min="0">
                            </div>
                            <div class="user-role-field">
                                <label for="<?php echo $role_name . '_max_qty'; ?>">Maximum Quantity</label>
                                <input type="number" id="<?php echo $role_name . '_max_qty'; ?>" name="user_role_fields[<?php echo $variation->ID; ?>][<?php echo $role_name; ?>][max_qty]" class="input-text" value="<?php echo $max_quantity; ?>" step="1" min="0">
                            </div>
                            <div class="user-role-field">
                                <label for="<?php echo $role_name . '_group_of'; ?>">Group of</label>
                                <input type="number" id="<?php echo $role_name . '_group_of'; ?>" name="user_role_fields[<?php echo $variation->ID; ?>][<?php echo $role_name; ?>][group_of]" class="input-text" value="<?php echo $group_of_quantity; ?>" step="1" min="0">
                            </div>
                        </div>
                        <?php
                    }
                } ?>
            </div>
        </div>
        <?php
    } else {
        echo 'No user roles found.';
    }
}


// Save variation user role fields data
add_action('woocommerce_save_product_variation', 'save_variation_user_roles_data', 20, 2);
function save_variation_user_roles_data($variation_id, $i) {
    if (isset($_POST['user_roles'][$variation_id])) {
        $user_roles = $_POST['user_roles'][$variation_id];
        update_post_meta($variation_id, '_user_roles', $user_roles);
        
        foreach ($user_roles as $role_name) {
            if (isset($_POST['user_role_fields'][$variation_id][$role_name])) {
                $fields = $_POST['user_role_fields'][$variation_id][$role_name];
                update_post_meta($variation_id, '_min_quantity_' . $role_name, $fields['min_qty']);
                update_post_meta($variation_id, '_max_quantity_' . $role_name, $fields['max_qty']);
                update_post_meta($variation_id, '_group_of_' . $role_name, $fields['group_of']);
            }
        }
        update_post_meta($variation_id, 'min_max_rules', 'yes'); // This updates min_max_rules meta value to 'yes'
    }
}

add_filter('woocommerce_available_variation', 'enforce_quantity_rules', 10, 3);
function enforce_quantity_rules($data, $product, $variation) {
    if ($product->is_sold_individually()) {
        return $data;
    }

    $variation_id = $variation->get_id();
    $min_max_rules = get_post_meta($variation_id, 'min_max_rules', true);

    if ($min_max_rules !== 'yes') {
        return $data;
    }

    // Get current user roles
    $user = wp_get_current_user();
    $user_roles = $user->roles;

    if (!empty($user_roles)) {
        foreach ($user_roles as $role_name) {
        
            // Get quantity rules for the role
            $min_quantity = get_post_meta($variation_id, '_min_quantity_' . $role_name, true);
            $max_quantity = get_post_meta($variation_id, '_max_quantity_' . $role_name, true);
            $group_of_quantity = get_post_meta($variation_id, '_group_of_' . $role_name, true);
            
            // Apply quantity rules to the variation data
            $data['min_qty'] = intval($min_quantity);
            $data['max_qty'] = intval($max_quantity) ? intval($max_quantity) : ''; // Set to infinite if no maximum quantity is set
            $data['step'] = intval($group_of_quantity); 
        }
    }
    return $data;
}
