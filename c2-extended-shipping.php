<?php
/*
Plugin Name: C2 Extended Shipping for a fee
Plugin URI: https://comunic2.com/
Description: C2 Extended Shipping for a fee adds a custom fee to a selected existing shipping rate and presents it as an additional rate option. Typically used when you want to offer an extra shipping service for selected (tagged) products and selected zones. 
Version: 1.0.4
Author: Comunica2 sdad coop
Author URI: https://comunica2.com/
*/


/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function c2_extended_shipping_init() {
		if ( ! class_exists( 'WC_C2_Extended_Shipping_Method' ) ) {
			class WC_C2_Extended_Shipping_Method extends WC_Shipping_Method {
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct() {
					$this->id                 = 'c2_extended_shipping'; // Id for your shipping method. Should be unique.
					$this->method_title       = __( 'C2 Extended Shipping' );  // Title shown in admin
					$this->method_description = __( 'C2 Extended Shipping for a fee adds a custom fee to one of selected existing shipping rates and presents it as an additional rate.' ); // Description shown in admin
					$this->tax_status         = 'taxable'; 
          $this->get_available_shipping_methods();
					// Load the settings API
          $this->form_fields = array(
            'enabled' => array(
              'title'   => __('Enable/Disable', 'woocommerce'),
              'type'    => 'checkbox',
              'label'   => __('Enable this shipping method', 'woocommerce'),
              'default' => 'no',
            ),
            'title' => array(
              'title'       => __('Shipping method title', 'woocommerce'),
              'type'        => 'text',
              'description' => __('The shipping option label that is shown to the user with the shipping cost.', 'woocommerce'),
              'default'     => __('Extended shipping', 'woocommerce'),
            ),
            'append_title' => array(
              'title'   => __('Append title', 'woocommerce'),
              'type'    => 'checkbox',
              'label'   => __('Append <i>Shipping method title</i> to Base rate label', 'woocommerce'),
              'default' => 'yes',
            ),            
            'additional_fee' => array(
              'title'       => __('Upgrade Fee', 'woocommerce'),
              'type'        => 'text',
              'description' => __('The fee (w/o tax) to be added to the chosen base rate.', 'woocommerce'),
              'default'     => '0.00',
            ),
            'base_method_id' => array(
              'title' => __('Base rate method ID', 'woocommerce'),
              'description' => __('Choose the method (ID: label) that determines the base rate.', 'woocommerce'),
              'type' => 'select',
              'default' => 'request_shipping_quote',
//              'css'         => sprintf('height: %sem',count($this->shipping_methods)*1.3),
//               'label' => 'Label', // checkbox only
              'options' => $this->shipping_methods,
            ),
            'applicable_zones' => array(
              'title'       => __('Applicable Shipping Zones', 'woocommerce'),
              'description' => __('Enable this shipping method for the selected zones (use ctrl-click)', 'woocommerce'),
              'type'        => 'multiselect',
              'default'     => ['2','3','4'],
              'options'     => $this->shipping_zones,
              'css'         => sprintf('height: %sem',count($this->shipping_zones)*1.3),
            ),
            'applicable_product_tag' => array(
              'title'       => __('Applicable Product Tag', 'woocommerce'),
              'type'        => 'text',
              'description' => __('Enable this shipping for the indicated product tag slug', 'woocommerce'),
              'default'     => 'pickup',
            ),
          );
					$this->init_settings(); // This is part of the settings API. Loads settings you previously init.


          $this->enabled                = $this->get_option('enabled');
          $this->title                  = $this->get_option('title');
          $this->append_title           = $this->get_option('append_title');
          $this->additional_fee         = $this->get_option('additional_fee');
          $this->base_method_id         = $this->get_option('base_method_id');
          $this->applicable_zones       = $this->get_option('applicable_zones');
          $this->applicable_product_tag = $this->get_option('applicable_product_tag');

					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}
        
        /**
         * Get available shipping methods
         *
         * @return array
         */
        public function get_available_shipping_methods() // works but misses dhlexpress and Correos is misleading
        {
            $shipping_methods = array();
            $zones = array();

            $shipping_zones = WC_Shipping_Zones::get_zones();

            foreach ($shipping_zones as $shipping_zone) {
                $zones[$shipping_zone['id']] = $shipping_zone['zone_name'];
                $zone_methods = $shipping_zone['shipping_methods'];

                foreach ($zone_methods as $zone_method) {
                  if ( isset($shipping_methods[$zone_method->id] ))
                    break;
                   $shipping_methods[$zone_method->id] = sprintf('%s: %s',$zone_method->id, $zone_method->method_title);
                }
            }
            // add (default) zone
            $zones[0] = __('Locations not covered by your other zones','woocommerce');
            
            // add methods manually that do not appear in the zones
            if (class_exists('WC_DHLExpress_Rates')) 
              $shipping_methods[ 'dhlexpress' ] = 'dhlexpress: DHL Express';
            
            $this->shipping_methods = $shipping_methods;
            $this->shipping_zones = $zones;
        }

//         public function get_available_shipping_methods(){ // is manual. 
//           return [
//   'flat_rate' => 'Precio fijo (flat_rate)',
//   'free_shipping' => 'EnvÃ­o gratuito (free_shipping)',
//   'local_pickup' => 'Recogida local (local_pickup)',
//   'request_shipping_quote' => 'Correos Oficial (request_shipping_quote)',
//   'dhlexpress' => 'DHL Express Shipping Rates (dhlexpress)',
//   'c2_extended_shipping' => 'Upgrade Shipping Rate (c2_extended_shipping)',
//   'seur' => 'SEUR (seur)',
// ];
//         }



				
				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param array $package
				 * @return void
				 */
        public function calculate_shipping( $package = array() ) {
          global $my_debug;
          $cond_check = false;
          $this->get_available_shipping_methods();
          
          $cart= WC()->cart->get_cart();
          foreach ( $cart as $cart_item ) {
                    
            $product_id = $cart_item['data']->get_id();
            
            // get the aplicable product tag 
            $applicable_product_tag=isset($this->settings['applicable_product_tag'])?$this->settings['applicable_product_tag']:'pickup';
            if ( has_term( $applicable_product_tag, 'product_tag', $product_id )  ) {
                $cond_check = true;
                // break because only one such product already meets the condition
                break;
            }
          }
          
          if ( $cond_check ) {
            $shipping_zone = wc_get_shipping_zone( $package );
            $zone_id   = $shipping_zone->get_id(); // Get the zone ID
//             $zone_name = $shipping_zone->get_zone_name(); // Get the zone name
            if ( in_array($zone_id,$this->settings['applicable_zones']) ) {
              $base_rate= null;
              $session_rates=WC()->session->get( 'shipping_for_package_0' )['rates'];
              send_to_console($session_rates,'Session package');
              
              foreach ( $session_rates as $rate ) {
//               foreach ( $package['rates'] as $rate ) {
                // TODO Add code that updates $this->shipping_methods with the ones missed by $this->get_available_shipping_methods()
                // TODO Extended feature to support multiple base_fees / add multiple Extended rates.  
                if ( $rate->get_method_id() == $this->settings['base_method_id'] )
                  $base_rate= $rate;
              }
              if (isset($base_rate)) {
                $cost= $base_rate->get_cost();
                $additional_fee = isset($this->settings['additional_fee']) ? $this->settings['additional_fee'] : '0.00';
                $base_label = $this->settings['append_title'] == 'yes' ? $base_label=$base_rate->get_label()." " : "";
                $this_label = isset($this->settings['title']) ? $this->settings['title'] : $this->title;
                $rate = array(
                  'label' => $base_label.$this_label,
                  'cost' => $cost+$additional_fee,
                  'calc_tax' => 'per_order'
                );

                // Register the rate
                $this->add_rate( $rate );
              }
            }
          }
				}
			}
		}
	}

	add_action( 'woocommerce_shipping_init', 'c2_extended_shipping_init' , 500);

	function add_c2_extended_shipping( $methods ) {
		$methods['c2_extended_shipping'] = 'WC_C2_Extended_Shipping_Method';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'add_c2_extended_shipping' );
}
