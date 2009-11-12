<?php
/*
Plugin Name: Shortcode Generator
Plugin URI: http://www.getson.info/shortcode-generator
Description: Plugin for allowing you to create an unlimited number of shortcodes. Shortcodes are short, re-usable codes that are replaced with longer values. Great for embedding videos, maps, or keeping content synchronized across multiple pages. Shortcodes can contain other shortcodes, so the possibilities are endless! Comes with a shortcode widget for use in your sidebars. 
Version: 1.0.0
Author: Kyle Getson
Author URI: http://www.kylegetson.com
*/

global $shortcodeGenerator;
$shortcodeGenerator = new shortcodeGenerator();

class shortcodeGenerator {
	function shortcodeGenerator(){
		global $wpdb;
		$wpdb->shortcodes = $wpdb->prefix . "shortcodes";
		
		define(SCG_VERSION,1.05);
		define(SCG_ADMIN_PATH,get_option('siteurl').'/wp-admin/admin.php?page=shortcode-generator/admin/');
		define(SCG_PREFIX_WYSIWYG,'');
		define(SCG_PREFIX_HTML,'html_');
		
		add_action('admin_menu',array(&$this,'admin_menu'));
		add_action('widgets_init', array(&$this,'scg_widget_register') );
		
		$shortcodes = $this->get_shortcodes();
		foreach($shortcodes as $sc){
			$code = "scg_" . ($sc->type == 'wysiwyg' ? SCG_PREFIX_WYSIWYG : SCG_PREFIX_HTML).$sc->shortcode;
			add_shortcode($code,create_function('$atts,$content=null','$value = "'.$sc->value.'"; return do_shortcode(stripslashes($value));'));
		}
		
		register_activation_hook(__FILE__,array(&$this,'install'));
		register_deactivation_hook(__FILE__,array(&$this,'uninstall'));
	}
	
	function admin_menu(){
		add_menu_page('shortcodes', 'ShortCodes', 'manage_options', 'shortcode-generator/admin/index.php');
		add_submenu_page('shortcode-generator/admin/index.php', 'HTML Codes', 'Add New HTML', 'manage_options', 'shortcode-generator/admin/add_new_html.php');
		add_submenu_page('shortcode-generator/admin/index.php', 'Shortcodes', 'Add New WYSIWYG', 'manage_options', 'shortcode-generator/admin/add_new_wysiwyg.php');
	}
	
	function install(){
		require(ABSPATH.'/wp-admin/includes/upgrade.php');
		$installed_version = get_option('scg_version');
		if($installed_version == '' || $installed_version < SCG_VERSION){
			$create_table = "
				CREATE TABLE {$wpdb->shortcodes} (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`shortcode` VARCHAR( 255 ) NOT NULL ,
				`value` LONGTEXT NOT NULL ,
				`type` VARCHAR( 255 ) NOT NULL ,
				UNIQUE (`shortcode`, `type`) ,
				FULLTEXT (
				`value`
				)
				) 
			";
			dbDelta($create_table);
		}
		add_option('scg_version',SCG_VERSION,'','no');
	}
	
	function uninstall(){
		
	}
	
	function clean_shortcode($code){
		$bad = array("'",'"',"\\",'/','[',']','(',')','<','>',' ','!','@','#','$','%','^','&','*','?','`','~');//that shoudl get rid of all the potential problems... maybe
		$clean_code = strtolower(str_replace($bad,'',$code));
		return $clean_code;
	}
	
	function get_shortcodes($start=0,$limit=0){
		global $wpdb;
		if($limit != 0){
			$limit_q = " LIMIT $start,$limit";
		}else{
			$limit_q = '';
		}
		return $wpdb->get_results("SELECT * FROM {$wpdb->shortcodes} ORDER BY ID $limit_q");
	}
	
	function get_shortcode($ID){
		global $wpdb;
		return $wpdb->get_row("SELECT * FROM {$wpdb->shortcodes} WHERE ID=$ID");
	}
	
	function add_shortcode($code,$value,$type){
		global $wpdb;
		$wpdb->insert($wpdb->shortcodes,array('shortcode'=>$code,'value'=>$value,'type'=>$type),array('%s','%s','%s'));
		return $wpdb->insert_id; //return the ID of this shortcode
	}
	
	function fade_msg($msg,$echo=true){
		if($echo)
			echo "\n\n".'<div class="updated fade"><p><strong>'.$msg."</strong></p></div>\n";
		else
			return "\n\n".'<div class="updated fade"><p><strong>'.$msg."</strong></p></div>\n";
	}
	
