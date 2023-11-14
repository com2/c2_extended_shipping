# c2_extended_shipping
C2 Extended Shipping for a fee adds a custom fee to a selected existing WooCommerce shipping rate and presents it as an additional rate option. Typically used when you want to offer an extra shipping service for selected (tagged) products and selected zones.
# Installation and Configuration
Like usual. Copy the c2_extended_shipping folder to /wp-content/plugins/ and activate the plugin. In **WooCommerce > Settings > Shipping > C2 Extended shipping** (/wp-admin/admin.php?page=wc-settings&tab=shipping&section=c2_extended_shipping) you can:
 * activate (by default deactivated) its functionality
 * choose the label of the new shipping option
 * either appande that label to the base shipping option or not
 * determine the upgrade fee (always without tax)
 * choose the base shipping rate
 * select the shipping zones (use ctrl Click) where the extended shipping should be active
 * write the product tag slug in cart that allows for extended shipping in check-out
# Disclamer and bugs
Use this code as is and at your own risk. It has still a number of bugs but I do not know how to fix them. They are kind of difficult to describe but I do my best to be as transparent as possible. In WC v. 8.2.2 it initially gives the impression that it just works but as soon as you change the address to another unselected zone and back to the seleted zone it either *greys out* the shipping options and it becomes impossible to order. Sometimes Reload helps then, sometimes it bases the price on an shipping option of a previous shipping zone, only visible when the price was different, sometimes you need to change zone again and reload to work around it. This has something to do with caching and my incorrect code not clearing it. In some previous vesions of WooCommerce it would still work fine, in later versions it would only work in debug mode (caching is the turned off). In version WC v. 8.2.2 it will not come out of *greyed out* when debug mode is active. May be it is related to the base shipping plugins I used:
* Correos (Spain) 1.4.1.1: https://www.correos.es/es/es/empresas/ecommerce/agiliza-la-gestion-de-tus-pedidos/woocommerce
* SEUR 2.1.1 (Spain): https://wordpress.org/plugins/seur/
* DHL woocommerce-dhlexpress-services (DHL provided)
