<?php
/**
 * Created by PhpStorm.
 * User: Mikhail
 * Date: 19.03.2016
 * Time: 16:56
 */

namespace AssetCombiner;

use yii\base\Behavior;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\web\AssetBundle;
use yii\web\View;

/**
 * Class AssetCombinerBehavior
 * @package AssetCombiner
 *
 * @property View $owner
 */
class AssetCombinerBehavior extends Behavior
{

    use AssetCombinerTrait;
    /**
     * @var boolean
     */
    public $enabled = true;

    /**
     * @var string[]
     */
    public $exclude = [];

    /**
     * @var AssetBundle[]
     */
    protected $bundles = [];

    /**
     * Тест ассета на список исключений
     *
     * @param string|AssetBundle& $bundle
     * @return boolean
     */
    protected function isExcluded($bundle)
    {
        if(is_string($bundle) && class_exists($bundle, true))
        {
            $_bundle = \Yii::createObject($bundle);
            if(!$_bundle instanceof AssetBundle)
            {
                throw new \Exception('Invalid AssetBundle class name in depends found!');
            }
        }
        else
        {
            $_bundle = $bundle;
        }

        return in_array(get_class($_bundle), $this->exclude)
            || ArrayHelper::getValue($_bundle->publishOptions, 'monolith', 0) === false;
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            View::EVENT_END_BODY => 'combineBundles',
        ];
    }

    /**
     * @param Event $event
     */
    public function combineBundles(Event $event)
    {
        if (!$this->enabled)
        {
            return;
        }

        $token = 'Combine bundles for page';
        \Yii::beginProfile($token, __METHOD__);

        $this->bundles = $this->owner->assetBundles;
        $this->setAssetManager($this->owner->getAssetManager());

        $_have_some_monolith = false;

        // Assemble monolith assets
        foreach ($this->bundles as $name => $bundle)
        {
            // If this is monolith bundle
            if (ArrayHelper::getValue($bundle->publishOptions, 'monolith', false) && !$this->isExcluded($bundle)
            )
            {
                $_have_some_monolith = true;

                // If it already processed and have no dependency
                if (empty($bundle->depends) && ArrayHelper::getValue($bundle->publishOptions, 'accProcessed', false))
                {
                    $this->registerMonolith($bundle);
                }
                // Otherwise process it and assemble
                else
                {
                    $this->assembleMonolith([$name => $bundle], $bundle->jsOptions, $bundle->cssOptions);
                }
            }
        }

        // Assemble rest of the assets

        if ($_have_some_monolith)
        {
            $this->assembleMonolith($this->bundles);
            $this->owner->assetBundles = $this->bundles;
        }

        \Yii::endProfile($token, __METHOD__);
    }

    /**
     * @param AssetBundle $bundle
     */
    public function registerMonolith($bundle)
    {
        if ($this->isExcluded($bundle))
        {
            return;
        }

        // Remove bundle and its dependencies from list
        unset($this->bundles[$bundle->className()]);

        if (!empty($bundle->publishOptions['accIncluded']))
        {
            foreach ($bundle->publishOptions['accIncluded'] as $name)
            {
                if (isset($this->bundles[$name]))
                {
                    unset($this->bundles[$name]);
                }
            }
        }
        // Register files
        foreach ($bundle->js as $filename)
        {
            $this->owner->registerJsFile($bundle->baseUrl . '/' . $filename, $bundle->jsOptions);
        }

        foreach ($bundle->css as $filename)
        {
            $this->owner->registerCssFile($bundle->baseUrl . '/' . $filename, $bundle->cssOptions);
        }
    }

    /**
     * @param AssetBundle[] $bundles
     * @param array $jsOptions
     * @param array $cssOptions
     */
    public function assembleMonolith($bundles, $jsOptions = [], $cssOptions = [])
    {
        $files = [
            'js'      => [],
            'css'     => [],
            'jsHash'  => '',
            'cssHash' => '',
        ];

        foreach ($bundles as $name => $bundle)
        {
            if ($this->isExcluded($bundle))
            {
                continue;
            }

            $this->collectFiles($name, $files);
        }

        if (!empty($files['js']) && !empty($files['jsHash']))
        {
            $filename = $this->writeFiles($files, 'js');
            $this->owner->registerJsFile($this->outputUrl . '/' . $filename, $jsOptions);
        }

        if (!empty($files['css']) && !empty($files['cssHash']))
        {
            $filename = $this->writeFiles($files, 'css');
            $this->owner->registerCssFile($this->outputUrl . '/' . $filename, $cssOptions);
        }
    }

    /**
     * @param $name
     * @param $files
     */
    protected function collectFiles($name, &$files)
    {
        if (!isset($this->bundles[$name]))
        {
            return;
        }

        $bundle = $this->bundles[$name];

        if ($bundle)
        {
            foreach ($bundle->depends as $_bundle)
            {
                if(!$this->isExcluded($_bundle))
                {
                    $this->collectFiles($_bundle, $files);
                }
            }

            $this->collectAssetFiles($bundle, $files);
        }

        unset($this->bundles[$name]);
    }

    /**
     * Remove bundle and its dependencies from list
     * @param $name
     */
    protected function removeBundle($name)
    {
        if (!isset($this->bundles[$name]))
        {
            return;
        }

        $bundle = $this->bundles[$name];
        unset($this->bundles[$name]);

        foreach ($bundle->depends as $depend)
        {
            $this->removeBundle($depend);
        }

        if (!empty($bundle->publishOptions['accIncluded']))
        {
            foreach ($bundle->publishOptions['accIncluded'] as $name)
            {
                $this->removeBundle($name);
            }
        }
    }
}