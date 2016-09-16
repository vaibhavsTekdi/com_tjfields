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
$jinput = JFactory::getApplication();
$baseurl = $jinput->input->server->get('REQUEST_URI', '', 'STRING');

// Make base URL starts
$urlArray = explode ('&',$baseurl);

foreach ($urlArray as $key => $url)
{
	// Unset Not required parameter from array
	if (!empty(strstr($url, 'ModFilterCat=')) || !empty(strstr($url, 'prod_cat=')) || !empty(strstr($url, 'tj_fields_value=')) || !empty(strstr($url, 'client=')))
	{
		unset($urlArray[$key]);
	}
}

$baseurl = implode('&', $urlArray);

// Make base URL ends
$selectedFilters = explode(',', $jinput->input->get('tj_fields_value', '', 'string'));
?>
<form method="post" name="tjfieldsSearchForm" id="tjfieldsSearchForm">
<?php

if (!empty($fieldsCategorys))
{
	?>
	<div><h4><?php echo JText::_('MOD_TJFIELDS_SEARCH_SELECT_CATEGORY');?></h4></div>
	<?php
	//echo JHtml::_('select.genericlist', $fieldsCategorys, 'category_id', 'class="form-control"  size="1" onchange="submitCategory()" title="' . JText::_('MOD_TJFIELDS_SEARCH_SELECT_CATEGORY') . '"', 'id', 'title', $selectedCategory, 'category_id');
	echo JHtml::_('select.genericlist', $fieldsCategorys, "category_id", 'class="form-control"  size="1" onchange="submitCategory()" title="' . JText::_('MOD_TJFIELDS_SEARCH_SELECT_CATEGORY') . '"', 'value', 'text', $selectedCategory, 'category_id');

}
foreach ($fieldsArray as $key => $fieldOptions)
{
	$i = 0;
	$fieldName = '';

	//~ if (!empty($field->id))
	//~ {
		//~ $fieldOptions = $tjfieldsHelper->getOptions($field->id);
	//~ }

	if (!empty($fieldOptions))
	{
	?>
		<div><h5><?php echo ucfirst($fieldOptions[0]->label);?></h5></div>
	<?php

	}

	foreach ($fieldOptions as $option)
	{
	?>
		<div class="tjfieldfilters-<?php echo $option->name;?>">
			<input type="checkbox" class="tjfieldCheck" name="tj_fields_value[]" id="<?php echo $option->name .'||'.  $option->option_id;?>" value="<?php echo $option->option_id;?>" <?php echo in_array($option->option_id, $selectedFilters)?'checked="checked"':'';?>/>
			<span>&nbsp;&nbsp;</span>
			<?php echo ucfirst($option->value);?>
		</div>
		<?php
	}
}

$jinput = JFactory::getApplication();
$mainframe =JFactory::getApplication();

		//~ jimport('joomla.application.module.helper');
		//~ $module = JModuleHelper::getModule('mod_q2cfilters');
		//~ echo JModuleHelper::renderModule($module);
?>
<p></p>
	<div class="center">
		<a class="btn btn-small btn-info" onclick='tjfieldsapplyfilters()'><?php echo JText::_('APPLY');?></a>
		<a class="btn btn-small btn-info" onclick='clearfilters()'><?php echo JText::_('CLEAR');?></a>
	</div>
</form>

<script>

	techjoomla.jquery = jQuery.noConflict();

	function tjfieldsapplyfilters()
	{
		var redirectlink = '<?php echo $baseurl;?>';

		var client = "com_quick2cart.products";
		var optionStr = "";

		if (typeof(client) != 'undefined')
		{
			if (redirectlink.indexOf('?') === -1)
			{
				optionStr += '?client='+client;
			}
			else
			{
				optionStr += '&client='+client;
			}

			redirectlink += optionStr;
		}

		optionStr = "";

		var urlValueName = "<?php echo $url_cat_param_name;?>";

		if (urlValueName != 'undefined')
		{
			if (redirectlink.indexOf('?') === -1)
			{
				optionStr += "?ModFilterCat="+urlValueName;
			}
			else
			{
				optionStr += "&ModFilterCat="+urlValueName;
			}

			redirectlink += optionStr;
		}

		optionStr = "";

		// Variable to get current filter values
		var category = techjoomla.jQuery('#category_id').val();

		if (typeof(category) != 'undefined')
		{
			if (urlValueName != 'undefined')
			{
				if (redirectlink.indexOf('?') === -1)
				{
					optionStr += "?"+urlValueName+"="+category;
				}
				else
				{
					optionStr += "&"+urlValueName+"="+category;
				}
			}

			redirectlink += optionStr;
		}

		optionStr = "";

		var tjFieldCheckedFilters = "";

		// Flag to add comma in filter fields
		var flag = 0;

		techjoomla.jQuery(".tjfieldCheck:checked").each(function()
		{
			if (Number(flag) != 0)
			{
				tjFieldCheckedFilters += ",";
			}

			flag++;

			tjFieldCheckedFilters += techjoomla.jQuery(this).val();
		});

		if (tjFieldCheckedFilters != '')
		{
			if (redirectlink.indexOf('?') === -1)
			{
				optionStr += "?tj_fields_value="+tjFieldCheckedFilters;
			}
			else
			{
				optionStr += "&tj_fields_value="+tjFieldCheckedFilters;
			}

			redirectlink += optionStr;
		}

		window.location = redirectlink;
	}

	function submitCategory()
	{
		var redirectlink = '<?php echo $baseurl;?>';

		var client = "com_quick2cart.products";
		var optionStr = "";

		if (typeof(client) != 'undefined')
		{
			if (redirectlink.indexOf('?') === -1)
			{
				optionStr += '?client='+client;
			}
			else
			{
				optionStr += '&client='+client;
			}

			redirectlink += optionStr;
		}

		optionStr = "";

		var urlValueName = "<?php echo $url_cat_param_name;?>";

		if (urlValueName != 'undefined')
		{
			if (redirectlink.indexOf('?') === -1)
			{
				optionStr += "?ModFilterCat="+urlValueName;
			}
			else
			{
				optionStr += "&ModFilterCat="+urlValueName;
			}

			redirectlink += optionStr;
		}

		optionStr = "";

		// Variable to get current filter values
		var category = techjoomla.jQuery('#category_id').val();

		if (typeof(category) != 'undefined')
		{
			if (urlValueName != 'undefined')
			{
				if (redirectlink.indexOf('?') === -1)
				{
					optionStr += "?"+urlValueName+"="+category;
				}
				else
				{
					optionStr += "&"+urlValueName+"="+category;
				}
			}

			redirectlink += optionStr;
		}

		window.location = redirectlink;
	}
</script>
