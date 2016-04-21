function mediaLoadElements()
{
	if(!mediaLock)
	{
		mediaLock = true;
		if(platformId == null || platformId == '')
		{
			$.ajaxCall('medialibrary.getEntriesFromType', 'u='+iUserId+'&t='+searchType+'&s='+elementsLoaded+'&c='+categoryId).done(function( data ) {
				mediaLock = false;
			})
		}
		else
		{
			$.ajaxCall('medialibrary.getEntriesFromUserPlatform', 'u='+iUserId+'&s='+elementsLoaded+'&p='+platformId).done(function( data ) {
				mediaLock = false;
			})
		}
	}
}

function addCategory()
{
	var input = $("#inputCategory");
	if(input.val().length < 2)
		alert($('<div/>').html(oTranslations['medialibrary.test_string_minimum']).text());
	else if($("#media_cat li").length >= categoriesMax)
		alert($('<div/>').html(oTranslations['medialibrary.categories_max']).text());
	else
	{
		$.ajaxCall('medialibrary.addUserCategory', 'c='+encodeURIComponent(input.val())+'&t='+searchType);
		input.val("");
	}
}

function deleteCategory(catId)
{
	if(confirm(oTranslations['medialibrary.confirm_delete_category']))
		$.ajaxCall('medialibrary.deleteUserCategory', 'c='+catId+'&t='+searchType);
}

function resetItemsActions()
{
	$('.image_hover_menu_link').unbind('click');
	$('.image_hover_holder').unbind('mouseover');
	$('.image_hover_holder').unbind('mouseout');
	$('body').unbind('click');
	$Behavior.imageHoverHolder();
}

function updateOrderMedia()
{
	var arNumbers = [];
	$("#like_content > li").each(function()
	{
		arNumbers.push($(this).attr('ref'));
	});
	var orArray = JSON.stringify(arNumbers);
	$(".like_status .updateMediaWaiting").show();
	$.ajaxCall('medialibrary.updateOrderMedias', 'ids='+orArray+'&c='+categoryId);
}

function updateOrderCategories()
{
	var arNumbers = [];
	$("#media_cat > li").each(function()
	{
		arNumbers.push($(this).attr('ref'));
	});
	
	var orArray = JSON.stringify(arNumbers);
	$("#media_cat_save_categories .updateMediaWaiting").show();
	$.ajaxCall('medialibrary.updateOrderCategories', 'ids='+orArray+'&t='+searchType);
}

function mediaAdmin()
{
	//Search
	$("#selectMedia").autocomplete({
		source:function( request, response ) {
			waitingOperation(true);
			$.ajaxCall('medialibrary.getMedias', 'q='+request.term+'&t='+searchType)
				.done(function( data ) {
					response( $.parseJSON(data) );
					waitingOperation(false);
				});
		},
		select: function(event, ui) {
			waitingOperation(true);
			$.ajaxCall('medialibrary.addMedia', 'id='+ui.item.valueid+'&t='+searchType+'&c='+categoryId);
		},
		delay: 1000,
		autoFocus: true
	});

	//Cat Sort
	$("#media_cat").sortable({
		placeholder: "ui-state-highlight",
		stop: function( event, ui ) {
			$("#media_cat_save_categories").fadeIn("slow");
		},
		delay: 500,
	}).disableSelection();
	
	//DragDrog Medias
	$("#like_content").sortable({
		placeholder: "ui-state-highlight",
		start: function( event, ui ) {
			var itm = $(ui.item).find(".mediathek_item_clip_holder");
			itm.attr("oldclick", itm.attr("onclick"));
			itm.attr("onclick", '');
		},
		stop: function( event, ui ) {
			if(!dragaction)
			{
				$(".like_status").fadeIn("slow");
				$(window).bind('beforeunload', function(){
				  return oTranslations['medialibrary.confirm_leave_window'];
				});
			}
			else
				dragaction = false;
			setTimeout(function()
			{
				var itm = $(ui.item).find(".mediathek_item_clip_holder");
				itm.attr("onclick", itm.attr("oldclick"));
				itm.attr("oldclick", '');
			},500);
		},
		delay: 300
	});
	$("#media_cat").disableSelection();
	reloadDroppable();
}

