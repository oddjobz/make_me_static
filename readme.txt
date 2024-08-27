=== Make Me Static ===
Contributors: madpenguin
Tags: static, static-site, static-wordpress, static-page, static-site-generation
Requires at least: 6.5
Tested up to: 6.6.1
Stable tag: 1.0.144
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
This plugin for your self-hosted wordpress that creates and updates a static version of your website as a project inside a Git repository.

== Description ==

Welcome to the Make Me Static Plugin for Wordpress. This software aims to create and maintain a static copy of your Wordpress website within a 
Git repository. The plugin provides a customised sitemap and change tracking, then connects to the MMS service. This external service does all 
the heavy lifting.

### What do we rely on?

This plugin employs a third party service to scan a Wordpress site and store the resulting
static copy in a Git repository. The service retains a database of metadata regarding the site 
which includes file names, sizes and modification times, together with any credentials that have 
been added when creating a profile. Sensitive information is encrypted at rest. The external service is responsible for scanning and processing activities in order to minimise the loading on the Wordpress server.

The only private data transferred to the external service is the information you enter when creating a profile.
All other information is information is obtained via an external scan, hence publically available.

The plugin references the third party service via directory services which are accessed at;
* https://mms-directory-1.madpenguin.uk
* https://mms-directory-2.madpenguin.uk
* https://mms-directory-3.madpenguin.uk

This in turn will refererence the crawler allocated to the site in question. Crawler URLs typically take the form https://mms-crawler-(n).madpenguin.uk. For on-premesis crawlers the 
url will also include a customer-id prefix, but will always end in ".madpenguin.uk".

#### Useful links relating to the 3rd party service

