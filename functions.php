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

 add_action('woocommerce_product_options_general_product_data', 'add_custom_meta_box', 5);
 function add_custom_meta_box() {
     global $post;
     $post_id = $post->ID; // Get the post ID
     $user_roles = get_post_meta($post_id, '_user_roles', true);
     global $wp_roles;
     $all_roles = $wp_roles->roles;
     
     // Generate HTML for user roles select box
     generate_user_roles_select_box($post_id, $user_roles, $all_roles);
     
     
     // Generate HTML for user role input fields
     generate_user_role_input_fields($post_id, $all_roles);
     
     // Add JavaScript to handle the opening of the boxes
     add_user_role_script();
 
     // Add a save button
     echo '<button type="submit" class="button button-primary button-large">Save</button>';
 }

 function generate_user_roles_select_box($post_id, $user_roles, $all_roles) {
    if (!empty($user_roles) && is_array($user_roles)) {
        echo '<div class="options_group">';
        echo '<p class="form-field">';
        echo '<label for="user_roles">User Roles</label>';
        echo '<select multiple id="user_roles" name="user_roles[' . $post_id . '][]" class="user-roles-select">';
        foreach($all_roles as $role_name => $role_info){
            if (isset($role_info['name']) && is_string($role_info['name'])) {
                $selected = '';
                if(in_array($role_name, $user_roles)) {
                    $selected = ' selected="selected"';
                }
                echo '<option value="' . $role_name . '"' . $selected . '>' . $role_info['name'] . '</option>';
            }
        }
        echo '</select>';
        echo '</p>';
        echo '</div>'; // end of options_group
    } else {
        echo 'No user roles found.';
    }
}
function add_user_role_script() {
    // Enqueue the external JavaScript file
    wp_enqueue_script('my_custom_script', get_template_directory_uri() . '/js/my_custom_script.js', array('jquery'), '1.0', true);

    // Pass PHP variables to the script
    wp_localize_script('my_custom_script', 'script_data', get_script_data());
}

function get_script_data() {
    global $product, $wp_roles;
    $script_data = array();

    if ($product && $product->is_type('variable')) {
        $variations = $product->get_available_variations();

        foreach ($variations as $variation) {
            $variation_id = $variation['variation_id'];
            $user_roles = get_post_meta($variation_id, '_user_roles', true);

            if (is_array($user_roles)) {
                foreach ($user_roles as $role_name) {
                    $script_data[$variation_id][$role_name]['min_quantity'] = get_post_meta($variation_id, '_min_quantity_' . $role_name, true);
                    $script_data[$variation_id][$role_name]['max_quantity'] = get_post_meta($variation_id, '_max_quantity_' . $role_name, true);
                    $script_data[$variation_id][$role_name]['group_of'] = get_post_meta($variation_id, '_group_of_' . $role_name, true);
                }
            }
        }
    }

    return $script_data;
}


function generate_user_role_input_fields($post_id, $all_roles) {
    global $post;
    
    foreach ($all_roles as $role_name => $role_info) {
        // Get saved values
        $min_quantity = get_post_meta($post_id, '_min_quantity_' . $role_name, true);
        $max_quantity = get_post_meta($post_id, '_max_quantity_' . $role_name, true);
        $group_of = get_post_meta($post_id, '_group_of_' . $role_name, true);

        // Ensure $min_quantity, $max_quantity, and $group_of are strings
        $min_quantity = is_string($min_quantity) ? $min_quantity : '';
        $max_quantity = is_string($max_quantity) ? $max_quantity : '';
        $group_of = is_string($group_of) ? $group_of : '';

        // Title with inline style to hide initially
        echo '<h3 id="title-' . $role_name . '" style="display: none;">' . $role_info['name'] . '</h3>';
        
        // Minimum Quantity
        echo '<div id="user-role-box-' . $role_name . '" class="user-role-box" style="display: none;">';
        echo '<p class="form-field">';
        echo '<label for="min-quantity-' . $role_name . '">Minimum quantity</label>';
        echo '<input type="number" class="short" style="" name="min-quantity-' . $role_name . '" id="min-quantity-' . $role_name . '" value="' . $min_quantity . '" placeholder="" min="0" step="1">';
        echo '</p>';
        
        // Maximum Quantity
        echo '<p class="form-field">';
        echo '<label for="max-quantity-' . $role_name . '">Maximum quantity</label>';
        echo '<input type="number" class="short" style="" name="max-quantity-' . $role_name . '" id="max-quantity-' . $role_name . '" value="' . $max_quantity . '" placeholder="" min="0" step="1">';
        echo '</p>';
        
        // Group Of
        echo '<p class="form-field">';
        echo '<label for="group-of-' . $role_name . '">Group of</label>';
        echo '<input type="number" class="short" style="" name="group-of-' . $role_name . '" id="group-of-' . $role_name . '" value="' . $group_of . '" placeholder="" min="0" step="1">';
        echo '</p>';
        echo '</div>'; // end of user-role-box
    }
}


