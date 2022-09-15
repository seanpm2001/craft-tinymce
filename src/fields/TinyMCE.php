<?php

/*
This class is based on the Redactor field class from the Redactor plugin version 3.0.2, by Pixel & Tonic, Inc.
https://github.com/craftcms/redactor/blob/3.0.2/src/Field.php
The Redactor plugin is released under the terms of the MIT License, a copy of which is included below.
https://github.com/craftcms/redactor/blob/3.0.2/LICENSE.md

The MIT License (MIT)

Copyright (c) Pixel & Tonic, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace spicyweb\tinymce\fields;

use Craft;
use craft\base\ElementInterface;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\Plugin as Commerce;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\htmlfield\HtmlField;
use craft\htmlfield\HtmlFieldData;
use craft\models\Section;
use spicyweb\tinymce\assets\FieldAsset;
use spicyweb\tinymce\assets\TinyMCEAsset;
use spicyweb\tinymce\Plugin;

/**
 * Class TinyMCE
 *
 * @package spicyweb\tinymce\fields
 * @author Spicy Web <plugins@spicyweb.com.au>
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 1.0.0
 */
class TinyMCE extends HtmlField
{
    /**
     * @var string|null The TinyMCE config file to use
     */
    public ?string $tinymceConfig = null;

    /**
     * @var string|array|null The volumes that should be available for Image selection.
     */
    public $availableVolumes = '*';

    /**
     * @var string|array|null The transforms available when selecting an image
     */
    public $availableTransforms = '*';

