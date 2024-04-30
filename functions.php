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

// Add the custom meta box for variable products
add_action('variable_product_options_inner', 'add_custom_meta_box_variation', 10, 3);
function add_custom_meta_box_variation($loop, $variation_data, $variation) {
    // Retrieve variation ID
    $variation_id = $variation->ID;

    // Display variation ID
    echo '<p>Variation ID: ' . $variation_id . '</p>';

    // Get all user roles
    $all_roles = get_editable_roles();

    // Generate HTML for user roles select box
    generate_variation_user_roles_select_box($variation_id, $all_roles); // Call the function here

    // Add a save button
    echo '<button type="submit" class="button button-primary button-large">Save</button>';

    // Generate HTML for input fields
    generate_variation_input_fields($variation_id, $all_roles);
}

// Generate HTML for user roles select box
function generate_variation_user_roles_select_box($variation_id, $all_roles) {
    if (!empty($all_roles) && is_array($all_roles)) {
        echo '<div class="options_group">';
        echo '<p class="form-field">';
        echo '<label for="user_roles_' . $variation_id . '">User Roles</label>'; 
        echo '<select id="user_roles_' . $variation_id . '" name="user_roles[' . $variation_id . '][]" class="user-roles-select" onchange="showUserRoleBox(this.value, ' . $variation_id . ')">';
        foreach($all_roles as $role_name => $role_info){
            if (isset($role_info['name']) && is_string($role_info['name'])) {
                echo '<option value="' . $role_name . '">' . $role_info['name'] . '</option>';
            }
        }
        echo '</select>';
        echo '</p>';
        echo '</div>'; 
    } else {
        echo 'No user roles found.';
    }
}

// Generate HTML for input fields
function generate_variation_input_fields($variation_id, $all_roles) {
    foreach ($all_roles as $role_name => $role_info) {
        // Title with inline style to hide initially
        echo '<div id="user-role-box-' . $variation_id . '-' . $role_name . '" class="user-role-box" style="display: none;">';
        echo '<h3 id="title-' . $role_name . '">' . $role_info['name'] . '</h3>';

        // Retrieve group-of value for the current variation and role
        $group_of_value = get_post_meta($variation_id, '_group_of_' . $role_name, true);

        // Minimum Quantity
        echo '<p class="form-field">';
        echo '<label for="min-quantity-' . $variation_id . '-' . $role_name . '">Minimum quantity</label>';
        echo '<input type="number" class="short qty" name="min-quantity[' . $variation_id . '][' . $role_name . ']" id="min-quantity-' . $variation_id . '-' . $role_name . '" value="" placeholder="" min="0" step="1" group-of="' . $group_of_value . '">';
        echo '</p>';

        // Maximum Quantity
        echo '<p class="form-field">';
        echo '<label for="max-quantity-' . $variation_id . '-' . $role_name . '">Maximum quantity</label>';
        echo '<input type="number" class="short qty" name="max-quantity[' . $variation_id . '][' . $role_name . ']" id="max-quantity-' . $variation_id . '-' . $role_name . '" value="" placeholder="" min="0" step="1" group-of="' . $group_of_value . '">';
        echo '</p>';

        // Group Of
        echo '<p class="form-field">';
        echo '<label for="group-of-' . $variation_id . '-' . $role_name . '">Group of</label>';
        echo '<input type="number" class="short qty" name="group-of[' . $variation_id . '][' . $role_name . ']" id="group-of-' . $variation_id . '-' . $role_name . '" value="' . $group_of_value . '" placeholder="" min="0" step="1">';
        echo '</p>';
        echo '</div>'; 
    }
}

// Save variable product user roles data
add_action('woocommerce_save_product_variation', 'save_variable_product_user_roles_data', 10, 2);
function save_variable_product_user_roles_data($variation_id) {
    if (isset($_POST['user_roles'][$variation_id]) && is_array($_POST['user_roles'][$variation_id])) {
        $user_roles = $_POST['user_roles'][$variation_id];
        update_post_meta($variation_id, '_user_roles', $user_roles);

        foreach($user_roles as $role_name) {
            if(isset($_POST['min-quantity'][$variation_id][$role_name])) {
                $min_quantity = $_POST['min-quantity'][$variation_id][$role_name];
                update_post_meta($variation_id, '_min_quantity_' . $role_name, $min_quantity);
            }
            if(isset($_POST['max-quantity'][$variation_id][$role_name])) {
                $max_quantity = $_POST['max-quantity'][$variation_id][$role_name];
                update_post_meta($variation_id, '_max_quantity_' . $role_name, $max_quantity);
            }
            if(isset($_POST['group-of'][$variation_id][$role_name])) {
                $group_of = $_POST['group-of'][$variation_id][$role_name];
                update_post_meta($variation_id, '_group_of_' . $role_name, $group_of);
            }
        }
    }
}

