function addClone(rId,rClass)
{
	//window.field_lenght=f_lenght;
	var pre=field_lenght;
	field_lenght++;

		var removeButton="<div id='remove_btn_div"+pre+"' class='com_tjfields_remove_button span2'>";
		removeButton+="<button class='btn btn-small btn-danger' type='button' id='remove"+pre+"'";
		removeButton+="onclick=\"removeClone('com_tjfields_repeating_block"+pre+"','remove_btn_div"+pre+"');\" title=\"<?php echo JText::_('COM_TJFIELDS_REMOVE_TOOLTIP');?>\" >";
		removeButton+="<i class=\""+tjfield_icon_minus+"\"></i></button>";
		removeButton+="</div>";

		var newElem=techjoomla.jQuery('#'+rId+pre).clone().attr('id',rId+field_lenght);
		newElem.find('input[name=\"tjfields[' + pre + '][optionname]\"]').val('');
		newElem.find('input[name=\"tjfields[' + pre + '][optionvalue]\"]').val('');
		newElem.find('input[name=\"tjfields[' + pre + '][optionname]\"]').attr({'name': 'tjfields[' + field_lenght + '][optionname]','value':''});
		newElem.find('input[name=\"tjfields[' + pre + '][optionvalue]\"]').attr({'name': 'tjfields[' + field_lenght + '][optionvalue]','value':''});
		newElem.find('input[name=\"tjfields[' + pre + '][hiddenoption]\"]').attr({'name': 'tjfields[' + field_lenght + '][hiddenoption]','value':''});
		newElem.find('input[name=\"tjfields[' + pre + '][hiddenoptionid]\"]').attr({'name': 'tjfields[' + field_lenght + '][hiddenoptionid]','value':''});
		newElem.find('span[name=\"tjfields[' + pre + '][defaultoptionvalue]\"]').attr({'name': 'tjfields[' + field_lenght + '][defaultoptionvalue]'}); //newElem.find('img[src="localhost/jt315/administrator/components/com_tjfields/images/default.png"]').attr({'src':'localhost/jt315/administrator/components/com_tjfields/images/nodefault.png'});

		/*incremnt id*/
		newElem.find('input[id=\"tjfields_optionname_'+pre+'\"]').attr({'id': 'tjfields_optionname_'+field_lenght,'value':''});
		newElem.find('input[id=\"tjfields_optionvalue_'+pre+'\"]').attr({'id': 'tjfields_optionvalue_'+field_lenght,'value':''});
		newElem.find('input[id=\"tjfields_hiddenoption_'+pre+'\"]').attr({'id': 'tjfields_hiddenoption_'+field_lenght,'value':''});
		newElem.find('input[id=\"tjfields_hiddenoptionid_'+pre+'\"]').attr({'id': 'tjfields_hiddenoptionid_'+field_lenght,'value':''});
		newElem.find('span[id=\"tjfields_defaultoptionvalue_'+pre+'\"]').attr({'id': 'tjfields_defaultoptionvalue_'+field_lenght,'value':''});

		techjoomla.jQuery('#'+rId+pre).after(newElem);
		techjoomla.jQuery('#tjfields_defaultoptionvalue_'+field_lenght).html('<i class="'+tjfield_icon_emptystar+'"></i>');
		techjoomla.jQuery('#'+rId+pre).after(removeButton)
	//	techjoomla.jQuery('#'+rId+pre).append(removeButton);
}

function removeClone(rId,r_btndivId){
			techjoomla.jQuery('#'+rId).remove();
			techjoomla.jQuery('#'+r_btndivId).remove();
}

function getdefaultimage(span_id)
{
	//make all nodefault..
		if(techjoomla.jQuery('#jform_type').val()=='single_select' || techjoomla.jQuery('#jform_type').val()=='radio')
		{
			techjoomla.jQuery('.tjfields_defaultoptionvalue').each(function(){

					techjoomla.jQuery(this).html("<i class='"+tjfield_icon_emptystar+"'></i>");
					//techjoomla.jQuery(this).attr('src',"<?php echo JUri::root().'administrator'.DS.'components'.DS.'com_tjfields'.DS.'images'.DS.'nodefault.png' ?>");

				});
			techjoomla.jQuery('.tjfields_hiddenoption').each(function(){
				techjoomla.jQuery(this).attr('value',0);
			});
		}
		var str1= span_id;
		var req_id= str1.split('_');


		if(techjoomla.jQuery('#'+span_id).children('i').hasClass( tjfield_icon_star ))
		{
			techjoomla.jQuery('#'+span_id).html("<i class='"+tjfield_icon_emptystar+"'></i>");
			techjoomla.jQuery('#tjfields_hiddenoption_'+req_id[2]).attr('value',0);
		}
		else
		{
			techjoomla.jQuery('#'+span_id).html("<i class='"+tjfield_icon_star+"'></i>");
			techjoomla.jQuery('#tjfields_hiddenoption_'+req_id[2]).attr('value',1);
		}
}

