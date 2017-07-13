<?php

/*  Copyright 2008 Simon Wheatley

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/**
 * Extends WP_User to add the ability to find and display the latest post
 * by that user.
 *
 * @package WordPress base library
 * @author Simon Wheatley
 * @copyright Copyright (C) Simon Wheatley
 **/
class AL_Author extends WP_User
{

	/**
	 * The latest post by this author
	 * @var object
	 **/
	protected $latest_post;
	/**
	 * A flag determining whether password protected posts 
	 * will be considered when locating the latest post
	 * @var integer
	 **/
	public  $include_protected_posts = false;
	
	/**
	 * Template function. Prints the URL for the latest post by this author.
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function latest_post_permalink()
	{
		$permalink = get_permalink( $this->get_latest_post()->ID );
		echo apply_filters( 'the_permalink', $permalink );
	}
	
	/**
	 * Very similar to the WordPress template "tag" the_date(). Prints the date
	 * of the latest post. The main difference between this and the time one is 
	 * the filters which are applied.
	 *
	 * @param string $d optional A PHP date() format string
	 * @param string $before optional A string to print before the date
	 * @param string $after optional A string to print after the date
	 * @param bool $echo optional Whether to echo (if true) or return (if false) the completed string
	 * @return string|void A string indicating the post date
	 * @author Simon Wheatley
	 **/
	// SWTODO: This and the time function could be drier by using an internal function to get the MySQL DATETIME value
	public function latest_post_date( $d = '', $before = '', $after = '', $echo = true )
	{
		$the_date = '';
		$the_date .= $before;
		if ( $d == '' ) {
			$the_date .= mysql2date( get_option('date_format'), $this->get_latest_post()->post_date );
		} else {
			$the_date .= mysql2date( $d, $this->get_latest_post()->post_date );
		}
		$the_date .= $after;
		$the_date = apply_filters( 'the_date', $the_date, $d, $before, $after );
		if ( $echo ) {
			echo $the_date;
		} else {
			return $the_date;
		}
	}

	/**
	 * Very similar to the WordPress template "tag" the_time(). Prints the time
	 * of the latest post. The main difference between this and the date one is 
	 * the filters which are applied.	 
	 *
	 * @param string $d optional A PHP date format string
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function latest_post_time( $d = '' ) {
		// Maybe use the default time formatting (if no other is supplied)
		if ( '' == $d ) {
			$d = get_option('time_format');
		}
		// Get the MySQL DATETIME value for the post
		$time = $this->get_latest_post()->post_date;
		// Format
		$time = mysql2date( $d, $time );
		// Loadsa filters!
		$time = apply_filters( 'get_post_time', $time, $d, $gmt );
		$time = apply_filters( 'get_the_time', $time, $d );
		// No option to return, as per WP template tag the_time()
		echo apply_filters( 'the_time', $time, $d );
	}

	/**
	 * Template function. Prints the latest post title, applies all the WordPress filters for the_title.
	 *
	 * @param string $before optional A string to print before the title
	 * @param string $after optional A string to print after the title
	 * @param string $echo optional Whether to echo or return the title
	 * @return void|string Depends if $echo is true or false as to whether the latest post title is returned or printed
	 * @author Simon Wheatley
	 **/
	public function latest_post_title( $before = '', $after = '', $echo = true )
	{
		$title = $this->get_latest_post()->post_title;

		if ( ! is_admin() ) {
			if ( ! empty( $this->get_latest_post()->post_password ) ) {
				$title = sprintf( __( 'Protected: %s' ), $title );
			} else if ( isset($post->post_status) && 'private' == $this->get_latest_post()->post_status ) {
				$title = sprintf( __( 'Private: %s' ), $title );
			}
		}
		$the_title = apply_filters( 'the_title', $title );

		if ( strlen($title) == 0 ) {
			return;
		}

		$title = $before . $title . $after;

		if ( $echo ) {
			echo $title;
		} else {
			return $title;
		}
	}
	
	/**
	 * Template function. Prints the latest post excerpt or the first part of the content before the,
	 * <!-- more --> "tag", or the content applies all the relevant WordPress filters in each case.
	 *
	 * @param string $more_link_text optional If a "more" link is printed, this defines it's text.
	 * @param string $after optional A string to print after the title
	 * @param string $echo optional Whether to echo or return the title
	 * @return void|string Depends if $echo is true or false as to whether the latest post title is returned or printed
	 * @author Simon Wheatley
	 **/
	public function latest_post_simple_excerpt( $more_link_text = '(more...)', $length = 200, $etc = '...', $break_word = false )
	{
		$output = '';
		$output = $this->get_latest_post()->post_content;
		// Strip shortcodes
		$output = strip_shortcodes( $output );
		// No HTML at all
		$output = strip_tags( $output );
		// Limit words/characters
		$output = $this->truncate( $output, $length, $etc, $break_word );
		// Add More Text
		$output .= " <a href=\"" . get_permalink( $this->get_latest_post()->ID ) . "#more-{$this->get_latest_post()->ID}\" class=\"more-link\" title=\"Read more on &quot;{$this->latest_post_title( '', '', false )}&quot;\">$more_link_text</a>";
		echo $output;
	}
	
