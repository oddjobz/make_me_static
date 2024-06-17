<?php

define('MMS_DIRECTORY_URL', 'https://mms-directory-dev.madpenguin.uk');

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://madpenguin.uk
 * @since      0.9.0
 * @package    class-mm-static-public
 *
 */

class mm_static_Public {

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
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Traverse the plugins filesystem (non recursive)
	 *
	 * @since		0.9.0
	 * @param      	string    $generator		A sitemapgenerator instance
	 * @param     	string    $path			The path to traverse
	 * @param      	string    $type			The type of file to look at
	 * @param      	string    $debug			Whether to turn debugging on
	 * @access   	private
	 * 
	 */

	private function traverse_root ( $index, $datdir, $paths, $recurse=true) {
		global $wp_filesystem;
		$sections = [];
		$tmps = [];
		foreach (['css', 'js', 'img'] as $key) {
			$tmps[$key] = sys_get_temp_dir() . '/' . uniqid('mms_');
			wp_mkdir_p( $tmps[$key] );	
			$config = new \Icamys\SitemapGenerator\Config();
			$config->setSaveDirectory($tmps[$key]);
			$config->setBaseURL(get_site_url ());
			$gen = new \Icamys\SitemapGenerator\SitemapGenerator($config);
			$sections[$key]['gen'] = $gen;
			$sections[$key]['date'] = (new DateTime())->setTimestamp(0);
			$gen->setSitemapStylesheet('wp-content/plugins/make-me-static/public/sitemap.xsl');
			$gen->setMaxURLsPerSitemap(100);
			$gen->setSitemapFileName('mm_sitemap_'.$key.'.xml');
			$gen->setSitemapIndexFileName('mm_sitemap_'.$key.'_index.xml');
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
			$name = 'mm_sitemap_'.$key;
			$name = $wp_filesystem->exists($tmps[$key].'/'.$name .'.xml') ? '/'.$name.'.xml' : '/'.$name.'_index.xml';
			$index->addURL($name, $entry['date'], 'never', $gen->urlCount(), []);
			$this->flush($tmps[$key], $datdir);
		}
	}

	/**
	 * Traverse the plugins filesystem
	 *
	 * @since		0.9.0
	 * @param      	string    $generator		A sitemapgenerator instance
	 * @param     	string    $path			The path to traverse
	 * @param      	string    $type			The type of file to look at
	 * @param      	string    $debug			Whether to turn debugging on
	 * @access   	private
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
				if (defined('MMS_FOLDER_BLIST') && in_array($sub, MMS_FOLDER_BLIST)) continue;
				if (in_array($sub, ['plugins'])) continue;
				$this->traverse($sections, $index, $sub, $recurse);
				continue;
			}
			$ext = pathinfo($name, PATHINFO_EXTENSION);
			if (str_starts_with($name, 'mm_') && $ext == 'xml') continue;
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
	 * @param		object	  $config		A sitemap config object
	 * @param      	object    $index		A sitemapgenerator instance
	 * @param		string	  $type		    Type of objects in this section
	 * @access   	private
	 * 
	 */

