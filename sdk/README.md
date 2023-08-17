<img src="../assets/images/logodiller.svg" width="250" height="100" />

---

# DILLER LOYALTY 2 SDK

## Table of Contents

- [List of hooks](#list-of-hooks)
- [Examples](#examples)
    - [Get Follower (by WP user ID)](#get-follower-user-has-already-enrolled-the-loyalty-program-and-exists-as-a-user-in-wp)
    - [Get Follower (by phone)](#get-follower-by-phone-number)
    - [Add a new Follower](#add-new-follower)
    - [Get coupons for Follower](#get-coupons-for-follower)
    - [Validate a coupon for a Follower](#validate-a-coupon-for-a-given-follower)
    - [Send order transaction](#sends-a-wc-order-transaction-to-diller-api)
    - [Cancel order transaction](#cancel-a-wc-order-transaction-in-diller-api)
    - [Add a new Follower](#add-new-follower)
    - [Filters / actions that Diller_Loyalty_Woocommerce uses](#overriding-filters-and-actions-that-diller_loyalty_woocommerce-uses)


## List of Hooks

---

**diller_api_follower_transaction_saved**

Fires after a transaction was successfully saved in Diller Api

**Parameters(1)**
- `$order_id` Woocommerce Order ID

```php
// define the diller_api_follower_transaction_saved 
function action_diller_api_follower_transaction_saved($order_id) { 
    // make action magic happen here... 
}; 
         
// add the action 
add_action( 'diller_api_follower_transaction_saved', 'action_diller_api_follower_transaction_saved', 10, 1 ); 
```
---
**diller_api_follower_unsubscribed**

Fires after a Follower successfully unsubscribed from Diller.

**Parameters(1)**
- `$follower` `Diller_Loyalty_Follower`  object

```php
// define the diller_api_follower_unsubscribed 
function action_diller_api_follower_unsubscribed($follower) { 
    // make action magic happen here... 
}; 
         
// add the action 
add_action( 'diller_api_follower_unsubscribed', 'action_diller_api_follower_unsubscribed', 10, 1 ); 
```
---
**diller_api_follower_registered**

Fires after a Follower was successfully created in Diller.

**Parameters(1)**
- `$follower` `Diller_Loyalty_Follower`  object

```php
// define the diller_api_follower_registered 
function action_diller_api_follower_registered($follower) { 
    // make action magic happen here... 
}; 
         
// add the action 
add_action( 'diller_api_follower_registered', 'action_diller_api_follower_registered', 10, 1 ); 
```
---
**diller_api_follower_updated**

Fires after a Follower was successfully updated in Diller.

**Parameters(1)**
- `$follower` `Diller_Loyalty_Follower`  object

```php
// define the diller_api_follower_updated 
function action_diller_api_follower_updated($follower) { 
    // make action magic happen here... 
}; 
         
// add the action 
add_action( 'diller_api_follower_updated', 'action_diller_api_follower_updated', 10, 1 ); 
```
---
**diller_woocommerce_actions**

Fires before `Diller_Loyalty_Woocommerce`  adds all the actions Diller plugin will hook into

**Parameters(1)**
- `$actions` array holding all the actions Diller plugin will hook into

```php
// define the diller_woocommerce_actions 
function filter_diller_woocommerce_actions($actions) { 
    // make action magic happen here... 
}; 
         
// add the action 
add_filter( 'diller_woocommerce_actions', 'filter_diller_woocommerce_actions', 10, 1 ); 
```
---
**diller_admin_woocommerce_actions**

Fires before `Diller_Loyalty_Woocommerce`  adds all the actions Diller plugin will hook into, for the backend (is_admin() == true)

**Parameters(1)**
- `$actions` array holding all the actions Diller plugin will hook into

```php
// define the diller_admin_woocommerce_actions 
function filter_diller_admin_woocommerce_actions($actions) { 
    // make action magic happen here... 
}; 
         
// add the action 
add_filter( 'diller_admin_woocommerce_actions', 'filter_diller_admin_woocommerce_actions', 10, 1 ); 
```
---
**diller_woocommerce_filters**

Fires before `Diller_Loyalty_Woocommerce`  adds all the filters Diller plugin will hook into

**Parameters(1)**
- `$filters` array holding all the filters Diller plugin will hook into

```php
// define the diller_woocommerce_filters 
function filter_diller_woocommerce_filters($filters) { 
    // make action magic happen here... 
}; 
         
// add the action 
add_filter( 'diller_woocommerce_filters', 'filter_diller_woocommerce_filters', 10, 1 ); 
```
---
**diller_admin_woocommerce_filters**

Fires before `Diller_Loyalty_Woocommerce`  adds all the filters Diller plugin will hook into, for the backend (is_admin() == true)

**Parameters(1)**
- `$filters` array holding all the filters Diller plugin will hook into

```php
// define the diller_admin_woocommerce_filters 
function filter_diller_admin_woocommerce_filters($filters) { 
    // make action magic happen here... 
}; 
         
// add the action 
add_filter( 'diller_admin_woocommerce_filters', 'filter_diller_admin_woocommerce_filters', 10, 1 ); 
```
---
# EXAMPLES

## Get Follower (User has already enrolled the Loyalty Program and exists as a user in WP)

---
**Parameters(2)**
- `$wp_user_id` int Wordpress user ID
- `$force_refresh` bool (Optional) If true, a fresh copy of the Follower data is fetched from Diller API and then cached localy. Default is false

**Returns**
- `Diller_Loyalty_Follower|WP_Error` `Diller_Loyalty_Follower` object or `WP_Error` object

 ```php
$result = DillerLoyalty()->get_follower($wp_user_id, $force_refresh);
if(!is_wp_error($result)){
    // Exists
}
```


## Get Follower by phone number

---
**Parameters(2)**
- `$country_code` string Phone country code
- `$phone_number` string Phone number

**Returns**
- `Diller_Loyalty_Follower|WP_Error` `Diller_Loyalty_Follower` object or `WP_Error` object

 ```php
$result = DillerLoyalty()->get_api()->get_follower($country_code, $phone_number);
if(!is_wp_error($result)){
    // Exists
}
```


## Add new Follower
The term "Add new Follower" is different from "Create new Follower", in the sense that Add new Follower just adds the phone number to the API, and then he/she will receive an SMS to ask for confirmation and to accept the GDPR.

In other hand "Create new Follower" creates the Follower in the API with all the fields and with the GDPR and marketing communication consent accepted. Eg. when Follower enrolls the LP from the checkout page or through the enrollment form.

---
**Parameters(2)**
- `$country_code` string Phone country code
- `$phone_number` string Phone number

**Returns**
- `Diller_Loyalty_Follower|WP_Error` `Diller_Loyalty_Follower` object or `WP_Error` object

 ```php
$result = DillerLoyalty()->get_api()->add_new_follower($country_code, $phone_number);
if(!is_wp_error($result)){
    // Success
}
```



## Get coupons for Follower

---
**Parameters(1)**
- `$follower` `Diller_Loyalty_Follower` object

**Returns**
- `Diller_Loyalty_Coupon[]|WP_Error` array of `Diller_Loyalty_Coupon` or `WP_Error` object

 ```php
$result = DillerLoyalty()->get_api()->get_coupons_for($follower);
if(!is_wp_error($result)){
    // Success
}
```


## Validate a coupon for a given Follower

---
**Parameters(1)**
- `$follower` `Diller_Loyalty_Follower` object
- `$coupon_code` string object

**Returns**
- `bool|WP_Error` true or `WP_Error` object with a detailed description of the error

 ```php
$result = DillerLoyalty()->get_api()->validate_coupon_for($follower, $coupon_code);
if(!is_wp_error($result)){
    // Success
}
```




## Sends a WC order transaction to Diller API

---
**Parameters(2)**
- `$follower` `Diller_Loyalty_Follower` object
- `$order` int|WC_Order the order id or the `WC_Order` object

**Returns**
- `bool` true or false

 ```php
$result = DillerLoyalty()->get_woocommerce()->save_transaction($follower, $order);
if(!is_wp_error($result)){
    // Success
}
```



## Cancel a WC order transaction in Diller API

---
**Parameters(1)**
- `$order` int|WC_Order the order id or the `WC_Order` object

**Returns**
- `bool|WP_Error` true or `WP_Error` object with a detailed description of the error

 ```php
$result = DillerLoyalty()->get_woocommerce()->cancel_order_transaction($order);
if($result !== false && !is_wp_error($result)){
    // Success
}
```


## Overriding filters and actions that `Diller_Loyalty_Woocommerce` uses

---
Copy the sample file `diller-loyalty/sdk/diller-loyalty-overrides-sample.php` into your theme root folder and rename it `diller-loyalty-overrides.php`.
Add your code to cover your business requirements, like in the following example:

```php
 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'DillerLoyalty' )) {
	exit;
}

// Comment next line, if this is supposed to trigger for frontend as well. One can also filter these per role, etc
if ( is_admin() ):

	add_filter( 'diller_woocommerce_actions', 'customize_diller_woocommerce_actions', 10, 1);
	function customize_diller_woocommerce_actions($actions) {
	    // Remove an existing hook
		if($found_index = array_search('hook_name_here', array_column($actions, 'hook'))) {
			unset( $actions[ $found_index ] );
		}
		
	    // Add a new hook (this most likely won't be necessary, but you can be done)
	    $my_component = new My_Component_Class();
	    $actions[] = array( 'hook' => 'some_other_hook_here', 'component' => $my_component, 'callback' => 'your_custom_callback', 'priority' => 10, 'accepted_args' => 1 );

	    return $actions;
	}

	add_filter( 'diller_woocommerce_filters', 'customize_diller_woocommerce_filters', 10, 1);
	function customize_diller_woocommerce_filters($filters) {
	    // same as above: customize_diller_woocommerce_actions()
	}

endif;
  ```

##Contributing

This plugin is constantly improving and evolving. If you want to contribute in any capacity or share some feedback, please drop us a line.

---
rev. 27.11.2021