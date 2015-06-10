<?php
/**
 * @package Adnetwork Blocker List
 * @version 1.0
 */
/*
Plugin Name: Adnetwork Blocker List
Plugin URI: http://github.com/newsworthy39/wordpress-adnetwork-blocker-plugin
Description: Connects to a database, and outputs CDATA into a specific page.
Author: Newsworthy39
Version: 1.0
Author URI: http://mjay.me
*/

/*
 * Lets create our own post-type, used in here.
 */

function adblock_save_post_type($post_id) {

    // verify nonce
    if (!wp_verify_nonce($_POST['eventmeta_noncename'], plugin_basename(__FILE__))) {
        return $post_id;
    }
    // check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }
    // check permissions
    if ('page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return $post_id;
        }
    } elseif (!current_user_can('edit_post', $post_id)) {
        return $post_id;

    }

    $old = get_post_meta($post_id, "_adblocklist", true);
    $new = $_POST["datasetname"];

    if ($new && $new != $old) {
            update_post_meta($post_id, "_adblocklist", $new);
    } elseif ('' == $new && $old) {
            delete_post_meta($post_id, "_adblocklist", $old);
    }

}

//Registers the Product's post type
function adblock_create_post_type() {

$labels = array(
    'name' => 'adblocklist', #_x('Adblocklist', 'post type general name'),
    'singular_name' => 'adblocklist', #_x('Adblocklist', 'post type singular name'),
    'add_new' => _x('Add New', 'Adblocklist'),
    'add_new_item' => __('Add new Adblocklist'),
    'edit_item' => __('Edit Adblocklist'),
    'new_item' => __('New Adblocklist'),
    'view_item' => __('View Adblocklist'),
    'search_items' => __('Search Adblocklists'),
    'not_found' =>  __('No List found'),
    'not_found_in_trash' => __('No lists found in Trash'),
    'parent_item_colon' => ''
  );
    $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'query_var' => true,
    'rewrite' => true,
    'has_archive' => true,
    'capability_type' => 'post',
    'hierarchical' => false,
    'menu_position' => null,
    'taxonomies' => array( 'category' ),
    'menu_position'=>5,
    'supports' => array('title','editor', 'author' ),
    'rewrite' => array('slug' => 'adblocklist'),
    'register_meta_box_cb' => 'add_events_metaboxes',
    );

    register_post_type( "postadblock", $args);
    register_taxonomy_for_object_type('category', 'postadblock');

/*,
        array(
            'labels' => array(
                'name' => __( 'AdBlockList' ),
                'singular_name' => __( 'AdBlockList' )
            ),
        'public' => true,
        'has_archive' => true,
        'supports' => array( 'title', 'author', 'editor', 'shortlinks'  ),
 	'register_meta_box_cb' => 'add_events_metaboxes',
	'rewrite' => array('slug'=>'','with_front'=>false),
        )
    );
*/
}

function add_events_metaboxes() {
    add_meta_box('wpt_adblock_list', 'Adblocklist dataset', 'wpt_meta_adblock_list', 'postadblock', 'normal', 'high');
}

function wpt_meta_adblock_list () {
    global $wpdb;
    global $post;
    // Noncename needed to verify where the data originated
    echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .    wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

    // Get the location data if its already been entered
    $location = get_post_meta($post->ID, '_adblocklist', true);

    $db_host =  get_option('adblock_plugin_page_host','localhost') ;
    $db_db =  get_option('adblock_plugin_page_db','wordpress') ;
    $db_user =  get_option('adblock_plugin_page_user','wordpress') ;
    $db_passwd =  get_option('adblock_plugin_page_password','wordpress') ;

    $wpdb_local = new wpdb($db_user, $db_passwd, $db_db, $db_host);
    $wpdb_local->prefix = $wpdb->prefix;
    $table_name = $wpdb->prefix . 'adblocklist';
    $charset_collate = $wpdb->get_charset_collate();
    $possibilities   = $wpdb_local->get_results("select datasetname from $table_name", "ARRAY_N");

    echo '<select name="datasetname" id="datasetname">';
    foreach ($possibilities as $option) {
	echo '<option ', $location == $option[0] ? ' selected="selected"' : '', '>', $option[0], '</option>';
    }
    echo '</select>';
}

function adblock_plugin_menu() {
        add_options_page( 'Adblock plugin options', 'Adblock', 'manage_options', 'adblock_plugin_identifier_1', 'adblock_plugin_options');
        add_action('admin_init', 'register_adblock_settings');
}

function register_adblock_settings() {
        //register our settings
        register_setting( 'adblock_plugin_settings-group', 'adblock_plugin_page_host' );
        register_setting( 'adblock_plugin_settings-group', 'adblock_plugin_page_db' );
        register_setting( 'adblock_plugin_settings-group', 'adblock_plugin_page_user' );
        register_setting( 'adblock_plugin_settings-group', 'adblock_plugin_page_password' );
}

