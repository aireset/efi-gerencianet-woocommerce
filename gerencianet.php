<?php

/**
 * Plugin Name:       Efí/Gerencianet por Aireset
 * Plugin URI:        https://wordpress.org/plugins/aireset/
 * Description:       Gateway de pagamento Efi/Gerencianet por Aireset
 * Version:           2.0.7
 * Author:            Aireset
 * Author URI:        https://aireset.com.br
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gerencianet-aireset
 * Domain Path:       /languages
 * WC requires at least: 5.0.0
 * WC tested up to: 7.4.0
 * 
 * Este é um plugin como fork do original, com alterações e ajustes que são usados principalmente nos nossos clientes. 
 * Se você procura algo para conectar com sua conta da Efí/Gerencianet pode usar este ou o original. 
 */

namespace Gerencianet_Oficial;

use GN_Includes\Gerencianet_Oficial;
use GN_Includes\Gerencianet_Activator;
use GN_Includes\Gerencianet_Deactivator;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'GERENCIANET_OFICIAL_VERSION', '2.0.7' );
define( 'GERENCIANET_BOLETO_ID', 'WC_Gerencianet_Boleto' );
define( 'GERENCIANET_CARTAO_ID', 'WC_Gerencianet_Cartao' );
define( 'GERENCIANET_PIX_ID', 'WC_Gerencianet_Pix' );


/**
 * Define global path constants
 */
define( 'GERENCIANET_OFICIAL_PLUGIN_FILE', __FILE__ );
define( 'GERENCIANET_OFICIAL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'GERENCIANET_OFICIAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once GERENCIANET_OFICIAL_PLUGIN_PATH . 'includes/helpers.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-gerencianet-oficial-activator.php
 */
register_activation_hook( GERENCIANET_OFICIAL_PLUGIN_FILE, array( Gerencianet_Activator::class, 'activate' ) );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-gerencianet-oficial-deactivator.php
 */
register_deactivation_hook( GERENCIANET_OFICIAL_PLUGIN_FILE, array( Gerencianet_Deactivator::class, 'deactivate' ) );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function run_gerencianet_oficial() {

	$plugin = new Gerencianet_Oficial();
	$plugin->run();

}
run_gerencianet_oficial();