// Filter available variation data
add_filter('woocommerce_available_variation', 'available_variation', 10, 3);
function available_variation($data, $product, $variation) {
    // Get the user roles for this variation
    $user_roles = get_post_meta($variation->get_id(), '_user_roles', true);

    // Get the current user
    $current_user = wp_get_current_user();

    // Check if the current user has any of the roles for this variation
    foreach ($current_user->roles as $role) {
        if (in_array($role, $user_roles)) {
            // Get the min-max rules for this role
            $variation_data = get_variation_data($variation->get_id());

            // Iterate over the result set
            foreach ($variation_data as $variation_row) {
                // Get min-max rules for each variation
                $min_quantity = $variation_row['min_quantity'];
                $max_quantity = $variation_row['max_quantity'];
                $group_of_quantity = $variation_row['group_of'];

                // Apply the min-max rules
                if ($min_quantity) {
                    $data['min_qty'] = $min_quantity;
                }
                if ($max_quantity) {
                    $data['max_qty'] = $max_quantity;
                }
                if ($group_of_quantity) {
                    $data['step'] = $group_of_quantity;
                }

                // Enforce quantity rules
                $quantity = isset($_REQUEST['quantity']) ? intval($_REQUEST['quantity']) : 1;
                if ($min_quantity && $quantity < $min_quantity) {
                    $data['min_qty'] = $min_quantity; // Adjust minimum quantity if necessary
                    $quantity = $min_quantity;
                }
                if ($max_quantity && $quantity > $max_quantity) {
                    $data['max_qty'] = $max_quantity; // Adjust maximum quantity if necessary
                    $quantity = $max_quantity;
                }
                if ($group_of_quantity && $quantity % $group_of_quantity !== 0) {
                    // Adjust quantity to be a multiple of the group size
                    $quantity = ceil($quantity / $group_of_quantity) * $group_of_quantity;
                }

                // Update quantity in the response
                $data['quantity'] = $quantity;
            }
        }
    }
    return $data;
}

// Retrieve variation data
function get_variation_data($id) {
    global $wpdb;

    $query = "
        SELECT 
            DISTINCT(p.ID) AS product_id,
            v.ID AS variation_id,
            p.post_title AS product_title,
            v.post_title AS variation_title,
            pm_min.meta_value AS min_quantity,
            pm_max.meta_value AS max_quantity,
            pm_group.meta_value AS group_of,
            GROUP_CONCAT(DISTINCT pm_user_roles.meta_value) AS user_roles
        FROM 
            {$wpdb->posts} AS p
        INNER JOIN 
            {$wpdb->posts} AS v ON p.ID = v.post_parent AND v.post_type = 'product_variation'
        LEFT JOIN 
            {$wpdb->postmeta} AS pm_min ON v.ID = pm_min.post_id AND pm_min.meta_key LIKE '_min_quantity_%'
        LEFT JOIN 
            {$wpdb->postmeta} AS pm_max ON v.ID = pm_max.post_id AND pm_max.meta_key LIKE '_max_quantity_%'
        LEFT JOIN 
            {$wpdb->postmeta} AS pm_group ON v.ID = pm_group.post_id AND pm_group.meta_key LIKE '_group_of_%'
        LEFT JOIN 
            {$wpdb->postmeta} AS pm_user_roles ON v.ID = pm_user_roles.post_id AND pm_user_roles.meta_key = '_user_roles'
        LEFT JOIN 
            {$wpdb->usermeta} AS um ON um.user_id = pm_user_roles.meta_value AND um.meta_key = 'wp_capabilities'
        WHERE 
            p.post_type = 'product'
        AND p.ID = %d
        GROUP BY 
            p.ID, v.ID;
    ";

    $results = $wpdb->get_results($wpdb->prepare($query, $id), ARRAY_A);

    return $results;
}
