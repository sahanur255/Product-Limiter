<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

require_once(dirname(__FILE__) . '/functions.php');

function limit_purchase($vars)
{
    $errors = array();
    $lp = new limit_purchase();

    $pids = $lp->getLimitedProducts();
    $user_id = intval($_SESSION['uid']);

    if (sizeof($_SESSION['cart']['products'])) {
        $counter = $delete = array();

        foreach ($_SESSION['cart']['products'] as $i => $product_details) {
            if (in_array($product_details['pid'], array_keys($pids))) {
                if (!isset($counter[$product_details['pid']])) {
                    $counter[$product_details['pid']] = 0;

                    if ($user_id) {
                        $totalProducts = Capsule::table('tblhosting')
                            ->where('userid', $user_id)
                            ->where('packageid', $product_details['pid'])
                            ->count();

                        $counter[$product_details['pid']] = intval($totalProducts);
                    }
                }

                if ($pids[$product_details['pid']]['limit'] <= intval($counter[$product_details['pid']])) {
                    if (!isset($delete[$product_details['pid']])) {
                        $product = Capsule::table('tblproducts')
                            ->where('id', $product_details['pid'])
                            ->first(['name']);

                        if ($product) {
                            $delete[$product_details['pid']] = $product;
                        }
                    }

                    // Uncomment the line below to automatically delete the unwanted products from the cart
                    // unset($_SESSION['cart']['products'][$i]);
                }

                $counter[$product_details['pid']]++;
            }
        }

        foreach ($delete as $product_id => $product_details) {
            $errors[] = str_replace('{PNAME}', $product_details->name, $pids[$product_id]['error']);
        }
    }

    return $errors;
}

function limit_purchase_delete($vars)
{
    Capsule::table('mod_limit_purchase')
        ->where('product_id', $vars['pid'])
        ->delete();
}

add_hook('ShoppingCartValidateCheckout', 0, 'limit_purchase');
add_hook('ProductDelete', 0, 'limit_purchase_delete');

?>
