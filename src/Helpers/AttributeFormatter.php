<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Helpers;

use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;

class AttributeFormatter
{

    public static function arrayFlatten($array) {
        if (!is_array($array)) {
            return false;
        }
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::arrayFlatten($value));
            }
            else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * AkeneoClientBuilder constructor.
     */
    public static function process($value, $attrType)
    {
        $callbackMethod = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $attrType))));
        if (method_exists(__CLASS__, $callbackMethod)) {
            $value = self::getLocaleValue($value);
            $value = call_user_func(__NAMESPACE__ . "\\AttributeFormatter::$callbackMethod", $value);
        } else {
            LoggerService::log(Logger::ERROR, sprintf(
                'Unknown formatter (Method %s)',
                print_r(__NAMESPACE__ . "\\AttributeFormatter::$attrType", true)
            ));
            return;
        }

        return $value;
    }

    public static function pimCatalogMetric($values) {
        return apply_filters('ak/attribute/metric', (float)$values[0]['data']['amount'], $values[0]['data']);
    }

    public static function pimCatalogPriceCollection($values) {
        return apply_filters('ak/attribute/price', (float)$values[0]['data'][0]['amount'], $values[0]['data'][0]);
    }

    public static function pimCatalogMultiselect($values) {
        return apply_filters('ak/attribute/multiselect', self::pimCatalogSimpleselect($values), $values);
    }

    public static function pimCatalogBoolean($values) {
        return apply_filters('ak/attribute/boolean', $values[0]['data'], $values);
    }

    public static function pimCatalogDate($values) {
        return apply_filters('ak/attribute/date', $values[0]['data'], $values[0]);
    }

    public static function pimCatalogDam($values) {
        $datas = json_decode($values[0]['data']);
        return apply_filters('ak/attribute/dam', $datas, $values[0]);
    }

    public static function pimCatalogTextarea($values) {
        return apply_filters('ak/attribute/textarea', self::pimCatalogText($values), $values[0]);
    }

    public static function pimCatalogText($values) {
        return apply_filters('ak/attribute/text', $values[0]['data'], $values[0]);
    }

    public static function pimCatalogSimpleselect($values) {

        $datas = [];

        foreach ($values as $value) {
            $datas[] = $value['data'];
        }

        return apply_filters('ak/attribute/select', $datas, $values);
    }

    public static function getLocaleValue($values) {

        $outputValues = [];

        // @todo implement polylang
        $language = 'fr_FR';

        foreach ($values as $value) {
            if (is_null($value['locale']) || $language === $value['locale']) {
                $outputValues[] = $value;
            }
        }

        return $outputValues;
    }

}