    /**
     * @var string The default transform to use.
     */
    public string $defaultTransform = '';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('tinymce', 'TinyMCE');
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): string
    {
        $volumeOptions = [];
        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            if ($volume->getFs()->hasUrls) {
                $volumeOptions[] = [
                    'label' => $volume->name,
                    'value' => $volume->uid,
                ];
            }
        }

        $transformOptions = [];
        foreach (Craft::$app->getImageTransforms()->getAllTransforms() as $transform) {
            $transformOptions[] = [
                'label' => $transform->name,
                'value' => $transform->uid,
            ];
        }

        return Craft::$app->getView()->renderTemplate('tinymce/_settings', [
            'field' => $this,
            'tinymceConfigOptions' => $this->configOptions('tinymce'),
            'purifierConfigOptions' => $this->configOptions('htmlpurifier'),
            'volumeOptions' => $volumeOptions,
            'transformOptions' => $transformOptions,
            'defaultTransformOptions' => array_merge([
                [
                    'label' => Craft::t('tinymce', 'No transform'),
                    'value' => null,
                ],
            ], $transformOptions),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(FieldAsset::class);

        $id = Html::id($this->handle);
        $sitesService = Craft::$app->getSites();
        $elementSite = $element ? $element->getSite() : $sitesService->getCurrentSite();
        $siteId = $element?->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
        $allSites = [];

        foreach ($sitesService->getAllSites(false) as $site) {
            $allSites[] = [
                'value' => (string)$site->id,
                'text' => $site->name,
            ];
        }

        $defaultTransform = '';

        if (!empty($this->defaultTransform) && $transform = Craft::$app->getImageTransforms()->getTransformByUid($this->defaultTransform)) {
            $defaultTransform = $transform->handle;
        }

        $apiKey = Plugin::$plugin->getSettings()->editorCloudApiKey;
        $editorConfig = [
            'skin' => $apiKey ? 'oxide' : 'craft',
        ];

        $language = Craft::$app->language;
        $translations = $this->_loadTranslations($language);

        $settings = [
            'id' => $view->namespaceInputId($id),
            'linkOptions' => $this->_getLinkOptions($element),
            'volumes' => $this->_getVolumeKeys(),
            'editorConfig' => $editorConfig + ($this->config('tinymce', $this->tinymceConfig) ?: []),
            'transforms' => $this->_getTransforms(),
            'defaultTransform' => $defaultTransform,
            'elementSiteId' => (string)$elementSite->id,
            'allSites' => $allSites,
            'direction' => $this->getOrientation($element),
            'language' => $language,
            'translations' => $translations,
        ];

        if ($apiKey) {
            $view->registerJsFile("https://cdn.tiny.cloud/1/{$apiKey}/tinymce/6/tinymce.min.js", [
                'referrerpolicy' => 'origin',
            ]);
        } else {
            $view->registerAssetBundle(TinyMCEAsset::class);
        }

        $view->registerAssetBundle(FieldAsset::class);
        $view->registerJs('TinyMCE.init(' . Json::encode($settings) . ');');
        $value = $this->prepValueForInput($value, $element);

        return implode('', [
            '<textarea id="' . $id . '" name="' . $this->handle . '" style="visibility: hidden; position: fixed; top: -9999px">',
                htmlentities($value, ENT_NOQUOTES, 'UTF-8'),
            '</textarea>',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return implode('', [
            '<div class="text">',
                ($this->prepValueForInput($value, $element) ?: '&nbsp;'),
            '</div>',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof HtmlFieldData) {
            $value = $value->getRawContent();
        }

        if ($this->removeEmptyTags) {
            $value = preg_replace('/<figure\s*><\/figure>/', '', $value);
        }

        return parent::serializeValue($value, $element);
    }

    private function _getLinkOptions(?ElementInterface $element = null): array
    {
        $pluginsService = Craft::$app->getPlugins();
        $options = [];

        $sectionSources = $this->_getSectionSources($element);
        $categorySources = $this->_getCategorySources($element);
        $volumeKeys = $this->_getVolumeKeys();

        if (!empty($sectionSources)) {
            $options[] = self::_option('Link to an entry', Entry::class, Entry::refHandle(), $sectionSources);
        }

        if (!empty($categorySources)) {
            $options[] = self::_option('Link to a category', Category::class, Category::refHandle(), $categorySources);
        }

        if (!empty($volumeKeys)) {
            $options[] = self::_option('Link to an asset', Asset::class, Asset::refHandle(), $volumeKeys);
        }

        if ($pluginsService->isPluginInstalled('commerce') && $pluginsService->isPluginEnabled('commerce')) {
            $productSources = $this->_getProductSources($element);

            if (!empty($productSources)) {
                $options[] = self::_option('Link to a product', Product::class, Product::refHandle(), $productSources);
                $options[] = self::_option('Link to a variant', Variant::class, Variant::refHandle(), $productSources);
            }
        }

        return $options;
    }

    private static function _option(string $optionTitle, string $elementType, string $refHandle, array $sources): array
    {
        return [
            'optionTitle' => Craft::t('tinymce', $optionTitle),
            'elementType' => $elementType,
            'refHandle' => $refHandle,
            'sources' => $sources,
        ];
    }

    private function _getSectionSources(?ElementInterface $element = null): array
    {
        $sources = [];
        $sections = Craft::$app->getSections()->getAllSections();
        $sites = Craft::$app->getSites()->getAllSites();
        $showSingles = false;

        foreach ($sections as $section) {
            if ($section->type === Section::TYPE_SINGLE) {
                $showSingles = true;
            } elseif ($element) {
                $sectionSiteSettings = $section->getSiteSettings();

                foreach ($sites as $site) {
                    if (isset($sectionSiteSettings[$site->id]) && $sectionSiteSettings[$site->id]->hasUrls) {
                        $sources[] = 'section:' . $section->uid;
                    }
                }
            }
        }

        if ($showSingles) {
            array_unshift($sources, 'singles');
        }

        if (!empty($sources)) {
            array_unshift($sources, '*');
        }

        return $sources;
    }

    private function _getCategorySources(?ElementInterface $element = null): array
    {
        return self::_sources($element, Craft::$app->getCategories()->getAllGroups(), 'group');
    }

    private function _getProductSources(?ElementInterface $element = null): array
    {
        return self::_sources($element, Commerce::getInstance()->getProductTypes()->getAllProductTypes(), 'productType');
    }

    private static function _sources(?ElementInterface $element, array $types, string $prefix): array
    {
        $sources = [];

        if ($element) {
            foreach ($types as $type) {
                $siteSettings = $type->getSiteSettings();

                if (isset($siteSettings[$element->siteId]) && $siteSettings[$element->siteId]->hasUrls) {
                    $sources[] = "$prefix:" . $type->uid;
                }
            }
        }

        return $sources;
    }

    private function _getVolumeKeys(): array
    {
        if (!$this->availableVolumes) {
            return [];
        }

        $criteria = ['parentId' => ':empty:'];

        $allVolumes = Craft::$app->getVolumes()->getAllVolumes();
        $allowedVolumes = [];
        $userService = Craft::$app->getUser();

        foreach ($allVolumes as $volume) {
            $allowedBySettings = $this->availableVolumes === '*' || (is_array($this->availableVolumes) && in_array($volume->uid, $this->availableVolumes));
            if ($allowedBySettings && $userService->checkPermission("viewAssets:$volume->uid")) {
                $allowedVolumes[] = 'volume:' . $volume->uid;
            }
        }

        return $allowedVolumes;
    }

    private function _getTransforms()
    {
        if (!$this->availableTransforms) {
            return [];
        }

        $transformList = [];

        foreach (Craft::$app->getImageTransforms()->getAllTransforms() as $transform) {
            if (!is_array($this->availableTransforms) || in_array($transform->uid, $this->availableTransforms, false)) {
                $transformList[] = [
                    'value' => $transform->handle,
                    'text' => $transform->name,
                ];
            }
        }

        return $transformList;
    }

    private function _loadTranslations(): array
    {
        // TODO: would be good to load translation packages
        $messages = [
            'Undo',
            'Redo',
            'Blocks',
            'Paragraph',
            'Heading 1',
            'Heading 2',
            'Heading 3',
            'Heading 4',
            'Heading 5',
            'Heading 6',
            'Preformatted',
            'Bold',
            'Italic',
            'Strikethrough',
            'Bullet list',
            'Numbered list',
            'Horizontal line',
            'Source code',
        ];
        $translations = [];

        foreach ($messages as $message) {
            $translations[$message] = Craft::t('tinymce', $message);
        }

        return $translations;
    }
}
