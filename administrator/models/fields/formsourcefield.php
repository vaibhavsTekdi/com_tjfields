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
 * Supports an formsource select list of subform
 *
 * @since  1.3
 */
class JFormFieldformsourcefield extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 *
	 * @since	1.3
	 */
	protected $type = 'text';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.3
	 */
	protected function getInput()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('id', 'title', 'unique_identifier')));
		$query->from($db->quoteName('#__tj_ucm_types'));
		$query->where($db->quoteName('params') . ' LIKE ' . $db->quote('%"is_subform":"%1%"%'));

		$db->setQuery($query);
		$isSubform  = $db->loadObjectList();

		$options = array();
		$options[] = JHtml::_('select.option', '', JText::_('COM_TJFIELDS_FORM_LBL_FIELD_SELECT_SOURCE'));

		foreach ($isSubform as $form)
		{
			$fullClient = explode('.', $form->unique_identifier);
			$client = $fullClient[0];
			$clientType = $fullClient[1];

			$filePathFrontend = 'components/' . $client . '/models/forms/' . $clientType . 'form_extra.xml';

			$options[] = JHtml::_('select.option', $filePathFrontend, $form->title);
		}

		return JHtml::_('select.genericlist', $options, $this->name, 'class="inputbox required"',
		'value', 'text', $this->value, $this->options['control'] . $this->name
		);
	}
}
