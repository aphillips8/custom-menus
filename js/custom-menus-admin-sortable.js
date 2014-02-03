jQuery(document).ready(function($) {
	
	// Sortable for items (they're separated because AJAX needs to insert the results into different tables
	$(".custom-sortable-items").sortable({axis: "y", handle: ".drag-me", containment: "parent", cursor: "move", items: "tr", opacity: 0.6, tolerance: 'pointer', helper: function(e, tr) {
    var originals = tr.children();
    var helper = tr.clone();
    helper.children().each(function(index) {
      // Set helper cell sizes to match the original sizes
      $(this).width(originals.eq(index).width())
    });
    return helper;
  } });
  
  	// AJAX to save sortable results
	var custom_sortable_items_order = new Array();
	
	$(".custom-sortable-items").bind("sortupdate", function(event, ui) {
		custom_sortable_items_order = [];
		
		$(this).find("tr input.menu-item-id").each(function(index) {
			//$(this).attr("value", (index + 1));
			custom_sortable_items_order.push($(this).attr("value"));
		});
	
		var data = {
			action: 'custom_sortable_items_save',
			custom_sortable_order: custom_sortable_items_order
		};
	
		jQuery.post(ajaxurl, data, function(response) {
			//Javascript response here
			//alert(response);
		});
		
	});
	
	
	// Sortable for categories
	$(".custom-sortable-categories").sortable({axis: "y", handle: ".drag-me", containment: "parent", cursor: "move", items: "tr", opacity: 0.6, tolerance: 'pointer', helper: function(e, tr) {
    var originals = tr.children();
    var helper = tr.clone();
    helper.children().each(function(index) {
      // Set helper cell sizes to match the original sizes
      $(this).width(originals.eq(index).width())
    });
    return helper;
  } });
  
  // AJAX to save sortable results
	var custom_sortable_categories_order = new Array();
	
	$(".custom-sortable-categories").bind("sortupdate", function(event, ui) {
		custom_sortable_categories_order = [];
		
		$(this).find("tr input.menu-category-id").each(function(index) {
			//$(this).attr("value", (index + 1));
			custom_sortable_categories_order.push($(this).attr("value"));
		});
	
		var data = {
			action: 'custom_sortable_categories_save',
			custom_sortable_order: custom_sortable_categories_order
		};
	
		jQuery.post(ajaxurl, data, function(response) {
			//Javascript response here
			//alert(response);
		});
		
	});
});