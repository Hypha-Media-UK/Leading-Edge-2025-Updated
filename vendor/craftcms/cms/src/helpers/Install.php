<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;

/**
 * Install helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.2
 */
class Install
{
    /**
     * @var array|false|null
     * @see _primarySiteConfig()
     */
    private static array|null|false $_primarySiteConfig = null;

    /**
     * Returns the default site name for the installer.
     *
     * @return string|null
     */
    public static function defaultSiteName(): ?string
    {
        // Is there a project.yaml that defines a primary site?
        $primarySite = self::_primarySiteConfig();
        if (!empty($primarySite['name'])) {
            return $primarySite['name'];
        }

        // If this is a console request, give up now
        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest()) {
            return null;
        }

        // Come up with something based on the server name
        $server = $request->getServerName();
        $words = preg_split('/[\-_\.]+/', $server);
        array_pop($words);
        return implode(' ', array_map('ucfirst', $words));
    }

    /**
     * Returns the default site URL for the installer.
     *
     * @return string|null
     */
    public static function defaultSiteUrl(): ?string
    {
        // Is there a project.yaml that defines a primary site with a base URL?
        $primarySite = self::_primarySiteConfig();
        if (!empty($primarySite['baseUrl'])) {
            return $primarySite['baseUrl'];
        }

        // Is there a PRIMARY_SITE_URL environment variable set?
        if ($envValue = App::env('PRIMARY_SITE_URL')) {
            return $envValue;
        }

        // If this is a console request, give up now
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return null;
        }

        // Return the URL to the web directory
        return Craft::getAlias('@web');
    }

    /**
     * Returns the default site language for the installer.
     *
     * @return string
     */
    public static function defaultSiteLanguage(): string
    {
        // Is there a project.yaml that defines a primary site?
        $primarySite = self::_primarySiteConfig();
        if (!empty($primarySite['language'])) {
            return $primarySite['language'];
        }

        return 'en';
    }

    /**
     * Returns the primary site config from project.yaml, if it exists.
     *
     * @return array|null
     */
    private static function _primarySiteConfig(): ?array
    {
        if (!isset(self::$_primarySiteConfig)) {
            $sites = Craft::$app->getProjectConfig()->get('sites', true) ?? [];
            self::$_primarySiteConfig = ArrayHelper::firstWhere($sites, 'primary') ?? false;
        }

        return self::$_primarySiteConfig ?: null;
    }
}
