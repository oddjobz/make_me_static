=== Make Me Static, Static Site Generator, Git, Pages and Live Stats ===
Contributors: madpenguin
Tags: static site generator, performance, security, stats, static
Requires at least: 6.5
Tested up to: 6.6.3
Stable tag: 1.1.45
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Static site generator using Git for storage. Comes with free integrated Git + Pages solution including Live WebStats.

== Description ==

Welcome to the Make Me Static Plugin for Wordpress. This plugin is a static site generator and aims to create and maintain a static copy of your Wordpress website within a Git repository. This version includes automatic access to a free Git solution and Page provider platform. (so no setup or credentials are necessary)

Alternatively the static site can be generated and stored in a GitLab Git Repository that can be used as a source for a static page platform such as CloudFlare Pages. The plugin provides a customised sitemap and change tracking which connects to an external crawling service which does all the heavy lifting.

We have made great efforts in this version to minimise the configuration required to get going, if you have any problems (with anything) please let is know and we'll do our best to help.

### How the service works

The plugin connects to a directory service on the Internet at one of the directory URL's listed below. This in turn will point the plugin to a 'crawler' that has been allocated to your site.
When you ask the plugin to make a static copy of your site, it will instruct the crawler to visit all the pages on your site to determine which have changed since it's last visit. Any changed pages will be copied to a Git repository, which in turn can publish pages directly to a page hosting service.

The default option is to use a Git account hosted by MadPenguin, and to publish the site on MadPenguin's page hosting platform. As a result the default options do not require any specific Git or Page hosting configuration to get going. If on the other hand you choose to use a hosted Git service such as GitLab, you will need to enter some credentials for your online account, and from there configure your GitLab account to publish to a page hosting service.

Once you have successfully published a static copy of your site, all you need to do is point your domain at the address of the page hosting service, and asssuming your domain matches the one you 
entered when setting up your profile within the plugin, you should be up and running.

The service retains a metadata database for the site which includes file names, sizes and modification times, together with any credentials that have been added when creating a profile. (Sensitive credentials and other information is encrypted at rest).  The external service is responsible for all scanning and processing activities to mitigate strain on the Wordpress server.
The only private data transferred to the external service is the information you enter when creating a profile. All other information is obtained via an anonymous external scan, hence publically available. If you have selected the default Git option, then the service will also retain a static copy of the site.

### Useful references