add_action('save_post', 'save_user_roles_data');
function save_user_roles_data($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
        return;
    if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id))
            return;
    } else {
        if (!current_user_can('edit_post', $post_id))
            return;
    }
    
    global $wp_roles;
    $all_roles = $wp_roles->roles;

    if (isset($_POST['user_roles']) && is_array($_POST['user_roles'])) {
        $user_roles = $_POST['user_roles'];
        update_post_meta($post_id, '_user_roles', $user_roles);
    }

    foreach($all_roles as $role_name => $role_info) {
        if(isset($_POST['min-quantity-' . $role_name])) {
            update_post_meta($post_id, '_min_quantity_' . $role_name, $_POST['min-quantity-' . $role_name]);
        }
        if(isset($_POST['max-quantity-' . $role_name])) {
            update_post_meta($post_id, '_max_quantity_' . $role_name, $_POST['max-quantity-' . $role_name]);
        }
        if(isset($_POST['group-of-' . $role_name])) {
            update_post_meta($post_id, '_group_of_' . $role_name, $_POST['group-of-' . $role_name]);
        }
    }
}
/*
add_action('woocommerce_after_add_to_cart_quantity', 'enforce_user_role_based_quantities');
function enforce_user_role_based_quantities() {
    global $post;
    global $wp_roles;
    $all_roles = $wp_roles->roles;

    $current_user = wp_get_current_user();
    $user_role = array_shift($current_user->roles);

    if (array_key_exists($user_role, $all_roles)) {
        $min_quantity = get_post_meta($post->ID, '_min_quantity_' . $user_role, true);
        $max_quantity = get_post_meta($post->ID, '_max_quantity_' . $user_role, true);
        $group_of = get_post_meta($post->ID, '_group_of_' . $user_role, true);

        // Prepare the data to pass to the script
        $script_data = array(
            'min_quantity' => $min_quantity,
            'max_quantity' => $max_quantity,
            'group_of' => $group_of
        );

        // Enqueue the script
        wp_enqueue_script('my_custom_script', get_template_directory_uri() . '/js/my_custom_script.js', array('jquery'), '1.0', true);

        // Pass PHP variables to the script
        wp_localize_script('my_custom_script', 'script_data', $script_data);
    }
}
*/


add_action('admin_head', 'hide_quantity_rules');
function hide_quantity_rules() {
    echo '
    <style type="text/css">
        .hr-section.hr-section-components,
        .form-field.minimum_allowed_quantity_field,
        .form-field.maximum_allowed_quantity_field,
        .form-field.group_of_quantity_field {
            display: none !important;
        }
    </style>
    ';
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

        // Hide all fields initially
        fieldsContainer.style.display = 'none';

        if (selectedRoles.length > 0) {
            // Show fields only if user roles are selected
            fieldsContainer.style.display = 'block';
            
            selectedRoles.forEach(function(role_name) {
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
            // Debugging output to verify user roles
            echo $role_name;

            // Get quantity rules for the role
            $min_quantity = get_post_meta($variation_id, '_min_quantity_' . $role_name, true);
            $max_quantity = get_post_meta($variation_id, '_max_quantity_' . $role_name, true);
            $group_of_quantity = get_post_meta($variation_id, '_group_of_' . $role_name, true);
            
            // Apply quantity rules to the variation data
            $data['min_qty'] = intval($min_quantity);
            $data['max_qty'] = intval($max_quantity);
            $data['step'] = intval($group_of_quantity); 
        }
    }
    return $data;
}
