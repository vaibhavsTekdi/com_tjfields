<?php
/**
 * @package		JBolo
 * @version		$versionID$
 * @author		TechJoomla
 * @author mail	extensions@techjoomla.com
 * @website		http://techjoomla.com
 * @copyright	Copyright © 2009-2013 TechJoomla. All rights reserved.
 * @license		GNU General Public License version 2, or later
*/
//no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.filesystem.file');
jimport('joomla.html.parameter');
jimport( 'joomla.plugin.plugin' );

if(!defined('DS'))
{
	define('DS',DIRECTORY_SEPARATOR);
}

/*load language file for plugin */
$lang =  JFactory::getLanguage();
$lang->load('tjassetsloader', JPATH_ADMINISTRATOR);

class plgSystemtjassetsloader extends JPlugin
{
	var $_com_jbolo_installed = 0;

	var $_com_jlike_installed = 0;

	var $_com_quick2cart_installed = 0;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe.
	 * @param   array   $config    An optional associative array of configuration settings.
	 *
	 * @since   1.5
	 */
	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		$this->_com_jbolo_installed = 0;
		$this->_com_jlike_installed = 0;
		$this->_com_quick2cart_installed = 0;
		$this->_com_invitex_installed = 0;

		/* Check if JBOLO is installed*/
		if (JFile::exists(JPATH_ROOT .DS . 'components'. DS . 'com_jbolo' . DS . 'jbolo.php'))
		{
			if (JComponentHelper::isEnabled('com_jbolo', true))
			{
				$this->_com_jbolo_installed = 1;
			}
		}

		/* Check if JLike is installed*/
		if (JFile::exists(JPATH_ROOT . DS . 'components' . DS . 'com_jlike' . DS . 'jlike.php'))
		{
			if (JComponentHelper::isEnabled('com_jlike', true))
			{
				$this->_com_jlike_installed = 1;
			}
		}

		/* Check if JLike is installed*/
		if (JFile::exists(JPATH_ROOT . DS . 'components' . DS . 'com_quick2cart' . DS . 'quick2cart.php'))
		{
			if (JComponentHelper::isEnabled('com_quick2cart', true))
			{
				$this->_com_quick2cart_installed = 1;
			}
		}

