<?php
/*
  Plugin Name: HH replayer
  Version: 1.0
  Author: Alexandre Altun
  License: GPL2
*/


/**
 * 
 * @param type $params
 * @return string
 */
function hhr_show_form( $params )
{
  require_once dirname(__FILE__) . '/controller/FormController.php';
  $fullMode = isset($params['fullMode']);
  $formController = new hhr_controller_FormController();
  return $formController->index($fullMode);
}

/**
 * Hook for form builder page shortcode tag
 */
add_shortcode( 'hhr_show_form', 'hhr_show_form' );

function hhr_show_full_page()
{
  require_once dirname(__FILE__) . '/controller/FormController.php';
  
  $formController = new hhr_controller_FormController();
  return $formController->full();
}

function hhr_add_query_vars($aVars) {
	$aVars[] = "hhr_id"; 
	return $aVars;
}
// hook add_query_vars function into query_vars
add_filter('query_vars', 'hhr_add_query_vars');


function hhr_add_rewrite_rules($aRules) {
	$aNewRules = array('hhreplayer/([^/]+)/?$' => 'index.php?pagename=hhreplayer&hhr_id=$matches[1]');
	$aRules = $aNewRules + $aRules;
	return $aRules;
} 
// hook add_rewrite_rules function into rewrite_rules_array
add_filter('rewrite_rules_array', 'hhr_add_rewrite_rules');

$formErrors = null;
function hhr_process_form(){
	global $formErrors;
    if (isset($_POST['hhreplayer_form'])) {
        $logContent = $_POST['lc_logcontent'];

		require_once dirname(__FILE__) . '/business/PluginManager.php';
		$manager = new hhr_business_PluginManager();
		if ($parser = $manager->getParser($logContent))
		{
			try {
				$logJSON = $manager->getLogJSON($parser);
				$id = $manager->save($logContent, $logJSON);
				$shortId = hhr_dto_HhReplayerDto::buildShortId($id);
				wp_redirect(home_url('/hhreplayer/'.$shortId));
				exit;
			} catch (Exception $e) {
				// erreur log invalide
				$formErrors = 'Le log est invalide';
			}
		} else {
			// erreur format inconnu
			$formErrors = 'Format de log non supporté';
		}
    }
}
add_action('wp_loaded', 'hhr_process_form');

// register javascripts and styles
function hhr_register_assets() {
    wp_register_script('hhreplayer_js', plugins_url('/assets/js/hhreplayer.js', __FILE__), array('jquery'), '1.0.0', 'all');
	wp_register_style( 'hhreplayer_css', plugins_url('/assets/css/hhreplayer.css', __FILE__), false, '1.0.0', 'all');
}
add_action('init', 'hhr_register_assets');

function hhr_enqueue_assets() {
	if (is_page('hhreplayer')) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('hhreplayer_js');
		
		wp_enqueue_style('hhreplayer_css');
    }
}
add_action('wp_print_scripts', 'hhr_enqueue_assets', 100);

function hhr_iframe_mode() {
	//var_dump($_SERVER);die;
	global $wp_query;
	
	if (preg_match('@/hhreplayer/full/([^/]+)/@', $_SERVER['REQUEST_URI'], $matches)) {
		$wp_query->query_vars['hhr_id'] = $matches[1];
		echo hhr_show_form(array('fullMode' => 1));
		exit;
	}
}
add_action('wp_loaded', 'hhr_iframe_mode');
//add_action('template_redirect', 'hhr_iframe_mode');




/**
 * 
 * @global type $wpdb
 */
function hhr_install()
{
  global $wpdb;

  $table_name = $wpdb->prefix . "hhreplayer";

  $sql = "CREATE TABLE $table_name (
      hh_id mediumint(9) NOT NULL AUTO_INCREMENT,
      hh_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      hh_log text DEFAULT '' NOT NULL,
	  hh_log_json text DEFAULT '' NOT NULL,
      hh_posted_by VARCHAR(32) NULL,
      hh_description VARCHAR(1024) NULL,
      UNIQUE KEY hh_id (hh_id)
    );";
  
  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta($sql);

//  add_option("jal_db_version", $jal_db_version);
}

/**
 * Creates the plugin 'form' page into the site
 */
function hhr_add_pages()
{
    $showPageName = 'hhreplayer';

    $isPagePresent = hhr_check_page_exists($showPageName);

    if ($isPagePresent)
    {
        return;
    }

    $post = array(
        'menu_order' => 0,
        'comment_status' => 'closed',
        'ping_status' => get_option('default_ping_status'),
        'post_author' => get_current_user_id(),
        'post_status' => 'publish',
        'post_content' => '[hhr_show_form]',
        'post_title' => 'HH replayer',
        'post_name' => $showPageName,
        'post_parent' => 0,
        'pinged' => '',
        'post_password' => '',
        'guid' => '',
        'post_content_filtered' => '',
        'post_type' => 'page',
        'post_excerpt' => '',
        'import_id' => 0,
        'to_ping' => '',
    );

	// Insert the post into the database
    wp_insert_post($post);
}

/**
 * Checks whether a page exists and is active for the site
 * @param string $pageTitle
 * @return boolean
 */
function hhr_check_page_exists($pageTitle)
{
    $result = get_page_by_title($pageTitle);

    if (empty($result))
    {
        return false;
    }

    if ($result->post_status == 'trash')
    {
        return false;
    }

    return true;
}

/**
 * Hook for creating Db on plugin activation
 */
register_activation_hook( __FILE__, 'hhr_install' );
register_activation_hook(__FILE__, 'hhr_add_pages');