	/**
	 * return ID or false
	*/
	function code_exists($code,$type){
		global $wpdb;
		$code = $wpdb->get_var("SELECT ID FROM {$wpdb->shortcodes} AND type='$type' WHERE shortcode='$code'");
		if(!empty($code)){
			return $code;
		}else{
			return false;
		}
		
	}
	
	function update_shortcode($ID,$code,$value,$type){
		global $wpdb;
		$wpdb->update($wpdb->shortcodes,array('shortcode'=>$code,'value'=>$value,'type'=>$type),array('ID'=>$ID),array('%s','%s','%s'));
		return true;		
	}
	
	function remove_shortcode($ID){
		global $wpdb;
		$wpdb->query("DELETE FROM {$wpdb->shortcodes} WHERE ID=$ID LIMIT 1");
		return true;
	}
	
	
	/**
	 * Registers each instance of our widget on startup.
	 */
	function scg_widget_register() {
		if ( !$options = get_option('scg_widget_many') )
			$options = array();
	
		$widget_ops = array('classname' => 'widget_many', 'description' => __('This widget which allows insertion of generated shortcodes into the sidebar.'));
		$control_ops = array('width' => 400, 'height' => 350, 'id_base' => 'scg_many');
		$name = __('ShortCode Generator');
	
		$registered = false;
		foreach ( array_keys($options) as $o ) {
			// Old widgets can have null values for some reason
			if ( !isset($options[$o]['title']) ) // we used 'something' above in our exampple.  Replace with with whatever your real data are.
				continue;
	
			// $id should look like {$id_base}-{$o}
			$id = "scg_many-$o"; // Never never never translate an id
			$registered = true;
			wp_register_sidebar_widget( $id, $name, array(&$this,'scg_widget_display'), $widget_ops, array( 'number' => $o ) );
			wp_register_widget_control( $id, $name, array(&$this,'scg_widget_control'), $control_ops, array( 'number' => $o ) );
		}
	
		// If there are none, we register the widget's existance with a generic template
		if ( !$registered ) {
			wp_register_sidebar_widget( 'scg_many-1', $name, array(&$this,'scg_widget_display'), $widget_ops, array( 'number' => -1 ) );
			wp_register_widget_control( 'scg_many-1', $name, array(&$this,'scg_widget_control'), $control_ops, array( 'number' => -1 ) );
		}
	}
	
	
	/**
	 * Displays form for a particular instance of the widget.
	 *
	 * Also updates the data after a POST submit.
	 *
	 * @param array|int $widget_args Widget number. Which of the several widgets of this type do we mean.
	 */
	function scg_widget_control( $widget_args = 1 ) {
		global $wp_registered_widgets;
		static $updated = false; // Whether or not we have already updated the data after a POST submit
	
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
	
		// Data should be stored as array:  array( number => data for that instance of the widget, ... )
		$options = get_option('scg_widget_many');
		if ( !is_array($options) )
			$options = array();
	
		// We need to update the data
		if ( !$updated && !empty($_POST['sidebar']) ) {
			// Tells us what sidebar to put the data in
			$sidebar = (string) $_POST['sidebar'];
	
			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();
	
			foreach ( $this_sidebar as $_widget_id ) {
				// Remove all widgets of this type from the sidebar.  We'll add the new data in a second.  This makes sure we don't get any duplicate data
				// since widget ids aren't necessarily persistent across multiple updates
				if ( 'scg_widget_many' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "many-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed. "many-$widget_number" is "{id_base}-{widget_number}
						unset($options[$widget_number]);
				}
			}
	
			foreach ( (array) $_POST['scg_widget-many'] as $widget_number => $widget_many_instance ) {
				// compile data from $widget_many_instance
				if ( !isset($widget_many_instance['shortcode']) && isset($options[$widget_number]) ) // user clicked cancel
					continue;
				$something = wp_specialchars( $widget_many_instance['title'] );
				$shortcode = $widget_many_instance['shortcode'];
				$options[$widget_number] = array( 'title' => $something,'shortcode'=>$shortcode );  // Even simple widgets should store stuff in array, rather than in scalar
			}
	
			update_option('scg_widget_many', $options);
	
			$updated = true; // So that we don't go through this more than once
		}
	
	
		// Here we echo out the form
		if ( -1 == $number ) { // We echo out a template for a form which can be converted to a specific form later via JS
			$something = '';
			$shortcode = '';
			$number = '%i%';
		} else {
			$something = attribute_escape($options[$number]['title']);
			$shortcode = attribute_escape($options[$number]['shortcode']);
		}
	
		// The form has inputs with names like widget-many[$number][something] so that all data for that instance of
		// the widget are stored in one $_POST variable: $_POST['widget-many'][$number]
	?>
			<p>
				<label>Title:</label><br />
				<input class="widefat" id="widget-many-something-<?php echo $number; ?>" name="scg_widget-many[<?php echo $number; ?>][title]" type="text" value="<?php echo $something; ?>" />
				<!--<input class="widefat" id="widget-many-something-<?php echo $number; ?>" name="scg_widget-many[<?php echo $number; ?>][something]" type="text" value="<?php echo $something; ?>" />-->
				<br /><br />
				<label>Shortcode: </label><br />
				<select name="scg_widget-many[<?php echo $number; ?>][shortcode]">
				<? 
				 
				$codes = $this->get_shortcodes();
				
				foreach($codes as $sc){
					if($shortcode == $sc->ID)//"scg_".($shortcode->type == 'wysiwyg' ? SCG_PREFIX_WYSIWYG : SCG_PREFIX_HTML) . $sc->shortcode )
						echo "<option value='{$sc->ID}' selected>";
					else
						echo "<option value='{$sc->ID}'>";
					echo '[scg_'. ($sc->type == 'wysiwyg' ? SCG_PREFIX_WYSIWYG : SCG_PREFIX_HTML) . $sc->shortcode .']';
					echo "</option>";
				}
				?>
				</select>
				<input type="hidden" id="scg_widget-many-submit-<?php echo $number; ?>" name="scg_widget-many[<?php echo $number; ?>][submit]" value="1" />
			</p>
	<?php
	}
	
	
	/**
	 * Displays widget.
	 *
	 * Supports multiple widgets.
	 *
	 * @param array $args Widget arguments.
	 * @param array|int $widget_args Widget number. Which of the several widgets of this type do we mean.
	 */
	function scg_widget_display( $args, $widget_args = 1 ) {
		extract( $args, EXTR_SKIP );
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
	
		// Data should be stored as array:  array( number => data for that instance of the widget, ... )
		$options = get_option('scg_widget_many');
		if ( !isset($options[$number]) )
			return;
	
		$shortcodeID = $options[$number]['shortcode'];
		$generated_value = stripslashes( $this->get_shortcode($shortcodeID)->value );
		
		echo $before_widget;
		
		echo $before_title;
		echo $options[$number]['title'];
		echo $after_title;
		echo "<div class='scg_widget_shortcode'>\n";
		echo "\n<!-- ID:$shortcodeID -->\n";
		echo do_shortcode($generated_value);//apply_filters('the_content',$generated_value);
		echo "\n<!-- ID:$shortcodeID -->\n";
		echo "\n</div>";
		
		
		// Do stuff for this widget, drawing data from $options[$number]
		echo $after_widget;
	}
	
	
	function get_WYSIWYG($id='content',$name='content',$value=''){
		$editor =<<<HTML
		<!-- /wp-includes/js/tinymce/tiny_mce.js needs to be included in the page(s) that use this-->
			<script type="text/javascript">
				<!--
				tinyMCE.init({
				theme : "advanced",	
				skin:"wp_theme", 
				theme_advanced_buttons1:"bold,italic,strikethrough,underline,separator,bullist,numlist,outdent,indent,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,styleprops,separator,separator,spellchecker,search,separator,fullscreen,wp_adv,code", 
				theme_advanced_buttons2:"fontsizeselect,formatselect,pastetext,pasteword,removeformat,separator,charmap,print,separator,forecolor,emotions,separator,sup,sub,separator,undo,redo,attribs,wp_help", 
				theme_advanced_buttons3:"", 
				theme_advanced_buttons4:"", 
				language:"en", 
				spellchecker_languages:"+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv", 
				theme_advanced_toolbar_location:"top", 
				theme_advanced_toolbar_align:"left", 
				theme_advanced_statusbar_location:"bottom", 
				theme_advanced_resizing:"1", 
				theme_advanced_resize_horizontal:"", 
				dialog_type:"modal", 
				relative_urls:"", 
				remove_script_host:"", 
				convert_urls:"", apply_source_formatting:"", remove_linebreaks:"1", paste_convert_middot_lists:"1", paste_remove_spans:"1", 
				paste_remove_styles:"1", 
				gecko_spellcheck:"1", 
				entities:"38,amp,60,lt,62,gt", 
				accessibility_focus:"1", tab_focus:":prev,:next",  
				wpeditimage_disable_captions:"", 
				plugins:"safari,inlinepopups,autosave,spellchecker,paste,wordpress,fullscreen,-emotions,-print,-searchreplace,-xhtmlxtras,-advlink,", 
				mode : "exact",
				elements : "{$id}",
				width : "565",
				height : "200"
				});
				-->
			</script>
			<textarea id="{$id}" name="{$name}">{$value}</textarea>
HTML;
	return $editor;
	}
}
?>