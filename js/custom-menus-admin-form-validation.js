function add_new_item_validate() {

	// Declare variables
	var item_title = document.getElementById('item_title').value;
	var item_category = document.getElementById('item_category').value;
	 
	if(item_title == "") {
		alert("Please enter a title.");
		return false;
	}
	
	if(item_category == "") {
		alert("Please select a category.");
		return false;
	}

	return true;
}

function add_new_menu_validate() {
	// Declare variables
	var menu_title = document.getElementById('category_title').value;
	
	if(menu_title == "") {
		alert("Please enter a title.");
		return false;
	}
	
	return true;
}

function add_new_category_validate() {
	// Declare variables
	var category_title = document.getElementById('category_title').value;
	var category_menu = document.getElementById('category_menu').value;
	
	if(category_title == "") {
		alert("Please enter a title.");
		return false;
	}
	
	if(category_menu == "") {
		alert("Please select a menu for the category.");
		return false;
	}
	
	return true;
}