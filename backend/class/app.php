<?php

namespace codename\core\ui;

use codename\core\exception;
use codename\core\generator\urlGenerator;
use codename\core\generator\urlGeneratorInterface;
use codename\core\response\http;
use ReflectionException;

/**
 * [app description]
 * app override class for ui purposes
 * this class may not be initialized or run, at all.
 */
class app extends \codename\core\app
{
    /**
     * Exception thrown if you call a method in this overridden class
     * that shouldn't be called
     * @var string
     */
    public const EXCEPTION_CORE_UI_APP_ILLEGAL_CALL = 'EXCEPTION_CORE_UI_APP_ILLEGAL_CALL';
    /**
     * [protected description]
     * @var array
     */
    protected static array $requireJsAssets = [];
    /**
     * [protected description]
     * @var bool [type]
     */
    protected static bool $requireJsAdded = false;
    /**
     * [protected description]
     * @var null|urlGeneratorInterface
     */
    protected static ?urlGeneratorInterface $urlGenerator = null;

    /**
     * {@inheritDoc}
     * this class may not be constructed/initialized
     */
    public function __construct()
    {
        throw new exception(self::EXCEPTION_CORE_UI_APP_ILLEGAL_CALL, exception::$ERRORLEVEL_FATAL);
    }

    /**
     * [requireAsset description]
     * @param string $type [description]
     * @param array|string $asset [description]
     * @throws ReflectionException
     * @throws exception
     */
    public static function requireAsset(string $type, array|string $asset): void
    {
        $response = app::getResponse();
        if (!($response instanceof http)) {
            return;
        }

        if ($type === 'requirejs') {
            // add requireJS if not already added
            if (!self::$requireJsAdded) {
                $response->requireResource('js', '/assets/requirejs/require.js', 0);
                $response->requireResource('js', '/assets/require.config.js?t=' . time(), 1);
                self::$requireJsAdded = true;
            }

            if (is_array($asset)) {
                $assets = $asset;
            } else {
                $assets = [$asset];
            }

            foreach ($assets as $a) {
                if (!in_array($a, self::$requireJsAssets)) {
                    $response->requireResource('script', "require(['$a'])");
                    self::$requireJsAssets[] = $a;
                }
            }
        } elseif ($type === 'requirecss') {
            $type = 'css';
            if (is_array($asset)) {
                foreach ($asset as $a) {
                    $response->requireResource($type, $a);
                }
            } else {
                $response->requireResource($type, $asset);
            }
        } elseif (is_array($asset)) {
            // TODO: require each resource?
            foreach ($asset as $a) {
                $response->requireResource($type, $a);
            }
        } else {
            $response->requireResource($type, $asset);
        }
    }

    /**
     * [getUrlGenerator description]
     * @return urlGeneratorInterface [description]
     */
    public static function getUrlGenerator(): urlGeneratorInterface
    {
        if (self::$urlGenerator == null) {
            self::$urlGenerator = new urlGenerator();
        }
        return self::$urlGenerator;
    }

    /**
     * [setUrlGenerator description]
     * @param urlGeneratorInterface $generator [description]
     */
    public static function setUrlGenerator(urlGeneratorInterface $generator): void
    {
        self::$urlGenerator = $generator;
    }

    /**
     * returns the current server/FE endpoint
     * including Protocol and Port (on need)
     *
     * @return string [description]
     */
    public static function getCurrentServerEndpoint(): string
    {
        // url base prefix preparation
        //
        // vendors and services like AWS (especially ELBs)
        // also provide a non-standard header like X-Forwarded-Port
        // which is the same as with the protocol, but for ports
        //
        // NOTE: we handle X-Forwarded-Proto separately during request object creation (request\http)
        //
        $port = $_SERVER['HTTP_X_FORWARDED_PORT'] ?? $_SERVER['SERVER_PORT'] ?? null;
        $proto = (($_SERVER['HTTPS'] ?? null) === 'on') ? 'https' : 'http';
        $portSuffix = (($proto === 'https' && $port != 443) || ($proto === 'http' && $port != 80)) ? (':' . $port) : '';
        return $proto . '://' . $_SERVER['SERVER_NAME'] . $portSuffix;
    }

    /**
     * {@inheritDoc}
     * this class may not be run
     */
    public function run(): void
    {
        throw new exception(self::EXCEPTION_CORE_UI_APP_ILLEGAL_CALL, exception::$ERRORLEVEL_FATAL);
    }
}
