<?php
/**
 * @version     1.0.0
 * @package     com_tjfields
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - www.techjoomla.com
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Group controller class.
 */
class TjfieldsControllerGroup extends JControllerForm
{

    function __construct() {
        $this->view_list = 'groups';
        parent::__construct();
    }


	function save()
	{
		$input=JFactory::getApplication()->input;
		$post=$input->post;
		$model = $this->getModel('group');
		$if_saved=$model->save($post);
		if($if_saved)
			{
				$msg='asdasdasdasdasdasdasd';
				$link = JRoute::_('index.php?option=com_tjfields&view=groups&client='.$input->get('client','','STRING'),false);
			}
		else
			{
				$msg=JText::_('TJFIELDS_ERROR_MSG');
				$link = JRoute::_('index.php?option=com_tjfields&view=groups&client='.$input->get('client','','STRING'),false);
			}

		$this->setRedirect($link,$msg);
	}

	function apply()
	{
		$input=JFactory::getApplication()->input;
		$data=$input->post;
		$model = $this->getModel('group');
		$group_id=$model->save($data);
		if($group_id)
			{
				$msg='';
				$link = JRoute::_('index.php?option=com_tjfields&view=group&layout=edit&id='.$group_id.'&client='.$input->get('client','','STRING'),false);
			}
		else
			{
				$msg=JText::_('TJFIELDS_ERROR_MSG');
				$link = JRoute::_('index.php?option=com_tjfields&view=group&layout=edit&id='.$group_id.'&client='.$input->get('client','','STRING'),false);
			}
		$this->setRedirect($link,$msg);
	}

	function add()
	{
		$input=JFactory::getApplication()->input;
		//print_r($input->get('client','','STRING')); die('asdasd');
		$link = JRoute::_('index.php?option=com_tjfields&view=group&layout=edit&client='.$input->get('client','','STRING'),false);
		$this->setRedirect($link);
	}

function edit()
	{
		$cid   = $this->input->post->get('cid', array(), 'array');
		$recordId = (int) (count($cid) ? $cid[0] : $this->input->getInt('id'));
		$input=JFactory::getApplication()->input;
		$link = JRoute::_('index.php?option=com_tjfields&view=group&layout=edit&id='.$recordId.'&client='.$input->get('client','','STRING'),false);
		$this->setRedirect($link);
	}

	function cancel()
	{
		$input=JFactory::getApplication()->input;
		//print_r($input->get('client','','STRING')); die('asdasd');
		$link = JRoute::_('index.php?option=com_tjfields&view=groups&client='.$input->get('client','','STRING'),false);
		$this->setRedirect($link);
	}

}
