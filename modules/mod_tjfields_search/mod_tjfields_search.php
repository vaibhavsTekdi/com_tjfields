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
	<div><h4><?php echo "categorys";?></h4></div>
	<?php
		echo JHtml::_('select.genericlist', $fieldsCategorys, 'category_id', 'class=""  size="1" title="' . JText::_('APP_Q2CMYPRODUCTS_SELECT_STORE_DESC') . '"', 'id', 'title', $selectedCategory, 'category_id');
}

foreach ($fieldsArray as $fieldstype => $fields)
{
	if(!empty($fields))
	{
		?>
		<div><h4><?php echo $fieldstype;?></h4></div>
		<?php

		foreach ($fields as $field)
		{
			if (!empty($field->id))
			{
				$fieldOptions = $tjfieldsHelper->getOptions($field->id);
			}
			?>
			<div>
				<h5>
				<?php
				if (!empty($fieldOptions))
				{
					echo $field->label;
				}
				?>
				</h5>
			</div>
			<?php
			foreach ($fieldOptions as $option)
			{
			?>
				<div class="tjfieldfilters-<?php echo $option->options;?>">
					<input type="checkbox" class="tjfieldCheck" name="tj_fields_value[]" id="<?php echo $field->name . $option->options;?>" value="<?php echo $option->id;?>" <?php echo in_array($option->id, $selectedFilters)?'checked="checked"':'';?>/>
					<span>&nbsp;&nbsp;</span>
					<?php echo $option->options;?>
				</div>
				<?php
			}
		}
	}
}

$jinput = JFactory::getApplication();
$mainframe =JFactory::getApplication();

?>
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

		techjoomla.jQuery(".tjfieldCheck:checked").each(function()
		{
			tjFieldCheckedFilters += techjoomla.jQuery(this).val();

			tjFieldCheckedFilters += ",";
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
</script>
