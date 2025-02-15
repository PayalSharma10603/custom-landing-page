<?php
/**
 * Plugin Name: Custom Landing Page
 * Description: A plugin to create a custom landing page with dynamic sections.
 * Version: 1.1
 * Author: Payal Sharma
 */

// Hook to initialize plugin settings
add_action('admin_menu', 'clp_landing_page_menu');
add_action('admin_init', 'clp_landing_page_settings');
add_action('admin_enqueue_scripts', 'clp_enqueue_media');
add_action('admin_post_clp_remove_section', 'clp_remove_section'); // Hook for the remove action

// Function to create menu item in the WordPress admin dashboard
function clp_landing_page_menu() {
    add_menu_page(
        'Landing Page Images',      // Page Title
        'Landing Page Images',      // Menu Title
        'manage_options',             // Capability
        'clp_landing_page_settings',  // Menu Slug
        'clp_landing_page_settings_page', // Function to display settings page
        'dashicons-admin-home'        // Icon
    );
}

// Function to initialize plugin settings
function clp_landing_page_settings() {
    register_setting('clp_options_group', 'clp_section_count');
    
    // Register each section's image and link dynamically based on section count
    $section_count = get_option('clp_section_count', 4);
    for ($i = 1; $i <= $section_count; $i++) {
        register_setting('clp_options_group', 'clp_section_image_' . $i);
        register_setting('clp_options_group', 'clp_section_link_' . $i);
        register_setting('clp_options_group', 'clp_section_title_' . $i);
    }
}

// Enqueue necessary media scripts
function clp_enqueue_media() {
    wp_enqueue_media();  // This loads the WordPress media uploader
}

// Enqueue plugin styles
function clp_enqueue_styles() {
    // Get the plugin directory URL
    $plugin_url = plugin_dir_url(__FILE__);

    // Enqueue the stylesheet from the assets folder
    wp_enqueue_style('clp-styles', $plugin_url . 'assets/style.css');
}
add_action('wp_enqueue_scripts', 'clp_enqueue_styles');

// Function to handle section removal
function clp_remove_section() {
    if (isset($_GET['section_id'])) {
        $section_id = intval($_GET['section_id']);
        
        // Delete image, link, and title for the given section ID
        delete_option('clp_section_image_' . $section_id);
        delete_option('clp_section_link_' . $section_id);
        delete_option('clp_section_title_' . $section_id);

        // Get the current section count
        $section_count = get_option('clp_section_count', 4);
        
        // Update the section count if necessary (ensure it's always above 1)
        if ($section_count > 0) {
            update_option('clp_section_count', $section_count - 1);
        }

        // Adjust the remaining sections by removing their values
        for ($i = $section_id + 1; $i <= $section_count; $i++) {
            // Shift each section's option to the previous index
            update_option('clp_section_image_' . ($i - 1), get_option('clp_section_image_' . $i));
            update_option('clp_section_link_' . ($i - 1), get_option('clp_section_link_' . $i));
            update_option('clp_section_title_' . ($i - 1), get_option('clp_section_title_' . $i));

            // Delete the options for the last section
            delete_option('clp_section_image_' . $i);
            delete_option('clp_section_link_' . $i);
            delete_option('clp_section_title_' . $i);
        }
    }

    // Redirect back to the settings page
    wp_redirect(admin_url('admin.php?page=clp_landing_page_settings'));
    exit;
}

// Function to display the plugin settings page
function clp_landing_page_settings_page() {
    ?>
    <div class="wrap">
        <h1>Manage Landing Page Sections</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('clp_options_group');
                do_settings_sections('clp_options_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Number of Sections</th>
                    <td>
                        <input type="number" name="clp_section_count" value="<?php echo esc_attr(get_option('clp_section_count')); ?>" />
                    </td>
                </tr>

                <!-- Loop to display image, link, and title fields for each section -->
                <?php 
                $section_count = get_option('clp_section_count', 4);
                for ($i = 1; $i <= $section_count; $i++) : ?>
                <tr valign="top">
                    <th scope="row">Section <?php echo $i; ?> Title</th>
                    <td>
                        <input type="text" name="clp_section_title_<?php echo $i; ?>" value="<?php echo esc_attr(get_option('clp_section_title_' . $i)); ?>" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Section <?php echo $i; ?> Image</th>
                    <td>
                        <input type="text" name="clp_section_image_<?php echo $i; ?>" value="<?php echo esc_attr(get_option('clp_section_image_' . $i)); ?>" id="clp_section_image_<?php echo $i; ?>" />
                        <button class="upload_image_button" id="upload_image_button_<?php echo $i; ?>" data-input-id="clp_section_image_<?php echo $i; ?>">Upload Image</button>
                        <div id="image_preview_<?php echo $i; ?>" style="margin-top: 10px;">
                            <?php 
                                $image_url = get_option('clp_section_image_' . $i);
                                if ($image_url) {
                                    echo '<img src="' . esc_url($image_url) . '" style="max-width: 100px; height: auto;" />';
                                }
                            ?>
                        </div>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Section <?php echo $i; ?> Link</th>
                    <td>
                        <input type="url" name="clp_section_link_<?php echo $i; ?>" value="<?php echo esc_attr(get_option('clp_section_link_' . $i)); ?>" placeholder="Enter link for Section <?php echo $i; ?>" />
                    </td>
                </tr>

                <!-- Add Remove button -->
                <tr>
                    <td colspan="2">
                        <a href="<?php echo esc_url(admin_url('admin-post.php?action=clp_remove_section&section_id=' . $i)); ?>" class="button button-secondary">Remove Section <?php echo $i; ?></a>
                    </td>
                </tr>
                <?php endfor; ?>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($){
            // Open media uploader when the upload button is clicked
            $('.upload_image_button').click(function(e) {
                e.preventDefault();
                var inputId = $(this).data('input-id');
                var fileFrame = wp.media({
                    title: 'Select an Image',
                    button: { text: 'Use this image' },
                    multiple: false
                });

                fileFrame.on('select', function() {
                    var attachment = fileFrame.state().get('selection').first().toJSON();
                    $('#' + inputId).val(attachment.url);  // Set the image URL in the input field
                    $('#image_preview_' + inputId.split('_').pop()).html('<img src="' + attachment.url + '" style="max-width: 100px; height: auto;" />');
                });

                fileFrame.open();
            });
        });
    </script>
    <?php
}

