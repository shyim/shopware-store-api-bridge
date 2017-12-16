<?php

namespace ShyimStoreApi\Components;

/**
 * Class Helper
 * @package ShyimStoreApi\Components
 */
class Helper
{
    /**
     * @param array $array1
     * @param array $array2
     * @param string $column
     * @return array
     */
    public static function mergeArray(array $array1, array $array2, string $column)
    {
        foreach ($array2 as $itemOfArray2) {
            $found = false;
            foreach ($array1 as $itemOfArray1) {
                if ($itemOfArray1[$column] === $itemOfArray2[$column]) {
                    $found = true;
                }
            }

            if (!$found) {
                $array1[] = $itemOfArray2;
            }
        }

        return $array1;
    }
}