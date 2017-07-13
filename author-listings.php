<?php
/*
Plugin Name: Author Listings
Plugin URI: http://wordpress.org/extend/plugins/author-listing/
Description: Provides template tags to which list the authors which have recently been active (or not active).
Author: Simon Wheatley
Version: 1.02
Author URI: http://simonwheatley.co.uk/wordpress/
*/

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

require_once ( dirname (__FILE__) . '/plugin.php' );
require_once ( dirname (__FILE__) . '/author.php' );

/**
 * A Class to list all active (or inactive) authors in the WordPress installation.
 *
 * Extends John Godley's WordPress Plugin Class, which adds all sorts of functionality
 * like templating which can be overriden by the theme, etc.
 * 
 * The following functions hook up public methods from this class are effectively template 
 * tags, see their own documentation for further information:
 * * list_inactive_authors()
 * * list_active_authors()
 *
 * @package default
 * @author Simon Wheatley
 **/
class AuthorListing extends AuthorListing_Plugin
{
	/**
	 * A "cache" to store the active author IDs in.
	 *
	 * @var array
	 **/
	protected $active_author_ids = array();

	/**
	 * A "cache" to store the active authors in.
	 *
	 * @var array
	 **/
	protected $active_authors = array();

	/**
	 * A "cache" to store the inactive authors in.
	 *
	 * @var array
	 **/
	protected $inactive_authors = array();
	
	/**
	 * The number of days over which we are considering the activity
	 * to have taken place.
	 *
	 * @var integer
	 **/
	public $cut_off_days;
	
	/**
	 * Determines whether password protected posts are considered or ignored. Defaults 
	 * to false.
	 *  
	 * You can change this flag like so:
	 * $AuthorListing->include_protected_posts = true;
	 * (After this point all protected posts will be considered when looking for 
	 * recent posts).
	 *
	 * @var bool
	 **/
	public $include_protected_posts;
	

	/**
	 * Constructor for this class. 
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	function AuthorListing() 
	{
		$this->register_plugin ('author-listings', __FILE__);
		$this->cut_off_days = 30;
		$include_protected_posts = false;
	}
	
	/**
	 * A template method to print the (in)active authors. Default HTML can be overriden
	 * by adding a new template file into view/author-listings/active-authors-list.php
	 * and/or view/author-listings/inactive-authors-list.php in the root of the 
	 * theme directory.
	 *
	 * @param int $active_authors optional Defaults to 1 to show active authors. Will be cast to boolean. Determines if we show active or inactive authors.
	 * @return void
	 * @author Simon Wheatley
	 **/
	public function list_authors( $active_authors )
	{
		$template_vars = array();
		if ( $active_authors ) {
			$template_vars['authors'] = $this->get_active_authors();
		} else {
			$template_vars['authors'] = $this->get_inactive_authors();
		}
		
		// Print the HTML
		if ( $active_authors ) {
			$this->render( 'active-authors-list', $template_vars );
		} else {
			$this->render( 'inactive-authors-list', $template_vars );
		}
	}

	/**
	 * A protected method to return the UNIX timestamp at which our activity period starts
	 *
	 * @return int UNIX timestamp
	 * @author Simon Wheatley
	 **/
	protected function cut_off_time()
	{
		return time() - ( 60 * 60 * 24 * $this->cut_off_days );
	}

	/**
	 * A method to return the list of author IDs who have been active in the last 30 days.
	 *
	 * @param boolean $active_authors optional Determines whether active or inactive author IDs are returned.
	 * @return array WordPress User IDs for the authors who have been (in)active in the last 30 days.
	 * @author Simon Wheatley
	 **/
	protected function active_author_ids()
	{
		// Maybe this has already been done?
		if ( ! empty( $this->active_author_ids ) ) return $this->active_author_ids;
		// ...obviously not

		global $wpdb;
		$cut_off_time = time() - ( 60 * 60 * 24 * $this->cut_off_days );
		// SWTODO This SELECT DISTINCT query could need optimising (maybe check there's an index on post_author)
		// N.B. The greater than *or equal* prevents people falling in the infinitesimally
		// small crack where they posted their article *exactly* thirty years ago to the day. :)
		// SWTODO: This does NOT cope with posts being marked "private", which is different to password protecting posts
		$unprepared_sql  = "SELECT DISTINCT my_posts.post_author FROM ( SELECT * FROM $wpdb->posts ORDER BY post_date_gmt DESC ) AS my_posts WHERE post_date_gmt >= FROM_UNIXTIME( %d ) ";
		$unprepared_sql .= "AND my_posts.post_status = 'publish' ";
		$unprepared_sql .= "AND my_posts.post_type = 'post' ";
		// It strikes me that post_password might be NULL or the empty string, best check both
		if ( ! $this->include_protected_posts ) $unprepared_sql .= "AND ( my_posts.post_password IS NULL OR my_posts.post_password = '' ) ";
		$unprepared_sql .= "ORDER BY my_posts.post_date_gmt DESC ";
		$sql = $wpdb->prepare( $unprepared_sql, $this->cut_off_time() );

		$this->active_author_ids = $wpdb->get_col( $sql );
		return $this->active_author_ids;
	}

