<?php

declare(strict_types=1);

namespace app\components\view;

use app\components\Html;
use app\controllers\Base;

/**
 * Renders the PHP templates in views/ and plugins/<id>/views/. Inside a template, $this refers to this object.
 *
 * Templates get their data as variables only: the explicitly passed parameters, plus the "globals" of this view
 * (for a page request: $layout, $consultation and $site), which are available in every template, including partials.
 * Explicitly passed parameters take precedence over globals.
 *
 * View names:
 * - "@app/views/motion/view": relative to the application root
 * - "view", "_partial", "../shared/x": relative to the directory of the template currently being rendered,
 *   or, for a top-level call, to the default view path (e.g. views/<controller id>/ for controllers)
 * The extension ".php" is added if the name has none.
 *
 * Within a full page, the main layout calls beginPage() / head() / endBody() / endPage(), so CSS and JavaScript of
 * asset bundles registered at any time during rendering end up at the right place in the page.
 */
final class View
{
    private const PLACEHOLDER_HEAD = '<![CDATA[ANTRAGSGRUEN-BLOCK-HEAD]]>';
    private const PLACEHOLDER_BODY_END = '<![CDATA[ANTRAGSGRUEN-BLOCK-BODY-END]]>';

    /** The page title, set by the content template, read by the layout */
    public ?string $title = null;

    /** @var string[] Files currently being rendered, innermost last; used to resolve relative view names */
    private array $renderStack = [];

    /** @var array<class-string<AssetBundle>, AssetBundle> */
    private array $assetBundles = [];

    /** @var array<array{file: string, params: array<string, mixed>}> Layouts opened by beginContent() */
    private array $contentLayouts = [];

    /**
     * @param array<string, mixed>|\Closure(): array<string, mixed> $globals Evaluated on each render if a closure,
     *        so templates always see the current state
     */
    public function __construct(
        private readonly array|\Closure $globals = [],
        private readonly ?string $defaultViewPath = null,
    ) {
    }

    /**
     * The view of the page currently being rendered, so partials rendered from outside of templates
     * (section types, forms, e-mails, plugins) get the same globals, and asset bundles end up on the page.
     * Outside of web page requests (console commands, background jobs), a new view without globals is returned.
     */
    public static function getCurrent(): View
    {
        $controller = \Yii::$app?->controller;
        if ($controller instanceof Base) {
            return $controller->getTemplateView();
        }

        return new View();
    }

    public static function getAppPath(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function render(string $view, array $params = []): string
    {
        return $this->renderFile($this->findViewFile($view), $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function renderFile(string $file, array $params = []): string
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException('The view file does not exist: ' . $file);
        }

        $this->renderStack[] = $file;
        $obInitialLevel = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        try {
            // A closure keeps the local variables of this method out of the template's scope
            (function (string $__file, array $__params): void {
                extract($__params, EXTR_OVERWRITE);
                unset($__params);
                require $__file;
            })->call($this, $file, array_merge($this->getGlobals(), $params));

            return (string)ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > $obInitialLevel) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        } finally {
            array_pop($this->renderStack);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getGlobals(): array
    {
        return ($this->globals instanceof \Closure ? ($this->globals)() : $this->globals);
    }

    public function findViewFile(string $view): string
    {
        if (str_starts_with($view, '@app/')) {
            $file = self::getAppPath() . DIRECTORY_SEPARATOR . substr($view, 5);
        } elseif (str_starts_with($view, '@') || str_starts_with($view, '/')) {
            throw new \InvalidArgumentException('Unsupported view name: ' . $view);
        } elseif (count($this->renderStack) > 0) {
            $file = dirname($this->renderStack[count($this->renderStack) - 1]) . DIRECTORY_SEPARATOR . $view;
        } elseif ($this->defaultViewPath !== null) {
            $file = $this->defaultViewPath . DIRECTORY_SEPARATOR . $view;
        } else {
            throw new \InvalidArgumentException('Unable to resolve the relative view name: ' . $view);
        }

        if (pathinfo($file, PATHINFO_EXTENSION) === '') {
            $file .= '.php';
        }

        return $file;
    }

    /**
     * Wraps everything rendered until endContent() into the given layout, which receives it as $content.
     * Used by layouts that extend other layouts (e.g. column2 extends main).
     *
     * @param array<string, mixed> $params
     */
    public function beginContent(string $layoutView, array $params = []): void
    {
        $this->contentLayouts[] = ['file' => $this->findViewFile($layoutView), 'params' => $params];
        ob_start();
        ob_implicit_flush(false);
    }

    public function endContent(): void
    {
        $layout = array_pop($this->contentLayouts);
        if ($layout === null) {
            throw new \LogicException('endContent() called without beginContent()');
        }
        $content = (string)ob_get_clean();
        echo $this->renderFile($layout['file'], array_merge($layout['params'], ['content' => $content]));
    }

    public function beginPage(): void
    {
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Marks the position for the CSS (and head JavaScript) of registered asset bundles.
     */
    public function head(): void
    {
        echo self::PLACEHOLDER_HEAD;
    }

    /**
     * Marks the position for the JavaScript of registered asset bundles; to be called right before </body>.
     */
    public function endBody(): void
    {
        echo self::PLACEHOLDER_BODY_END;
    }

    public function endPage(): void
    {
        $content = (string)ob_get_clean();

        $headTags = [];
        $bodyEndTags = [];
        foreach ($this->assetBundles as $bundle) {
            foreach ($bundle->css as $file) {
                $headTags[] = Html::tag('link', '', ['href' => $bundle->getFileUrl($file), 'rel' => 'stylesheet']);
            }
            foreach ($bundle->js as $file) {
                $bodyEndTags[] = Html::tag('script', '', ['src' => $bundle->getFileUrl($file)]);
            }
        }

        echo strtr($content, [
            self::PLACEHOLDER_HEAD => implode("\n", $headTags),
            self::PLACEHOLDER_BODY_END => implode("\n", $bodyEndTags),
        ]);
    }

    /**
     * Publishes the bundle's files and includes its CSS / JavaScript in the page (see head() / endBody()).
     * Registering a bundle multiple times has no further effect.
     *
     * @template T of AssetBundle
     * @param class-string<T> $bundleClass
     * @return T
     */
    public function registerAssetBundle(string $bundleClass): AssetBundle
    {
        if (!isset($this->assetBundles[$bundleClass])) {
            $this->assetBundles[$bundleClass] = new $bundleClass();
        }

        /** @var T $bundle */
        $bundle = $this->assetBundles[$bundleClass];

        return $bundle;
    }
}