* [The service product page](https://makemestatic.com/)
* [Terms and Conditions of Service](https://makemestatic.com/terms-and-conditions/)
* [Privacy Policy](https://makemestatic.com/privacy-policy/)
* [Getting Started](https://makemestatic.com/getting-started/)
* [Service Status](https://makemestatic.com/service-status/)
* [Support Forum](https://support.madpenguin.uk/)

Make me static directory service URL's;

* https://mms-directory-1.madpenguin.uk
* https://mms-directory-2.madpenguin.uk
* https://mms-directory-3.madpenguin.uk

Other URL's used to load code;

* https://assets.makemestatic.com, source for stage-2 crawler UI
* https://status.makemestatic.com, endpoints that indicate the currently deployed version of a site
* https://weblogs.makemestatic.com, source for dynamically loaded weblogs data / websockets
* https://mms-crawler-*.madpenguin.uk, location of the actual crawlers (via websocket)

Note that this in an integrated solution, the 3rd party crawling service is owned and operated by the plugin authors on a combination of cloud hosted and on premesis equipment.

### Live Web Statistics (*experimental*)

If you opt to use the integrated Pages platform, this also provides a live WebStats option that uses the following URL;

* https://weblogs.makemestatic.com

This allows live webstats to be seamlessly delivered into your control panel and updated in real-time via a websocket connection. Do not use this URL directly!
This URL is only referenced once you click on the webstats icon next to your profile.

### How Does it work?

The Wordpress site is scanned by the MMS service under direction from the Wordpress plugin. This off-loads the scanning process to specialised software which aims to minimise the loading on the Wordpress server while scans are in progress.

There are three types of scan that can be performed;

* An "update", which literally only looks at entries with changed sitemap timestamps
  (this is very quick and great for typo's and any changes that only affect a single page)
  
* A "synchronise", typically this will scan every asset on the Wordpress site and compare a checksum of each asset against it's database to see if it's changed since the last scan. Any changes are then transferred to the connected Git repository.

* A "Git verification", this is like a "synchronise", but also scans the Git repository for assets that are no longer referenced by the site (and removes them).

As the site is scanned "from the outside" there should be no risk of the plugins actions exposing any data that isn't already public. By the same token the external service has no ability to modify Wordpress so the security footprint of the plugin is tiny.

### Feature bullet points

* The plugin provides a way to produce a static copy of your website in a git repository
* The result is compatible with both Github pages and CloudFlare pages for automatic publication
* Multiple profiles are supported for (A+B_...) testing
* Various scan rates are supported from one page per 5s to 7 cores flat out
* Scheduled updates are supported and automated scanning
* Currently "Gitlab" is supported with plans for GitHub and on-prem Gitlab
* Support push services (WebPushr) to automatically notify subscribers on scan completion
* Issue tracker for each profile to diagnose problems
  * Handles current and historical issues
  * Ability to acknowlede or delete issues when resolved
* GUI access to the internal asset database
  * Search as you type filtering
  * Pinning (force assets to be scanned regardless of perceived change)
  * Selective / manual queueing of assets for testing
* Ability to include manual / static assets in the scan (in the WP tree but unmanaged by WP)
* Ability to exclude specific files and folders
* Setup Wizard is included for a guided profile creation

### The Technology

![](images/mms.jpeg)

* The Plugin presents as a Wordpress Admin / plugin page
* Backend connections are made over websockets using PKI
* The crawler respects Robots.txt, (make sure the "MMSbot" agent is allowed on your site)
* MMS Javascript runs in an isolated module and CSS "plays nice" with Wordpress
* Global CSS and theming is partitioned and user editable (if you feel the need ...)
* All of the front-end JS code and PHP is either GPL2 or MIT licensed
* Backend-code is all based on the Orbit Framework (the framework and DB are all MIT licensed)
* Orbit is a 'real time' framework, so the admin panel is 100% reactive.
  * Progress bar updates are typically "per percent" granularity
  * All statuses, totals, etc, are updated as they happen
  * Unless you've hit a bug, there is no mileage in reloading the MMS plugin page
  * If you change your license information, it will change in real-time
  * If the crawler allocated to your site changes (perhaps due to a license upgrade) then the
    appropriate version of the crawler UI will be uploaded "into" the page from the new
    crawler.

== Frequently Asked Questions ==

= Are all the features free? =

Yes, the license relates to number of scans per day, scan rates, database size etc

= Will it work on any Wordpress site? =

In theory yes, although compatibility and interaction with with plugins will vary. The production process tries to be be clever when it comes to rewriting URL's for AJAX, forms etc, but given the number of potential edge cases this will always be ongoing. (please request support for specific plugins if you're having problems. We can't guarantee to add support, but we'll be happy to take a look to see what's involved.)

= How "good" is it? =

For a few examples, take a look at;
 * https://makemestatic.com
 * https://madpenguin.uk
 * https://linux.uk
 * https://nutpress.co.uk

= How can I see my published site?

In your plugin, if you look at the "Profiles" view, the first column is labelled "Profile". If you click on the profile name, it will attempt to link to where it thinks your static site should be if it has been published.
If you are using GitLab, this link should be to the "public URL" entered into your profile. If you are using the default Git option, this link should point to a unique URL on the MadPenguin pages platform.

= How to I point my domain at my new static site?

First you need to make sure you have verified your email address, do this by clicking on the Subscription button at the top of the screen. There is no obligation here, it's Free, we just need a point of reference before allowing you to point a domain at us.

When you've done this, edit the profile to which you want to add a domain. If your email address has been verified, you should see a "domain" field. Enter your domain in here (just the domain, so "abc.com" or "test.abc.com") then click in the orange "verify" button next to it. This will give you instructions on which DNS records you need to add to make it work. This might take a short while, you can click on "verify" and "Ok" repeatedly to re-check the result. Once it's verified, you will get a green tick next to the domain and the Orange button will turn blue. If you then click "confirm" to close the edit window, clicking on the profile name in the list of profiles should hopefully take you to your new site via your new domain.

= The Crawl doesn't find any pages? =

* Make sure you set your permalink structure to be something other than "Plain" as this mode won't generate a file and folder structure suitable for a static site.
* Make sure you have NOT enabled "short tags", so you need "short_open_tag = Off" in your PHP config

= How quick it is? =

This depends to an extent on the speed of your website, but also on the crawl rate you select. If you are running your Wordpress effectively as an off-line copy and presenting your main site as a static, and are prepared to hammer your Wordpress instance when it comes time to update, it's pretty quick.

= Looks like the scan rate isn't being applied? =

The scan rate ONLY affects pages, it is assumed static assets will be subject to caching hence will be scanned as fast as the scanner can go based on the number of cores at it's dispostal.

= The progress bar isn't linear? =

No, it's based on the number of items remaining vs the number scanned. As assets are "discovered", they will be added to the "pages remaining" total. There is no way of knowing how many assets will be discovered, so the progress indicator is a "best effort".

= If no changes are detected on the site, will the Git repository see a commit or PR? =

No. If it does, then the plugin thinks something changed. You will be able to see what got updated by looking at the commit / PR on your Git control panel. (or with "git diff")

= The label next to my profile says "Pending", what does that mean? =

It means the plugin thinks "something" has changed on the site. It watches for changes to posts, pages, comments, images, plugin updates etc. It might be the change doesn't affect your site so a sync will have no effect - but it has no way of know that until you actually do a sync.

= How to I add specific static assets to the process? =

In your "wp-config.php" file, you need to add a define section, in this example we're including the "static" folder in your wordpress directory, so all assets in this folder tree will be included.

`define( 'MAKE_ME_STATIC_FOLDER_WLIST', ['static']);`

* To prevent folders from being scanned, in this example we're excluding an entire plugin;

`define( 'MAKE_ME_STATIC_FOLDER_BLIST', ['wp-content/plugins/my-bad-plugin']);`

= Does this work over HTTP? =

* No. Your site will need at least to have a front-end HTTPS front-end. Always make sure your site address in Settings -> General reflects the HTTPS address you want the plugin to use.

= Does this work with Plain permalinks?

* No, if you have a .php file in your link, this site will not work well as a static site. A good choice is something based just on the post name, time, date etc.

= How do I see my Live WebStats for Free?

* Live WebStats are a fearure of the MakeMeStatic Pages platform, so to see live webstats on your console you will need to;
  - Select the "default" git option
  - Sync your site so you have a static copy
  - Point a REAL domain at your site by verifying your email address (subscription button)
  - Add your domain name to your profile
  - Click on "verify" next to the domain
  - Add the two DNS records listed to your DNS setup
  - Click verify, assuming this works, you should be good to go
  - There should be a green Stats button next to your profile
  .. access your new domain name and wait 10-15 seconds for the stats to become available


More Detail can be found here ...
https://makemestatic.com/

== Screenshots ==

1. Dashboard, Profiles
2. Setup Wizard Frame, Intro
3. Setup Wizard Frame, Site Details
4. Setup Wizard Frame, Operating Instructions
5. Setup Wizard Frame, Verification
6. Setup Wizard Frame, Ready
7. Dashboard, Live Web Stats (wide view)
8. Dashboard, Partial Live Stats detail

Screenshots and images are stored in the /assets directory.

== Changelog ==

= 1.1.45 =

* Stage-2 loading now happens from CDN rather than crawler
* Crawler access is now RVP'd

= 1.1.43 =

* Documentation update to support WordPress 6.6.3
* LiveStats now available on all default git deployments

= 1.1.42 =

* Typos, remove redundant debugging

= 1.1.41 =

* Fixed a number of crawler edge-cases
* Crawler speed improvements
* Added integrated / Live WebStats into the console
* Improved UI Error reporting
* Fixed UI Setup Wizard glitch
* Improved permalink detection code

= 1.1.38 =

* Fixed PHP components to work with PHP 7.4

= 1.1.36 =

* Fixing typos
* Fixing UI glitch

= 1.1.35 =

UI Improvedments

* Changing text and labels for smaller Screens
* Deployment checking fixes for non SSL targets
* Fix for local sitemap issue

= 1.1.30 =

UI improvements

* Adding support for Wordpress Playground preview
* Added live deployment tracking for integrated git/pages

= 1.1.2 =

Major new release

* Static copies are now "relative" which makes them more portable between static hosts
* Our own Git and Pages solutions have been integrated as the default solution
* Fall-back authentication now works for sites with limited or disabled JSON API
* Watermarking option now available
* Per pages feeds can now be toggled
* Rewritten profile editor
* Extensive front-end checking for connection and WP config issues
* Plugin should now work with blueprints

= 1.0.247 =

Back-end crawling improvements

* Crawler: Better support form /wp-json and dynamic URL's
* Crawler: Fixed reporting on invalid GitLab token
* Crawler: Fixed exception report in site validation fail
* Crawler: Changed behaviour when dealing with local (#) links
* Crawler: Changed behaviour when dealing /wp-json and oembed

= 1.0.239 =

Crawler improvements and documentation

* Docs: New site at https://makemestatic.com
* Crawler: Better support for oembed mapping
* Crawler: Fixed auto-robots.txt location
* Crawler/UI: Better re-conection on server restart

= 1.0.231 =

Useful fixes for new users

* UI: Better error trapping for plain permalinks
* UI: Better error trapping for sites using http (not https)
* Crawler: Better handling on non-specific script tags
* Crawler: Better handling of /wp-admin links
* Crawler: Better detection of mixed mode URL's (http+https)
* Better support for http behind CloudFlare tunnels

= 1.0.213 =

Misc Bug fixes and edge case traps

* Report and block if site is configured with "plain" permalinks
* Report and block if WP back-end is intefering with WP JSON API query strings
* Fix some spelling errors and comments

= 1.0.193 =

Updating readme.txt

* Changed tags to static site generator, performance, security, speed, static
* Changed short Description to This plugin is a static site generator for your Wordpress instance that stores and updates a static version of your site inside a Git repository.

= 1.0.191 =

Updating assets and readme.txt

= 1.0.189 =

Wordpress Directory Initial Release

* Integrated subscriptions system powered by stripe
* Email address verification and notification system
* Subscription based resources

= 1.0.102 =

Third submission:

* composer.json was present in the source repo but not in the plugin zip file
* documentation was in README.md rather than readme.txt, transferred

= 1.0.97 =

Second submission with changes to namespacing and various documentation changes. (no change in functionality)

= 1.0.58 =

Initial Directory Submission

== Upgrade Notice ==

= 1.0.102 =

This is an initial release.

