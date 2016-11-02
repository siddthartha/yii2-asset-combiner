# Asset combiner for Yii 2

Yii 2 extension to compress and concatenate assets

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ composer require siddthartha/yii2-asset-combiner
```

or add

```
"siddthartha/yii2-asset-combiner": "*"
```

to the `require` section of your `composer.json` file.

## Конфигурация 

```php
        'view' => [
            // asset combiner config
            'class' => yii\web\View::className(),
            'as assetCombiner' => [
                'class' => \AssetCombiner\AssetCombinerBehavior::className(),

                // вкл-выкл, можно мерджить в конфигах в зависимости от environment dev, master, local
                'enabled' => true,

                // исключения из компиляции
                // можно указывать здесь (вендорные ассеты), а можно в конкретном ассете если наш
                // publishOptions = [ 'monolith' => false ] // принудительно выкл
                'exclude' => [
                ],
                /**/
                'filterJs' => [
                    'class' => \AssetCombiner\filters\UglifyJsFilter::className(),
                    'sourceMap' => false,
                    'compress' => false,
                    'mangle' => false,
                    'beautify' => true,
                    'keepFunctionNames' => true,
                    'keepComments' => true,
                ],
                /**/
                'filterCss' => [
                    'class' => \AssetCombiner\filters\UglifyCssFilter::className(),
                    'sort' => true,
                ],
                /**/
            ],
            // end of asset combiner config
            ...
        ],
```

### Для работы фильтров ### 

`sudo npm -g install uglifyjs`
`sudo npm -g install uglifycss`
