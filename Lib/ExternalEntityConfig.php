<?php

namespace BisonLab\SakonninBundle\Lib;

/*
 * Idea blatantly nicked from:
 * http://dev4theweb.blogspot.pt/2012/08/how-to-access-configuration-values.html
 * and is it as wrong as people say?
 * It works, well. That makes me happier than "bad pattern"
 * (He even uses Lib/ as I am alot already, so it cannot be wrong!)
 */

class ExternalEntityConfig
{
    protected static $states = array();

    /*
     * Usually I do "Types for" config and not specific.
     * Even I can be inconsistent.
     * TODO: Consistency.
     */
    protected static $base_types = array();
    protected static $address_types = array();
    protected static $file_types = array();

    public static function setBaseTypesConfig($base_types)
    {
        self::$base_types = $base_types;
    }

    public static function getBaseTypes()
    {
        return self::$base_types;
    }

    public static function getBaseTypesAsChoices()
    {
        $choices = array();
        foreach (self::$base_types as $type => $params) {
            if (!$params['chooseable']) continue;
            $choices[$type] = $type;
        }
        return $choices;
    }

    public static function setAddressTypesConfig($address_types)
    {
        self::$address_types = $address_types;
    }

    public static function getAddressTypes()
    {
        return self::$address_types;
    }

    public static function getAddressTypesAsChoices()
    {
        $choices = array();
        foreach (self::$address_types as $type => $params) {
            if (!$params['chooseable']) continue;
            $choices[$type] = $type;
        }
        return $choices;
    }

    public static function setFileTypesConfig($file_types)
    {
        self::$file_types = $file_types;
    }

    public static function getFileTypes()
    {
        return self::$file_types;
    }

    public static function getFileTypesAsChoices()
    {
        $choices = array();
        foreach (self::$file_types as $type => $params) {
            if (!$params['chooseable']) continue;
            $choices[$type] = $type;
        }
        return $choices;
    }

    // States stuff
    public static function setStatesConfig($states)
    {
        self::$states = $states;
    }

    public static function getStatesConfig()
    {
        return self::$states;
    }

    public static function getStatesFor($thingie)
    {
        return isset(self::$states[$thingie]) ? self::$states[$thingie]['states'] : self::$states['default']['states'];
    }

    public static function getStatesAsChoicesFor($thingie)
    {
        $states = self::getStatesFor($thingie);
        return array_combine(array_keys($states), array_keys($states));
    }
}