	private function include_items ( $items, $datdir, $index, $type, $debug=false) {
		global $wp_filesystem;
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		wp_mkdir_p( $tmpdir );	
		$config = new \Icamys\SitemapGenerator\Config();
		$config->setSaveDirectory($tmpdir);
		$config->setBaseURL(get_site_url ());
		$generator = new \Icamys\SitemapGenerator\SitemapGenerator($config);				
		$generator->setSitemapFileName("mm_sitemap_" . $type . ".xml");
		$generator->setSitemapIndexFileName("mm_sitemap_" . $type ."_index.xml");
		$generator->setSitemapStylesheet('wp-content/plugins/make-me-static/public/sitemap.xsl');
		$generator->setMaxURLsPerSitemap(100);
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
			$name = 'mm_sitemap_' . $type;
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
	 * @param		object	  $config		A sitemap config object
	 * @param      	object    $index		A sitemapgenerator instance
	 * @param		string	  $type		    Type of objects in this section
	 * @access   	private
	 * 
	 */

	private function include_posts ($datdir, $index, $type, $debug=false) {
		global $wp_filesystem;
		$archives = [];
		$offset = 0;
		$page_size = 100;
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		wp_mkdir_p( $tmpdir );	
		$config = new \Icamys\SitemapGenerator\Config();
		$config->setSaveDirectory($tmpdir);
		$config->setBaseURL(get_site_url ());
		$generator = new \Icamys\SitemapGenerator\SitemapGenerator($config);				
		$generator->setSitemapFileName("mm_sitemap_" . $type . ".xml");
		$generator->setSitemapIndexFileName("mm_sitemap_" . $type ."_index.xml");
		$generator->setSitemapStylesheet('wp-content/plugins/make-me-static/public/sitemap.xsl');
		$generator->setMaxURLsPerSitemap($page_size);
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
			$name = 'mm_sitemap_' . $type;
			$name = $wp_filesystem->exists($tmpdir.'/'.$name .'.xml') ? '/'.$name.'.xml' : '/'.$name.'_index.xml';
			$index->addURL($name, DateTime::createFromImmutable($newest_date), 'never', $generator->urlCount(), []);
		} catch (Exception $e) {
			return;
		}
		$this->flush($tmpdir, $datdir);		

		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		wp_mkdir_p( $tmpdir );	
		$config = new \Icamys\SitemapGenerator\Config();
		$config->setSaveDirectory($tmpdir);
		$config->setBaseURL(get_site_url ());
		$generator = new \Icamys\SitemapGenerator\SitemapGenerator($config);				
		$generator->setSitemapFileName("mm_sitemap_archives.xml");
		$generator->setSitemapIndexFileName("mm_sitemap_archives_index.xml");
		$generator->setSitemapStylesheet('wp-content/plugins/make-me-static/public/sitemap.xsl');
		$generator->setMaxURLsPerSitemap(50000);
		$newest_date = (new DateTime())->setTimestamp(0);
		foreach ($archives as $year => $months) {
			foreach ($months as $month => $date) {
				$name = '/'.$year.'/'.$month.'/';
				$generator->addURL($name, DateTime::createFromImmutable($date), 'never', 0, []);
				if ($date > $newest_date) $newest_date = $date;
			}
		}
		$generator->flush();
		$generator->finalize();
		$name = 'mm_sitemap_archives';
		$name = $wp_filesystem->exists($tmpdir.'/'.$name .'.xml') ? '/'.$name.'.xml' : '/'.$name.'_index.xml';
		$index->addURL($name, DateTime::createFromImmutable($newest_date), 'never', $generator->urlCount(), []);
		$this->flush($tmpdir, $datdir);		
	}

	/**
	 * Recurse through all categories
	 *
	 * @since		0.9.0
	 * @param		object	  $datdir		Final destination
	 * @param      	object    $index		A sitemapgenerator instance
 	 * @param      	object    $category		Root cat to start from
	 * @access   	private
	 * 
	 */

