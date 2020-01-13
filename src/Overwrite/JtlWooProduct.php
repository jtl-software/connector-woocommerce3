<?php
namespace JtlWooCommerceConnector\Overwrite;

/**
 * Class JtlWooProduct
 * @package JtlWooCommerceConnector\Overwrite
 */
class JtlWooProduct extends \WC_Product
{
    /**
     * Logic is overwritten because of WP_Product apply_changes method bug with array_replace_recursive
     */
    function apply_changes()
    {
        $this->data = $this->arrayReplaceRecursive($this->data, $this->changes);
        $this->changes = array();
    }

    /**
     * @param $array1
     * @param $array2
     * @return mixed
     */
    function arrayReplaceRecursive($array1, $array2)
    {
        foreach ($array1 as $key => $value) {
            if (isset($array2[$key])) {
                if (is_array($array1[$key]) && is_array($array2[$key]) && !empty($array2[$key])) {
                    $array1[$key] = $this->arrayReplaceRecursive($array1[$key], $array2[$key]);
                } else {
                    $array1[$key] = $array2[$key];
                }
            }
        }
        return $array1;
    }
}