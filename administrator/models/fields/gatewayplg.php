<?php
/**
 * @version    SVN: <svn_id>
 * @package    Quick2cart
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();

jimport('joomla.form.formfield');

/**
 * Class for custom gateway element
 *
 * @since  1.0.0
 */
class JFormFieldGatewayplg extends JFormField
{
	protected $type = 'Gatewayplg';

	protected $name = 'Gatewayplg';

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   11.1
	 */
	public function getLabel()
	{
		if ($this->hidden)
		{
			return '';
		}

		// Get the label text from the XML element, defaulting to the element name.
		$text = $this->element['label'] ? (string) $this->element['label'] : (string) $this->element['name'];
		$text = $this->translateLabel ? JText::_($text) : $text;

		// Forcing the Alias field to display the tip below
		$position = $this->element['name'] == 'alias' ? ' data-placement="bottom" ' : '';

		$description = ($this->translateDescription && !empty($this->description)) ? JText::_($this->description) : $this->description;

		$displayData = array(
				'text'        => $text,
				'description' => $description,
				'for'         => 'jformgateways',
				'classes'     => explode(' ', $this->labelclass),
				'required'    => (bool) false,
				'position'    => $position
			);

		return JLayoutHelper::render($this->renderLabelLayout, $displayData);
	}

	/**
	 * Function to genarate html of custom element
	 *
	 * @return  HTML
	 *
	 * @since  1.0.0
	 */
	public function getInput()
	{
		$this->value = $this->getSelectedCategories();

		return $this->fetchElement($this->name, $this->value, $this->element, $this->options['control']);
	}

	/**
	 * Function to fetch a tooltip
	 *
	 * @param   string  $name          name of field
	 * @param   string  $value         value of field
	 * @param   string  &$node         node of field
	 * @param   string  $control_name  control_name of field
	 *
	 * @return  HTML
	 *
	 * @since  1.0.0
	 */
	public function fetchElement($name, $value, &$node, $control_name)
	{
		$jinput = JFactory::getApplication()->input;
		$clientStr = $jinput->get("client");
		$ClientDetail = explode('.', $clientStr);
		$client = $ClientDetail[0];
		$options         = array();

		/*$options[]       = JHtml::_('select.option', '', JText::_('COM_TJFIELDS_FORM_SELECT_CLIENT_CATEGORY'));*/

		// Fetch only published category. Static public function options($extension, $config = array('filter.published' => array(0,1)))
		$categories = JHtml::_('category.options', $client, array('filter.published' => array(1)));
		$category_list               = array_merge($options, $categories);

		$options = array();

		foreach ($category_list as $category)
		{
			$options[] = JHtml::_('select.option', $category->value, $category->text);
		}

		if (JVERSION >= 1.6)
		{
			$fieldName = $name;
		}
		else
		{
			$fieldName = $control_name . '[' . $name . ']';
		}

		$html = JHtml::_('select.genericlist', $options, $fieldName,
		'class="inputbox required"  multiple="multiple" size="5"', 'value', 'text', $value, $control_name . $name
		);

		return $html;
	}

	/**
	 * Function to fetch a tooltip
	 *
	 * @param   string  $label         label of field
	 * @param   string  $description   description of field
	 * @param   string  &$node         node of field
	 * @param   string  $control_name  control_name of field
	 * @param   string  $name          name of field
	 *
	 * @return  HTML
	 *
	 * @since  1.0.0
	 */
	public function fetchTooltip($label, $description, &$node, $control_name, $name)
	{
		return null;
	}

	/**
	 * Fetch category list for field
	 *
	 * @return  category array
	 *
	 * @since  1.0.0
	 */
	public function getSelectedCategories()
	{
		$catList = array();
		$jinput = JFactory::getApplication()->input;
		$fieldId = $jinput->get("id");

		if (!empty($fieldId))
		{
			$db    = JFactory::getDBO();
			$query = $db->getQuery(true)
						->select('category_id')
						->from($db->quoteName('#__tjfields_category_mapping'))
						->where('field_id=' . $fieldId);
			$db->setQuery($query);

			return $db->loadColumn();
		}
	}
}
