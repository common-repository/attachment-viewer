<?php
/*
	Plugin Name: Attachment Viewer Widget
	Plugin URI: http://blog.alexgirard.com/
	Description: Display the latest pictures attachment of the blog.
	Version: 1.0
	Author: Alexandre Girard
	Author URI: http://www.alexgirard.com/
*/

function widget_attachview_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;
		
	function sanitize_attachment($name) {
    	$name = strtolower($name); // all lowercase
    	$name = preg_replace('/[^a-z0-9 ]/','', $name); // nothing but a-z 0-9 and spaces
    	$name = preg_replace('/\s+/','-', $name); // spaces become hyphens
    	return $name;
  	}

	// Options and default values for this widget
	function widget_attachview_options() {
		return array(
			'Title' => "Latest Pictures",
			'Picture Count' => 10,
			'Picture Link' => "post",
			'Picture Category' => 0
			);
	}

	function widget_attachview($args) {
		global $wpdb;

		extract($args);

		// Each widget can store and retrieve its own options.
		// Here we retrieve any options that may have been set by the user
		// relying on widget defaults to fill the gaps.
		$options = array_merge(widget_attachview_options(), get_option('widget_attachview'));
		unset($options[0]); //returned by get_option(), but we don't need it

		$query = "SELECT DISTINCT * FROM $wpdb->posts, $wpdb->term_relationships, $wpdb->term_taxonomy ";
		$query .= "WHERE $wpdb->posts.post_type LIKE 'attachment' ";
		$query .= "AND $wpdb->posts.post_mime_type REGEXP 'image' ";
		$query .= "AND ($wpdb->posts.post_parent = $wpdb->term_relationships.object_id AND $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id AND $wpdb->term_taxonomy.term_id = " . $options['Picture Category']. ") ";
		$query .= "GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_date DESC ";
		$query .= "LIMIT ".$options['Picture Count'];
		
		$attachments = $wpdb->get_results($query);
		
		echo $before_widget . $before_title . $options['Title'] . $after_title;
		
		$counter = 0;
		$attachment_list = "<ul>";
		foreach($attachments as $attach) {
			$attachment_list .= "<li>";
			
			if (strcmp($options['Picture Link'], "post") == 0) {
				$attachment_list .= "<a href='".get_permalink($attach->post_parent)."'>";
			}
			elseif (strcmp($options['Picture Link'], "file") == 0) {
				$attachment_list .= "<a href='".wp_get_attachment_url($attach->ID)."'>";
			}
			
			$attachment_list .= "<img src='".wp_get_attachment_thumb_url($attach->ID)."'/></a></li>";
		}
		$attachment_list .= "</ul>";
		echo $attachment_list;
		
		echo $after_widget;
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function widget_attachview_control() {
		// Each widget can store and retrieve its own options.
		// Here we retrieve any options that may have been set by the user
		// relying on widget defaults to fill the gaps.
		if(($options = get_option('widget_attachview')) === FALSE) $options = array();

		$options = array_merge(widget_attachview_options(), $options);
		unset($options[0]); //returned by get_option(), but we don't need it

		// If user is submitting custom option values for this widget
		if ( $_POST['attachview-submit'] ) {
			// Remember to sanitize and format use input appropriately.
			foreach($options as $key => $value)
				if(array_key_exists('attachview-'.sanitize_attachment($key), $_POST))
				$options[$key] = strip_tags(stripslashes($_POST['attachview-'.sanitize_attachment($key)]));

			// Save changes
			update_option('widget_attachview', $options);
		}
		
		// Title option
		echo '<p style="text-align:left"><label for="attachview-title">Title: <input style="width: 200px;" id="attachview-title" name="attachview-title" type="text" value="'.$options['Title'].'" /></label></p>';
		// Picture count option
		echo '<p style="text-align:left"><label for="attachview-picture-count">Picture Count: <input style="width: 200px;" id="attachview-picture-count" name="attachview-picture-count" type="text" value="'.$options['Picture Count'].'" /></label></p>';
		// Link option
		echo '<p style="text-align:left"><label for="attachview-picture-link">Link to: <select id="attachview-picture-link" name="attachview-picture-link">';
		
		$option_link = '<option value="post" ';
		$option_link .= strcmp($options['Picture Link'], "post") != 0 ? '' : 'selected="selected"';
		$option_link .= '>Post</option>';
		echo $option_link;
		
		$option_link = '<option value="file" ';
		$option_link .= strcmp($options['Picture Link'], "file") != 0 ? '' : 'selected="selected"';
		$option_link .= '>File</option>';
		echo $option_link;
		
		echo'</select></label></p>';
		// Category option
		$categories = get_categories(); 
		echo '<p style="text-align:left"><label for="attachview-picture-category">Select category: <select id="attachview-picture-category" name="attachview-picture-category">';
		echo '<option value="0">All</option>';
		foreach ($categories as $cat) {
			if(!empty($cat->term_id)){
				$option = '<option value="'.$cat->term_id.'" ';
				$option .= strcmp($options['Picture Category'], $cat->term_id) != 0 ? '' : 'selected="selected"';
				$option .= '>';
				$option .= $cat->cat_name;
				$option .= ' ('.$cat->category_count.')';
				$option .= '</option>';
				echo $option;
			}
		}
		echo'</select></label></p>';
		// Submit
		echo '<input type="hidden" id="attachview-submit" name="attachview-submit" value="1" />';
	}
	
	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	register_sidebar_widget('Latest Pictures', 'widget_attachview');

	// This registers our optional widget control form.
	register_widget_control('Latest Pictures', 'widget_attachview_control');
}

// Run our code later in case this loads prior to any required plugins.
add_action('plugins_loaded', 'widget_attachview_init');

?>