function adblock_plugin_options() {
?>
        <div class="wrap">
        <h2>Adblock plugin options</h2>
	<i>This lets you setup the database to connect to, to load adblock-list(s)</i>
        <form method="post" action="options.php">

        <?php settings_fields('adblock_plugin_settings-group'); ?>
        <?php do_settings_sections('adblock_plugin_settings-group'); ?>


        <table class="form-table">

        <tr valign="top">
        <th scope="row">Database-host</th>
        <td><input type="text" name="adblock_plugin_page_host" value="<?php echo esc_attr( get_option('adblock_plugin_page_host','localhost') ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Database</th>
        <td><input type="text" name="adblock_plugin_page_db" value="<?php echo esc_attr( get_option('adblock_plugin_page_db','wordpress') ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">User</th>
        <td><input type="text" name="adblock_plugin_page_user" value="<?php echo esc_attr( get_option('adblock_plugin_page_user','wordpress') ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Password</th>
        <td><input type="password" name="adblock_plugin_page_password" value="<?php echo esc_attr( get_option('adblock_plugin_page_password','blank') ); ?>" /></td>
        </tr>


        </table>

        <?php submit_button(); ?>
        </form>
        </div>
<?php
}

// Lets hook up a menu,
add_action ( 'admin_menu', 'adblock_plugin_menu' );

$adblock_list_db_version = '1.0';

/**
 * Installation procedures and upgrade checks, related to the plugin.
 * We are not going to remove user-data in the other table.
 */
function adblock_list_db_install() {

	// Check if we should upgrade tables, by asking if adblock_list_db_version is different than
	// get_option('adblock_db_version')..

	global $wpdb;
	global $adblock_list_db_version;

	$table_name = $wpdb->prefix . 'adblocklist';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                datasetname char(32) NOT NULL,
                PRIMARY KEY (id)
        ) $charset_collate; ";

	// Create plugin table (if not exists)
	$wpdb->query($sql);


	$sql_insert = "INSERT INTO `$table_name` (`datasetname`) VALUES ('Default');";

	// Create data
	$wpdb->query($sql_insert);

	// Add the table option
	add_option('adblock_db_version', $adblock_list_db_version);
} 

/**
 * install data 
 */
function adblock_list_db_data_install() {
	global $wpdb;
	global $adblock_list_db_version;

	$table_name = $wpdb->prefix . 'adblocklist_data';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
                `id` mediumint(9) NOT NULL AUTO_INCREMENT,
		`frid` mediumint(9) NOT NULL,
                `content` varchar(255) NOT NULL,
                PRIMARY KEY (`id`),
		INDEX fk_blocklistid (`frid`)
        ) $charset_collate; ";

	// Create data table (if not exists)
	$wpdb->query($sql);
}

function adblock_list_db_uninstall() {
	global $wpdb;
	global $adblock_list_db_version;

 	$table_name = $wpdb->prefix . 'adblocklist';

	$sql = "DROP TABLE IF EXISTS $table_name;";

	$wpdb->query($sql);

	delete_option('adblock_db_version', $adblock_list_db_version);

}

// register plugin installation
register_activation_hook( __FILE__, 'adblock_list_db_install' );
register_activation_hook( __FILE__, 'adblock_list_db_data_install' );

// register plugin de-installation
register_deactivation_hook(__FILE__, 'adblock_list_db_uninstall');

// add actions
add_action( 'init', 'adblock_create_post_type' );
add_action( 'save_post', 'adblock_save_post_type');

add_filter( 'the_content', 'my_the_content_filter', 20 );

/**
 * Add a icon to the beginning of every post page.
 *
 * @uses is_single()
 *
 */
function my_the_content_filter( $content ) {
    global $wpdb;
    $post_id = get_the_ID();

    if ( is_single() && get_post_type( $post_id ) == "postadblock") {
    $db_host =  get_option('adblock_plugin_page_host','localhost') ;
    $db_db =  get_option('adblock_plugin_page_db','wordpress') ;
    $db_user =  get_option('adblock_plugin_page_user','wordpress') ;
    $db_passwd =  get_option('adblock_plugin_page_password','wordpress') ;
    $listname = get_post_meta($post_id, "_adblocklist", true);

    $wpdb_local = new wpdb($db_user, $db_passwd, $db_db, $db_host);
    $wpdb_local->prefix = $wpdb->prefix;
    $table_name = $wpdb->prefix . 'adblocklist_data';
    $charset_collate = $wpdb->get_charset_collate();
    
    $possibilities   = $wpdb_local->get_results("select content from $table_name where frid = (select id from wp_adblocklist where datasetname = '$listname')", "ARRAY_N");

    $list = array();
    foreach($possibilities as $row=>$item) {
	array_push($list, $item[0]);
    }
    $content .= sprintf('<![CDATA[%s]]>', join(',', $list));
    } // End IF Statementi

    // Returns the content.
    return $content;
}


add_filter( 'pre_get_posts', 'my_get_posts' );

function my_get_posts( $query ) {
    if ( ( is_category() && $query->is_main_query() ) || is_home() && $query->is_main_query() || is_feed() )
        $query->set( 'post_type', array( 'post', 'postadblock' ) );

    return $query;
}
?>