	/**
	 * A method to return the list of author IDs who have NOT been active in the last 30 days.
	 *
	 * @param boolean $active_authors optional Determines whether active or inactive author IDs are returned.
	 * @return array WordPress User IDs for the authors who have been (in)active in the last 30 days.
	 * @author Simon Wheatley
	 **/
	protected function inactive_author_ids()
	{
		// Maybe this has already been done?
		if ( ! empty( $this->inactive_author_ids ) ) return $this->inactive_author_ids;
		// ...obviously not

		global $wpdb;
		// SWTODO: This SELECT DISTINCT query may need optimising (maybe check there's an index on post_author)
		// SWTODO: This does NOT cope with posts being marked "private", which is different to password protecting posts
		$unprepared_sql  = "SELECT DISTINCT my_posts.post_author ";
		$unprepared_sql .= "FROM ( SELECT * FROM $wpdb->posts ORDER BY post_date_gmt DESC ) AS my_posts ";
		$unprepared_sql .= "WHERE my_posts.post_date_gmt < FROM_UNIXTIME( %d ) ";
		$unprepared_sql .= "AND my_posts.post_status = 'publish' ";
		$unprepared_sql .= "AND my_posts.post_type = 'post' ";
		// Now weed out those who HAVE posted in the period of activity.
		$active_author_ids = implode( ',', $this->active_author_ids() );
		$unprepared_sql .= "AND my_posts.post_author NOT IN ( $active_author_ids ) ";
		// It strikes me that post_password might be NULL or the empty string, best check both
		if ( ! $this->include_protected_posts ) $unprepared_sql .= "AND ( my_posts.post_password IS NULL OR my_posts.post_password = '' ) ";
		$unprepared_sql .= "ORDER BY my_posts.post_date_gmt DESC ";
		$sql = $wpdb->prepare( $unprepared_sql, $this->cut_off_time() );

		$this->inactive_author_ids = $wpdb->get_col( $sql );
		return $this->inactive_author_ids;
	}

	/**
	 * A getter which returns an array of active authors as WP_User objects
	 *
	 * @return array An array of WP_User objects.
	 * @author Simon Wheatley
	 **/
	protected function get_active_authors()
	{
		// Maybe this has already been done?
		if ( ! empty( $this->active_authors ) ) return $this->active_authors;
		// ...obviously not
		$author_ids = $this->active_author_ids();
		foreach ( $author_ids AS $author_id ) {
			$author = new AL_Author( $author_id );
			$author->include_protected_posts = $this->include_protected_posts;
			$this->active_authors[] = $author;
		}
		// All ready.
		return $this->active_authors;
	}

	/**
	 * A getter which returns an array of active authors as WP_User objects
	 *
	 * @return array An array of WP_User objects.
	 * @author Simon Wheatley
	 **/
	protected function get_inactive_authors()
	{
		// Maybe this has already been done?
		if ( ! empty( $this->inactive_authors ) ) return $this->inactive_authors;
		// ...obviously not
		$author_ids = $this->inactive_author_ids( false );
		foreach ( $author_ids AS $author_id ) {
			$author = new AL_Author( $author_id );
			$author->include_protected_posts = $this->include_protected_posts;
			$this->inactive_authors[] = $author;
		}
		// All ready.
		return $this->inactive_authors;
	}
	
}

/**
 * Instantiate the plugin
 *
 * @global
 **/

$AuthorListing = new AuthorListing();

/**
 * A template tag function which wraps the list_authors method from the 
 * AuthorListing class for convenience.
 *
 * @param string $args optional A string of URL GET alike variables which are parsed into params for the method call
 * @return void Prints some HTML
 * @author Simon Wheatley
 **/
function list_active_authors( $args = null )
{
	global $AuthorListing;

	// Traditional WP argument munging.
	$defaults = array(
		'days' => 30,
		'include_protected_posts' => false
	);
	$r = wp_parse_args( $args, $defaults );
	
	// Sort out include_protected_posts arg
	if ( $r['include_protected_posts'] == 'yes' ) {
		$r['include_protected_posts'] = true;
	}
	if ( $r['include_protected_posts'] == 'no' ) {
		$r['include_protected_posts'] = false;
	}
	// Now cast to a boolean to be sure
	$r['include_protected_posts'] = (bool) $r['include_protected_posts'];
	
	// Set the activity period
	$AuthorListing->cut_off_days = $r['days'];
	
	// Set the protected posts
	$AuthorListing->include_protected_posts = $r['include_protected_posts'];	
	
	// Call the method
	$active = true;
	$AuthorListing->list_authors( $active );
}

/**
 * A template tag function which wraps the list_authors method from the 
 * AuthorListing class for convenience.
 *
 * @param string $args optional A string of URL GET alike variables which are parsed into params for the method call
 * @return void Prints some HTML
 * @author Simon Wheatley
 **/
function list_inactive_authors( $args = null )
{
	global $AuthorListing;

	// Traditional WP argument munging.
	$defaults = array(
		'days' => 30,
		'include_protected_posts' => false
	);
	$r = wp_parse_args( $args, $defaults );
	
	// Sort out include_protected_posts arg
	if ( $r['include_protected_posts'] == 'yes' ) {
		$r['include_protected_posts'] = true;
	}
	if ( $r['include_protected_posts'] == 'no' ) {
		$r['include_protected_posts'] = false;
	}
	// Now cast to a boolean to be sure
	$r['include_protected_posts'] = (bool) $r['include_protected_posts'];
		
	// Set the activity period
	$AuthorListing->cut_off_days = $r['days'];
	
	// Set the protected posts
	$AuthorListing->include_protected_posts = $r['include_protected_posts'];	
	
	// Call the method
	$active = false;
	$AuthorListing->list_authors( $active );
}

?>