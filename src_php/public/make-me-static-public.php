<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://madpenguin.uk
 * @since      0.9.0
 * @package    make-me-static-public
 *
 * So the main focus of this module is to produce a customised version of an XML sitemap
 * for use exclusively by our crawler. This sitemap contains a pretty stylesheet and views
 * quite happily in a browser, however we have commandeered the "priority" field to store
 * the number of "sub" items in nested sitemaps. This way we can determine the number of
 * items that exist in the entire site from the sitemap indexes.
 * 
 * The Icamys SitemapGenerator is probably overkill in terms of what we need, and it's a
 * little painful in terms of customisation (see class-make-me-static-sitemapgenerator.php)
 * however it seems like a solid way to get started. We probably need to code our own 
 * lightweight generator once out PHP gets good enough.
 * 
 */

require_once plugin_dir_path( __FILE__ ) . 'make-me-static-sitemapgenerator.php';
require_once ABSPATH . 'wp-admin/includes/file.php';


class make_me_static_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.9.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */

	private $plugin_name;
	
	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */

	private $version;
	
	private $test;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    		The version of this plugin.
	 */

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Wrap the sitemap generator so we can access urlCount
	 *
	 * @since		1.0.59
	 * @access   	private
	 * @param      	string    $tmpdir		Our temporary directory
	 * @param      	int    	  $page_size	The maximum number of items to allow per page
	 * @return   	object    An instance of an overridden Icamys/SitemapGenerator
	 * 
	 */

	 private function get_generator ( $tmpdir, $page_size, $nest=false ) {
		wp_mkdir_p( $tmpdir );	
		// if ((PHP_VERSION_ID >= 80000)) {
		// 	$config = new make_me_static_sitemapconfig ($tmpdir, $nest);
		// 	$gen = new make_me_static_sitemapgenerator ($config );
		// } else {
		$gen = new make_me_static_sitemapgenerator_legacy ( get_site_url (), $tmpdir);
		// }
		$gen->setSitemapStylesheet('wp-content/plugins/make-me-static/public/sitemap.xsl');
		$gen->setMaxURLsPerSitemap($page_size);
		return $gen;
	}

	/**
	 * Traverse the plugins filesystem (non recursive)
	 *
	 * @since		0.9.0
	 * @access   	private
	 * @param       object    	$index		A sitemapgenerator instance
	 * @param     	string    	$datadir	The path for our results
	 * @param      	array    	$paths		The paths to traverse
	 * @param      	bool    	$recurse	Whether to recurse the path
	 * 
	 */

	private function traverse_root ( $index, $datdir, $paths, $recurse=true) {
		global $wp_filesystem;
		$sections = [];
		$tmps = [];
		foreach (['css', 'js', 'img'] as $key) {
			$tmps[$key] = sys_get_temp_dir() . '/' . uniqid('mms_');
			$gen = $this->get_generator ($tmps[$key], 100);
			$sections[$key]['gen'] = $gen;
			$sections[$key]['date'] = (new DateTime())->setTimestamp(0);
			$gen->setSitemapFileName('make_me_static_sitemap_'.$key.'.xml');
			$gen->setSitemapIndexFileName('make_me_static_sitemap_'.$key.'_index.xml');
		}
		foreach ($paths as $path) {
			$this->traverse($sections, $index, $path, $recurse);
		}
		foreach ($sections as $key => $entry) {
			$gen = $entry['gen'];
			$gen->flush();
			try {
				$gen->finalize();
			} catch (Exception $e) {
				continue;
			}
			$name = 'make_me_static_sitemap_'.$key;
			$name = $wp_filesystem->exists($tmps[$key].'/'.$name .'.xml') ? '/'.$name.'.xml' : '/'.$name.'_index.xml';
			$index->addURL($name, $entry['date'], 'never', $gen->urlCount(), []);
			$this->flush($tmps[$key], $datdir);
		}
	}

	/**
	 * Traverse the plugins filesystem
	 *
	 * @since		0.9.0
	 * @access   	private
	 * @param      	array     			$sections	Array of generators (one per indexed type)
	 * @param       SitemapGenerator    $generator	A sitemapgenerator instance
	 * @param     	string    			$path		The path to traverse
	 * @param      	bool      			$recurse	Whether to recurse the path
	 * 
	 */

	private function traverse ( &$sections, $index, $path, $recurse=true) {
		global $wp_filesystem;
		if (strstr($path, 'node_modules') !== false) return;
		$imgs = ['ogg','ogv','svg','svgz','eot','otf','woff','mp4','ttf','rss','atom','jpg','jpeg','gif','png','ico','zip','tgz','gz','rar','bz2','doc','xls','exe','ppt','tar','mid','midi','wav','bmp','rtf','html', 'woff2', 'txt', 'webp', 'xml', 'xsl'];
		$ign = ['php', 'json', 'mo', 'po', 'mmdb', 'asc', 'pubkey', 'pem', 'pot', 'map', 'crt', 'md', 'map', 'lock', 'scss', 'old', 'inc', 'php8', 'key', 'pot__OLD', 'yml', 'ts', 'zip', 'txt'];
		$newest_date = (new DateTime())->setTimestamp(0);
		foreach ($wp_filesystem->dirlist( ABSPATH . $path ) as $name => $atts) {
			if (str_starts_with($name, '.')||($name=='cache')) continue;
			if ($atts['type'] == 'd' && $recurse) {
				$sub = $path . '/' . $name;
				if (defined('MAKE_ME_STATIC_FOLDER_BLIST') && in_array($sub, MAKE_ME_STATIC_FOLDER_BLIST)) continue;
				if (in_array($sub, ['plugins'])) continue;
				$this->traverse($sections, $index, $sub, $recurse);
				continue;
			}
			$ext = pathinfo($name, PATHINFO_EXTENSION);
			if (str_starts_with($name, 'make_me_static_') && $ext == 'xml') continue;
			if (!$ext||in_array($ext, $ign)) continue;
			if (in_array($ext, $imgs)) $ext = 'img';
			if (!(array_key_exists($ext, $sections))) {
				continue;
			}
			$gen = $sections[$ext]['gen'];
			$date = new DateTime();
			$date->setTimestamp($atts['lastmodunix']);
			if ($path) {
				$gen->addURL('/' . $path . '/' . $name, $date, 'never', 0, []);
			} else {
				$gen->addURL('/' . $name, $date, 'never', $gen->urlCount(), []);
			}
			if ($date > $sections[$ext]['date']) {
				$sections[$ext]['date'] = $date;
			}
		}
	}

	/**
	 * Include items in the sitemap
	 *
	 * @since		0.9.0
	 * @param		array	  $items		Array of WP_Post items to add to sitemap
	 * @param		string	  $datadir		The folder for our results
	 * @param		object	  $config		A sitemap config object
	 * @param      	object    $index		A sitemapgenerator instance
	 * @param		string	  $type		    Type of objects in this section
	 * @access   	private
	 * 
	 */

	private function include_items ( $items, $datdir, $index, $type) {
		global $wp_filesystem;
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		$generator = $this->get_generator ( $tmpdir, 100, true );
		$generator->setSitemapFileName("make_me_static_sitemap_" . $type . ".xml");
		$generator->setSitemapIndexFileName("make_me_static_sitemap_" . $type ."_index.xml");
		$newest_date = (new DateTime())->setTimestamp(0); 
		foreach ($items as $item) {
			$date = get_post_datetime($item, 'modified', 'gmt');
			if ($date > $newest_date) $newest_date = $date;
			$when = DateTime::createFromImmutable($date);
			$full_permalink = get_permalink($item);
			$parsed_url = wp_parse_url($full_permalink);
			$relative_path = $parsed_url['path'];
			$generator->addURL($relative_path, $when, 'never', 0, []);
		}
		$generator->flush();
		try {
			$generator->finalize();			
			$name = 'make_me_static_sitemap_' . $type;
			$name = $wp_filesystem->exists($tmpdir.'/'.$name .'.xml') ? '/'.$name.'.xml' : '/'.$name.'_index.xml';
			$index->addURL($name, DateTime::createFromImmutable($newest_date), 'never', $generator->urlCount(), []);
		} catch (Exception $e) {
			return;
		}
		$this->flush($tmpdir, $datdir);		
	}

	/**
	 * Include posts in the sitemap
	 *
	 * @since		0.9.0
	 * @access   	private
	 * @param		string	  $datadir		The path for our results
	 * @param      	object    $index		A sitemapgenerator instance
	 * @param		object	  $type			The resource type we're dealing with
	 * 
	 */

	private function include_posts ($datdir, $index, $type) {
		global $wp_filesystem;
		$archives = [];
		$offset = 0;
		$page_size = 100;
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');

		$generator = $this->get_generator ( $tmpdir, $page_size, true );
		$generator->setSitemapFileName("make_me_static_sitemap_" . $type . ".xml");
		$generator->setSitemapIndexFileName("make_me_static_sitemap_" . $type ."_index.xml");
		$newest_date = (new DateTime())->setTimestamp(0);
		$site_url = get_site_url ();

		while (true) {
			$items = get_posts(array(
				'post_type' => 'post',
				'post_status' => 'publish',
				'numberposts' => $page_size,
				'offset' => $offset
			));
			foreach ($items as $item) {
				$date = get_post_datetime($item, 'modified', 'gmt');
				if ($date > $newest_date) $newest_date = $date;
				$pub = get_post_datetime($item, 'published', 'gmt');
				$year = $pub->format('Y');
				$month = $pub->format('m');
				if (!isset($archives[$year])) {
					$archives[$year] = [];
				}
				if (!isset($archives[$month])) {
					$archives[$year][$month] = $pub;
				} else {
					if ($archives[$year][$month] < $pub) {
						$archives[$year][$month] = $pub;
					}
				}
				$when = DateTime::createFromImmutable($date);
				$full_permalink = get_permalink($item);
				if (str_starts_with($full_permalink, $site_url)) {
					$parsed_url = wp_parse_url($full_permalink);
					$relative_path = $parsed_url['path'];
					$generator->addURL($relative_path, $when, 'never', 0, []);
				}
			}
			if (count($items) < $page_size) break;
			$offset = $offset + $page_size;
		}
		$generator->flush();
		try {
			$generator->finalize();			
			$name = 'make_me_static_sitemap_' . $type;
			$name = $wp_filesystem->exists($tmpdir.'/'.$name .'.xml') ? '/'.$name.'.xml' : '/'.$name.'_index.xml';
			$index->addURL($name, DateTime::createFromImmutable($newest_date), 'never', $generator->urlCount(), []);
		} catch (Exception $e) {
		}
		$this->flush($tmpdir, $datdir);		

		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		$generator = $this->get_generator ( $tmpdir, 50000 );
		$generator->setSitemapFileName("make_me_static_sitemap_archives.xml");
		$generator->setSitemapIndexFileName("make_me_static_sitemap_archives_index.xml");
		$newest_date = (new DateTime())->setTimestamp(0);
		foreach ($archives as $year => $months) {
			foreach ($months as $month => $date) {
				$name = '/'.$year.'/'.$month.'/';
				$generator->addURL($name, DateTime::createFromImmutable($date), 'never', 0, []);
				if ($date > $newest_date) $newest_date = $date;
			}
		}
		$generator->flush();
		try {
			$generator->finalize();
		} catch (Exception $e) {
			return;
		}
		$name = 'make_me_static_sitemap_archives';
		$name = $wp_filesystem->exists($tmpdir.'/'.$name .'.xml') ? '/'.$name.'.xml' : '/'.$name.'_index.xml';
		$index->addURL($name, DateTime::createFromImmutable($newest_date), 'never', $generator->urlCount(), []);
		$this->flush($tmpdir, $datdir);		
	}

	/**
	 * Recurse through all categories
	 *
	 * @since		0.9.0
	 * @param		string	  $datdir		Our output path
	 * @param      	object    $index		A sitemapgenerator instance for the index
	 * @param		object	  $generator	Our sitemap generator for the page
 	 * @param      	string    $category		The category to include
	 * @param      	string    $root			Root cat to start from
	 * @access   	private
	 * 
	 */

	private function include_subcategories ($datdir, $index, $generator, $category, $root) {
		$args = array
		(
			'parent' => $category ? $category->cat_ID : 0,
			'hide_empty' => true,
		);
		$newest_date = (new DateTime())->setTimestamp(0);
		foreach ( get_categories($args) as $cat) {
			$args = array(
				'post_type' => 'post',
				'posts_per_page' => 1,
				'cat' => $cat->cat_ID,
			);
			$query = new WP_Query($args);
			if ($query->post_count) {
				$post = $query->posts[0];
				$date = get_post_datetime($post, 'modified', 'gmt');
				$path = $root.'/'.$cat->slug;
				$when = DateTime::createFromImmutable($date);
				if ($when > $newest_date) $newest_date = $when;
				$generator->addURL($path.'/', $when, 'never', 0, []);
				$sub_date = $this->include_subcategories ($datdir, $index, $generator, $cat, $path);
				if ($sub_date > $newest_date) $newest_date = $sub_date;
			}
		}
		return $newest_date;
	}

	/**
	 * Include categories in the sitemap
	 *
	 * @since		0.9.0
	 * @param		string	  $datdir		Final destination
	 * @param      	object    $index		A sitemapgenerator instance
	 * @access   	private
	 * 
	 */

	private function include_categories ($datdir, $index) {
		global $wp_filesystem;
		$type = 'categories';
		$page_size = 100;
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		$generator = $this->get_generator ( $tmpdir, $page_size, true );
		$generator->setSitemapFileName("make_me_static_sitemap_" . $type . ".xml");
		$generator->setSitemapIndexFileName("make_me_static_sitemap_" . $type ."_index.xml");
		$newest_date = $this->include_subcategories ($datdir, $index, $generator, '', '/category');
		$generator->flush();
		try {
			$generator->finalize();			
			$name = 'make_me_static_sitemap_' . $type;
			$name = $wp_filesystem->exists($tmpdir.'/'.$name .'.xml') ? '/'.$name.'.xml' : '/'.$name.'_index.xml';
			$index->addURL($name, $newest_date, 'never', $generator->urlCount(), []);
		} catch (Exception $e) {
			return;
		}
		$this->flush($tmpdir, $datdir);		
	}

	/**
	 * Include tags in the sitemap
	 *
	 * @since		0.9.0
	 * @param		string	  $datdir		Final destination
	 * @param      	object    $index		A sitemapgenerator instance
	 * @access   	private
	 * 
	 */

	private function include_tags ($datdir, $index) {
		global $wp_filesystem;
		$type = 'tags';
		$page_size = 100;
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		$generator = $this->get_generator ( $tmpdir, $page_size, true );
		$generator->setSitemapFileName("make_me_static_sitemap_" . $type . ".xml");
		$generator->setSitemapIndexFileName("make_me_static_sitemap_" . $type ."_index.xml");
		$newest_date = (new DateTime())->setTimestamp(0);
		$root = '/tag';
		foreach ( get_tags() as $tag) {
			$args = array(
				'post_type' => 'post',
				'posts_per_page' => 1,
				'tag_id' => $tag->term_id,
			);
			$query = new WP_Query($args);
			if ($query->post_count) {
				$post = $query->posts[0];
				$date = get_post_datetime($post, 'modified', 'gmt');
				$path = $root.'/'.$tag->slug.'/';
				$when = DateTime::createFromImmutable($date);
				if ($when > $newest_date) $newest_date = $when;
				$generator->addURL($path, $when, 'never', 0, []);
			}
		}
		$generator->flush();
		try {
			$generator->finalize();			
			$name = 'make_me_static_sitemap_' . $type;
			$name = $wp_filesystem->exists($tmpdir.'/'.$name .'.xml') ? '/'.$name.'.xml' : '/'.$name.'_index.xml';
			$index->addURL($name, $newest_date, 'never', $generator->urlCount(), []);
		} catch (Exception $e) {
			return;
		}
		$this->flush($tmpdir, $datdir);		
	}

	/**
	 * Include authors in the sitemap
	 *
	 * @since		0.9.0
	 * @param		string	  $datdir		Final destination
	 * @param      	object    $index		A sitemapgenerator instance
	 * @access   	private
	 * 
	 */

	private function include_authors ($datdir, $index) {
		global $wp_filesystem;
		$users = get_users();
		if ( count( $users ) === 0 ) return 0;
		$type = 'authors';
		$page_size = 100;
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		$generator = $this->get_generator ( $tmpdir, $page_size, true );
		$generator->setSitemapFileName("make_me_static_sitemap_" . $type . ".xml");
		$generator->setSitemapIndexFileName("make_me_static_sitemap_" . $type ."_index.xml");
		$newest_date = (new DateTime())->setTimestamp(0);
		$root = '/author';
		foreach ( $users as $user ) {
			$args = array(
				'post_type' => 'post',
				'posts_per_page' => 1,
				'author' => $user->ID,
			);
			$query = new WP_Query($args);
			if ($query->post_count) {
				$post = $query->posts[0];
				$date = get_post_datetime($post, 'modified', 'gmt');
				$url = get_author_posts_url($user->ID);
				$parsed = wp_parse_url($url);
				$path = $parsed['path'];
				$when = DateTime::createFromImmutable($date);
				if ($when > $newest_date) $newest_date = $when;
				$generator->addURL($path, $when, 'never', 0, []);
			}
		}
		$generator->flush();
		try {
			$generator->finalize();			
			$name = 'make_me_static_sitemap_' . $type;
			$name = $wp_filesystem->exists($tmpdir.'/'.$name .'.xml') ? '/'.$name.'.xml' : '/'.$name.'_index.xml';
			$index->addURL($name, $newest_date, 'never', $generator->urlCount(), []);
		} catch (Exception $e) {
			return;
		}
		$this->flush($tmpdir, $datdir);		
	}

	/**
	 * Regenerate the entire sitemap
	 * @since		0.9.0
	 * @access   	private
	 * 
	 */

	private function regenerate_sitemap () {
		global $wp_filesystem;
		$datdir = sys_get_temp_dir() . '/' . uniqid('mms_data_');
		wp_mkdir_p( $datdir );
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		$index = $this->get_generator ( $tmpdir, 100 );
		$index->setSitemapFileName("make_me_static_sitemap.xml");
		$items = get_pages(array('post_type' => 'page', 'post_status' => 'publish'));
		$this->include_items($items, $datdir, $index, 'pages');
		$this->include_posts($datdir, $index, 'posts');
		$this->include_categories($datdir, $index);
		$this->include_tags($datdir, $index);
		$this->include_authors($datdir, $index);
		$folders = [];
		if (defined('MAKE_ME_STATIC_FOLDER_WLIST'))
			$folders = array_merge($folders, MAKE_ME_STATIC_FOLDER_WLIST);
		$this->traverse_root($index, $datdir, $folders);
		$index->flush();
		try {
			$index->finalize();
		} catch (Exception $e) {

		}
		$this->flush($tmpdir, $datdir);
		if (move_dir($datdir, plugin_dir_path( __FILE__ ) . 'data', true) != true) {
			// error_log('Error copying files, check permissions for: '.plugin_dir_path( __FILE__ ) . 'data');
			exit;
		}
	}

	/**
	* Return a mame_me_static_sitemap(n).xml if available
	*
	* @since       0.9.0
	* @access      private	
	* @param       string    $name             Sitemap file name
	* 
	*/

	private function return_sitemap ($name) {
		if (!WP_Filesystem()) { die; }
		global $wp_filesystem;
		status_header (200);
		header('Content-Type: application/xml');

		$path1 = plugin_dir_path( __FILE__ ) . 'data/' . str_replace('-','.',$name);
		$path2 = plugin_dir_path( __FILE__ ) . 'data/sitemap-index.xml';
		$last_change = get_option ('make-me-static-change', (new DateTime())->setTimestamp(1));
		$last_sitemap = get_option ('make-me-static-last', (new DateTime())->setTimestamp(0));
		if (($last_change > $last_sitemap)||(!$wp_filesystem->exists($path1)&&!$wp_filesystem->exists($path2))) {
			$this->regenerate_sitemap();
			update_option ('make-me-static-last', $last_change);
		}
		if ($wp_filesystem->exists($path1)) {
			include $path1;
			die;
		}
		if ($wp_filesystem->exists($path2)) {
			include $path2;
			die;
		}
		status_header (404);
		wp_die ('failed to find sitemap file');
	}

	/**
	* Simple Comments RSS feed
	*
	* @since       0.9.1
	* @access      private
	* 
	*/

	private function return_comments () {
		status_header (200);
		header('Content-Type: application/rss+xml');
		$args = array(
			'number' => 20,
		);
		$comment_query = new WP_Comment_Query;
		$comments = $comment_query->query($args);	
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<rss version="2.0">';
		echo '<channel>';
		if ($comments) {
			foreach ($comments as $comment) {
				echo '<item>';
				echo '<comment_id>' . esc_html($comment->comment_ID) . '</comment_id>';
				echo '<pubDate>' . esc_html($comment->comment_date) . '</pubDate>';
				echo '<link>' . esc_url(get_permalink($comment->comment_post_ID)) . '</link>';
				echo '<approved>' . esc_html($comment->comment_approved) . '</approved>';
				echo '</item>';
			}
		}
		echo '</channel>';
		echo '</rss>';
		die;
	}

	/**
	 * Flush temporary gen files to final
	 *
	 * @since		0.9.0
	 * @access   	private
	 * @param		string	  $tmpdir		    Source folder
	 * @param		string	  $datdir		    Target folder
	 * 
	 */
	
	private function flush ($tmpdir, $datdir) {
		global $wp_filesystem;
		foreach ($wp_filesystem->dirlist($tmpdir) as $src) {
			$path = $src['name'];
			copy ($tmpdir.'/'.$path, $datdir.'/'.$path);
		}
	}

	/**
	 * 
	 * 	update_metadata - record the session token against our host_id in the
	 *  metadata for this user. Expire any old host_id's for which the session
	 *  token has expired or is no longer valid.
	 * 
	 * @since		1.0.248
	 * @access   	private
	 * @param       string    $host_id       The Make_Me_Static host_id
	 * 
	 */

	 private function update_metadata ( $host_id ) {
		//
		//	Get the array of host_id's for this user
		//
		$user_id = wp_get_current_user()->ID;
		$session_token = wp_get_session_token();
		$meta = get_user_meta ( $user_id , 'make_me_static_host_ids' );
		if ($meta)
			$meta = $meta[0];
		else
			$meta = array();
		//
		//	Clean any expired sessions
		//
		$manager = WP_Session_Tokens::get_instance( $user_id );
		$meta = array_filter( $meta, function( $token ) use ( $manager ) {
			return $manager->verify( $token );
		});
		//
		//	Update the stamp and save ...
		//
		$meta[$host_id] = $session_token;
		update_user_meta ( $user_id, 'make_me_static_host_ids', $meta );
	}

	/**
	 * 
	 * 	Determine is the host_id has previously been validated for this user_id
	 * 
	 * @since		1.0.248
	 * @access   	private
	 * @param       string    $user_id       The Wordpress user_id
	 * @param       string    $host_id       The Make_Me_Static host_id
	 * @return    	boolean   				 Whether the user/host combination is currently valid
	 * 
	 */

	private function is_host_id_valid ( $user_id, $host_id ) {
		$meta = get_user_meta ( $user_id , 'make_me_static_host_ids' );
		if ($meta)
			$meta = $meta[0];
		else
			$meta = array();
		if (!isset($meta[$host_id])) {
			// error_log ('Host ID invalid: '.$host_id);
			return false;
		}
		$manager = WP_Session_Tokens::get_instance( $user_id );
		if (!$manager->verify( $meta[$host_id] )) {
			// error_log ('Session token invalid: '.$meta[$host_id]);
			return false;
		}
		return true;
	}

	/**
	 * Legacy version if API notify changes
	 *
	 * @since		1.0.248
	 * @access   	public
	 * 
	 */

	private function api_notify_changes () {
		$last_change = get_option ('make-me-static-change', (new DateTime())->setTimestamp(1))->format('c');
		$last_sitemap = get_option ('make-me-static-last', (new DateTime())->setTimestamp(0))->format('c');
		wp_send_json(array(
			'last_change' => $last_change,
			'last_sitemap' => $last_sitemap
		), 200);
	}

	/**
	 * Validation service for MMS to use.
	 * 
	 * MMS Will send a UUID, USER and HOST_ID, we're just going to look it up in our
	 * metadata and return whether the HOST_ID is valid on this site a current admin session.
	 * It's effectively an anonymous service so no user restrictions or nonce's apply.
	 * 
	 * All it does is return a 200 or 403, the Plugin checker gives a warning, it should not!
	 *
	 * @since		1.0.248
	 * @access   	private
	 * @param		site, host_id, user 
	 * @return    	WP_REST_Response 		  200 if Ok or 403 if unauthorized, 500 on error
	 * 
	 */

	 private function api_validate_host () {
		//
		//	Note on NONCE: this is a fall-back routine which was initially implemented
		//	using the Wordpress API. The problem is that out in the Wild the API rarely
		//	seems to work as people either disable it, make it authenticated only, or
		//	mess with query strings to break it.
		//
		//	This routine is called by the MMS back-end to make sure "it" is allowed
		//	to crawl the site. There is ZERO risk for Wordpress, so it REALLY does
		//	not neet a nonce - there is no NONCE, MMS can crawl when nobody is logged in.
		//  I see complaints from people complaining that forcing nonce verification is 
		//	annoying, and other people saying it's "required".
		//
		//	It some cases, it's just not. I understand the "use the API", and I try, but
		//	if it doesn't work, the choice is removed. It would be nice if there was a 
		//	little acceptance that real world operation does not always == what would
		//	be nice in theory.
		//
		//	Incoming parameters include the site (a uuid) and host_id and user
		//
		if (!isset($_GET['site'])) // phpcs:ignore
			wp_send_json(array( 'message' => 'Missing a site parameter' ), 500);
		$site = sanitize_text_field(wp_unslash($_GET['site'])); // phpcs:ignore

		if (!isset($_GET['host_id'])) // phpcs:ignore
			wp_send_json(array( 'message' => 'Missing a host parameter' ), 500);
		$host = sanitize_text_field(wp_unslash($_GET['host_id'])); // phpcs:ignore

		if (!isset($_GET['user'])) // phpcs:ignore
			wp_send_json(array( 'message' => 'Missing a user parameter' ), 500);
		$user = sanitize_text_field(wp_unslash($_GET['user'])); // phpcs:ignore
		//
		//	Make sure this request is for us ...
		//
		if (get_option ('make-me-static-uuid', false) != $site)
			wp_send_json(array( 'message' => 'Request was for the wrong domain: '.$site ), 403);
		//
		//	Check the metadata to see if there is a valid session for this user/host_id
		//
		if ($this->is_host_id_valid (sanitize_text_field($user), sanitize_text_field($host)))
			wp_send_json( array( 'message' => 'Ok, host_id attached to a valid session' ), 200);
		wp_send_json( array( 'message' => 'Session invalid or expired' ), 403);
	}

	/**
	 * Register the current session. MMS identifies users via "host_id" which is 
	 * negotiated via a public key encryptione exchange between the app and MMS.
	 * Once the app has a valid HOST_ID it stores it here so MMS can make sure
	 * it's session was initiated my an admin user for this site. Effectively a
	 * form of MMS nonse.
	 *
	 * @since		1.0.248
	 * @access   	private
	 * @param		$_GET['site']	
	 * @param		$_GET['host_id']
	 * @return    	WP_REST_Response 		200 if Ok or 401 if not admin or 403 if unauthorized
	 * 
	 */

	 private function api_register_host () {
		//
		//	Verify the nonce
		//
		if (!isset($_SERVER['X-Wp-Nonce']))
			wp_send_json(array('message'=>'Missing nonce'),500);
		$nonce = sanitize_text_field(wp_unslash($_SERVER['X-Wp-Nonce']));
		if (!wp_verify_nonce($nonce, 'wp-rest'))
			wp_die ('bad nonce');
		//
		//	Incoming parameters include the site (a uuid) and host_id
		//
		if (!isset($_GET['site']))
			wp_send_json(array( 'message' => 'Missing a site parameter' ), 500);
		$site = sanitize_text_field(wp_unslash($_GET['site']));

		if (!isset($_GET['host_id']))
			wp_send_json(array( 'message' => 'Missing a host parameter' ), 500);
		$host = sanitize_text_field(wp_unslash($_GET['host_id']));
		//
		//	Needs to be an admin session
		//
		if (!current_user_can('administrator'))
			wp_send_json( array( 'message' => 'Request was for not authorized: '.$site ), 401);
		//
		//	Make sure this request is for us ...
		//
		if (get_option ('make-me-static-uuid', false) != $site)
			wp_send_json( array( 'message' => 'Request was for the wrong domain: '.$site ), 403);
		//
		//	Make sure host_id is available and update it's stamp if it already exists
		//
		$this->update_metadata ( $host );
		//
		//	Also check the permalink structure is ok
		//
		$permalink_structure = get_option( 'permalink_structure' );
		//
		wp_send_json( array( 'message' => 'Ok, session registered', 'permalink' => empty($permalink_structure) ? 'plain' : 'ok' ), 200);
	}


	/**
	 * Install redirects for our dynamically generated pages
	 *
	 * @since		0.9.0
	 * @access   	private
	 * 
	 */

	public function make_me_static_template_redirect () {
		global $wp_query;
		$name = $wp_query->query_vars['name'];
		if (preg_match('/^make_me_static_api_register_host-json$/', $name))		return $this->api_register_host();
		if (preg_match('/^make_me_static_api_validate_host-json$/', $name))		return $this->api_validate_host();
		if (preg_match('/^make_me_static_api_notify_changes-json$/', $name))	return $this->api_notify_changes();
		if (preg_match('/^make_me_static_sitemap_comments-xml$/', $name))   	return $this->return_comments($name);		
		if (preg_match('/^make_me_static_sitemap(.*)-xml$/', $name)) 			return $this->return_sitemap($name);

	}
}
