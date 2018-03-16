<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    TJFields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

defined('JPATH_PLATFORM') or die;

JLoader::register('JFormFieldTextarea', JPATH_BASE . '/libraries/joomla/form/fields/textarea.php');

/**
 * Form Field Textareacounter class
 * Supports a multi line area for entry of plain text with count char
 *
 * @since  11.1
 */
class JFormFieldTextareacounter extends JFormFieldTextarea
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Textareacounter';

	/**
	 * The number of rows in textarea.
	 *
	 * @var    mixed
	 * @since  3.2
	 */
	protected $rows;

	/**
	 * The number of columns in textarea.
	 *
	 * @var    mixed
	 * @since  3.2
	 */
	protected $columns;

	/**
	 * The maximum number of characters in textarea.
	 *
	 * @var    mixed
	 * @since  3.4
	 */
	protected $maxlength;

	/**
	 * The minimum number of characters in textarea.
	 *
	 * @var    mixed
	 * @since  3.4
	 */
	protected $minlength;

	/**
	 * The minimum number of characters in textarea countertext.
	 *
	 * @var    mixed
	 * @since  3.4
	 */
	protected $countertext;

	/**
	 * The textarea input field id.
	 *
	 * @var    int
	 * @since  3.4
	 */
	protected $id;

	/**
	 * The class for textarea counter input field
	 *
	 * @var    mixed
	 * @since  3.4
	 */
	protected $class;

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   3.2
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->countertext = isset($this->element['countertext']) ? (string) $this->element['countertext'] : '';
			$this->countertext = JText::_($this->countertext);
			$this->maxlength = isset($this->element['maxlength']) ? (int) $this->element['maxlength'] : 0;
			$this->minlength = isset($this->element['minlength']) ? (int) $this->element['minlength'] : 0;
			$this->class .= ' charcounter';

			if (!empty($this->minlength))
			{
				$this->class .= ' validate-min' . $this->minlength;
			}
		}

		return $return;
	}

	/**
	 * Method to get the textarea field input markup.
	 * Use the rows and columns attributes to specify the dimensions of the area.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$html = parent::getInput();
		$html .= $this->getCounterMask();

		// Using the .charcounter selector
		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration('
			jQuery(document).ready(function() {

				jQuery(".charcounter").each(function() {
					let usedcharlength = parseInt(jQuery(this).val().length);
					let maxlength = parseInt(jQuery(this).siblings("span").find(".charscontainer_maxlength").text());
					let availablecharlength = maxlength - usedcharlength;
					jQuery(this).siblings("span").find(".charscontainer_remaining").text(availablecharlength);
				})

				jQuery("#' . $this->id . '").on("keyup", function() {
					jQuery("#usedchars_' . $this->id . '").text(jQuery("#' . $this->id . '").val().length);
					jQuery("#remainingchars_' . $this->id . '").text((' . $this->maxlength . ' - jQuery("#' . $this->id . '").val().length));
				});

				document.formvalidator.setHandler("blank-space", function(value, element) {
					if (value.trim() == "") {
						return false;
					}
					return true;
				});
			});
		');

		if (!empty($this->minlength))
		{
			$doc->addScriptDeclaration('
				jQuery(document).ready(function() {
					document.formvalidator.setHandler("min' . $this->minlength . '", function(value) {
						return (value.trim().length >=' . $this->minlength . ');
					});
				});
			');
		}

		return $html;
	}

	/**
	 * Method to get the textarea field char count container.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	private function getCounterMask()
	{
		$text = '<span class="charscontainer" id="charscontainer_' . $this->id . '">';
		$text .= $this->countertext;

		$text = str_replace('{used}', '<span class="charscontainer_used" id="usedchars_' . $this->id . '">0</span>', $text);
		$text = str_replace('{remaining}',
							'<span class="charscontainer_remaining" id="remainingchars_' . $this->id . '">' . $this->maxlength . '</span>',
							$text
							);
		$text = str_replace('{maxlength}',
						'<span class="charscontainer_maxlength" id="maxlength_' . $this->id . '">' . $this->maxlength . '</span>',
						$text
						);

		$text .= '</span>';

		return $text;
	}
}
