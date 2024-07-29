<?php

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

/**
 * Class Config
 * @package extends Icamys\SitemapGenerator\Config
 */

class make_me_static_sitemapconfig extends Config {

    public function __construct ( $tmpdir ) {
        parent::__construct();
        $this->setSaveDirectory( $tmpdir );
        $this->setBaseURL(get_site_url ());
    }

}


use Icamys\SitemapGenerator\SitemapGenerator;

/**
 * Class SitemapGenerator
 * @package extends Icamys\SitemapGenerator\SitemapGenerator
 */

class make_me_static_SitemapGenerator extends SitemapGenerator {

	private $reflect;
	private $MAX_URL_LEN = 2048;
    private array $validChangefreqValues = [
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
    ];

    /**
     * 
     *  urlCount - we need access to totalURLCount and unfortunately it's defined
     *             as private, which isn't helpful. Not overly happy with having
     *             to use reflect, but this seems to be the only way to get access
     *             to a private variable.
     * 
     * @return $totalURLCount
     * 
     */

	public function urlCount () {
		if ($this->reflect == null) {
			$this->reflect = new ReflectionClass(SitemapGenerator::class);
			$property = $this->reflect->getProperty('totalURLCount');
			$property->setAccessible(true);
		} else {
			$property = $this->reflect->getProperty('totalURLCount');
		}
		$count = $property->getValue($this);
		return $count;
	}
    
    /**
    * 
    *   validate - We're not actually generating a sitemap for public consumption so
    *              we don't need to adhere to the standard validation routine. In this 
    *              instance we're using the priority as a count of the number of items
    *              in the sub-sitemap, so the limit of priority >=0 <=1 is irrelevant.
    *
	* @param string $path
	* @param string|null $changeFrequency
	* @param float|null $priority
	* @param array $extensions
	* @return void
	* @throws InvalidArgumentException
	*/

   public function validate(
	   string   $path,
	   string   $changeFrequency = null,
	   float    $priority = null,
	   array    $extensions = []): void
   {
	   if (!(1 <= mb_strlen($path) && mb_strlen($path) <= $this->MAX_URL_LEN)) {
		   throw new InvalidArgumentException(
			   sprintf("The urlPath argument length must be between 1 and %d.", $this->MAX_URL_LEN)
		   );
	   }
	   if ($changeFrequency !== null && !in_array($changeFrequency, $this->validChangefreqValues)) {
		   throw new InvalidArgumentException(
			   'The change frequency argument should be one of: %s' . implode(',', $this->validChangefreqValues)
		   );
	   }
       //
       // We already check this in our code ...
       //
	   // if ($priority !== null && !in_array($priority, $this->validPriorities)) {
	   //     throw new InvalidArgumentException("Priority argument should be a float number in the range [0.0..1.0]");
	   // }
	   if (count($extensions) > 0) {
		   if (isset($extensions['google_video'])) {
			   GoogleVideoExtension::validate($this->baseURL . $path, $extensions['google_video']);
		   }

		   if (isset($extensions['google_image'])) {
			   GoogleImageExtension::validateEntryFields($extensions['google_image']);
		   }
	   }
   }

}