techjoomla.jQuery( document ).ready(function(){
	var field_type = techjoomla.jQuery('#jform_type').val();

	techjoomla.jQuery('#jform_filterable').parent().parent().hide();

	if (field_type == 'radio' || field_type == 'single_select' || field_type == 'multi_select' || field_type == 'list')
	{
		techjoomla.jQuery('#jform_filterable').parent().parent().show();
		techjoomla.jQuery('#option_div').show();
	}

	//if edit ..make name field readonly
	var field_id=techjoomla.jQuery('#jform_id').val();

	if(field_id!=0)
	{
		techjoomla.jQuery('#jform_name').attr('readonly',true);
	}

	// show the default path for file upload & attach the folder name with respect to name entered for file field
	if (field_type == 'file')
	{
		// folder name should contain only alpahnumeric characters & also remove the underscores and spaces
		if(field_id==0)
		{
			techjoomla.jQuery('#jform_params_uploadpath').val(fileUploadDefaultPath+client+"/");
			techjoomla.jQuery("#jform_name").keyup(function(){
				techjoomla.jQuery('#jform_params_uploadpath').val(fileUploadDefaultPath+client+"_"+clientType+"_"+techjoomla.jQuery("#jform_name").val().replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '')+"/");
			});
		}
		else
		{
			if (techjoomla.jQuery('#jform_params_uploadpath').val()=="")
			{
				techjoomla.jQuery('#jform_params_uploadpath').val(fileUploadDefaultPath+client+"/"+clientType+"/");
			}

			// Show current file upload path & notice to admin user in case if he is going to custmozie the file upload path
			techjoomla.jQuery('#currentUploadPath').html(techjoomla.jQuery('#jform_params_uploadpath').val());
			var fileUploadhtml = techjoomla.jQuery('.fileUploadAlert').html();
			techjoomla.jQuery('#jform_params_uploadpath').parent('div').append(fileUploadhtml);
		}
	}
});

Joomla.submitbutton = function(task)
{
	// Remove disable attribute from category select so that the selected category can be saved
	if (task == 'field.apply' || task == 'field.save' || task == 'field.newsave' || task == 'field.save2copy')
	{
		techjoomla.jQuery('#jformcategory').attr("disabled", false);
	}

	whitespaces_not_llowed = Joomla.JText._('COM_TJFIELDS_LABEL_WHITESPACES_NOT_ALLOWED');

	if (task == 'field.cancel')
	{
		Joomla.submitform(task, document.getElementById('field-form'));
	}
	else
	{
		if (task != 'field.cancel' && document.formvalidator.isValid(document.id('field-form')))
		{
			var isrequired = techjoomla.jQuery('input[name="jform[required]"]:checked', '#field-form').val();
			var field_type = techjoomla.jQuery('#jform_type').val();

			switch(field_type)
			{
				case 'multi_select':
				case 'single_select':
				case 'radio':
					if(isrequired === 1)
					{
						if(techjoomla.jQuery('.tjfields_optionname').val().trim() == '' && techjoomla.jQuery('.tjfields_optionvalue').val().trim() == '')
						{
							techjoomla.jQuery('.tjfields_optionname').val('');
							techjoomla.jQuery('.tjfields_optionname').attr('required', 'required');
							techjoomla.jQuery('.tjfields_optionvalue').attr('required', 'required');
							techjoomla.jQuery('.tjfields_optionname').focus();
							return false;
						}
					}
					break;
				case 'text':
				case 'textarea':
				case 'calendar':
				case 'email_field':
					break;
			}

			if (techjoomla.jQuery('#jform_label').val().trim() == '')
			{
				alert(whitespaces_not_llowed);
				techjoomla.jQuery('#jform_label').val('');
				techjoomla.jQuery('#jform_label').focus();
				return false;
			}

			if (techjoomla.jQuery('#jform_name').val().trim() == '')
			{
				alert(whitespaces_not_llowed);
				techjoomla.jQuery('#jform_name').val('');
				techjoomla.jQuery('#jform_name').focus();
				return false;
			}

			Joomla.submitform(task, document.getElementById('field-form'));
		}
		else
		{
			alert(invalidFormErrorMsg);
		}
	}
}

function show_option_div()
{
	techjoomla.jQuery('input[name=task]').val('field.saveFormState');
	document.forms.adminForm.action= editFormlink;
	document.forms.adminForm.submit();
}

function showOptions()
{
	techjoomla.jQuery('#option_div').show();
	techjoomla.jQuery('.textarea_inputs').children().removeAttr('required');
}
