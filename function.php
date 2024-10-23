<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

use WHMCS\Database\Capsule;

class limit_purchase
{
    var $config;

    function __construct()
    {
        $this->loadConfig();
    }

    function loadConfig()
    {
        $this->config = [];

        $configDetails = Capsule::table('mod_limit_purchase_config')->get();

        foreach ($configDetails as $config_detail) {
            $this->config[$config_detail->name] = $config_detail->value;
        }
    }

    function setConfig($name, $value)
    {
        $exists = Capsule::table('mod_limit_purchase_config')
            ->where('name', $name)
            ->exists();

        if ($exists) {
            Capsule::table('mod_limit_purchase_config')
                ->where('name', $name)
                ->update(['value' => $value]);
        } else {
            Capsule::table('mod_limit_purchase_config')
                ->insert(['name' => $name, 'value' => $value]);
        }

        $this->config[$name] = $value;
    }

    function getLimitedProducts()
    {
        $output = [];

        $limits = Capsule::table('mod_limit_purchase as l')
            ->join('tblproducts as p', 'p.id', '=', 'l.product_id')
            ->where('l.active', 1)
            ->select('l.*')
            ->get();

        foreach ($limits as $limit) {
            $output[$limit->product_id] = [
                'limit' => $limit->limit,
                'error' => $limit->error
            ];
        }

        return $output;
    }
}

?>