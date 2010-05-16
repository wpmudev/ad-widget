<?php
/*
Plugin Name: Ad widget lite
Plugin URI: http://incsub.com
Description: This plugin adds a simple advertisement widget with customisable display options.
Author: Barry
Version: 2.0
Author URI: http://caffeinatedb.com
WDP ID: 85
*/

/*
Copyright 2007-2009 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Set this to yes, to allow PHP to be entered in the code box - on your own head be it
//define( 'ADLITE_IAMAPRO', 'yes');
//define( 'ADLITE_SUPPORTERONLY', 'yes');

class adlitewidget extends WP_Widget {

	function adlitewidget() {

		// Load the text-domain
		$locale = apply_filters( 'adlitewidget_locale', get_locale() );
		$mofile = dirname(__FILE__) . "/adlitewidget-$locale.mo";

		if ( file_exists( $mofile ) )
			load_textdomain( 'adlitewidget', $mofile );

		$widget_ops = array( 'classname' => 'adlitewidget', 'description' => __('Display HTML selectively based on simple rules', 'adlitewidget') );
		$control_ops = array('width' => 400, 'height' => 350, 'id_base' => 'adlitewidget');
		$this->WP_Widget( 'adlitewidget', __('AD lite Widget', 'adlitewidget'), $widget_ops, $control_ops );
	}

	function is_fromsearchengine() {
		$ref = $_SERVER['HTTP_REFERER'];

		$SE = array('/search?', 'images.google.', 'web.info.com', 'search.', 'del.icio.us/search', 'soso.com', '/search/', '.yahoo.' );

		foreach ($SE as $url) {
			if (strpos($ref,$url)!==false) return true;
		}
		return false;
	}

	function is_ie()
	{
	    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
	        return true;
	    else
	        return false;
	}

	function hit_selective($selectives = array()) {

		if(!empty($selectives)) {
			foreach($selectives as $key => $value) {
				switch($key) {

					case 'notloggedin':	if(!is_user_logged_in()) {
											return true;
										}
										break;

					case 'isloggedin':	if(is_user_logged_in()) {
											return true;
										}
										break;

					case 'notcommented':
										if ( !isset($_COOKIE['comment_author_'.COOKIEHASH]) ) {
											return true;
										}
										break;

					case 'issearched':	if($this->is_fromsearchengine()) {
											return true;
										}
										break;

					case 'isexternal':	if(!empty($_SERVER['HTTP_REFERER'])) {
											$internal = str_replace('http://','',get_option('siteurl'));
											if(!preg_match( '/' . addcslashes($internal,"/") . '/i', $_SERVER['HTTP_REFERER'] )) {
													return true;
											}
										}
										break;

					case 'isie':		if($this->is_ie()) {
											return true;
										}
										break;
					case 'notsupporter':
										if(function_exists('is_supporter') && !is_supporter()) {
											return true;
										}
										break;

					case 'none':		break;
					default:
										return true;
				}
			}
			// Passed everything without a true so return false
			return false;
		} else {
			return true;
		}

	}

	function widget( $args, $instance ) {

		extract( $args );

		// build the check array
		$options = array(
			'notloggedin' 	=> '0',
			'isloggedin' 	=> '0',
			'notcommented' 	=> '0',
			'issearched'	=> '0',
			'isexternal'	=> '0',
			'isie'			=> '0',
			'notsupporter'	=> '0'
		);

		foreach($options as $key => $value) {
			if(isset($instance[$key])) {
				$options[$key] = $instance[$key];
			} else {
				unset($options[$key]);
			}
		}

		if($this->hit_selective($options) || empty($options)) {
			echo $before_widget;
			$title = apply_filters('widget_title', $instance['title'] );

			if ( $title ) {
				echo $before_title . $title . $after_title;
			}

			if ( !empty( $instance['content'] ) ) {
				echo '<div class="textwidget">';
				if(defined('ADLITE_IAMAPRO') && ADLITE_IAMAPRO == 'yes') {
					eval(" ?> " . stripslashes($instance['content']) . " <?php ");
				} else {
					echo stripslashes($instance['content']);
				}
				echo '</div>';
			}
			echo $after_widget;
		}


	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$defaults = array(
			'title' 		=> '',
			'content' 		=> '',
			'none' 			=> '1',
			'notloggedin' 	=> '0',
			'isloggedin' 	=> '0',
			'notcommented' 	=> '0',
			'issearched'	=> '0',
			'isexternal'	=> '0',
			'isie'			=> '0',
			'notsupporter'	=> '0'
		);

		foreach ( $defaults as $key => $val ) {
			$instance[$key] = $new_instance[$key];
		}

		if ( current_user_can('unfiltered_html') ) {
			$instance['content'] =  $instance['content'];
		} else {
			$instance['content'] = stripslashes( wp_filter_post_kses( addslashes($instance['content']) ) ); // wp_filter_post_kses() expects slashed
		}

		return $instance;
	}

	function form( $instance ) {

		$defaults = array(
			'title' 		=> '',
			'content' 		=> '',
			'none' 			=> '1',
			'notloggedin' 	=> '0',
			'isloggedin' 	=> '0',
			'notcommented' 	=> '0',
			'issearched'	=> '0',
			'isexternal'	=> '0',
			'isie'			=> '0',
			'notsupporter'	=> '0'
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$selections = array(
								"notloggedin"	=>	__("User isn't logged in",'adlitewidget'),
								"isloggedin"	=>	__("User is logged in",'adlitewidget'),
								"notcommented"	=>	__("User hasn't commented before",'adlitewidget'),
								"issearched"	=>	__("User arrived via a search engine",'adlitewidget'),
								"isexternal"	=>	__("User arrived via a link",'adlitewidget'),
								"isie"		=>	__("User is using Internet Explorer",'adlitewidget')
								);

		if(function_exists('is_supporter')) {
			$selections['notsupporter'] = __("User isn't a supporter",'adlitewidget');
		}

		?>
			<p>
				<?php _e('Show the content below if one of the checked items is true (or no items are checked):','adlitewidget'); ?>
			</p>
			<p>
				<?php
					echo "<input type='hidden' value='1' name='" . $this->get_field_name( 'none' ) . "' id='" . $this->get_field_name( 'none' ) . "' />";
					foreach($selections as $key => $value) {
						echo "<input type='checkbox' value='1' name='" . $this->get_field_name( $key ) . "' id='" . $this->get_field_name( $key ) . "' ";
						if($instance[$key] == '1') echo "checked='checked' ";
						echo "/>&nbsp;" . $value . "<br/>";
					}
				?>
			</p>
			<p>
				<?php _e('Content Title','adlitewidget'); ?><br/>
				<input type='text' class='widefat' name='<?php echo $this->get_field_name( 'title' ); ?>' id='<?php echo $this->get_field_id( 'title' ); ?>' value='<?php echo esc_attr(stripslashes($instance['title'])); ?>' />
			</p>
			<p>
				<?php _e('Content to display','adlitewidget'); ?><br/>
				<textarea class='widefat' name='<?php echo $this->get_field_name( 'content' ); ?>' id='<?php echo $this->get_field_id( 'content' ); ?>' rows='5' cols='40'><?php echo stripslashes($instance['content']); ?></textarea>
			</p>
	<?php
	}
}

function adlitewidget_register() {
	if(defined('ADLITE_SUPPORTERONLY') && function_exists('is_supporter')) {
		register_widget( 'adlitewidget' );
	} elseif(!defined('ADLITE_SUPPORTERONLY')) {
		register_widget( 'adlitewidget' );
	}

}

add_action( 'widgets_init', 'adlitewidget_register' );


?>