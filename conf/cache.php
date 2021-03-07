<?php 
/**
 * These are the cache settings for phpWebSite
 * If CACHE == TRUE, then modules will be able to cache
 * information in the cache table. If you have a low
 * volume site, you could set this to FALSE to save some cycles.
 *
 * GLOBAL_CACHE will set information into a GLOBAL variable which
 * will prevent repeated hits to the database for the same information.
 *
 * TTL is the number of seconds to keep a cache value by default
 *
 * PAGE_CACHE looks for matches based on the module and template
 * information. If a match is made, the cache info is returned
 * instead of creating the template. Does not override the CACHE
 * switch.
 *
 * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
 */

if (!defined("CACHE"))
     define("CACHE", FALSE);

if (!defined("GLOBAL_CACHE"))
     define("GLOBAL_CACHE", FALSE);

if (!defined("TTL"))
     define("TTL", 15);

if (!defined("PAGE_CACHE"))
     define("PAGE_CACHE", FALSE);
?>