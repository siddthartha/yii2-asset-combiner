<?php
/**
 * Created by PhpStorm.
 * User: Mikhail
 * Date: 20.03.2016
 * Time: 18:03
 */

namespace AssetCombiner\filters;

use yii\base\Object;

/**
 * Class BaseFilter
 * @package AssetCombiner
 */
abstract class BaseFilter extends Object
{

    /**
     * @param string[] $files
     * @param string $output
     * @return boolean
     */
    abstract public function process($files, $output);

    /**
     * Remove BOM signature from content of processed files
     *
     * @param string $content
     * @return string
     */
    static public function removeBOM($content)
    {
        return preg_replace('#^' . chr(0xEF) . chr(0xBB) . chr(0xBF) . '#', '', $content);
    }
}