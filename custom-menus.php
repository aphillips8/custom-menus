<?php
/*
Plugin Name: Custom Menus
Description: Organises and displays food and drink items.
Author: Anna Phillips
Author URI: http://annaphillips.co.nz/
*/

// Prevent people from loading the plugin directly
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('You are not allowed to call this page directly.');
}

// Set plugin directory
define('CUSTOM_MENU_TABLE', $wpdb->prefix . 'custom_menu');
define('CUSTOM_MENU_CATEGORIES_TABLE', $wpdb->prefix . 'custom_menu_categories');

// Creates table etc when plugin is activated
register_activation_hook(__FILE__,'custom_menu_tables');

function custom_menu_tables () {
	global $wpdb;
		
	$table_name = $wpdb->prefix . "custom_menu";

	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			
		$sql = "CREATE TABLE " . $table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			menu_parent mediumint(9) DEFAULT '1' NOT NULL,
			category mediumint(9) DEFAULT '1' NOT NULL,
			sort_order mediumint(9) DEFAULT '1' NOT NULL,
			title text NOT NULL,
			description longtext NOT NULL,
			price text NOT NULL,
			additional_info text NOT NULL,
			UNIQUE KEY id (id)
		);";
			
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	
	$table_name = $wpdb->prefix . "custom_menu_categories";

	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		
	$sql = "CREATE TABLE " . $table_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		menu_parent mediumint(9) DEFAULT '0' NOT NULL,
		sort_order mediumint(9) DEFAULT '0' NOT NULL,
		title text NOT NULL,
		subheading text NOT NULL,
		UNIQUE KEY id (id)
	);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
}

add_action("admin_init", "custom_menus_admin_init");
add_action("admin_menu", "custom_menus_admin_menu");

function custom_menus_admin_init() {
	// Register any stylesheets
	wp_register_style("custom-menus-admin-styles", plugins_url("css/custom-menus-admin.css", __FILE__));
	
	// Register any scripts
	wp_register_script("custom-menus-admin-form-validation", plugins_url("js/custom-menus-admin-form-validation.js", __FILE__));
	wp_register_script("custom-menus-admin-sortable", plugins_url("js/custom-menus-admin-sortable.js", __FILE__), array("jquery", "jquery-ui-core", "jquery-ui-sortable"));
}

function custom_menus_admin_menu() {
	global $wpdb;
	$pages = array();
	
	$parent_menu_names = $wpdb->get_results("SELECT * FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE menu_parent = 0 ORDER BY sort_order ASC");

	if(!empty($parent_menu_names)) {
		$first_run = true;
		foreach ($parent_menu_names as $parent_menu_name) {
			if($first_run) {
				$first_id = stripslashes($parent_menu_name->id);
				// Page title, Menu title, Capability, Menu Slug, Function, Icon URL
				// Don't add top level page to $pages array
				add_menu_page('Menus', 'Menus', 'upload_files', 'custom-menu-' . stripslashes($parent_menu_name->id), 'custom_menu_items', plugins_url('/images/icon.png', __FILE__), "51.5");
				$first_run = false;
			}
			// Page title, Menu title, Capability, Menu Slug, Function, Icon URL
			$pages[] = add_submenu_page('custom-menu-' . $first_id, stripslashes($parent_menu_name->title), stripslashes($parent_menu_name->title), 'upload_files', 'custom-menu-' . stripslashes($parent_menu_name->id), 'custom_menu_items');
		}
	} else {
		$first_id = 1;
		add_menu_page('Menus', 'Menus', 'upload_files', 'custom-menu-add', 'custom_menu_add', plugins_url('/images/icon.png', __FILE__));
	}
	
	// Page title, Menu title, Capability, Menu Slug, Function, Icon URL
	$pages[] = add_submenu_page('custom-menu-' . $first_id, 'Menu Categories', 'Menu Categories', 'upload_files', 'custom-menu-categories', 'custom_menu_categories');
	$pages[] = add_submenu_page('custom-menu-' . $first_id, 'Add Menu', 'Add Menu', 'upload_files', 'custom-menu-add', 'custom_menu_add');
	
	// Enqueue plugin styles and scripts to each admin page
	foreach($pages as $page) {
		// Use registered $page handle to hook stylesheets
		add_action("admin_print_styles-" . $page, "custom_menus_admin_styles");
		
		// Use registered $page handle to hook scripts
		add_action("admin_print_scripts-" . $page, "custom_menus_admin_scripts");
	}
}

