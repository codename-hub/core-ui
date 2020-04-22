<?php
namespace codename\core\ui;
use \codename\core\app;

/**
 * Here we have some frontend helpers
 * @package core-ui
 * @since 2016-02-11
 * Moved to core-ui at 2020-04-21
 */
class frontend {

    /**
     * There are no groups for navigation buttons available at all
     * @var string
     */
    CONST EXCEPTION_GETGROUP_NOGROUPSAVAILABLE = 'EXCEPTION_GETGROUP_NOGROUPSAVAILABLE';

    /**
     * The desired navigation group cannot be found.
     * @var string
     */
    CONST EXCEPTION_GETGROUP_GROUPNOTFOUND = 'EXCEPTION_GETGROUP_GROUPNOTFOUND';

    /**
     * Returns navigation config nested in an object of type \codename\core\config
     * @return \codename\core\config
     */
    protected function getNavigation() : \codename\core\config {
        return new \codename\core\config\json('config/navigation.json');
    }

    /**
     * Returns the navigation HTML code for the application
     * @param  string $key
     * @return string
     */
    public function outputNavigation(string $key) : string {
        $output = '';
        $config = $this->getNavigation();

        if(!$config->exists($key)) {
            return $output;
        }

        foreach($config->get($key) as $element) {
            if($element['type'] == 'group') {
                $output .= $this->parseGroup($element);
                continue;
            } else if($element['type'] == 'iframe') {
              $output .= $this->parseIframe($element);
              continue;
            }
            $output .= $this->parseLink($element);
        }

        return $output;
    }

    /**
     * Parses a navigation group
     * @param array $group
     * @return string
     */
    protected function parseGroup(array $group) : string {

        //
        // Evaulate context permissions
        //
        if($group['context']) {
          $allowedContextGroup = app::getConfig()->get('context>' . $group['context'] . '>_security>group');
          if($allowedContextGroup) {
            if(!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedContextGroups))) {
              return '';
            }
          }
        }

        //
        // Evaulate view permissions
        //
        if($group['view']) {
          $allowedViewGroup = app::getConfig()->get('context>' . $group['context'] . '>view>' .$group['view'] . '>_security>group');
          if($allowedViewGroup) {
            if(!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedViewGroup))) {
              return '';
            }
          }
        }

        $filteredChildren = [];
        foreach($group['children'] as $key => $child) {
          //
          // Evaulate context permissions
          //
          $allowedContextGroup = app::getConfig()->get('context>' . $child['context'] . '>_security>group');
          if($allowedContextGroup) {
            if(!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedContextGroups))) {
              continue;
            }
          }

          //
          // Evaulate view permissions
          //
          $allowedViewGroup = app::getConfig()->get('context>' . $child['context'] . '>view>' .$child['view'] . '>_security>group');
          if($allowedViewGroup) {
            if(!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedViewGroup))) {
              continue;
            }
          }

          $filteredChildren[$key] = $child;
        }

        $group['children'] = $filteredChildren;

        $templateengine = 'default';
        return app::getTemplateEngine($templateengine)->render('template/' . app::getRequest()->getData('template') . '/mainnavi/group', $group);
        // return app::parseFile(app::getInheritedPath('frontend/template/' . app::getRequest()->getData('template') . '/mainnavi/group.php'), $group);
    }

    /**
     * Parses a single link
     * @param array $link
     * @return string
     */
    protected function parseLink(array $link) : string {
      //
      // Evaulate context permissions
      //
      $allowedContextGroup = app::getConfig()->get('context>' . $link['context'] . '>_security>group');
      if($allowedContextGroup) {
        if(!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedContextGroups))) {
          return '';
        }
      }

      //
      // Evaulate view permissions
      //
      $allowedViewGroup = app::getConfig()->get('context>' . $link['context'] . '>view>' .$link['view'] . '>_security>group');
      if($allowedViewGroup) {
        if(!(app::getAuth()->isAuthenticated() && app::getAuth()->memberOf($allowedViewGroup))) {
          return '';
        }
      }

      $templateengine = 'default';
      return app::getTemplateEngine($templateengine)->render('template/' . app::getRequest()->getData('template') . '/mainnavi/link', $link);
        // return app::parseFile(app::getInheritedPath('frontend/template/' . app::getRequest()->getData('template') . '/mainnavi/link.php'), $link);
    }

    /**
     * Parses a dropdown containing an iframe
     * @param array     $action
     * @return string
     */
    protected function parseIframe(array $action) : string {
      $templateengine = 'default';
      return app::getTemplateEngine($templateengine)->render('template/' . app::getRequest()->getData('template') . '/mainnavi/iframe', $action);
        // return app::parseFile(app::getInheritedPath('frontend/template/' . app::getRequest()->getData('template') . '/mainnavi/iframe.php'), $action);
    }

    /**
     * Returns a complete configuration
     * @param string $groupname
     * @throws \codename\core\exception
     * @return string
     */
    public function getGroup(string $groupname) : string {
        $data = $this->getNavigation();

        if(!$data->exists("group")) {
            throw new \codename\core\exception(self::EXCEPTION_GETGROUP_NOGROUPSAVAILABLE, \codename\core\exception::$ERRORLEVEL_ERROR, null);
        }

        if(!$data->exists("group>{$groupname}")) {
            throw new \codename\core\exception(self::EXCEPTION_GETGROUP_GROUPNOTFOUND, \codename\core\exception::$ERRORLEVEL_ERROR, $groupname);
        }

        return app::getTemplateEngine($templateengine)->render('template/' . app::getRequest()->getData('template') . '/groupnavi/group', $data->get("group>{$groupname}"));
        // return app::parseFile(app::getInheritedPath('frontend/template/' . app::getRequest()->getData('template') . '/groupnavi/group.php'), $data->get("group>{$groupname}"));
    }

}
