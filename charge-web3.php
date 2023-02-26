<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://chargeweb3.com/
 * @since             1.0.0
 * @package           Charge_Web3
 *
 * @wordpress-plugin
 * Plugin Name:       Charge Web3
 * Plugin URI:        https://chargeweb3.com/projects/app-store/woocommerce
 * Description:       This plugin creates a payment gateway for the chargeweb3 woocommerce plugin.
 * Version:           1.0.0
 * Author:            Charge
 * Author URI:        https://chargeweb3.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       charge-web3
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CHARGE_WEB3_VERSION', '1.0.0' );
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-charge-web3.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_charge_web3() {

	$plugin = new Charge_Web3();
	$plugin->run();

}
run_charge_web3();