// Function to display the landing page with dynamic sections
// function clp_display_landing_page() {
//     $section_count = get_option('clp_section_count', 4); // Get the number of sections
//     if ($section_count <= 0) {
//         return; // No sections to display
//     }

//     $columns = 4; // Default for even numbers like 4, 8, 12, etc.

//     // Logic to determine column count dynamically
//     if ($section_count % 4 == 0) {
//         $columns = 4; // 4 in a row for multiples of 4
//     } elseif ($section_count % 3 == 0) {
//         $columns = 3; // 3 in a row for multiples of 3
//     } elseif ($section_count % 5 == 0 || $section_count % 2 == 0) {
//         $columns = 2; // Adjust for 5, 2
//     } else {
//         $columns = 3; // Default to 3 for any other case
//     }

//     // Start output
//     echo '<div class="landing-page" data-columns="' . esc_attr($columns) . '">';
    
//     // Loop through sections
//     for ($i = 1; $i <= $section_count; $i++) {
//         $image_url = get_option('clp_section_image_' . $i);
//         $link_url = get_option('clp_section_link_' . $i);
//         $section_title = get_option('clp_section_title_' . $i);

//         // Skip section if all options are empty
//         if (empty($image_url) && empty($link_url) && empty($section_title)) {
//             continue;
//         }
        
//         // Display section
//         echo '<div class="section">';
//         if (!empty($section_title)) {
//             echo '<span class="section-title image-top-title">' . esc_html($section_title) . '</span>';
//         }
//         if (!empty($link_url) && !empty($image_url)) {
//             echo '<a href="' . esc_url($link_url) . '" class="section-link">';
//             echo '<div class="image-container" style="background-image: url(\'' . esc_url($image_url) . '\');"></div>';
//             echo '</a>';
//         } else if (!empty($image_url)) {
//             echo '<div class="image-container" style="background-image: url(\'' . esc_url($image_url) . '\');"></div>';
//         }
//         echo '</div>';
//     }
    
//     echo '</div>'; // End of landing-page
// }

function clp_display_landing_page() {
    $section_count = get_option('clp_section_count', 4); // Get the number of sections
    if ($section_count <= 0) {
        return; // No sections to display
    }

    $columns = 4; // Default for even numbers like 4, 8, 12, etc.

    // Logic to determine column count dynamically
    if ($section_count % 4 == 0) {
        $columns = 4; // 4 in a row for multiples of 4
    } elseif ($section_count % 3 == 0) {
        $columns = 3; // 3 in a row for multiples of 3
    } elseif ($section_count == 5) {
        $columns = 3; // 3 in the first row, 2 in the second row for 5 images
    } elseif ($section_count == 7) {
        $columns = 4; // 4 in the first row, 3 in the second row for 7 images
    } elseif ($section_count % 5 == 0 || $section_count % 2 == 0) {
        $columns = 2; // Adjust for 5, 2
    } else {
        $columns = 3; // Default to 3 for any other case
    }

    // Start output
    echo '<div class="landing-page" data-columns="' . esc_attr($columns) . '">';
    
    // Loop through sections
    for ($i = 1; $i <= $section_count; $i++) {
        $image_url = get_option('clp_section_image_' . $i);
        $link_url = get_option('clp_section_link_' . $i);
        $section_title = get_option('clp_section_title_' . $i);

        // Skip section if all options are empty
        if (empty($image_url) && empty($link_url) && empty($section_title)) {
            continue;
        }
        
        // Display section
        echo '<div class="section">';
        if (!empty($section_title)) {
            echo '<span class="section-title image-top-title">' . esc_html($section_title) . '</span>';
        }
        if (!empty($link_url) && !empty($image_url)) {
            echo '<a href="' . esc_url($link_url) . '" class="section-link">';
            echo '<div class="image-container" style="background-image: url(\'' . esc_url($image_url) . '\');"></div>';
            echo '</a>';
        } else if (!empty($image_url)) {
            echo '<div class="image-container" style="background-image: url(\'' . esc_url($image_url) . '\');"></div>';
        }
        echo '</div>';
    }
    
    echo '</div>'; // End of landing-page
}


// Register shortcode to display landing page anywhere
function clp_landing_page_shortcode() {
    ob_start();
    clp_display_landing_page();
    return ob_get_clean();
}

add_shortcode('clp_landing_page', 'clp_landing_page_shortcode');

