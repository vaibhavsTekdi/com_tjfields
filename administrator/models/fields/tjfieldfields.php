<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJ-Fields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

/**
 * List of fields
 *
 * @since  1.3
 */
class JFormFieldtjfieldfields extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 *
	 * @since	1.3
	 */
	protected $type = 'tjfieldfields';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.3
	 */
	protected function getInput()
	{
		jimport('joomla.filesystem.file');

		$installUcm = 1;

		// Check if tjucm is installed
		if (JFile::exists(JPATH_ROOT . '/components/com_tjucm/tjucm.php'))
		{
			if (!JComponentHelper::isEnabled('com_tjucm', true))
			{
				$installUcm = 0;
			}
		}
		else
		{
			$installUcm = 0;
		}

		$options = array();
		$options[] = JHtml::_('select.option', 'text', JText::_('COM_TJFIELDS_TEXT'));
		$options[] = JHtml::_('select.option', 'radio', JText::_('COM_TJFIELDS_RADIO'));
		$options[] = JHtml::_('select.option', 'checkbox', JText::_('COM_TJFIELDS_CHECKBOX'));
		$options[] = JHtml::_('select.option', 'single_select', JText::_('COM_TJFIELDS_SINGLE_SELECT'));
		$options[] = JHtml::_('select.option', 'multi_select', JText::_('COM_TJFIELDS_MULTI_SELECT'));
		$options[] = JHtml::_('select.option', 'sql', JText::_('COM_TJFIELDS_SQL'));
		$options[] = JHtml::_('select.option', 'textarea', JText::_('COM_TJFIELDS_TEXTAREA'));
		$options[] = JHtml::_('select.option', 'calendar', JText::_('COM_TJFIELDS_CALENDAR'));
		$options[] = JHtml::_('select.option', 'editor', JText::_('COM_TJFIELDS_EDITOR'));
		$options[] = JHtml::_('select.option', 'email', JText::_('COM_TJFIELDS_EMAIL'));
		$options[] = JHtml::_('select.option', 'user', JText::_('COM_TJFIELDS_USERS'));
		$options[] = JHtml::_('select.option', 'file', JText::_('COM_TJFIELDS_FILE'));
		$options[] = JHtml::_('select.option', 'spacer', JText::_('COM_TJFIELDS_SPACER'));
		$options[] = JHtml::_('select.option', 'subform', JText::_('COM_TJFIELDS_SUBFORM'));

		if ($installUcm === 1)
		{
			$options[] = JHtml::_('select.option', 'ucmsubform', JText::_('COM_TJFIELDS_UCMSUBFORM'));
		}

		$options[] = JHtml::_('select.option', 'number', JText::_('COM_TJFIELDS_NUMBER'));

		return JHtml::_('select.genericlist', $options, $this->name,
		'class="required" onchange="show_option_div(this.value)"', 'value', 'text', $this->value, 'jform_type'
		);
	}
}
