<?php defined('_JEXEC') or die;

// http://docs.joomla.org/Plugin/Events/System
// http://docs.joomla.org/J2.5:Creating_a_System_Plugin_to_augment_JRouter
// http://docs.joomla.org/J3.x:Creating_a_Plugin_for_Joomla
//~ 
//~ function pre($var) { return "<pre>".print_r($var,true)."</pre>"; }
//~ error_reporting(E_ALL&~E_NOTICE);
//~ ini_set('display_errors',1);

class PlgSystemMultiCampus extends JPlugin {
		
	public function __construct(&$subject, $config) {
		
		parent::__construct($subject, $config);

		//~ echo 'subject',pre($subject);
		//~ echo 'config',pre($config);
		//~ echo 'paramps',pre($this->params);
		//~ exit;		
	}

	// used to add build/parseroutes to the router and drop a cookie of the campus
	public function onAfterInitialise() {

		$app = JFactory::getApplication(); 
		$doc = JFactory::getDocument();
		
		if ($app->isAdmin()) return;
		
		if ($this->params->get('redirectPublished',0)) $this->redirectPublished();
		
		$router = $app->getRouter();
		$router->attachBuildRule(array($this, 'buildRule'));
		$router->attachParseRule(array($this, 'parseRule'));
		
		$campusconfig = $this->getCampusConfig();
		
		$multicampus = $app->input->post->getString('multicampus');
		if (empty($multicampus)) $multicampus = $app->input->get->getString('multicampus'); 
		
		if (!empty($multicampus)&&empty($campusconfig[$multicampus])) $multicampus = '';
		
		if (empty($multicampus)) {
			$uri = JUri::getInstance();
			$path = $uri->getPath();
			if (!empty($path)) {
				$segments = explode('/',$path);
				if (!empty($segments[1])&&!empty($campusconfig[$segments[1]])) {
					$multicampus = $segments[1];
				}
			}
		}
		
		if (empty($multicampus)||empty($campusconfig[$multicampus])) return;
			
		// set multicampus cookie
		$time = time() + 60*60*24*365; // expire in a year
		$conf = JFactory::getConfig();
		$domain = $conf->get('cookie_domain', '');
		$path = $conf->get('cookie_path', '/');
		$app->input->cookie->set('multicampus',$multicampus,$time,$path,$domain);
		
		// set multicampus css
		if ($this->params->get('hideShowCss',0)) {
			$doc->addStyleDeclaration("
				[class^='multicampusshow-']:not(.multicampusshow-".$multicampus."), 
				[class*=' multicampusshow=']:not(.multicampusshow-".$multicampus."),
				.multicampushide-".$multicampus." { 
					display: none !important; 
				}				 
			");			
		}
	}
	
	// adapts urls to be campus specific if necessary
	public function buildRule(&$router, &$uri) {

		//~ echo 'buildRulerouter',pre($router);
		//~ echo 'buildRuleuri',pre($uri);
		//~ exit;

		$app = JFactory::getApplication(); 

		if ($app->isAdmin()) return;

		$multicampus = $app->input->cookie->getString('multicampus'); // echo pre($multicampus);
		if (empty($multicampus)) return;

		JLoader::import('cms.application.helper');
		$urlmulticampus = JApplicationHelper::stringURLSafe($multicampus);
		if (empty($urlmulticampus)) return;

		$Itemid = $uri->getVar('Itemid');
		if (empty($Itemid)) return;

		$menu = $app->getMenu();
		$item = $menu->getItem($Itemid);
		if (empty($item)||empty($item->route)) return;

		// route matching or external urls with redirects -- joomla doesnt pass External Urls or Menu Item Aliases to this
		//~ $route = $item->type=='url' && JUri::isInternal($item->link) ? $item->link : $item->route; //
		
		$segments = explode('/',$item->route);	
		if ($urlmulticampus==$segments[0]) return;

		$items = $this->getItemsByRoute();
		if (empty($items)) return;

		$newroute = implode('/',array_merge(array($urlmulticampus),$segments));
		if (!isset($items[$newroute])) return;

		$uri->setVar('Itemid',$items[$newroute]->id);
		if (!empty($items[$newroute]->query)) {
			foreach ($items[$newroute]->query as $key => $val) $uri->setVar($key,$val);
		}
	}
	
	// not really used
	public function parseRule(&$router, &$uri) {
		
		//~ echo 'parseRulerouter',pre($router);
		//~ echo 'parseRuleuri',pre($uri);
		//~ exit;
		
		$vars = array(); return $vars;
		
		$app = JFactory::getApplication(); 
		
		if ($app->isAdmin()) return $vars;

		$multicampus = $app->input->cookie->getString('multicampus');

		return $vars;
	}
	
	protected static $itemsbyroute;
	public function getItemsByRoute() {
		
		if (isset(static::$itemsbyroute)) return static::$itemsbyroute;
		static::$itemsbyroute = array();
		
		$menu = JMenu::getInstance('site');
		$items = $menu->getItems(array(),array());
		if (empty($items)) return static::$itemsbyroute;
		
		foreach ($items as $item) static::$itemsbyroute[$item->route] = $item;
			
		return static::$itemsbyroute;
	}
	
	protected static $campusconfig;
	public function getCampusConfig() {
		
		if (isset(static::$campusconfig)) return static::$campusconfig;
		static::$campusconfig = array();
		
		$campuses = $this->params->get('campuses');
		if (empty($campuses)) return static::$campusconfig;
		
		$rows = preg_split('/[\r\n]+/',$campuses);
		if (empty($rows)) return static::$campusconfig;
		
		foreach ($rows as $row) {
			if (empty($row)||strpos($row,'=')<=0) continue;
			$cols = explode('=',$row);
			static::$campusconfig[trim($cols[0])] = trim($cols[1]);
		}
			
		return static::$campusconfig;		
	}
	
	public function redirectPublished() {
		
		// Get the application object.
		$app = JFactory::getApplication();
		
		// Get the full current URI.
		$uri = JUri::getInstance();
		$current = rawurldecode($uri->toString(array('scheme', 'host', 'port', 'path', 'query', 'fragment')));

		// See if the current url exists in the database as a redirect.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('new_url'))
			->select($db->quoteName('published'))
			->from($db->quoteName('#__redirect_links'))
			->where($db->quoteName('old_url') . ' = ' . $db->quote($current));
		$db->setQuery($query, 0, 1);
		$link = $db->loadObject();

		// If a redirect exists and is published, permanently redirect.
		if ($link and ($link->published == 1)) {
			$app->redirect($link->new_url, true);
		}
	}
}

/* 
JUri Object
(
    [uri:protected] => index.php?Itemid=708&option=com_content&view=article&id=229
    [scheme:protected] => 
    [host:protected] => 
    [port:protected] => 
    [user:protected] => 
    [pass:protected] => 
    [path:protected] => index.php
    [query:protected] => Itemid=708&option=com_content&view=article&id=229
    [fragment:protected] => 
    [vars:protected] => Array
        (
            [Itemid] => 708
            [option] => com_content
            [view] => article
            [id] => 229
        )

)
*/
