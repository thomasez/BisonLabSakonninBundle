<?php

namespace BisonLab\SakonninBundle\Lib;

/*
 *  Idea blatantly nicked from:
 * http://dev4theweb.blogspot.pt/2012/08/how-to-access-configuration-values.html
 * and is it as wrong as people say?
 * It works, well. That makes me happier than "bad pattern"
 * (He even uses Lib/ as I am alot already, so it cannot be wrong!)
 */

class ExternalEntityConfig
{
    protected static $address_types = array();
    public static function setAddressTypesConfig($address_types)
    {
        self::$address_types = $address_types;
    }

    public static function getAddressTypesFor($entity, $type)
    {
        if (!isset(self::$address_types[$entity])) return array();

        return isset(self::$address_types[$entity][$type]) ? self::$address_types[$entity][$type] : array();
    }

    public static function getAddressTypesAsChoicesFor($entity, $type)
    {
        $address_types = self::getAddressTypesFor($entity, $type);
        $choices = array();
        foreach ($address_types as $type => $params) {
            if (!$params['chooseable']) continue;
            $choices[$type] = $type;
        }
        return $choices;
    }
}