* [The service product page](https://madpenguin.uk/make-me-static/)
* [Getting Started](https://madpenguin.uk/make-me-static-getting-started/)
* [Terms and Conditions of Service](https://madpenguin.uk/mms-terms-and-conditions/)
* [Privacy Policy](https://madpenguin.uk/privacy-policy/)
* [Support Forum](https://support.madpenguin.uk/)

Note that this in an integrated solution, the 3rd party service is owned and operated by the 
plugin authors on a combination of cloud hosted and on premesis equipment.

### How Does it work?

The Wordpress site is scanned by the MMS service under direction from the Wordpress plugin. This
off-loads the scanning process to specialised software aims to minimise the loading on the Wordpress server while scans are in progress.

There are three types of scan that can be performed;

* An "update", which literally only looks at entries with changed sitemap timestamps
  (this is very quick and great for typo's and any changes that only affect a single page)
  
* A "synchronise", typically this will scan every asset on the Wordpress site and compare a
  checksum of each asset against it's database to see if it's changed since the last scan.
  Any changes are then transferred to the connected Git repository.

* A "Git verification", this is like a "synchronise", but also scans the Git repository for assets
  that are no longer referenced by the site (and removes them).

As the site is scanned "from the outside" there should be no risk of the plugins actions exposing
any data that isn't already public. By the same token the external service has no ability to modify
Wordpress so the security footprint of the plugin is tiny.

### Feature bullet points

* The plugin provides a way to produce a static copy of your website in a git repository
* The result is compatible with both Github pages and CloudFlare pages for automatic publication
* Multiple profiles are supported for (A+B_...) testing
* Various scan rates are supported from one page per 5s to 7 cores flat out
* Scheduled updates are supported or automated scanning
* Currently "Gitlab" is supported with plans for GitHub and on-prem Gitlab
* Support push services (WebPushr) to automatically notify subscribers on scan completion
* Issue tracker for each profile to diagnose problems
  * Handles current and historical issues
  * Ability to acknowlede or delete issues when resolved
* GUI access to the internal asset database
  * Search as you type filtering
  * Pinning (force assets to be scanned regardless of perceived change)
  * Selective / manual queing of assets for testing
* Ability to include manual / static assets in the scan (in the WP tree but unmanaged by WP)
* Ability to exclude specific files and folders
* Setup Wizard is included for a guided profile creation

### The Technology

![](images/mms.jpeg)

* The Plugin presents as a Wordpress Admin / plugin page
* Backend connections are made over websockets using PKI
* The crawler respects Robots.txt, (make sure the "MMSbot" agent is allowed on your site)
* MMS Javascript runs in an isolated module and CSS "plays nice" with Wordpress
* Global CSS and themeing is partitioned and user editable (if you feel the need ...)
* All of the front-end JS code and PHP is either GPL2 or MIT licensed
* Backend-code is all based on the Orbit Framework (the framework and DB are all MIT licensed)
* Orbit is a 'real time' framework, so the admin panel is 100% reactive.
  * Progress bar updates are typically "per percent" granularity
  * All statuses, totals, etc, are updated as they happen
  * Unless you've hit a but, there is no mileage in reloading the MMS plugin page
  * If you change your license information, it will change in real-time
  * If the crawler allocated to your site changes (perhaps due to a license upgrade) then the
    appriate version of the crawler UI will be uploaded "into" the page from the new
    crawler.

== Frequently Asked Questions ==

= Are all the features free? =
Yes, the license relates to number of scans per day, scan rates, database size etc

= Will it work on any Wordpress site? =
In theory yes, although compatibility and interaction with with plugins will vary. The production process tries to 
be be clever when it comes to rewriting URL's for AJAX, forms etc, but given the number of potential edge cases this 
will always be ongoing. (please request support for specific plugins if you're having problems. We can't guarantee to 
add support, but we'll be happy to take a look to see what's involved.)

= How "good" is it? =

For a few examples, take a look at;
 * https://linux.uk
 * https://madpenguin.uk
 * https://nutpress.co.uk

= How quick it is? =

This depends to an extent on the speed of your website, but also on the crawl rate you select.
If you are running your Wordpress effectively as an off-line copy and presenting your main site
as a static, and are prepared to hammer your Wordpress instance when it comes time to update,
it's pretty quick.

= Looks like the scan rate isn't being applied? =

The scan rate ONLY affects pages, it is assumed static assets will be subject to caching hence
will be scanned as fast as the scanner can go based on the number of cores at it's dispostal.

= The progress bar isn't linear? =

No, it's based on the number of items remaining vs the number scanned. As assets are "discovered",
they will be added to the "pages remaining" total. There is no way of knowing how many assets will
be discovered, so the progress indicator is a "best effort".

= If no changes are detected on the site, will the Git repository see a commit or PR? =

No. If it does, then the plugin thinks something changed. You will be able to see what got updated
by looking at the commit / PR on your Git control panel. (or with "git diff")

= The label next to my profile says "Pending", what does that mean? =

It means the plugin thinks "something" has changed on the site. It watches for changes to posts,
pages, comments, images, plugin updates etc. It might be the change doesn't affect your site so
a sync will have no effect - but it has no way of know that until you actually do a sync.

= How to I add specific static assets to the process? =

in your "wp-config.php" file, you need to add a define section, in this example we're including
the "static" folder in your wordpress directory, so all assets in this folder tree will be included.

`define( 'MMS_FOLDER_WLIST', ['static']);`

* To prevent folders from being scanned, in this example we're excluding an entire plugin;

`define( 'MMS_FOLDER_BLIST', ['wp-content/plugins/my-bad-plugin']);`

= Does this work over HTTP? =

* No. Your site will need at least to have a front-end HTTPS front-end. Always make sure your site address in Settings -> General reflects the HTTPS address you want the plugin to use.

More Detail can be found here ...
https://madpenguin.uk/make-me-static/

== Screenshots ==

1. Screenshot of the main admin page

Screenshots and images are stored in the /assets directory.

== Changelog ==

= 1.0.102 =

Third submission:

* composer.json was present in the source repo but not in the plugin zip file
* documentation was in README.md rather than readme.txt, transferred

= 1.0.97 =

Second submission with changes to namespacing and various documentation changes.
(no change in functionality)

= 1.0.58 =

Initial Directory Submission

== Upgrade Notice ==

= 1.0.102 =

This is an initial release.

