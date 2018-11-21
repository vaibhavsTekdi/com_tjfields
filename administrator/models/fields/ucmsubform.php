<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJ-Fields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.path');


JLoader::register('JFormFieldSubform', JPATH_BASE . '/libraries/joomla/form/fields/subform.php');

/**
 * The Field to load the form inside current form
 *
 * @Example with all attributes:
 * 	<field name="field-name" type="subform"
 * 		formsource="path/to/form.xml" min="1" max="3" multiple="true" buttons="add,remove,move"
 * 		layout="joomla.form.field.subform.repeatable-table" groupByFieldset="false" component="com_example" client="site"
 * 		label="Field Label" description="Field Description" />
 *
 * @since  1.3
 */
class JFormFieldUcmsubform extends JFormFieldSubform
{
	/**
	 * The form field type.
	 * @var    string
	 */
	protected $type = 'Ucmsubform';
}
