<?php
/**
 * Created by PhpStorm.
 * User: Mikhail
 * Date: 20.03.2016
 * Time: 18:04
 */

namespace AssetCombiner\filters;

/**
 * Class UglifyJsFilter
 * @package AssetCombiner
 */
class UglifyJsFilter extends BaseFilter
{
    /**
     * @var string Path to UglifyJs
     */
    public $libPath = 'uglifyjs';

    /**
     * @var bool Add source map generation
     */
    public $sourceMap = false;

    /**
     * @var bool Add --compress flag
     */
    public $compress = false;

    /**
     * @var bool Add --mangle flag
     */
    public $mangle = false;

    /**
     * @var bool Add --beautify flag
     */
    public $beautify = false;

    /**
     * @var bool Add --beautify flag
     */
    public $keepFunctionNames = false;

    /**
     * @var type
     */
    public $keepComments = false;

    /**
     * @var string Add other options to command
     */
    public $options = false;

    /**
     * @inheritdoc
     */
    public function process($files, $output)
    {
        foreach ($files as $i => $file)
        {
            $files[$i] = escapeshellarg($file);
        }

        $cmd = $this->libPath . ' ' . implode(' ', $files) . ' -o ' . escapeshellarg($output);

        if ($this->sourceMap)
        {
            $prefix  = (int) substr_count(\Yii::getAlias('@webroot'), '/');
            $mapFile = escapeshellarg($output . '.map');
            $mapRoot = escapeshellarg(rtrim(\Yii::getAlias('@web'), '/') . '/');
            $mapUrl  = escapeshellarg(basename($output) . '.map');
            $cmd .= " -p $prefix --source-map $mapFile --source-map-root $mapRoot --source-map-url $mapUrl";
        }

        $cmd .= $this->compress ? ' --compress' : '';
        $cmd .= $this->mangle ? ' --mangle' : '';
        $cmd .= $this->beautify ? ' --beautify' : '';
        $cmd .= $this->keepFunctionNames ? ' --keep-fnames' : '';
        $cmd .= $this->keepComments ? ' --comments' : '';
        $cmd .= $this->options ? (' ' . $this->options) : '';

        shell_exec($cmd);

        if (!file_exists($output))
        {
            \Yii::error("Failed to process JS files by UglifyJs with command: $cmd", __METHOD__);
            return false;
        }

        return true;
    }
}