// Enqueue plugin stylesheets
function custom_menus_admin_styles() {
	wp_enqueue_style("custom-menus-admin-styles");
}

// Enqueue plugin scripts
function custom_menus_admin_scripts() {
	wp_enqueue_script("custom-menus-admin-form-validation");
	wp_enqueue_script("custom-menus-admin-sortable");
}

add_action('wp_ajax_custom_sortable_items_save', 'custom_sortable_items_save');
add_action('wp_ajax_custom_sortable_categories_save', 'custom_sortable_categories_save');

function custom_sortable_items_save() {
	global $wpdb;
	
	$sortable_order_array = $_POST['custom_sortable_order'];
	$sort_order_value = 1;
	
	foreach($sortable_order_array as $sortable_order) {
		$wpdb->get_results("UPDATE " . CUSTOM_MENU_TABLE . " SET sort_order = '". mysql_escape_string($sort_order_value) . "' WHERE id = " . mysql_escape_string($sortable_order));
		$sort_order_value++;
	}
	die();
}

function custom_sortable_categories_save() {
	global $wpdb;
	
	$sortable_order_array = $_POST['custom_sortable_order'];
	$sort_order_value = 1;
	
	foreach($sortable_order_array as $sortable_order) {
		$wpdb->get_results("UPDATE " . CUSTOM_MENU_CATEGORIES_TABLE . " SET sort_order = '". mysql_escape_string($sort_order_value) . "' WHERE id = " . mysql_escape_string($sortable_order));
		$sort_order_value++;
	}
	die();
}

function add_menu_item() {
	global $wpdb;
	if(isset($_GET["page"])) {
		$page_slug_items = explode("-", $_GET["page"]);
		$custom_menu_id = $page_slug_items[2];
	}
	
	$item_title = htmlentities(($_POST["item_title"]), ENT_QUOTES, "UTF-8", false);	
	$item_description = htmlentities(($_POST["item_description"]), ENT_QUOTES, "UTF-8", false);
	$item_price = htmlentities(($_POST["item_price"]), ENT_QUOTES, "UTF-8", false);
	$item_additional_info = htmlentities(($_POST["item_additional_info"]), ENT_QUOTES, "UTF-8", false);
	$item_category = htmlentities(($_POST["item_category"]), ENT_QUOTES, "UTF-8", false);
	
	$sort_order = $wpdb->get_var("SELECT sort_order FROM " . CUSTOM_MENU_TABLE . " WHERE category = " . mysql_escape_string($item_category) . " ORDER BY sort_order DESC LIMIT 1");
	
	if(!empty($sort_order)) {
		$sort_order = $sort_order + 1;
	} else {
		$sort_order = 1;
	}
	
	if($item_title != "" && $item_category != "") {
		$wpdb->get_results("INSERT INTO " . CUSTOM_MENU_TABLE . " SET menu_parent='" . mysql_escape_string($custom_menu_id) . "', category='" . mysql_escape_string($item_category) . "', sort_order='" . mysql_escape_string($sort_order) . "', title='" . mysql_escape_string($item_title) . "', description='" . mysql_escape_string($item_description) . "', price='" . mysql_escape_string($item_price) . "', additional_info='" . mysql_escape_string($item_additional_info) . "'");
		
		show_notification_message("item-added");
	}
}

function update_menu_items() {
	global $wpdb;
	if(isset($_GET["page"])) {
		$page_slug_items = explode("-", $_GET["page"]);
		$custom_menu_id = $page_slug_items[2];
	}
	
	if(isset($_POST["current_category_id"])) {
		$current_category_id = $_POST["current_category_id"];

		$menu_item_ids = $wpdb->get_col("SELECT id FROM " . CUSTOM_MENU_TABLE . " WHERE category 	=" . mysql_escape_string($current_category_id) . " ORDER BY id ASC");
		
		foreach ($menu_item_ids as $menu_item_id) {
			if(isset($_POST['title_' . $menu_item_id])) {
				$menu_item_title = htmlentities(($_POST['title_' . $menu_item_id]), ENT_QUOTES, "UTF-8", false);
				$menu_item_description = htmlentities(($_POST['description_' . $menu_item_id]), ENT_QUOTES, "UTF-8", false);
				$menu_item_price = htmlentities(($_POST['price_' . $menu_item_id]), ENT_QUOTES, "UTF-8", false);
				$menu_item_additional_info = htmlentities(($_POST['additional_info_' . $menu_item_id]), ENT_QUOTES, "UTF-8", false);
				if($menu_item_title != "") {
					$wpdb->get_results("UPDATE " . CUSTOM_MENU_TABLE . " SET title = '". mysql_escape_string($menu_item_title) . "', description = '". mysql_escape_string($menu_item_description) . "', price = '". mysql_escape_string($menu_item_price) . "', additional_info = '" . mysql_escape_string($menu_item_additional_info) . "' WHERE id = " . $menu_item_id);
				}
			}
		}
	}
	show_notification_message("items-updated");
}

