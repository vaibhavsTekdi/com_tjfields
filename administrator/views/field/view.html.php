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

jimport('joomla.application.component.view');

/**
 * View to edit
 */
class TjfieldsViewField extends JViewLegacy
{
	protected $state;
	protected $item;
	protected $form;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		$this->state	= $this->get('State');
		$this->item		= $this->get('Item');
		$this->form		= $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$user		= JFactory::getUser();
		$isNew		= ($this->item->id == 0);

        if (isset($this->item->checked_out))
        {
		    $checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
        }
        else
        {
            $checkedOut = false;
        }

		$canDo		= TjfieldsHelper::getActions();
		$input = JFactory::getApplication()->input;
		$client = $input->get('client');
		$component_title = JText::_('COM_TJFIELDS_TITLE_COMPONENT');

		if (!empty($client))
		{
			$client = explode('.', $client);

			if ($client['0'] == 'com_jticketing')
			{
				$component_title = JText::_('COM_JTICKETING_COMPONENT');
			}

		}

		if ($isNew)
		{
			$viewTitle = JText::_('COM_TJFIELDS_ADD_FIELD');
		}
		else
		{
			$viewTitle = JText::_('COM_TJFIELDS_EDIT_FIELD');
		}

		if (JVERSION >= '3.0')
		{
			JToolbarHelper::title($component_title. $viewTitle, 'pencil-2');
		}
		else
		{
			JToolbarHelper::title($component_title . $viewTitle, 'field.png');
		}

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit')||($canDo->get('core.create'))))
		{
			JToolBarHelper::apply('field.apply', 'JTOOLBAR_APPLY');
			JToolBarHelper::save('field.save', 'JTOOLBAR_SAVE');
		}
		if (!$checkedOut && ($canDo->get('core.create'))){
			JToolBarHelper::custom('field.newsave', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}
		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create')) {
			JToolBarHelper::custom('field.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
		}
		if (empty($this->item->id)) {
			JToolBarHelper::cancel('field.cancel', 'JTOOLBAR_CANCEL');
		}
		else {
			JToolBarHelper::cancel('field.cancel', 'JTOOLBAR_CLOSE');
		}

	}
}
