<?php
/*
Plugin Name: Quantity Rules By User Role
Description: Allows setting custom quantity rules for WooCommerce products based on user roles. Requires WooCommerce Min Max Quantities Pro plugin.
Version: 1.0
Author: Rafael Magalhães
*/

add_action('admin_enqueue_scripts', 'enqueue_select2');
function enqueue_select2() {
   wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
   wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', array(), '4.0.13');
}


add_action('woocommerce_product_options_general_product_data', 'add_user_roles_select_box_for_simple_products');
function add_user_roles_select_box_for_simple_products() {
   global $post;
   $all_roles = get_editable_roles();
   ?>
   <div class="options_group">
       <p class="form-field">
           <label for="user_roles">User Roles</label>
           <select multiple id="user_roles" name="user_roles[]" class="user-roles-select" style="width: 100%;" data-placeholder="Select user roles">
               <?php
               foreach ($all_roles as $role_name => $role_info) {
                   $min_quantity = get_post_meta($post->ID, '_min_quantity_' . $role_name, true);
                   $selected = $min_quantity ? 'selected' : '';
                   if (isset($role_info['name']) && is_string($role_info['name'])) {
                       ?>
                       <option value="<?php echo $role_name; ?>" <?php echo $selected; ?>><?php echo $role_info['name']; ?></option>
                       <?php
                   }
               }
               ?>
           </select>
       </p>

       <div class="user-role-fields-container">
           <?php
           foreach ($all_roles as $role_name => $role_info) {
               $min_quantity = get_post_meta($post->ID, '_min_quantity_' . $role_name, true);
               ?>
               <div class="role-fields <?php echo $role_name; ?>" <?php if ($min_quantity) echo 'style="display: block;"'; ?>>
                   <h4><?php echo $role_info['name']; ?></h4>
                   <p class="form-field minimum_allowed_quantity_field">
                       <label for="minimum_allowed_quantity_<?php echo $role_name; ?>">Minimum quantity</label>
                       <input type="number" class="short" name="minimum_allowed_quantity_<?php echo $role_name; ?>" id="minimum_allowed_quantity_<?php echo $role_name; ?>" placeholder="" min="0" step="1" value="<?php echo $min_quantity; ?>">
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
   <script>
       jQuery(document).ready(function($) {
           $('#user_roles').select2();

           var userRolesSelect = $('#user_roles');
           var roleFieldsContainers = $('.role-fields');

           roleFieldsContainers.hide();

           roleFieldsContainers.each(function() {
               if ($(this).find('.minimum_allowed_quantity_field input').val() !== '') {
                   $(this).show();
               }
           });

           userRolesSelect.on('change', function() {
               var selectedRoles = $(this).val();

               roleFieldsContainers.hide();

               if (selectedRoles) {
                   selectedRoles.forEach(function(selectedRole) {
                       var roleFieldsContainer = $('.role-fields.' + selectedRole);
                       if (roleFieldsContainer) {
                           roleFieldsContainer.show();
                       }
                   });
               }
           });
       });
   </script>
   <?php
}


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

   if ($product->is_type('simple')) {
 
       $user = wp_get_current_user();
       $user_roles = $user->roles;

       $min_quantity = $max_quantity = $group_of_quantity = '';
       foreach ($user_roles as $role_name) {

           $min_quantity = get_post_meta($product->get_id(), '_min_quantity_' . $role_name, true);
           $max_quantity = get_post_meta($product->get_id(), '_max_quantity_' . $role_name, true);
           $group_of_quantity = get_post_meta($product->get_id(), '_group_of_' . $role_name, true);
           if ($min_quantity || $max_quantity || $group_of_quantity) {
               break;
           }
       }

       ?>
       <script>
           jQuery(document).ready(function($) {

               var quantityInput = $('input[name="quantity"]');
               
               quantityInput.attr('min', <?php echo intval($min_quantity); ?>);

               quantityInput.attr('max', <?php echo intval($max_quantity) ? intval($max_quantity) : '""'; ?>);

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


add_action('admin_footer', 'add_custom_js_script');
function add_custom_js_script() {
   ?>
   <script>
function showUserRoleFields(select, variationId) {
   var fieldsContainer = document.getElementById('user_role_fields_' + variationId);
   
   // Clear the container
   while (fieldsContainer.firstChild) {
       fieldsContainer.removeChild(fieldsContainer.firstChild);
   }

   Array.from(select.selectedOptions).forEach(function(option) {
       var selectedRole = option.value;
       if (selectedRole) {
           var minQty = option.getAttribute('data-min-quantity');
           var maxQty = option.getAttribute('data-max-quantity');
           var groupOf = option.getAttribute('data-group-of');

           var fieldName = selectedRole + '_fields_' + variationId;
           var fieldElement = document.createElement('div');
           fieldElement.id = fieldName;
           fieldElement.classList.add('user-role-fields');
           fieldsContainer.appendChild(fieldElement);
           fieldElement.innerHTML = `
               <h4>${selectedRole}</h4>
               <div class="user-role-field">
                   <label for="${fieldName}_min_qty">Minimum Quantity</label>
                   <input type="number" id="${fieldName}_min_qty" name="user_role_fields[${variationId}][${selectedRole}][min_qty]" class="input-text" value="${minQty}" step="1" min="0">
               </div>
               <div class="user-role-field">
                   <label for="${fieldName}_max_qty">Maximum Quantity</label>
                   <input type="number" id="${fieldName}_max_qty" name="user_role_fields[${variationId}][${selectedRole}][max_qty]" class="input-text" value="${maxQty}" step="1" min="0">
               </div>
               <div class="user-role-field">
                   <label for="${fieldName}_group_of">Group of</label>
                   <input type="number" id="${fieldName}_group_of" name="user_role_fields[${variationId}][${selectedRole}][group_of]" class="input-text" value="${groupOf}" step="1" min="0">
               </div>
           `;
       }
   });
   fieldsContainer.style.display = select.selectedOptions.length > 0 ? 'block' : 'none';
}
   </script>
   <?php
}


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
                           $min_quantity = get_post_meta($variation->ID, '_min_quantity_' . $role_name, true);
                           $max_quantity = get_post_meta($variation->ID, '_max_quantity_' . $role_name, true);
                           $group_of_quantity = get_post_meta($variation->ID, '_group_of_' . $role_name, true);
                           ?>
                           <option value="<?php echo $role_name; ?>" 
                               data-min-quantity="<?php echo $min_quantity; ?>"
                               data-max-quantity="<?php echo $max_quantity; ?>"
                               data-group-of="<?php echo $group_of_quantity; ?>">
                               <?php echo $role_info['name']; ?>
                           </option>
                           <?php
                       }
                   } ?>
               </select>
           </p>
           <div id="user_role_fields_<?php echo $variation->ID; ?>" class="user-role-fields-container"></div>
       </div>
       <script>
        jQuery(document).ready(function($) {
   var selectElement = $('#user_roles_<?php echo $variation->ID; ?>');
   selectElement.select2();

   selectElement.find('option').each(function() {
       var optionElement = $(this);
       var minQty = optionElement.data('min-quantity');

       if (minQty) {
           optionElement.prop('selected', true);
           selectElement.trigger('change');
       }
   });
});

       </script>
       <?php
   } else {
       echo 'No user roles found.';
   }
}  

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
       update_post_meta($variation_id, 'min_max_rules', 'yes');
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
   $user = wp_get_current_user();
   $user_roles = $user->roles;

   if (!empty($user_roles)) {
       foreach ($user_roles as $role_name) {
       
           $min_quantity = get_post_meta($variation_id, '_min_quantity_' . $role_name, true);
           $max_quantity = get_post_meta($variation_id, '_max_quantity_' . $role_name, true);
           $group_of_quantity = get_post_meta($variation_id, '_group_of_' . $role_name, true);
           
           $data['min_qty'] = intval($min_quantity);
           $data['max_qty'] = intval($max_quantity) ? intval($max_quantity) : '';
           $data['step'] = intval($group_of_quantity); 
       }
   }
   return $data;
}