	/**
	 * Ripped from Smarty truncate modifier plugin
	 *
	 * Purpose:  Truncate a string to a certain length if necessary,
	 *           optionally splitting in the middle of a word, and
	 *           appending the $etc string or inserting $etc into the middle.
	 *
	 * @author   Monte Ohrt <monte at ohrt dot com>
	 * @param $string string The string to operate on.
	 * @param $length integer The number of characters we're aiming for
	 * @param $etc string A string to add onto the end, after we've truncated
	 * @return string
	 */
	protected function truncate( $string, $length = 80, $etc = '...', $break_words = false )
	{
	    if ($length == 0) {
	        return '';
		}

	    if (strlen($string) > $length) {
	        $length -= strlen($etc);
	        if ( ! $break_words && ! $middle ) {
	            $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length+1));
	        }
            return substr($string, 0, $length).$etc;
	    } else {
	        return $string;
	    }
	}
	
	
	/**
	 * Template function. Hooks into the User Photo plugin functionality to display an IMG for the author.
	 *
	 * @param string $before optional A string to place before the user photo, can contain HTML
	 * @param string $after optional A string to place after the user photo, can contain HTML
	 * @param array $attributes optional An array of attributes to put in the IMG element
	 * @param string $default_src optional A default image to use if one is not available/approved
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function user_photo( $before = '', $after = '', $attributes = array(), $default_src = '' )
	{
		if ( ! function_exists( 'userphoto__get_userphoto' ) ) {
			echo '<p><strong>The <a href="http://wordpress.org/extend/plugins/user-photo/">User Photo plugin</a> does not appear to be installed.</strong></p>';
			return;
		}
		echo userphoto__get_userphoto( $this->ID, USERPHOTO_FULL_SIZE, $before, $after, $attributes, $default_src );
	}
	
	/**
	 * Template function. Hooks into the User Photo plugin functionality to display a thumbnail IMG for the author.
	 *
	 * @param string $before optional A string to place before the user thumbnail, can contain HTML
	 * @param string $after optional A string to place after the user thumbnail, can contain HTML
	 * @param array $attributes optional An array of attributes to put in the IMG element
	 * @param string $default_src optional A default image to use if one is not available/approved
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function user_thumbnail( $before = '', $after = '', $attributes = array(), $default_src = '' )
	{
		if ( ! function_exists( 'userphoto__get_userphoto' ) ) {
			echo '<p><strong>The <a href="http://wordpress.org/extend/plugins/user-photo/">User Photo plugin</a> does not appear to be installed.</strong></p>';
			return;
		}
		echo userphoto__get_userphoto( $this->ID, USERPHOTO_THUMBNAIL_SIZE, $before, $after, $attributes, $default_src );
	}

	/**
	 * A getter to return the latest post by this particular author, caching it the 
	 * first time it does so to reduce DB load.
	 *
	 * @return object The latest post by this author.
	 * @author Simon Wheatley
	 **/
	protected function get_latest_post()
	{
		// Maybe we've found and cached this already
		if ( ! empty( $this->latest_post ) ) return $this->latest_post;
		// ...obviously not
		$this->latest_post = get_post( $this->latest_post_id() );
		return $this->latest_post;
	}

	/**
	 * A method to return the ID for the latest post by this particular author.
	 *
	 * @return integer The ID for the latest post by this particular author.
	 * @author Simon Wheatley
	 **/
	protected function latest_post_id() 
	{
		global $wpdb;
		$unprepared_sql  = "SELECT ID FROM $wpdb->posts WHERE post_author = %d AND post_status = 'publish' AND post_type = 'post' ";
		if ( ! $this->include_protected_posts ) $unprepared_sql .= "AND ( post_password IS NULL OR post_password = '' ) ";
		$unprepared_sql .= "ORDER BY post_date_gmt DESC LIMIT 1 ";
		$sql = $wpdb->prepare( $unprepared_sql, $this->ID );
		return $wpdb->get_var( $sql );
	}

}

?>