function delete_menu_item($id) {
	global $wpdb;
	if($id != NULL) {
		$wpdb->get_results("DELETE FROM " . CUSTOM_MENU_TABLE . " WHERE id = " . mysql_escape_string($id));
		show_notification_message("item-deleted");
	}
}

function add_menu_category($top_level = false) {
	global $wpdb;
	
	$category_title = htmlentities(($_POST["category_title"]), ENT_QUOTES, "UTF-8", false);

	if($top_level) {
		if($category_title != "") {
			$wpdb->get_results("INSERT INTO " . CUSTOM_MENU_CATEGORIES_TABLE . " SET menu_parent='0', title='" . mysql_escape_string($category_title) . "'");
			
			show_notification_message("menu-added");
		}
	} else {
		$category_subheading = htmlentities(($_POST["category_subheading"]), ENT_QUOTES, "UTF-8", false);
		$category_menu = htmlentities(($_POST["category_menu"]), ENT_QUOTES, "UTF-8", false);
		
		$sort_order = $wpdb->get_var("SELECT sort_order FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE menu_parent = " . mysql_escape_string($category_menu) . " ORDER BY sort_order DESC LIMIT 1");
		
		if(!empty($sort_order)) {
			$sort_order = $sort_order + 1;
		} else {
			$sort_order = 1;
		}
		
		if($category_title != "" && $category_menu != "") {
			$wpdb->get_results("INSERT INTO " . CUSTOM_MENU_CATEGORIES_TABLE . " SET menu_parent='" . mysql_escape_string($category_menu) . "', sort_order ='" . mysql_escape_string($sort_order) . "', title='" . mysql_escape_string($category_title) . "', subheading='" . mysql_escape_string($category_subheading) . "'");
			
			show_notification_message("category-added");
		}
	}
}

function update_menu_categories($top_level = false) {
	global $wpdb;

	if($top_level) {
		$menu_ids = $wpdb->get_col("SELECT id FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE menu_parent = 0 ORDER BY id ASC");
		foreach ($menu_ids as $menu_id) {
			$menu_title = htmlentities(($_POST['title_' . $menu_id]), ENT_QUOTES, "UTF-8", false);
			if($menu_title != "") {
				$wpdb->get_results("UPDATE " . CUSTOM_MENU_CATEGORIES_TABLE . " SET title = '". mysql_escape_string($menu_title) . "' WHERE id = " . $menu_id);
			}
		}
		show_notification_message("menu-updated");
	} else {
		$category_ids = $wpdb->get_col("SELECT id FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE menu_parent != 0 ORDER BY id ASC");
		foreach ($category_ids as $category_id) {
			$category_title = htmlentities(($_POST['title_' . $category_id]), ENT_QUOTES, "UTF-8", false);
			$category_subheading = htmlentities(($_POST['subheading_' . $category_id]), ENT_QUOTES, "UTF-8", false);
			if($category_title != "") {
				$wpdb->get_results("UPDATE " . CUSTOM_MENU_CATEGORIES_TABLE . " SET title = '". mysql_escape_string($category_title) . "', subheading = '". mysql_escape_string($category_subheading) . "' WHERE id = " . $category_id);
			}
		}
		show_notification_message("category-updated");
	}
}

function delete_menu_category($id, $top_level = false) {
	global $wpdb;

	if($id != NULL) {
		$wpdb->get_results("DELETE FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE id = " . mysql_escape_string($id));
		if($top_level) {
			show_notification_message("menu-deleted");
		} else {
			show_notification_message("category-deleted");
		}
	}
}

