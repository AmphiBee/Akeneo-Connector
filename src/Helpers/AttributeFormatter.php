<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Helpers;

use Monolog\Logger;
use Illuminate\Support\Str;
use AmphiBee\AkeneoConnector\Service\LoggerService;

/**
 * This file is part of the Amphibee package.
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee & tgeorgel
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @access     public
 */
class AttributeFormatter
{
    /**
     * Flatten multi dimentionnal arrays recursively.
     *
     * @param  array $array Multi-dimensional array
     * @return array Single-dimensional
     */
    public static function arrayFlatten($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::arrayFlatten($value));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Get values for the given Locale.
     *
     * @param array   $value     The attribute value(s)
     * @param array   $attrType  The attribute type
     * @param bool    $default   Weither it's the default locale, if so, take values where $locale === null
     *
     * @return mixed
     */
    public static function process($value, $attrType, $locale = '')
    {
        $callback = Str::camel($attrType);

        if (method_exists(static::class, $callback)) {
            $value = self::getLocaleValue($value, $locale);
            $value = call_user_func([static::class, $callback], $value);
        } else {
            LoggerService::log(Logger::ERROR, sprintf(
                'Unknown formatter (Method %s)',
                print_r(__NAMESPACE__ . "\\AttributeFormatter::$attrType", true)
            ));
            return;
        }

        return $value;
    }


    /**
     * Get values for the given Locale.
     *
     * @param array   $values   Api values, all locales mixed
     * @param string  $locale   The Locale to retreive
     * @param bool    $default  Weither it's the default locale, if so, take values where $locale === null
     *
     * @return array
     */
    public static function getLocaleValue(array $values, string $locale)
    {
        $output_values = [];

        foreach ($values as $value) {
            if (is_null($value['locale']) || $locale === $value['locale']) {
                $output_values[] = $value;
            }
        }

        return $output_values;
    }


    /**
     * formatters:
     */

    public static function pimCatalogMetric($values)
    {
        return apply_filters('ak/attribute/metric', (float)$values[0]['data']['amount'], $values[0]['data']);
    }

    public static function pimCatalogPriceCollection($values)
    {
        return apply_filters('ak/attribute/price', (float)$values[0]['data'][0]['amount'], $values[0]['data'][0]);
    }

    public static function pimCatalogMultiselect($values)
    {
        return apply_filters('ak/attribute/multiselect', self::pimCatalogSimpleselect($values), $values);
    }

    public static function pimCatalogBoolean($values)
    {
        return apply_filters('ak/attribute/boolean', $values[0]['data'], $values);
    }

    public static function pimCatalogDate($values)
    {
        return apply_filters('ak/attribute/date', $values[0]['data'], $values[0]);
    }

    public static function pimCatalogDam($values)
    {
        $datas = json_decode($values[0]['data']);
        return apply_filters('ak/attribute/dam', $datas, $values[0]);
    }

    public static function pimCatalogTextarea($values)
    {
        return apply_filters('ak/attribute/textarea', self::pimCatalogText($values), $values[0]);
    }

    public static function pimCatalogText($values)
    {
        return isset($values[0]['data']) ? apply_filters('ak/attribute/text', $values[0]['data'], $values[0]) : '';
    }

    public static function pimCatalogSimpleselect($values)
    {
        $datas = [];

        foreach ($values as $value) {
            $datas[] = $value['data'];
        }

        return apply_filters('ak/attribute/select', $datas, $values);
    }
}