function reloadCategoryNumbers(catId)
{
	modifyCategoryNumber(catId, 1);
	removeCategoryNumber();
}

function addCategoryNumber()
{
	modifyCategoryNumber(categoryId, 1);
}

function removeCategoryNumber()
{
	modifyCategoryNumber(categoryId, -1);
}

function modifyCategoryNumber(catId, number)
{
	var newCatNbr=$("#js_media_category_number_"+catId);
	var newNbr=parseInt(newCatNbr.attr("nbr"))+number;
	newCatNbr.text("("+newNbr+")");
	newCatNbr.attr("nbr",newNbr);
}

function modifyMediaNumber(typeId, number)
{
	var newTypeNbr=$("#js_media_media_"+typeId);
	var newNbr=parseInt(newTypeNbr.attr("nbr"))+number;
	newTypeNbr.text("("+newNbr+")");
	newTypeNbr.attr("nbr",newNbr);
}

function renameCategory()
{
	var newName=prompt(oTranslations['medialibrary.new_category_name']);
	if(newName.length < 2)
		alert($('<div/>').html(oTranslations['medialibrary.test_string_minimum']).text());
	else if(newName.length > 16)
		alert($('<div/>').html(oTranslations['medialibrary.test_string_maximum']).text());
	else
		$.ajaxCall('medialibrary.renameUserCategory', 'i='+categoryId+'&c='+encodeURIComponent(newName)+'&t='+searchType);
}

function reloadDroppable()
{
	if(bMediaAdmin)
	{
		$(".media_draggable").droppable('destroy');
		$(".media_draggable").droppable({
			accept: "#like_content li",
			activeClass: "ui-state-highlight",
			hoverClass: "ui-state-hover",
			drop: function( event, ui ) {
				dragaction = true;
				var media = $(ui.draggable);
				var mediaId = media.attr('ref');
				var catId = $(this).attr('ref');
				$.ajaxCall('medialibrary.sendMediaToCategory', 'id='+mediaId+'&c='+catId+'&t='+searchType);
				media.hide();
			}
		});
	}
}

function saveGameSettings()
{
	var input = [];
	var mediaId = parseInt($("#media_platforms").attr("ref"));
	$(".media_platform_item_holder").each(function(e)
	{
		var platformId = parseInt($(this).attr("ref"));
		var checkbox = $("#js_platform_checkbox_"+platformId);
		if(checkbox.attr("checked") == "checked")
			input.push({p:platformId, a: $("#js_platform_input_"+platformId).val()});
	})
	var orArray = JSON.stringify(input);
	$.ajaxCall('medialibrary.saveGameSettings', 'id='+mediaId+'&d='+orArray);
}

function loadGameSettings()
{
	/*$(".media_platform_checkbox input").click(function(e)
	{
		var target = $("#"+e.target.id);
		var platformId = target.parent().parent().attr("ref");
		if(target.attr("checked") == "checked")
			$("#js_platform_input_"+platformId).parent().show();
		else
			$("#js_platform_input_"+platformId).parent().hide();
			
	});
	
	$(".media_platform_info input").each(function(e)
	{	
		var platformId = $(this).parent().parent().attr("ref");
		if($("#js_platform_checkbox_"+platformId).attr("checked") != "checked")
		{
			$(this).css("color", "#dddddd");
			$(this).css("border", "1px solid #dddddd");
		}
	});
	
	$(".media_platform_info input").click(function(e)
	{
		var target = $("#"+e.target.id);
		target.css("border", "1px solid #777777");
		target.css("color", "#777777");
		target.select();
	});*/
}

function unloadGameSettings()
{
	$(".media_platform_checkbox input").unbind("click");
	$(".media_platform_info input").unbind("click");
}

function waitingOperation(activeState, update)
{
	if(activeState)
	{
		$("#selectMedia").prop('disabled', true).css('background-color','#cccccc');
		$("#selectMediaWaiting").show();
		$(".ui-menu-item").hide();
	}
	else
	{
		$("#selectMedia").prop('disabled', false).css('background-color','#ffffff');
		$("#selectMediaWaiting").hide();
	}
}

if(bMediaAdmin)
	mediaAdmin();