<?php

namespace codename\core\ui;

use codename\core\app;
use codename\core\config;
use codename\core\config\json;
use codename\core\exception;
use ReflectionException;

/**
 * Here we have some frontend helpers
 * @package core-ui
 * @since 2016-02-11
 * Moved to core-ui at 2020-04-21
 */
class frontend
{
    /**
     * There are no groups for navigation buttons available at all
     * @var string
     */
    public const EXCEPTION_GETGROUP_NOGROUPSAVAILABLE = 'EXCEPTION_GETGROUP_NOGROUPSAVAILABLE';

    /**
     * The desired navigation group cannot be found.
     * @var string
     */
    public const EXCEPTION_GETGROUP_GROUPNOTFOUND = 'EXCEPTION_GETGROUP_GROUPNOTFOUND';

    /**
     * Returns the navigation HTML code for the application
     * @param string $key
     * @return string
     * @throws ReflectionException
     * @throws exception
     */
    public function outputNavigation(string $key): string
    {
        $output = '';
        $config = $this->getNavigation();

        if (!$config->exists($key)) {
            return $output;
        }

        foreach ($config->get($key) as $element) {
            if ($element['type'] == 'group') {
                $output .= $this->parseGroup($element);
                continue;
            } elseif ($element['type'] == 'iframe') {
                $output .= $this->parseIframe($element);
                continue;
            }
            $output .= $this->parseLink($element);
        }

        return $output;
    }

    /**
     * Returns navigation config nested in an object of type \codename\core\config
     * @return config
     */
    protected function getNavigation(): config
    {
        return new json('config/navigation.json');
    }

    /**
     * Parses a navigation group
     * @param array $group
     * @return string
     * @throws ReflectionException
     * @throws exception
     */
    protected function parseGroup(array $group): string
    {
        //
        // Evaluate context permissions
        //
        if ($group['context'] ?? false) {
            $allowedContextGroup = app::getConfig()->get('context>' . $group['context'] . '>_security>group');
            if ($allowedContextGroup) {
                if (!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedContextGroup))) {
                    return '';
                }
            }
        }

        //
        // Evaluate view permissions
        //
        if ($group['view'] ?? false) {
            $allowedViewGroup = app::getConfig()->get('context>' . $group['context'] . '>view>' . $group['view'] . '>_security>group');
            if ($allowedViewGroup) {
                if (!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedViewGroup))) {
                    return '';
                }
            }
        }

        $filteredChildren = [];
        foreach ($group['children'] as $key => $child) {
            //
            // Evaluate context permissions
            //
            $allowedContextGroup = app::getConfig()->get('context>' . $child['context'] . '>_security>group');
            if ($allowedContextGroup) {
                if (!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedContextGroup))) {
                    continue;
                }
            }

            //
            // Evaluate view permissions
            //
            $allowedViewGroup = app::getConfig()->get('context>' . $child['context'] . '>view>' . $child['view'] . '>_security>group');
            if ($allowedViewGroup) {
                if (!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedViewGroup))) {
                    continue;
                }
            }

            $filteredChildren[$key] = $child;
        }

        $group['children'] = $filteredChildren;

        $templateengine = 'default';
        return app::getTemplateEngine($templateengine)->render('template/' . app::getRequest()->getData('template') . '/mainnavi/group', $group);
    }

    /**
     * Parses a dropdown containing an iframe
     * @param array $action
     * @return string
     * @throws ReflectionException
     * @throws exception
     */
    protected function parseIframe(array $action): string
    {
        $templateengine = 'default';
        return app::getTemplateEngine($templateengine)->render('template/' . app::getRequest()->getData('template') . '/mainnavi/iframe', $action);
    }

    /**
     * Parses a single link
     * @param array $link
     * @return string
     * @throws ReflectionException
     * @throws exception
     */
    protected function parseLink(array $link): string
    {
        //
        // Evaluate context permissions
        //
        $allowedContextGroup = app::getConfig()->get('context>' . $link['context'] . '>_security>group');
        if ($allowedContextGroup) {
            if (!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedContextGroup))) {
                return '';
            }
        }

        //
        // Evaluate view permissions
        //
        $allowedViewGroup = app::getConfig()->get('context>' . $link['context'] . '>view>' . $link['view'] . '>_security>group');
        if ($allowedViewGroup) {
            if (!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedViewGroup))) {
                return '';
            }
        }

        $templateengine = 'default';
        return app::getTemplateEngine($templateengine)->render('template/' . app::getRequest()->getData('template') . '/mainnavi/link', $link);
    }

    /**
     * Returns a complete configuration
     * @param string $groupname
     * @return string
     * @throws ReflectionException
     * @throws exception
     */
    public function getGroup(string $groupname): string
    {
        $data = $this->getNavigation();

        if (!$data->exists("group")) {
            throw new exception(self::EXCEPTION_GETGROUP_NOGROUPSAVAILABLE, exception::$ERRORLEVEL_ERROR, null);
        }

        if (!$data->exists("group>$groupname")) {
            throw new exception(self::EXCEPTION_GETGROUP_GROUPNOTFOUND, exception::$ERRORLEVEL_ERROR, $groupname);
        }

        $templateengine = 'default';
        return app::getTemplateEngine($templateengine)->render('template/' . app::getRequest()->getData('template') . '/groupnavi/group', $data->get("group>$groupname"));
    }
}