function show_notification_message($notification_to_show) {
	echo '<div class="updated fade" id="message"><p>';
	if($notification_to_show == "item-added") {
		echo "Menu item added successfully.";
	} elseif($notification_to_show == "items-updated") {
		echo "Menu items updated successfully.";
	} elseif($notification_to_show == "item-deleted") {
		echo "Menu item deleted successfully.";
	} elseif($notification_to_show == "category-added") {
		echo "Menu category added successfully.";
	} elseif($notification_to_show == "category-updated") {
		echo "Menu categories updated successfully.";
	} elseif($notification_to_show == "category-deleted") {
		echo "Menu category deleted successfully.";
	} elseif($notification_to_show == "menu-added") {
		echo "Menu added successfully. <a href=\"?page=custom-menu-categories\">Add a category</a> to get started.";
	} elseif($notification_to_show == "menu-updated") {
		echo "Menu updated successfully.";
	} elseif($notification_to_show == "menu-deleted") {
		echo "Menu deleted successfully.";
	}
	echo '</p></div>';
}

function custom_menu_items() {
	global $wpdb;
	if(isset($_GET["page"])) {
		$page_slug_items = explode("-", $_GET["page"]);
		$custom_menu_id = $page_slug_items[2];
	}
	
	if(isset($_REQUEST['action'])) {
		if($_REQUEST['action'] == 'add-item') {
			add_menu_item();
		} elseif($_REQUEST['action'] == 'update-item') {
			update_menu_items();
		} elseif($_REQUEST['action'] == 'delete-item') {
			if(isset($_GET['id'])) {
				$id = $_GET['id'];
			} else {
				$id = NULL;
			}
			delete_menu_item($id);
		}
	}
?>
	<div class="wrap">
		<h2>Add a New Menu Item</h2>
		<form method="post" action="?page=<?php echo $_GET["page"]; ?>" onsubmit="return add_new_item_validate();">
			<input type="hidden" name="action" value="add-item" />
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="item_title">Title</label>
					</th>
					<td>
						<input type="text" name="item_title" id="item_title" class="custom-long-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="item_description">Description</label>
					</th>
					<td>
						<input type="text" name="item_description" id="item_description" class="custom-long-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="item_price">Price</label>
					</th>
					<td>
						<input type="text" name="item_price" id="item_price" size="10" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="item_additional_info">Additional Info</label>
					</th>
					<td>
						<input type="text" name="item_additional_info" id="item_additional_info" class="custom-long-text" />
					</td>
				</tr>
				<?php
				// Loop through all categories, get title and ID while categories exist
				$category_menu_names = $wpdb->get_results("SELECT * FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE menu_parent=" . $custom_menu_id . " ORDER BY sort_order ASC");

				// Only show dropdown if there's more than one item
				if(count($category_menu_names) > 1) {
				?>
				<tr>
					<th scope="row">Category</th>
					<td>
						<select name="item_category" id="item_category">
							<option value=""></option>
							<?php foreach ($category_menu_names as $category_menu_name) { ?>
								<option value="<?php echo stripslashes($category_menu_name->id); ?>"><?php echo stripslashes($category_menu_name->title); ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<?php } else { ?>
				<tr class="hidden">
					<td colspan="2">
						<input type="hidden" name="item_category" value="<?php echo stripslashes($category_menu_names[0]->id); ?>" />
					</td>
				</tr>
				<?php } ?>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="Add Menu Item" />
			</p>
		</form>
		<p><strong>Hint:</strong> Drag and drop the arrow icon in the &lsquo;order&rsquo; column to rearrange the menu items.</p>
		<?php $parent_menu_name_title = $wpdb->get_var("SELECT title FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE menu_parent=0 AND id=" . $custom_menu_id); ?>
		<h2><?php echo $parent_menu_name_title; ?></h2>
		<?php
		$category_menu_names = $wpdb->get_results("SELECT * FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE menu_parent=" . $custom_menu_id . " ORDER BY sort_order ASC");
		if(!empty($category_menu_names)) {
		foreach ($category_menu_names as $category_menu_name) {
			$category_menu_items = $wpdb->get_results("SELECT * FROM " . CUSTOM_MENU_TABLE . " WHERE category=" . $category_menu_name->id . " ORDER BY sort_order ASC");
		?>
		<h3><?php echo stripslashes($category_menu_name->title); ?></h3>
		<?php
		// If the category contains menu items, display them
		if(!empty($category_menu_items)) { ?>
		<form method="post" action="?page=<?php echo $_GET["page"]; ?>">
		<input type="hidden" name="action" value="update-item" />
		<input type="hidden" name="current_category_id" value="<?php echo $category_menu_name->id; ?>" />
		<table class="widefat fixed" cellspacing="0">
			<thead>
				<tr class="thead">
					<th scope="col" class="custom-width-order">Order</th>
					<th scope="col">Title</th>
					<th scope="col">Description</th>
					<th scope="col" class="custom-width-price">Price</th>
					<th scope="col">Additional Info</th>
					<th scope="col" class="custom-width-buttons"></th>
				</tr>
			</thead>
			<tfoot>
				<tr class="thead">
					<th scope="col" class="custom-width-order">Order</th>
					<th scope="col">Title</th>
					<th scope="col">Description</th>
					<th scope="col" class="custom-width-price">Price</th>
					<th scope="col">Additional Info</th>
					<th scope="col" class="custom-width-buttons"></th>
				</tr>
			</tfoot>
		<tbody class="custom-sortable-items">
			<?php foreach ($category_menu_items as $category_menu_item) { ?>
			<tr>
				<td class="drag-me custom-width-order"></td>
				<td><input type="hidden" name="menu_item_id" value="<?php echo stripslashes($category_menu_item->id);?>" class="menu-item-id" /><input type="text" name="title_<?php echo stripslashes($category_menu_item->id);?>" value="<?php echo stripslashes($category_menu_item->title); ?>" class="custom-full-width" /></td>
				<td><input type="text" name="description_<?php echo stripslashes($category_menu_item->id);?>" value="<?php echo stripslashes($category_menu_item->description); ?>" class="custom-full-width" /></td>
				<td class="custom-width-price"><input type="text" name="price_<?php echo stripslashes($category_menu_item->id);?>" value="<?php echo stripslashes($category_menu_item->price); ?>" class="custom-full-width" /></td>
				<td><input type="text" name="additional_info_<?php echo stripslashes($category_menu_item->id);?>" value="<?php echo stripslashes($category_menu_item->additional_info); ?>" class="custom-full-width" /></td>
				<td class="custom-width-buttons"><input type="submit" class="button-primary" value="Save Changes" /> <a href="?page=<?php echo $_GET["page"]; ?>&amp;action=delete-item&amp;id=<?php echo stripslashes($category_menu_item->id);?>" class="button" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a></td>
			</tr>
			<?php } // End menu items loop ?>
		</tbody>
		</table><br />
		</form>
		<?php 
		// If menu items is empty
		} else {
			echo "<p>There are no menu items in this category.</p>";
		}  ?>
		<?php } // End categories loop ?>
		<?php
		// If categories is empty
		} else {
			echo "<p>There are no categories for this menu.</p>";
		} ?>
	</div>
<?php
}

function custom_menu_add() {
	global $wpdb;
	if(isset($_REQUEST['action'])) {
		if($_REQUEST['action'] == 'add-category') {
			add_menu_category(true);
		} elseif($_REQUEST['action'] == 'update-category') {
			update_menu_categories(true);
		} elseif($_REQUEST['action'] == 'delete-category') {
			if(isset($_GET['id'])) {
				$id = $_GET['id'];
			} else {
				$id = NULL;
			}
			delete_menu_category($id, true);
		}
	}
	// Output wrapper
	?>
	<div class="wrap">
	<h2>Add a New Menu</h2>
		<form method="post" action="?page=<?php echo $_GET["page"]; ?>" onsubmit="return add_new_menu_validate();">
			<input type="hidden" name="action" value="add-category" />
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="category_title">Title</label>
					</th>
					<td>
						<input type="text" name="category_title" id="category_title" class="custom-long-text" />
					</td>
				</tr>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="Add Menu" />
			</p>
		</form>
	<?php
		$menus = $wpdb->get_results("SELECT * FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE menu_parent=0 ORDER BY sort_order ASC");
		if(!empty($menus)) {
		?>
		<form method="post" action="?page=<?php echo $_GET["page"]; ?>">
		<input type="hidden" name="action" value="update-category" />
		<h2>Menus</h2>
		<table class="widefat fixed" cellspacing="0">
			<thead>
				<tr class="thead">
					<th scope="col">Title</th>
					<th scope="col" class="custom-width-buttons"></th>
				</tr>
			</thead>
			<tfoot>
				<tr class="thead">
					<th scope="col">Title</th>
					<th scope="col" class="custom-width-buttons"></th>
				</tr>
			</tfoot>
		<tbody>
		<?php
			foreach ($menus as $menu) {
			?>
		<tr>
			<td><input type="hidden" name="menu_category_id" value="<?php echo stripslashes($menu->id);?>" class="menu-category-id" /><input type="text" name="title_<?php echo stripslashes($menu->id);?>" value="<?php echo stripslashes($menu->title); ?>" class="custom-full-width" /></td>
			<td class="custom-width-buttons"><input type="submit" class="button-primary" value="Save Changes" /> <a href="?page=<?php echo $_GET["page"]; ?>&amp;action=delete-category&amp;id=<?php echo stripslashes($menu->id);?>" class="button" onclick="return confirm('Are you sure you want to delete this menu? \nMenu items and categories will also be deleted.')">Delete</a></td>
		</tr>
			<?php } // End parent menu loop
		?>
		</tbody>
		</table><br />
	</form>
	<?php
	} // End if parent menu is not empty
	?>
	</div>
	<?php
}

function custom_menu_categories() {
	global $wpdb;
	if(isset($_REQUEST['action'])) {
		if($_REQUEST['action'] == 'add-category') {
			add_menu_category();
		} elseif($_REQUEST['action'] == 'update-category') {
			update_menu_categories();
		} elseif($_REQUEST['action'] == 'delete-category') {
			if(isset($_GET['id'])) {
				$id = $_GET['id'];
			} else {
				$id = NULL;
			}
			delete_menu_category($id);
		}
	}
	// Output wrapper
	?>
	<div class="wrap">
	<h2>Add a New Menu Category</h2>
		<form method="post" action="?page=<?php echo $_GET["page"]; ?>" onsubmit="return add_new_category_validate();">
			<input type="hidden" name="action" value="add-category" />
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="category_title">Title</label>
					</th>
					<td>
						<input type="text" name="category_title" id="category_title" class="custom-long-text" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="category_subheading">Subheading</label>
					</th>
					<td>
						<input type="text" name="category_subheading" id="category_subheading" class="custom-long-text" />
					</td>
				</tr>
				<?php
				// Loop through all categories, get title and ID while categories exist
				$parent_menu_names = $wpdb->get_results("SELECT * FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE menu_parent=0 ORDER BY sort_order ASC");

				// Only show dropdown if there's more than one item
				if(count($parent_menu_names) > 1) {
				?>
				<tr>
					<th scope="row">Menu</th>
					<td>
						<select name="category_menu" id="category_menu">
							<option value=""></option>
							<?php foreach ($parent_menu_names as $parent_menu_name) { ?>
								<option value="<?php echo stripslashes($parent_menu_name->id); ?>"><?php echo stripslashes($parent_menu_name->title); ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<?php } else { ?>
				<tr class="hidden">
					<td colspan="2">
						<input type="hidden" name="category_menu" value="<?php echo stripslashes($parent_menu_names[0]->id); ?>" />
					</td>
				</tr>
				<?php } ?>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="Add Category" />
			</p>
		</form>
		<p><strong>Hint:</strong> Drag and drop the arrow icon in the &lsquo;order&rsquo; column to rearrange the categories.</p>
	<?php
		if(!empty($parent_menu_names)) {
		?>
		<form method="post" action="?page=<?php echo $_GET["page"]; ?>">
		<input type="hidden" name="action" value="update-category" />
		<?php
		foreach ($parent_menu_names as $parent_menu_name) {
		?>
		<h2><?php echo stripslashes($parent_menu_name->title); ?></h2>
		<?php
		$menu_categories = $wpdb->get_results("SELECT * FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE menu_parent=" . $parent_menu_name->id . " ORDER BY sort_order ASC");
		if(!empty($menu_categories)) {
		?>
			<table class="widefat fixed" cellspacing="0">
			<thead>
				<tr class="thead">
					<th scope="col" class="custom-width-order">Order</th>
					<th scope="col">Title</th>
					<th scope="col">Subheading</th>
					<th scope="col" class="custom-width-buttons"></th>
				</tr>
			</thead>
			<tfoot>
				<tr class="thead">
					<th scope="col" class="custom-width-order">Order</th>
					<th scope="col">Title</th>
					<th scope="col">Subheading</th>
					<th scope="col" class="custom-width-buttons"></th>
				</tr>
			</tfoot>
		<tbody class="custom-sortable-categories">
		<?php
			foreach ($menu_categories as $menu_category) {
			?>
		<tr>
			<td class="drag-me custom-width-order"></td>
			<td><input type="hidden" name="menu_category_id" value="<?php echo stripslashes($menu_category->id);?>" class="menu-category-id" /><input type="text" name="title_<?php echo stripslashes($menu_category->id);?>" value="<?php echo stripslashes($menu_category->title); ?>" class="custom-full-width" /></td>
			<td><input type="text" name="subheading_<?php echo stripslashes($menu_category->id);?>" value="<?php echo stripslashes($menu_category->subheading); ?>" class="custom-full-width" /></td>
			<td class="custom-width-buttons"><input type="submit" class="button-primary" value="Save Changes" /> <a href="?page=<?php echo $_GET["page"]; ?>&amp;action=delete-category&amp;id=<?php echo stripslashes($menu_category->id);?>" class="button" onclick="return confirm('Are you sure you want to delete this category? \nMenu items for this category will also be deleted.')">Delete</a></td>
		</tr>
			<?php } // End categories loop
		?>
		</tbody>
		</table><br />
		<?php
			// End if category is empty
			} else {
				echo "<p>There are no categories for this menu</p>";
			} 
		} // End parent menu loop
	?>
	</form>
	<?php
	} // End if parent menu is not empty
	?>
	</div>
	<?php
}

// Display the menu on the front-end
function display_custom_menu($custom_menu_id) {
	global $wpdb;
	
	$output = "";
	$run_count = 0;

	$category_menu_names = $wpdb->get_results("SELECT * FROM " . CUSTOM_MENU_CATEGORIES_TABLE . " WHERE menu_parent=" . $custom_menu_id . " ORDER BY sort_order ASC");

	if(!empty($category_menu_names)) {
		foreach ($category_menu_names as $category_menu_name) {
			$category_menu_items = $wpdb->get_results("SELECT * FROM " . CUSTOM_MENU_TABLE . " WHERE category=" . $category_menu_name->id . " ORDER BY sort_order ASC");

			// If the category contains menu items, display them
			if(!empty($category_menu_items)) {
				$output .= "\n			<section class=\"clearfix";
				
				if($run_count == 0) {
					$output .= " first";
				}
				
				$output .= "\">
				<h1>" . stripslashes($category_menu_name->title) . "</h1>";
				if(stripslashes($category_menu_name->subheading) != "") {
					$output .= "\n				<p class=\"category-description\">" . stripslashes($category_menu_name->subheading) . "</p>";
				}

				$class = "";
				foreach ($category_menu_items as $category_menu_item) {
					if($class == " left") {
						$class = "";
					} else {
						$class = " left";
					}
					$output .= "\n				<div class=\"menu-item" . $class . "\">";
					if(stripslashes($category_menu_item->title) != "") {
						$output .= "\n					<h2>" . stripslashes($category_menu_item->title) . "</h2>";
					} elseif(stripslashes($category_menu_item->title) == "" && stripslashes($category_menu_item->description) == "") {
						$output .= "\n					<h2>-</h2>";
					}
					$output .= "\n					<p class=\"price\">";
					if(stripslashes($category_menu_item->price) != "") {
						$output .= stripslashes($category_menu_item->price);
					}
					$output .= "</p>";
					if(stripslashes($category_menu_item->description) != "") {
						$output .= "\n					<p>" . stripslashes($category_menu_item->description) . "</p>";
					}
					if(stripslashes($category_menu_item->additional_info) != "") {
						$output .= "\n					<p class=\"additional\">" . stripslashes($category_menu_item->additional_info) . "</p>";
					}
					$output .= "\n				</div>";
				} // End menu items loop
				$output .= "\n			</section>";
				$run_count++;
			} // End if menu items is not empty
		} // End categories loop 
	} // End if categories is not empty
	
	return $output;
}

function showmenu($atts) {
	extract(shortcode_atts(array(
		'id' => '',
	), $atts));

	return display_custom_menu($id);
}
add_shortcode('menu', 'showmenu');

?>