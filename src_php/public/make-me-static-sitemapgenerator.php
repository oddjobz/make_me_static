<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * This isn an override for the SitemapGenerator class from Icamys. The SitemapGenerator
 * class is probably overkill for our needs but it was a relatively easy way to get going.
 * The sitemap we're generating will only be ready by us (and maybe humans) and is not
 * intended for consumption by search engines or crawlers.
 *
 * @link       https://madpenguin.uk
 * @since      1.0.59
 * @package    class-make-me-static-sitemapgenerator
 *
 * @TODO: write a dedicated / more concide SitemapGenerator library so we don't need to work
 * around the Icamys code, OR, put in a PR against the Icamys library to include these mods.
 * 
 */

require_once plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';

use Icamys\SitemapGenerator\Config;
use Icamys\SitemapGenerator\SitemapGenerator;

/**
 * Class Config
 * @package extends Icamys\SitemapGenerator\Config
 */

// if ((PHP_VERSION_ID >= 80000)) {

// 	class make_me_static_sitemapconfig extends Config {

// 		/**
// 		 * Initialize the class and set its properties.
// 		 *
// 		 * @since    1.0.59
// 		 * @access   private
// 		 * @param    string    $tmpdir		Where we're going to store our work files
// 		 */

// 		public function __construct ( $tmpdir, $nest=false ) {
// 			parent::__construct();
// 			$this->setSaveDirectory( $tmpdir );
// 			$base = get_site_url ();
// 			$permalink = get_option ('permalink_structure');
// 			if (strpos($permalink, 'index.php') != false) {
// 				if ((strpos($base, 'index.php') == false) && (!$nest))
// 					$base = trailingslashit ($base) . 'index.php';
// 			}
// 			$this->setBaseURL($base);
// 		}

// 	}

// 	/**
// 	 * Class SitemapGenerator
// 	 * @package extends Icamys\SitemapGenerator\SitemapGenerator
// 	 */

// 	class make_me_static_SitemapGenerator extends SitemapGenerator {

// 		/**
// 		 * A Reflection instance so we can access totalURLCount
// 		 *
// 		 * @since    1.0.59
// 		 * @access   private
// 		 * @var      object    $reflect
// 		 */

// 		private $reflect;

// 		/**
// 		 * The maximum URL length to allow
// 		 *
// 		 * @since    1.0.59
// 		 * @access   private
// 		 * @var      int		$MAX_URL_LEN
// 		 */

// 		private $MAX_URL_LEN = 2048;

// 		/**
// 		 * Valid frequencies to allow
// 		 *
// 		 * @since    1.0.59
// 		 * @access   private
// 		 * @var      int		$validChangefreqValues
// 		 */

// 		private array $validChangefreqValues = [
// 			'always',
// 			'hourly',
// 			'daily',
// 			'weekly',
// 			'monthly',
// 			'yearly',
// 			'never',
// 		];

// 		/**
// 		 * We need access to totalURLCount and unfortunately it's defined
// 		 * as private, which isn't helpful. Not overly happy with having
// 		 * to use reflect, but this seems to be the only way to get access
// 		 * to a private variable.
// 		 *
// 		 * @since    1.0.59
// 		 * @access   private
// 		 * @param    string    $tmpdir			Where we're going to store our work files
// 		 * @return 			   $totalURLCount	Number of URL's in the sitemap
// 		 * 
// 		 */

// 		public function urlCount () {
// 			if ($this->reflect == null) {
// 				$this->reflect = new ReflectionClass(SitemapGenerator::class);
// 				$property = $this->reflect->getProperty('totalURLCount');
// 				$property->setAccessible(true);
// 			} else {
// 				$property = $this->reflect->getProperty('totalURLCount');
// 			}
// 			$count = $property->getValue($this);
// 			return $count;
// 		}
		
// 		/**
// 		* 
// 		*	Validate the config settings
// 		*
// 		*   We're not actually generating a sitemap for public consumption so
// 		*   we don't need to adhere to the standard validation routine. In this 
// 		*   instance we're using the priority as a count of the number of items
// 		*   in the sub-sitemap, so the limit of priority >=0 <=1 is irrelevant.
// 		*
// 		* @since	1.0.59
// 		* @access   private
// 		* @param 	string 		$path
// 		* @param 	string|null $changeFrequency
// 		* @param 	float|null 	$priority
// 		* @param 	array 		$extensions
// 		* @return 	void
// 		* @throws 	InvalidArgumentException
// 		*/

// 	public function validate(
// 		string   $path,
// 		string   $changeFrequency = null,
// 		float    $priority = null,
// 		array    $extensions = []): void
// 	{
// 		if (!(1 <= mb_strlen($path) && mb_strlen($path) <= $this->MAX_URL_LEN)) {
// 			$error = sprintf("The urlPath argument length must be between 1 and %d.", $this->MAX_URL_LEN);
// 			throw new InvalidArgumentException(esc_html($error));
// 		}
// 		if ($changeFrequency !== null && !in_array($changeFrequency, $this->validChangefreqValues)) {
// 				$error = 'The change frequency argument should be one of: %s' . implode(',', $this->validChangefreqValues);
// 				throw new InvalidArgumentException(esc_html($error));
// 		}
// 		//
// 		// We already check this in our code ...
// 		//
// 		// if ($priority !== null && !in_array($priority, $this->validPriorities)) {
// 		//     throw new InvalidArgumentException("Priority argument should be a float number in the range [0.0..1.0]");
// 		// }
// 		if (count($extensions) > 0) {
// 			if (isset($extensions['google_video'])) {
// 				GoogleVideoExtension::validate($this->baseURL . $path, $extensions['google_video']);
// 			}

// 			if (isset($extensions['google_image'])) {
// 				GoogleImageExtension::validateEntryFields($extensions['google_image']);
// 			}
// 		}
// 	}

// 	}
// } else {

	// So this was all working nicely on v4 when I initiall wrote it.
	// Then to get it approved I was told to use composer, which installed version 5.
	// Spent a lot of time making V5 work properly with reflect.
	// Now I find V5 is PHP8 only, so I've had to go back to V4.
	// Lost 2 weeks at least on this :-(
	//

	class make_me_static_SitemapGenerator_legacy extends SitemapGenerator {

		/**
		 * A Reflection instance so we can access totalURLCount
		 *
		 * @since    1.0.59
		 * @access   private
		 * @var      object    $reflect
		 */

		 private $reflect;

		/**
		 * We need access to totalURLCount and unfortunately it's defined
		 * as private, which isn't helpful. Not overly happy with having
		 * to use reflect, but this seems to be the only way to get access
		 * to a private variable.
		 *
		 * @since    1.0.59
		 * @access   private
		 * @param    string    $tmpdir			Where we're going to store our work files
		 * @return 			   $totalURLCount	Number of URL's in the sitemap
		 * 
		 */

		 public function urlCount () {
			if ($this->reflect == null) {
				$this->reflect = new ReflectionClass(SitemapGenerator::class);
				$property = $this->reflect->getProperty('totalUrlCount');
				$property->setAccessible(true);
			} else {
				$property = $this->reflect->getProperty('totalUrlCount');
			}
			$count = $property->getValue($this);
			return $count;
		}

        // $this->validate($path, $lastModified, $changeFrequency, $priority, $alternates, $extensions);

		public function validate(
			string   $path,
			DateTime $lastModified = null,
			string   $changeFrequency = null,
			float    $priority = null,
			array    $alternates = null,
			array    $extensions = [])

		{
		}

	}
// }