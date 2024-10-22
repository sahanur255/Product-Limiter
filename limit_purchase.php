<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

function limit_purchase_config() {
    return array(
        "name" => "Product Limiter",
        "description" => "This addon allows you to limit the purchase of products/services for each client",
        "version" => "1.0.0",
        "author" => "Sahanur Monal",
        "language" => "english",
    );
}

function limit_purchase_activate() {
    $error = [];

    try {
        Capsule::schema()->create('mod_limit_purchase_config', function ($table) {
            $table->string('name')->unique();
            $table->text('value');
        });

        Capsule::table('mod_limit_purchase_config')->insert([
            ['name' => 'localkey', 'value' => ''],
            ['name' => 'version_check', 'value' => '0'],
            ['name' => 'version_new', 'value' => ''],
        ]);

        Capsule::schema()->create('mod_limit_purchase', function ($table) {
            $table->increments('id');
            $table->integer('product_id')->default(0);
            $table->integer('limit')->default(0);
            $table->string('error');
            $table->boolean('active')->default(0);
        });

    } catch (Exception $e) {
        $error[] = "Error: " . $e->getMessage();
    }

    return array(
        'status' => empty($error) ? 'success' : 'error',
        'description' => empty($error) ? '' : implode(" -> ", $error),
    );
}

function limit_purchase_deactivate() {
    $error = [];

    try {
        Capsule::schema()->dropIfExists('mod_limit_purchase');
        Capsule::schema()->dropIfExists('mod_limit_purchase_config');
    } catch (Exception $e) {
        $error[] = "Error: " . $e->getMessage();
    }

    return array(
        'status' => empty($error) ? 'success' : 'error',
        'description' => empty($error) ? '' : implode(" -> ", $error),
    );
}

