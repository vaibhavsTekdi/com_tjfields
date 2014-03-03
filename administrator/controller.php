<?php
/**
 * @version     1.0.0
 * @package     com_tjfields
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */


// No direct access
defined('_JEXEC') or die;

class TjfieldsController extends JControllerLegacy
{
	/**
	 * Method to display a view.
	 *
	 * @param	boolean			$cachable	If true, the view output will be cached
	 * @param	array			$urlparams	An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return	JController		This object to support chaining.
	 * @since	1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT.'/helpers/tjfields.php';

		$view		= JFactory::getApplication()->input->getCmd('view', 'fields');
        JFactory::getApplication()->input->set('view', $view);

		parent::display($cachable, $urlparams);

		return $this;
	}

	function test()
	{
		$TjfieldsHelper=new TjfieldsHelper();
		$data=array();
		$data['client']='com_jticketing.event';
		$data['content_id']=1;
		$data['fieldsvalue']=array();
			$data['fieldsvalue']['com_jticketing_Event_TestEvent']='aniket';
			$data['fieldsvalue']['com_jticketing_event_singleselect']=2;
		$data['user_id']=100;
		$TjfieldsHelper->saveFieldsValue($data);
	}

	function test1()
	{
		$TjfieldsHelper=new TjfieldsHelper();
		$data=array();
		$data['client']='com_jticketing.event';
		$data['content_id']=1;
		$data['user_id']=100;
		$TjfieldsHelper->FetchDatavalue($data);
	}


}
