<?php

/**
 * Plugin Name: Razorpay Subscription Button
 * Plugin URI:  https://github.com/razorpay/subscription-button-wordpress-plugin
 * Description: Razorpay Subscription Button
 * Version:     1.0.4
 * Author:      Razorpay
 * Author URI:  https://razorpay.com
 */

require_once __DIR__.'/razorpay-sdk/Razorpay.php';
require_once __DIR__.'/includes/rzp-btn-view.php';
require_once __DIR__.'/includes/rzp-btn-action.php';
require_once __DIR__.'/includes/rzp-btn-settings.php';
require_once __DIR__.'/includes/rzp-subscription-buttons.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors;

add_action('admin_enqueue_scripts', 'bootstrap_scripts_enqueue_subscription', 0);
add_action('admin_post_rzp_sub_btn_action', 'razorpay_subscription_button_action');

function bootstrap_scripts_enqueue_subscription($admin_page)
{
    wp_register_style('sub_button-css', plugin_dir_url(__FILE__)  . 'public/css/sub_button.css', null, null);
    wp_enqueue_style('sub_button-css');

    if ($admin_page != 'admin_page_rzp_subscription_button_view')
    {
        return;
    }

    wp_register_style('bootstrap-css', plugin_dir_url(__FILE__)  . 'public/css/bootstrap.min.css', null, null);
    wp_enqueue_style('bootstrap-css');
    wp_enqueue_script('jquery');
}

/**
 * This is the RZP Payment button loader class.
 *
 * @package RZP WP List Table
 */
if (!class_exists('RZP_Subscription_Button_Loader')) 
{
    // Adding constants
    if (!defined('RZP_BASE_NAME'))
    {
        define('RZP_BASE_NAME', plugin_basename(__FILE__));
    }

    if (!defined('RZP_REDIRECT_URL'))
    {
        // admin-post.php is a file that contains methods for us to process HTTP requests
        define('RZP_REDIRECT_URL', esc_url(admin_url('admin-post.php')));
    }

    class RZP_Subscription_Button_Loader
    {
        /**
         * Start up
         */
        public function __construct()
        {
            add_action('admin_menu', array($this, 'rzp_add_plugin_page'));
            add_action('enqueue_block_editor_assets', array($this , 'load_razorpay_block'), 10);

            add_filter('plugin_action_links_' . RZP_BASE_NAME, array($this, 'razorpay_plugin_links'));

            $this->settings = new RZP_Subscription_Setting();
        }

        /**
         * Creating the menu for plugin after load
        **/
        public function rzp_add_plugin_page()
        {
            /* add pages & menu items */
            add_menu_page(esc_attr__('Razorpay Subscription Button', 'textdomain'), esc_html__('Razorpay Subscription Buttons', 'textdomain'),
            'administrator','razorpay_Subscription_button',array($this, 'rzp_view_sub_buttons_page'), '', 10);

            add_submenu_page(esc_attr__('razorpay_Subscription_button', 'textdomain'), esc_html__('Razorpay Settings', 'textdomain'),
            'Settings', 'administrator','razorpay_subscription_settings', array($this, 'razorpay_subscription_settings'));

            add_submenu_page(esc_attr__('', 'textdomain'), esc_html__('Razorpay Subscription Buttons', 'textdomain'),
            'Razorpay Subscription Buttons', 'administrator','rzp_subscription_button_view', array($this, 'rzp_subscription_button_view'));
        }

        /**
         * Initialize razorpay api instance
        **/
        public function get_razorpay_api_instance()
        {
            $key = get_option('key_id_field');

            $secret = get_option('key_secret_field');

            if(empty($key) === false and empty($secret) === false)
            {
                return new Api($key, $secret);
            }

            wp_die('<div class="error notice">
                        <p>RAZORPAY ERROR: Payment button fetch failed.</p>
                     </div>'); 
        } 

        /**
         * Initialize razorpay custom block.js and initialize buttons from api
        **/
        public function load_razorpay_block() 
        {
            // Register the script
            wp_register_script('rzp_subscription_button', plugin_dir_url(__FILE__) . 'public/js/blocks.js', array(
                    'wp-blocks',
                    'wp-i18n',
                    'wp-element',
                    'wp-components',
                    'wp-editor'
                ) 
            );
            if (! function_exists('get_plugin_data')) {
                    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            $mod_version = get_plugin_data(plugin_dir_path(__FILE__) . 'razorpay-subscription-buttons.php')['Version'];

            $button_array = array(
                'payment_buttons' => $this->get_buttons(),
                'payment_buttons_plugin_version' => $mod_version,
            );

            // Localize the script with new data
            wp_localize_script('rzp_subscription_button', 'razorpaybutton', $button_array);
             
            // Enqueued script with localized data.
            wp_enqueue_script('rzp_subscription_button');
        }

        public function get_buttons() 
        {
            $buttons = array();

            $api = $this->get_razorpay_api_instance();

            try
            {
                $items = $api->paymentPage->all(['view_type' => 'subscription_button', "status" => 'active','count'=> 100]);
            }
            catch (\Exception $e)
            {
                $message = $e->getMessage();

                wp_die('<div class="error notice">
                    <p>RAZORPAY ERROR: Payment button fetch failed with the following message: '.$message.'</p>
                 </div>');
            }

            if ($items) 
            {
                foreach ($items['items'] as $item) 
                {
                    $buttons[] = array(
                        'id' => $item['id'],
                        'title' => $item['title']
                    );
                }
            }

            return $buttons;
        }

        /**
         * Creating the settings link from the plug ins page
        **/
        function razorpay_plugin_links($links)
        {
            $pluginLinks = array(
                            'settings' => '<a href="'. esc_url(admin_url('admin.php?page=razorpay_subscription_settings')) .'">Settings</a>',
                            'docs'     => '<a href="#">Docs</a>',
                            'support'  => '<a href="https://razorpay.com/contact/">Support</a>'
                        );

            $links = array_merge($links, $pluginLinks);

            return $links;
        }

        /**
         * Razorpay Payment Button Page
         */
        public function rzp_view_sub_buttons_page()
        {
            $rzp_subscription_buttons = new RZP_Subscription_Button();

            $rzp_subscription_buttons->rzp_buttons();
        }

        /**
         * Razorpay Setting Page
         */
        public function razorpay_subscription_settings()
        {
            $this->settings->razorpaySettings();
        }  

        /**
         * Razorpay Setting Page
         */
        public function rzp_subscription_button_view()
        {
            $new_button = new RZP_Subscription_View_Button();

            $new_button->razorpay_view_button();
        }
    }
}

/**
* Instantiate the loader class.
*
* @since     2.0
*/
$RZP_Subscription_Button_Loader = new RZP_Subscription_Button_Loader();

function razorpay_subscription_button_action()
{
    $btn_action = new RZP_Subscription_Button_Action();
    
    $btn_action->process();
}