function limit_purchase_output($vars) {
    $modulelink = $vars['modulelink'];
    $limits = Capsule::table('mod_limit_purchase')->get()->toArray();
    $manage_details = [];
    
    // Handle actions
    $action = $_REQUEST['action'] ?? '';
    $product_id = intval($_REQUEST['product_id'] ?? 0);
    $id = intval($_REQUEST['id'] ?? 0);
    $limit = intval($_REQUEST['limit'] ?? 0);
    $error = $_REQUEST['error'] ?? '';
    $active = intval($_REQUEST['active'] ?? 0);

    switch ($action) {
        case 'add':
            if ($product_id) {
                $exists = Capsule::table('mod_limit_purchase')->where('product_id', $product_id)->exists();

                if (!$exists) {
                    if ($limit > 0 && $error) {
                        Capsule::table('mod_limit_purchase')->insert([
                            'product_id' => $product_id,
                            'limit' => $limit,
                            'error' => $error,
                            'active' => $active,
                        ]);
                        $_SESSION['limit_purchase'] = array(
                            'type' => 'success',
                            'message' => "Limit added successfully!",
                        );
                    } else {
                        $_SESSION['limit_purchase'] = array(
                            'type' => 'error',
                            'message' => "All fields are required.",
                        );
                    }
                } else {
                    $_SESSION['limit_purchase'] = array(
                        'type' => 'error',
                        'message' => "Limit already exists for this product.",
                    );
                }
            } else {
                $_SESSION['limit_purchase'] = array(
                    'type' => 'error',
                    'message' => "Please select a product.",
                );
            }
            break;

        case 'edit':
            if ($id) {
                $limit_details = Capsule::table('mod_limit_purchase')->find($id);

                if ($limit_details) {
                    if ($product_id) {
                        if ($limit > 0 && $error) {
                            Capsule::table('mod_limit_purchase')
                                ->where('id', $id)
                                ->update([
                                    'product_id' => $product_id,
                                    'limit' => $limit,
                                    'error' => $error,
                                    'active' => $active,
                                ]);
                            $_SESSION['limit_purchase'] = array(
                                'type' => 'success',
                                'message' => "Limit edited successfully!",
                            );
                        } else {
                            $_SESSION['limit_purchase'] = array(
                                'type' => 'error',
                                'message' => "All fields are required.",
                            );
                        }
                    } else {
                        $_SESSION['limit_purchase'] = array(
                            'type' => 'error',
                            'message' => "Please select a product.",
                        );
                    }
                } else {
                    $_SESSION['limit_purchase'] = array(
                        'type' => 'error',
                        'message' => "Limit not found.",
                    );
                }
            } else {
                $_SESSION['limit_purchase'] = array(
                    'type' => 'error',
                    'message' => "No limit ID provided.",
                );
            }
            break;

        case 'delete':
            if ($id) {
                $limit_details = Capsule::table('mod_limit_purchase')->find($id);

                if ($limit_details) {
                    Capsule::table('mod_limit_purchase')->where('id', $id)->delete();
                    $_SESSION['limit_purchase'] = array(
                        'type' => 'success',
                        'message' => "Limit deleted successfully!",
                    );
                } else {
                    $_SESSION['limit_purchase'] = array(
                        'type' => 'error',
                        'message' => "Limit not found.",
                    );
                }
            } else {
                $_SESSION['limit_purchase'] = array(
                    'type' => 'error',
                    'message' => "No limit ID provided.",
                );
            }
            break;

        case 'manage':
            if ($id) {
                $manage_details = Capsule::table('mod_limit_purchase')->find($id);
            } else {
                $_SESSION['limit_purchase'] = array(
                    'type' => 'error',
                    'message' => "No limit ID provided.",
                );
            }
            break;
    }

    // Show success or error messages
    if (isset($_SESSION['limit_purchase'])) {
        echo '<div class="' . $_SESSION['limit_purchase']['type'] . 'box">';
        echo '<strong>' . $_SESSION['limit_purchase']['message'] . '</strong>';
        echo '</div>';
        unset($_SESSION['limit_purchase']);
    }

    // Fetch all products
    $products = Capsule::table('tblproducts')->get();

    // Output the form
    echo '<h2>' . (sizeof($manage_details) ? "Edit Limit" : "Add Limit") . '</h2>';
    echo '<form action="' . $modulelink . '&amp;action=' . (sizeof($manage_details) ? 'edit&amp;id=' . $manage_details->id : 'add') . '" method="post">';
    echo '<table width="100%" cellspacing="2" cellpadding="3" border="0" class="form">';
    echo '<tbody>';
    echo '<tr>';
    echo '<td width="15%" class="fieldlabel">Product</td>';
    echo '<td class="fieldarea">';
    echo '<select name="product_id" class="form-control select-inline">';
    echo '<option value="0">Select a Product</option>';
    foreach ($products as $product) {
        echo '<option value="' . $product->id . '"' . (isset($manage_details->product_id) && $manage_details->product_id == $product->id ? ' selected' : '') . '>' . $product->name . '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td class="fieldlabel">Limit</td>';
    echo '<td class="fieldarea"><input type="text" value="' . (isset($manage_details->limit) ? $manage_details->limit : '') . '" size="5" name="limit" /> Maximum number of purchases allowed.</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td class="fieldlabel">Error Message</td>';
    echo '<td class="fieldarea"><input type="text" value="' . (isset($manage_details->error) ? $manage_details->error : '') . '" size="50" name="error" /></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td class="fieldlabel">Active</td>';
    echo '<td class="fieldarea"><input type="checkbox" name="active" value="1"' . (isset($manage_details->active) && $manage_details->active ? ' checked' : '') . ' /> Yes</td>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
    echo '<input type="submit" class="btn btn-primary" value="' . (sizeof($manage_details) ? "Edit Limit" : "Add Limit") . '">';
    echo '</form>';

    // List all limits
    echo '<h2>Existing Limits</h2>';
    if (count($limits) > 0) {
        echo '<table class="table table-bordered">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Product</th>';
        echo '<th>Limit</th>';
        echo '<th>Error Message</th>';
        echo '<th>Active</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($limits as $limit) {
            $product = Capsule::table('tblproducts')->where('id', $limit->product_id)->first();
            echo '<tr>';
            echo '<td>' . ($product ? $product->name : 'Deleted Product') . '</td>';
            echo '<td>' . $limit->limit . '</td>';
            echo '<td>' . $limit->error . '</td>';
            echo '<td>' . ($limit->active ? 'Yes' : 'No') . '</td>';
            echo '<td>';
            echo '<a href="' . $modulelink . '&amp;action=manage&amp;id=' . $limit->id . '" class="btn btn-default">Edit</a> ';
            echo '<a href="' . $modulelink . '&amp;action=delete&amp;id=' . $limit->id . '" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to delete this limit?\');">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No limits found.</p>';
    }
}