		/* Check if JLike is installed*/
		if (JFile::exists(JPATH_ROOT . DS . 'components' . DS . 'com_invitex' . DS . 'invitex.php'))
		{
			if (JComponentHelper::isEnabled('com_invitex', true))
			{
				$this->_com_invitex_installed = 1;
			}
		}
	}

	function onAfterRoute()
	{
		$document = JFactory::getDocument();

		// Do not run in admin.
		$mainframe = JFactory::getApplication();

		if($mainframe->isAdmin())
		{
			//return false;
		}

		$fix_js = $this->params->get('fix_js');

		/* if Fix javascript errors parameter is set to NO */
		if(!$fix_js)
		{
			// Get all JS files array, load the important ones first.
			$tjjsFiles = $this->_getTechjoomlaJSArray($firstThingsFirst=1);

			/*Remove JS files if those files are already present in document*/
			$tjjsFiles = $this->remove_duplicate_files($tjjsFiles);

			if (!empty($tjjsFiles))
			{
				foreach ($tjjsFiles as $file)
				{
					if ($file[0] == '/')
					{
						$document->addScript(JUri::root(true) . $file);
					}
					else
					{
						$document->addScript(JUri::root(true) . '/'. $file);
					}
				}
			}
		}
	}

	function onAfterRender()
	{
		// Do not run in admin.
		$mainframe = JFactory::getApplication();

		if($mainframe->isAdmin())
		{
			//return false;
		}

		$dynamic_js_code = $jsScripts1 = '';

		$cssScripts = $jsScripts2 = array();

		if ($this->_com_jbolo_installed == 1)
		{
			/* validate User if JBOLO is installed*/
			if( $this->_validateUser())
			{
				//return false;

				// Get all CSS files array required for JBOLO.
				$cssFiles = $this->_getJboloCssArray();

				// Call CSS loader function.
				$this->_getCSSscripts($cssScripts, $cssFiles);
			}
		}

		if($this->_com_jlike_installed == 1)
		{
			$cssFiles = $this->_getJlikeCssArray();

			// Call CSS loader function.
			$this->_getCSSscripts($cssScripts, $cssFiles);
		}

		$cssFiles = $this->_getTJCssArray();

		// Call CSS loader function.
		$this->_getCSSscripts($cssScripts, $cssFiles);

		$fix_js = $this->params->get('fix_js');

		if($fix_js==1)
		{

			// Get first JS files.
			$jsFiles1 = $this->_getTechjoomlaJSArray($firstThingsFirst=1);

			/*Remove JS files if those files are alraedy present in document*/
			$jsFiles1=	$this->remove_duplicate_files($jsFiles1);


			// Call JS loader function.
			$this->_getJSscripts($jsScripts1, $jsFiles1);

			$jsScripts1 = implode("\n", $jsScripts1);
		}

		// Get other JS files.
		$jsFiles2 = $this->_getTechjoomlaJSArray($firstThingsFirst=0);

		/*Remove JS files if those files are alraedy present in document*/
		$jsFiles2=	$this->remove_duplicate_files($jsFiles2);

		// Call JS loader function.

		$this->_getJSscripts($jsScripts2, $jsFiles2);

		if($this->_com_jbolo_installed == 1)
		{
			if( $this->_validateUser())
			{
				// Get all dynamic JS code required for JBOLo.
				$dynamic_js_code = $this->_getJboloDynamicJs();
			}
		}

		// Insert all scripts into head tag.
		// Get page HTML.
		$body = JResponse::getBody();

		$includescripts = implode("\n", $cssScripts) .
						 $jsScripts1 .
						 $dynamic_js_code .
						 implode("\n", $jsScripts2);

		if( $this->_com_jbolo_installed == 1 )
		{
			if( $this->_validateUser())
			{
				// Get all jQuery chat templates reqyired for JBOLO.
				$jsChatTemplates = $this-> _getJboloJqueryChatTemplates();
				$includescripts .= $jsChatTemplates;
			}
		}

		if($fix_js==1)
		{
			// Push JS into head at start or end of head tag.
			if($this->params->get('headtag_position'))
			{
				$body = str_replace('<head>', '<head>' . $includescripts, $body);
			}
			else
			{
				$body = str_replace('</head>', $includescripts . '</head>', $body);
			}
		}
		else
		{
			$body = str_replace('</head>', $includescripts . '</head>', $body);
		}

		$html_code = '';
		if($this->_com_jbolo_installed == 1)
		{
			if( $this->_validateUser())
			{
				// Push jbolo HTML before closing body tag.
				$html_code = $this->_getJboloHtmlCode();
			}

		}

		$body = str_replace('</body>', $html_code . '</body>', $body);

		JResponse::setBody($body);

		return true;
	}

	function _validateUser()
	{
		// Load user helper if not loaded
		if (!class_exists('jbolousersHelper'))
		{
			//Helper file path
			$jbolousersHelperPath = JPATH_SITE.DS.'components'.DS.'com_jbolo'.DS.'helpers'.DS.'users.php';

			JLoader::register('jbolousersHelper', $jbolousersHelperPath);
			JLoader::load('jbolousersHelper');
		}

		// Do not run in admin.
		$mainframe = JFactory::getApplication();

		if ($mainframe->isAdmin())
		{
			//return false;
		}

		// Do not run in tmpl=component.
		$input = JFactory::getApplication()->input;
		$tmpl  = $input->get->get('tmpl','','string');

		if ($tmpl=='component')
		{
			return false;
		}

		$user = JFactory::getUser();

		// Do not run if user is not logged in.
		if (!$user->id)
		{
			return false;
		}

		// Check ACL for user to allow chat.
		if (!JFactory::getUser($user->id)->authorise('core.chat', 'com_jbolo'))
		{
			return false;
		}
		else
		{
			$jbolousersHelper = new jbolousersHelper();

			// Check if user opted out of group chat.
			$user_chat_pref = $jbolousersHelper->getUserChatSettings();

			if($user_chat_pref)
			{
				if ($user_chat_pref->state==-1)
				{
					return false;
				}
			}
		}

		return true;
	}

	function _getTJCssArray()
	{

		if(JVERSION < '3.0')
		{
			$cssfilesArray[] = 'media/techjoomla_strapper/css/bootstrap.min.css';
			$cssfilesArray[] = 'media/techjoomla_strapper/css/bootstrap-responsive.min.css';
		}
		else
		{
			$cssfilesArray[] = 'media/techjoomla_strapper/css/bootstrap.j3.css';
		}

		$cssfilesArray[] = 'media/techjoomla_strapper/css/strapper.css';

		return $cssfilesArray;
	}

	/*
	 * Get jbolo css files array.
	 *
	 * */
	function _getJboloCssArray()
	{
		$template = $this->_getJboloCurrentChatTheme();

		// Load all needed css in array.
		// Set jbolo theme css.
		$cssfilesArray[] = 'components/com_jbolo/jbolo/view/' . $template . '/style.css';

		// Load css for jquery ui.
		$cssfilesArray[] = 'components/com_jbolo/jbolo/assets/css/smoothness/jquery-ui-1.10.4.custom.min.css';

		return $cssfilesArray;
	}

	/*
	 * Get jbolo css files array.
	 *
	 * */
	function _getJlikeCssArray()
	{
		// Load css for jLike
		$cssfilesArray[] = 'components/com_jlike/assets/css/like.css';

		return $cssfilesArray;
	}

	/*
	 * Get jbolo js files array.
	 *
	 * */
	function _getTechjoomlaJSArray($firstThingsFirst=0)
	{
		$jsFilesArray = array();

		// These need to be loaded first before other JS files.
		if($firstThingsFirst)
		{
			if(JVERSION >= '3.0')
			{
				JHtml::_('bootstrap.framework');
				$jsFilesArray[] = 'media/jui/js/jquery.min.js';
				$jsFilesArray[] = 'media/jui/js/jquery-noconflict.js';
				$jsFilesArray[] = 'media/techjoomla_strapper/js/namespace.js';
				//$jsFilesArray[] = 'media/jui/js/jquery-migrate.js';
			}
			else
			{
				$jsFilesArray[] = 'media/techjoomla_strapper/js/akeebajq.js';
				$jsFilesArray[] = 'media/techjoomla_strapper/js/bootstrap.min.js';
			}

			if($this->_com_jbolo_installed == 1)
			{
				if( $this->_validateUser())
				{
					// Load quicksearch jquery plugin.
					$jsFilesArray[] = 'components/com_jbolo/jbolo/model/jquery.quicksearch.js';

					// Load jquery templating plugin.
					$jsFilesArray[] = 'components/com_jbolo/jbolo/model/jquery.tmpl.min.js';

					// Load modernizr js file.
					$jsFilesArray[] = 'components/com_jbolo/jbolo/model/modernizr-latest.js';

					// Load autocomplete jquery ui js.
					$jsFilesArray[] = 'components/com_jbolo/jbolo/assets/js/jquery-ui-1.10.4.custom.min.js';

					// Load jquery tooltip plugin.
					//$jsFilesArray[] = 'components/com_jbolo/jbolo/model/wtooltip.min.js';
				}
			}

			if($this->_com_jlike_installed	==	1)
			{
				// Load jlike plugin.
				$jsFilesArray[] = 'components/com_jlike/assets/scripts/jlike.js';
			}
		}
		else
		{
			if ($this->_com_jbolo_installed == 1)
			{
				if( $this->_validateUser())
				{
					// Load AjaxQ plugin
					$jsFilesArray[] = 'components/com_jbolo/jbolo/assets/js/ajaxq.js';

					// Load jbolo chat js.
					$jsFilesArray[] = 'components/com_jbolo/jbolo/model/jbolo_chat.js';
				}
			}

			if ($this->_com_quick2cart_installed == 1)
			{
				// Load Quick2cart helper class for js files.
				$path = JPATH_SITE . DS . "components" . DS . "com_quick2cart" . DS . "helper.php";
				$comquick2cartHelper = $this->_TjloadClass($path, 'comquick2cartHelper');

				if (method_exists($comquick2cartHelper, "getQuick2cartJsFiles"))
				{
					// Add component specific js file in provided array.
					$comquick2cartHelper->getQuick2cartJsFiles($jsFilesArray);
				}
			}

			if ($this->_com_invitex_installed == 1)
			{
				// Load Quick2cart helper class for js files.
				$path = JPATH_SITE . DS . "components" . DS . "com_invitex" . DS . "helper.php";
				$cominvitexHelper = $this->_TjloadClass($path, 'cominvitexHelper');

				if (method_exists($cominvitexHelper, "getInvitexJsFiles"))
				{
					// Add component specific js file in provided array.
					$cominvitexHelper->getInvitexJsFiles($jsFilesArray);
				}
			}
		}

		return $jsFilesArray;
	}

	/**
	 * This function to load class.
	 *
	 * @param   string  $path  Path of file.
	 * @param   string  $classname  Class Name to load.
	 *
	 * @return  Object of provided class.
	 */
	function _TjloadClass($path,$classname)
	{
		if(!class_exists($classname))
		{
			JLoader::register($classname,$path);
			JLoader::load($classname);
		}
		return new $classname();
	}
	/*
	 * Get current chattheme.
	 *
	 * */
	function _getJboloCurrentChatTheme()
	{
		$params = JComponentHelper::getParams('com_jbolo');

		// Get current template from config.
		$template = $params->get('template');

		// Get current template from cookie if available.
		if(isset($_COOKIE["jboloTheme"]))
		{
			$template = $_COOKIE["jboloTheme"];
		}

		return $template;
	}

	/*
	 * Get Jbolo jQuery chat templates.
	 *
	 * */
	function _getJboloJqueryChatTemplates()
	{
		$params        = JComponentHelper::getParams('com_jbolo');
		$show_activity = $params->get('show_activity');
		$template      = $this->_getJboloCurrentChatTheme();
		$jqct_code     = '';

		// Chat Message template.
		$jqct_code .= '<script id="cmessage" type="text/x-jquery-tmpl"></script>';

		// Tool tip template.
		//$jqct_code .= '<script id="tooltip_temp" type="text/x-jquery-tmpl"></script>';

		// Load chat message template into jqury template script tag.
		$jqct_code .= '<script id="chatmessage" type="text/x-jquery-tmpl">';
		$file       = JPATH_SITE . '/components/com_jbolo/jbolo/view/' . $template . '/chatmessage.htm';
		$jqct_code .= file_get_contents($file);
		$jqct_code .= '</script>';

		// Load outer list template into jqury template script tag.
		$jqct_code .= '<script id="outerlist" type="text/x-jquery-tmpl">';
		$file       = JPATH_SITE.'/components/com_jbolo/jbolo/view/' . $template . '/outerlist.htm';
		$jqct_code .= file_get_contents($file);
		$jqct_code .= '</script>';

		// Load userlist template into jqury template script tag.
		$jqct_code .= '<script id="listtemplate" type="text/x-jquery-tmpl">';
		$file       = JPATH_SITE . '/components/com_jbolo/jbolo/view/' . $template . '/list.htm';
		$jqct_code .= file_get_contents($file);
		$jqct_code .= '</script>';

		// Load logged_user template into jqury template script tag.
		$jqct_code .= '<script id="logged_user" type="text/x-jquery-tmpl">';
		$file       = JPATH_SITE . '/components/com_jbolo/jbolo/view/' . $template . '/logged_user.htm';
		$jqct_code .= file_get_contents($file);
		$jqct_code .= '</script>';

		// Load chat window template into jqury template script tag.
		$jqct_code .= '<script id="chatwindow" type="text/x-jquery-tmpl">';
		$file       = JPATH_SITE . '/components/com_jbolo/jbolo/view/' . $template . '/chatwindow.htm';
		$jqct_code .= file_get_contents($file);
		$jqct_code .= '</script>';

		// Load chat window template into jqury template script tag.
		$jqct_code .= '<script id="pdetails" type="text/x-jquery-tmpl">';
		$file       = JPATH_SITE . '/components/com_jbolo/jbolo/view/' . $template . '/pdetails.htm';
		$jqct_code .= file_get_contents($file);
		$jqct_code .= '</script>';

		if($show_activity)
		{
			// Load activity sream only for FB template.
			if($template=='facebook')
			{
				// Load activitystream template into jqury template script tag.
				$jqct_code .= '<script id="activitystream" type="text/x-jquery-tmpl">';
				$file       = JPATH_SITE . '/components/com_jbolo/jbolo/view/' . $template . '/activitystream.htm';
				$jqct_code .= file_get_contents($file);
				$jqct_code .= '</script>';
			}
		}

		return $jqct_code;
	}

	/*
	 * Get Jbolo HTML code.
	 *
	 * */
	function _getJboloHtmlCode()
	{
		$user          = JFactory::getUser();
		$params        = JComponentHelper::getParams('com_jbolo');
		$show_activity = $params->get('show_activity');
		$template      = $this->_getJboloCurrentChatTheme();
		$html_code     = '';

		if($user->id)
		{
			$html_code='
			<div style="display: none;">
				<div id="HTML5Audio" style="display: none;">
					<input id="audiofile" type="text" value="" style="display: none;"/>
				</div>
				<audio id="myaudio" src="' . JUri::base() . 'components/com_jbolo/jbolo/assets/sounds/sample.wav"  style="display: none;">
					<span id="OldSound"></span>
					<input type="button" value="Play Sound" onClick="LegacyPlaySound(\'LegacySound\')">
				</audio>
			</div>

			<button class="listopener" id="listopener">
				<div></div>
				<span id="onlineusers">' . JText::_('COM_JBOLO_CHAT') . '</span>
			</button>

			<div id="jbolouserlist_container" class="jbolouserlist_container" >';
				if($show_activity)
				{
					// Load activity sream only for FB template.
					if($template=='facebook')
					{
						$html_code .= '
						<div class="jboloactivity_container">
							<div id="jboloactivity" class="jboloactivity" ></div>
						</div>
						';
					}
				}
				$html_code .= '
				<div id="jbolouserlist" class="jbolouserlist" ></div>
			</div> <!-- end of <div class="jbolouserlist"> -->

			<div class="jbolochatwin" id="jbolochatwin" style="display:none">
			</div><!-- end of <div class="jbolochatwin"> -->
			';
		}
		else
		{
			$html_code = '<b>' . JText::_('COM_JBOLO_LOGIN_CHAT') . '</b>';
		}

		return $html_code;
	}

	/*
	 * Get dynamic JS code for Jbolo.
	 *
	 * */
	function _getJboloDynamicJs()
	{
		// Load user helper if not loaded
		if (!class_exists ('jbolousersHelper'))
		{
			//Helper file path
			$jbolousersHelperPath=JPATH_SITE.DS.'components'.DS.'com_jbolo'.DS.'helpers'.DS.'users.php';
			JLoader::register('jbolousersHelper',$jbolousersHelperPath);
			JLoader::load('jbolousersHelper');
		}

		$user                = JFactory::getUser();

		$jbolousersHelper    = new jbolousersHelper();
		$user_chat_pref      = $jbolousersHelper->getUserChatSettings();

		$params              = JComponentHelper::getParams('com_jbolo');
		$show_activity       = $params->get('show_activity');
		$maxChatUsers        = $params->get('maxChatUsers');
		$show_activity       = $params->get('show_activity');
		$polltime            = $params->get('polltime') * 1000;
		$jb_minChatHeartbeat = $params->get('polltime') * 1000;
		$jb_maxChatHeartbeat = $params->get('maxChatHeartbeat') * 1000;
		$template            = $this->_getJboloCurrentChatTheme();
		$dynamic_js          = '';

		$sendfile = $params->get('sendfile');
		// Check if user's user group has permission to send files.
		if (!JFactory::getUser($user->id)->authorise('core.send_file', 'com_jbolo'))
		{
			$sendfile = 0;
		}

		$groupchat = $params->get('groupchat');

		// Check if user's user group has permission for group chat.
		if (!JFactory::getUser($user->id)->authorise('core.group_chat', 'com_jbolo'))
		{
			$groupchat = 0;
		}
		else
		{
			// Check if user has opted out of group chat or not.
			if($user_chat_pref)
			{
				if ($user_chat_pref->state==0)
				{
					$groupchat = 0;
				}
			}
		}

		// Check that user group has a permission to add member in group chat.
		if (!JFactory::getUser($user->id)->authorise('core.add_member_in_group_chat', 'com_jbolo'))
		{
			$groupchat = 0;
		}

		$chathistory = $params->get('chathistory');

		//Check that user group has a permission to view chat history.
		if (!JFactory::getUser($user->id)->authorise('core.view_history', 'com_jbolo'))
		{
			$chathistory = 0;
		}

		$allow_user_blocking=0;
		$allow_user_blocking = $params->get('allow_user_blocking');

		if(empty($allow_user_blocking))
		{
			$allow_user_blocking=0;
		}

		$reqURI = JUri::root();
		// Added to fix non www to www redirects.
		// uncomment following comment to fix non www to www redirects.
		/*
		// If host have wwww, but Config doesn't.
		if(isset($_SERVER['HTTP_HOST']))
		{
			if((substr_count($_SERVER['HTTP_HOST'], "www.") != 0) && (substr_count($reqURI, "www.") == 0))
			{
				$reqURI = str_replace("://", "://www.", $reqURI);
			}
			else if((substr_count($_SERVER['HTTP_HOST'], "www.") == 0) && (substr_count($reqURI, "www.") != 0))
			{
				// host do not have 'www' but Config does
				$reqURI = str_replace("www.", "", $reqURI);
			}
		}
		*/

		$jbolousersHelper = new jbolousersHelper();
		// Load Jbolo language file.
		$lang = JFactory::getLanguage();
		$lang->load('com_jbolo', JPATH_SITE);

		$dynamic_js = '
		<script type="text/javascript">
			var site_link="' . $reqURI . '";
			var user_id=' . $user->id . ';
			var template="' . $template . '";
			var sendfile=' . $sendfile . ';
			var groupchat=' . $groupchat . ';
			var chathistory=' . $chathistory . ';
			var is_su=' . $jbolousersHelper->is_support_user() . ';
			var show_activity=' .$show_activity . ';
			var maxChatUsers=' . $maxChatUsers . ';
			var jb_minChatHeartbeat=' . $jb_minChatHeartbeat . ';
			var jb_maxChatHeartbeat=' . $jb_maxChatHeartbeat . ';
			var allow_user_blocking = ' . $allow_user_blocking . ';

			var me_avatar_url="'.JUri::root().'components/com_jbolo/jbolo/view/"+template+"/images/me_avatar_default.png";
			var avatar_url="'.JUri::root().'components/com_jbolo/jbolo/view/"+template+"/images/avatar_default.png";

			var jbolo_lang=new Array();
			jbolo_lang["COM_JBOLO_ME"]="' . JText::_('COM_JBOLO_ME') . '";
			jbolo_lang["COM_JBOLO_GC_MAX_USERS"]="' . JText::_('COM_JBOLO_GC_MAX_USERS') . '";
			jbolo_lang["COM_JBOLO_NO_USERS_ONLINE"]="' . JText::_('COM_JBOLO_NO_USERS_ONLINE') . '";
			jbolo_lang["COM_JBOLO_SAYS"]="' . JText::_('COM_JBOLO_SAYS') . '";
			jbolo_lang["COM_JBOLO_SET_STATUS"]="' . JText::_('COM_JBOLO_SET_STATUS') . '";
			jbolo_lang["COM_JBOLO_CHAT_WINDOW_EMPTY"]="' . JText::_('COM_JBOLO_CHAT_WINDOW_EMPTY') . '";
			jbolo_lang["COM_JBOLO_ADD_ACTIVITY_PROMPT_MSG"]="' . JText::_('COM_JBOLO_ADD_ACTIVITY_PROMPT_MSG') . '";
			jbolo_lang["COM_JBOLO_TICKED_ID_NO_SPACE"]="' . JText::_('COM_JBOLO_TICKED_ID_NO_SPACE') . '";
			jbolo_lang["COM_JBOLO_CHAT"]="' . JText::_('COM_JBOLO_CHAT') . '";
			jbolo_lang["COM_JBOLO_SEARCH_PEOPLE"]="' . JText::_('COM_JBOLO_SEARCH_PEOPLE') . '";
			jbolo_lang["COM_JBOLO_AVAILABLE"]="' . JText::_('COM_JBOLO_AVAILABLE') . '";
			jbolo_lang["COM_JBOLO_AWAY"]="' . JText::_('COM_JBOLO_AWAY') . '";
			jbolo_lang["COM_JBOLO_BUSY"]="' . JText::_('COM_JBOLO_BUSY') . '";
			jbolo_lang["COM_JBOLO_CLEAR_CUSTOM_MSGS"]="' . JText::_('COM_JBOLO_CLEAR_CUSTOM_MSGS') . '";
			jbolo_lang["COM_JBOLO_MINIMIZE"]="' . JText::_('COM_JBOLO_MINIMIZE') . '";
			jbolo_lang["COM_JBOLO_CLOSE"]="' . JText::_('COM_JBOLO_CLOSE') . '";
			jbolo_lang["COM_JBOLO_INVITE"]="' . JText::_('COM_JBOLO_INVITE') . '";
			jbolo_lang["COM_JBOLO_VIEW_HISTORY"]="' . JText::_('COM_JBOLO_VIEW_HISTORY') . '";
			jbolo_lang["COM_JBOLO_SEND_FILE"]="' . JText::_('COM_JBOLO_SEND_FILE') . '";
			jbolo_lang["COM_JBOLO_CLEAR_CONVERSATION"]="' . JText::_('COM_JBOLO_CLEAR_CONVERSATION') . '";
			jbolo_lang["COM_JBOLO_ADD_USERS"]="' . JText::_('COM_JBOLO_ADD_USERS') . '";
			jbolo_lang["COM_JBOLO_ADD_ACTIVITY_TO_TICKET"]="' . JText::_('COM_JBOLO_ADD_ACTIVITY_TO_TICKET') . '";
			jbolo_lang["COM_JBOLO_LEAVE_CHAT"]="' . JText::_('COM_JBOLO_LEAVE_CHAT') . '";
			jbolo_lang["COM_JBOLO_LEAVE_CHAT_CONFIRM_MSG"]="' . JText::_('COM_JBOLO_LEAVE_CHAT_CONFIRM_MSG') . '";
			jbolo_lang["COM_JBOLO_OFFLINE_MSG1"]="' . JText::_('COM_JBOLO_OFFLINE_MSG1') . '";
			jbolo_lang["COM_JBOLO_OFFLINE_MSG2"]="' . JText::_('COM_JBOLO_OFFLINE_MSG2') . '";
			jbolo_lang["COM_JBOLO_BLOCK_USER"]="' . JText::_('COM_JBOLO_BLOCK_USER') . '";
			jbolo_lang["COM_JBOLO_BLOCKED_USER_CONFIRM"]="' . JText::_('COM_JBOLO_BLOCKED_USER_CONFIRM') . '";
			techjoomla.jQuery(document).ready(function()
			{
				var cookie;
				var close_cookie;
				chat_window_function();
				outerlist_fun();
				list_opener();
				start_chat_session();
				setTimeout("poll_msg()",' . $polltime. ');
			});
		</script>';

		return $dynamic_js;
	}

	function _getCSSscripts(&$scriptList, $filenames)
	{
		//clear file status cache
		clearstatcache();
		$cssfile_path = JPATH_SITE.DS."components".DS."com_jbolo".DS."css".DS."jbolocss.php";
		//combine and minify css

		//echo $this->params->get('comb_mini');
		//var_dump(is_writable($cssfile_path)); die;

		if($this->params->get('comb_mini') && is_writable($cssfile_path))
		{

			//$sitepath=JPATH_SITE;
			$sitepath=JPATH_SITE.'/';
			foreach($filenames as $file){
				//$css_script[]="include('".$sitepath."/components/com_jbolo/css/".$file."');";
				$css_script[]="include('".$sitepath.$file."');";
			}
			$css_script=implode("\n",$css_script);
			$cssfile_path=JPATH_SITE.DS."components".DS."com_jbolo".DS."css".DS."jbolocss.php";
			$cssgzip='header("Content-type: text/css");
				ob_start("compress");
				function compress($buffer){
					/* remove comments */
					$buffer = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!", "", $buffer);
					/* remove tabs, spaces, newlines, etc. */
					$buffer = str_replace(array("\r\n", "\r", "\n", "\t", "  ", "    ", "    "), "", $buffer);
					return $buffer;
				}';

			$data="<?php ".$cssgzip ."\n".$css_script."\n ob_end_flush();?>";
			if(JFile::write($cssfile_path, $data)){
				$scriptList[]='<link rel="stylesheet" href="'.JUri::root(true).'/components/com_jbolo/css/jbolocss.php" type="text/css" />';
			}
			else{
				foreach($filenames as $file){
					//$scriptList[]='<link rel="stylesheet" href="'.JUri::root(true).'/components/com_jbolo/css/'.$file.'" type="text/css" />';
					$scriptList[]='<link rel="stylesheet" href="'.JUri::root(true).'/'.$file.'" type="text/css" />';
				}
			}
		}
		else{
			foreach($filenames as $file){
				//$scriptList[]='<link rel="stylesheet" href="'.JUri::root(true).'components/com_jbolo/css/'.$file.'" type="text/css" />';
				$scriptList[]='<link rel="stylesheet" href="'.JUri::root(true).'/'.$file.'" type="text/css" />';
			}
		}
		//die("hrr");
	}

	function _getJSscripts(&$scriptList, $filenames)
	{
		//clear file status cache
		clearstatcache();
		$jsfile_path = JPATH_SITE.DS."components".DS."com_jbolo".DS."js".DS."jbolojs.php";
		//combine and minify js


		if($this->params->get('comb_mini') && is_writable($jsfile_path) && $this->_com_jbolo_installed)
		{
			if( $this->_validateUser())
			{
				//$sitepath= JPATH_SITE;
				$sitepath=JPATH_SITE.'/';
				foreach($filenames as $file){
					if($file[0] == '/'){
						//$js_script[] = "include('".$sitepath."/components/com_jbolo".$file."');";
						$js_script[] = "include('".$sitepath.$file."');";
					}
					else{
						//$js_script[] = "include('".$sitepath."/components/com_jbolo/js/".$file."');";
						$js_script[] = "include('".$sitepath.$file."');";
					}
				}//end foreach
				//$js_script[] = "include('".JRoute::_('index.php?option=com_jbolo&view=js&format=raw')."');";
				$js_script = implode("\n",$js_script);

				$jsgzip='header("Content-type: text/javascript;");
					ob_start("compress");
					function compress($buffer){
						/* remove comments */
						$buffer = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!", "", $buffer);
						/* remove tabs, spaces, newlines, etc. */
						$buffer = str_replace(array("\r\n", "\r", "\n", "\t", "  ", "    ", "    "), "", $buffer);
						return $buffer;
					}';

				$data = "<?php ".$jsgzip ."\n".$js_script."\n ob_end_flush();?>";
				if(JFile::write($jsfile_path, $data)){
					$scriptList[]='<script type="text/javascript" src="'.JUri::root(true).'/components/com_jbolo/js/jbolojs.php"> </script>';
				}
				else{
					foreach($filenames as $file){
						if($file[0] == '/'){
							//$scriptList[]='<script type="text/javascript" src="'.JUri::root(true).'/components/com_jbolo'.$file.'"> </script>';
							$scriptList[]='<script type="text/javascript" src="'.JUri::root(true).'/'.$file.'"> </script>';
						}
						else{
							//$scriptList[]='<script type="text/javascript" src="'.JUri::root(true).'/components/com_jbolo/js/'.$file.'"> </script>';
							$scriptList[]='<script type="text/javascript" src="'.JUri::root(true).'/'.$file.'"> </script>';
						}
					}//end foreach
				}
			}
		}//end if
		else{
			foreach($filenames as $file){
				if($file[0] == '/'){
					//$scriptList[]='<script type="text/javascript" src="'.JUri::root(true).'/components/com_jbolo'.$file.'"> </script>';
					$scriptList[]='<script type="text/javascript" src="'.JUri::root(true).'/'.$file.'"> </script>';
				}
				else{
					//$scriptList[]='<script type="text/javascript" src="'.JUri::root(true).'/components/com_jbolo/js/'.$file.'"> </script>';
					$scriptList[]='<script type="text/javascript" src="'.JUri::root(true).'/'.$file.'"> </script>';
				}
			}
		}
	}//end function

	/*Remove JS files from JSFILES array if those files are alraedy present in document*/
	function remove_duplicate_files($assetsarray)
	{
		$doc = JFactory::getDocument();
		$flg=0;

		$notToRemoveDuplicate = 0;

		// Get js load option value
		$force_js_load = $this->params->get('force_js_load');

		foreach($assetsarray as $key=>$file)
		{

			if($file[0] == '/')
			{
				$assets_name_relative	=	JUri::root(true) . $file;
				$assets_name_absolute	=	JUri::root() . $file;
			}
			else
			{
				$assets_name_relative 	=	JUri::root(true) . '/'. $file;
				$assets_name_absolute	=	JUri::root() . '/'. $file;
			}

			if($force_js_load)
			{
				$notToRemoveDuplicate = strrpos($file, 'jquery.min.js');
			}

			//not to remove duplicate jquery.min.js
			if($notToRemoveDuplicate == 0 OR $notToRemoveDuplicate === false)
			{
				if(array_key_exists($assets_name_relative, $doc->_scripts) )
				{
					unset($assetsarray[$key]);
				}
				if(array_key_exists($assets_name_absolute, $doc->_scripts) )
				{
					unset($assetsarray[$key]);
				}
			}

		}

		return $assetsarray;
	}
}