	private function include_subcategories ($datdir, $index, $generator, $category, $root, $debug=false) {
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
	 * @param		object	  $datdir		Final destination
	 * @param      	object    $index		A sitemapgenerator instance
	 * @access   	private
	 * 
	 */

	private function include_categories ($datdir, $index, $debug=false) {
		global $wp_filesystem;
		$type = 'categories';
		$page_size = 100;
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		wp_mkdir_p( $tmpdir );	
		$config = new \Icamys\SitemapGenerator\Config();
		$config->setSaveDirectory($tmpdir);
		$config->setBaseURL(get_site_url ());
		$generator = new \Icamys\SitemapGenerator\SitemapGenerator($config);				
		$generator->setSitemapFileName("mm_sitemap_" . $type . ".xml");
		$generator->setSitemapIndexFileName("mm_sitemap_" . $type ."_index.xml");
		$generator->setSitemapStylesheet('wp-content/plugins/make-me-static/public/sitemap.xsl');
		$generator->setMaxURLsPerSitemap($page_size);
		$newest_date = $this->include_subcategories ($datdir, $index, $generator, '', '/category');
		$generator->flush();
		try {
			$generator->finalize();			
			$name = 'mm_sitemap_' . $type;
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
	 * @param		object	  $datdir		Final destination
	 * @param      	object    $index		A sitemapgenerator instance
	 * @access   	private
	 * 
	 */

	private function include_tags ($datdir, $index, $debug=false) {
		global $wp_filesystem;
		$type = 'tags';
		$page_size = 100;
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		wp_mkdir_p( $tmpdir );	
		$config = new \Icamys\SitemapGenerator\Config();
		$config->setSaveDirectory($tmpdir);
		$config->setBaseURL(get_site_url ());
		$generator = new \Icamys\SitemapGenerator\SitemapGenerator($config);				
		$generator->setSitemapFileName("mm_sitemap_" . $type . ".xml");
		$generator->setSitemapIndexFileName("mm_sitemap_" . $type ."_index.xml");
		$generator->setSitemapStylesheet('wp-content/plugins/make-me-static/public/sitemap.xsl');
		$generator->setMaxURLsPerSitemap($page_size);
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
			$name = 'mm_sitemap_' . $type;
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
	 * @param		object	  $datdir		Final destination
	 * @param      	object    $index		A sitemapgenerator instance
	 * @access   	private
	 * 
	 */

	private function include_authors ($datdir, $index, $debug=false) {
		global $wp_filesystem;

		$users = get_users();
		if ( count( $users ) === 0 ) return 0;

		$type = 'authors';
		$page_size = 100;
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		wp_mkdir_p( $tmpdir );	
		$config = new \Icamys\SitemapGenerator\Config();
		$config->setSaveDirectory($tmpdir);
		$config->setBaseURL(get_site_url ());
		$generator = new \Icamys\SitemapGenerator\SitemapGenerator($config);				
		$generator->setSitemapFileName("mm_sitemap_" . $type . ".xml");
		$generator->setSitemapIndexFileName("mm_sitemap_" . $type ."_index.xml");
		$generator->setSitemapStylesheet('wp-content/plugins/make-me-static/public/sitemap.xsl');
		$generator->setMaxURLsPerSitemap($page_size);
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
				// $path = $root.'/'.$user->user_login.'/';
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
			$name = 'mm_sitemap_' . $type;
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
	
		require_once plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';
		global $wp_filesystem;

		$datdir = sys_get_temp_dir() . '/' . uniqid('mms_data_');
		wp_mkdir_p( $datdir );
		$tmpdir = sys_get_temp_dir() . '/' . uniqid('mms_');
		wp_mkdir_p( $tmpdir );
		$config = new \Icamys\SitemapGenerator\Config();
		$config->setSaveDirectory($tmpdir);
		$config->setBaseURL(get_site_url ());
		$index = new \Icamys\SitemapGenerator\SitemapGenerator($config);
		$index->setSitemapFileName("mm_sitemap.xml");
		$index->setSitemapStylesheet('wp-content/plugins/make-me-static/public/sitemap.xsl');
		$index->setMaxURLsPerSitemap(100);
		$items = get_pages(array('post_type' => 'page', 'post_status' => 'publish'));
		$this->include_items($items, $datdir, $index, 'pages');
		$this->include_posts($datdir, $index, 'posts');
		$this->include_categories($datdir, $index);
		$this->include_tags($datdir, $index);
		$this->include_authors($datdir, $index);
		$folders = [];
		if (defined('MMS_FOLDER_WLIST'))
			$folders = array_merge($folders, MMS_FOLDER_WLIST);
		$this->traverse_root($index, $datdir, $folders);
		$index->flush();
		$index->finalize();
		$this->flush($tmpdir, $datdir);
		if (move_dir($datdir, plugin_dir_path( __FILE__ ) . 'data', true) != true) {
			error_log('Error copying files, check permissions for: '.plugin_dir_path( __FILE__ ) . 'data');
			exit;
		}
	}

	/**
	* Return a mm_sitemap(n).xml if available
	*
	* @since               0.9.0
	* @param               string    $name             Sitemap file name
	* @access      private
	* 
	*/

	private function return_sitemap ($name) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if (!WP_Filesystem()) { die; }
		global $wp_filesystem;
		status_header (200);
		header('Content-Type: application/xml');
		$path1 = plugin_dir_path( __FILE__ ) . 'data/' . str_replace('-','.',$name);
		$path2 = plugin_dir_path( __FILE__ ) . 'data/sitemap-index.xml';
		$last_change = get_option ('mm-static-change', (new DateTime())->setTimestamp(1));
		$last_sitemap = get_option ('mm-static-last', (new DateTime())->setTimestamp(0));
		if (($last_change > $last_sitemap)||(!$wp_filesystem->exists($path1)&&!$wp_filesystem->exists($path2))) {
			$this->regenerate_sitemap();
			update_option ('mm-static-last', $last_change);
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
		die;
	}

	/**
	* Return a recent comments
	*
	* @since               0.9.1
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
		print_r('<?xml version="1.0" encoding="UTF-8"?>');
		print_r('<rss version="2.0">');
		print_r('<channel>');
		if ($comments) {
			foreach ($comments as $comment) {
				print_r('<item>');
				print_r('<comment_id>' . $comment->comment_ID . '</comment_id>');
				print_r('<pubDate>' . $comment->comment_date . '</pubDate>');
				print_r('<link>' . esc_url(get_permalink($comment->comment_post_ID)) . '</link>');
				print_r('<approved>' . $comment->comment_approved . '</approved>');
				print_r('</item>');
			}
		}
		print_r('</channel>');
		print_r('</rss>');
		die;
	}

	/**
	 * Flush temporary gen files to final
	 *
	 * @since		0.9.0
	 * @param		string	  $tmpdir		    Source folder
	 * @param		string	  $datdir		    Target folder
	 * @access   	private
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
	 * Install redirects for our dynamically generated pages
	 *
	 * @since		0.9.0
	 * @access   	private
	 * 
	 */

	public function mm_static_template_redirect () {
		global $wp_query;
		$name = $wp_query->query_vars['name'];

		if (preg_match('/^mm_sitemap_comments-xml$/', $name))   return $this->return_comments($name);		
		if (preg_match('/^mm_sitemap(.*)-xml$/', $name)) 		return $this->return_sitemap($name);
	}
}
