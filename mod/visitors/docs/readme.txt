

visitors 1.2.1, 2005-07-21
~~~~~~~~~~~~~~~~~~~~~~~~~~


by rck <http://www.kiesler.at/>


Visitors is a module I wanted to write long ago. It's basically
a remix of "Who's online,, "Statistics" and automatic Spider
detection.

Right now it's more of a "Who's online light with a brain." Unlike
who's online, it doesn't throw away its knowledge so it can
process statistics in a later version.

What other things are different with Who's online?

- no flags!
- no notes-link! (will include that later)

- no duplicate ips! I don't filter them. But use the session_id
  for filtering instead.

- session oriented! Now you can see what a user did in a session
  as well as how long it lasted.



CONTACT


got any questions about this or suggestions for future versions?
please contact the author via his forum. Please don't use mails
or notes if not absolutely necessary. Stick to the forum, so
others can benefit about your post and discuss it as well.

	http://www.kiesler.at/


Got any problems or questions about phpWebSite in general? Please
contact the phpws support forums. The author of this program (rck)
can be found there as well.

	http://phpwsforums.com/



CONTRIBUTORS

	MosMensk: translation to danish & quality assurance
	tluft: translation to german & quality assurance
	tonynl: translation to dutch & quality assurance

	Robert Kennedy: general english quality assurance




CHANGELOG


Visitors 1.2.1, 2005-10-17

	bug-fix in deity_details.php

	- I mixed up $forwarded_for and $forward in deity_details.
	  No biggie, just annoying :-)

	  see also http://www.kiesler.at/phpwsbb~PHPWSBB_MAN_OP~view~PHPWS_MAN_ITEMS~510~page~last.html

	  Thanks to abcbm for reporting!


Visitors 1.2, 2005-07-21

	show search engine queries

	- rewritten the dispatcher to be more compact and modular

	- added external search engine query analysis

	- fix: changed "foward" to "forward" in class/deity_details.php
	  (thanks, tonynl + DnOlvrB!)

	- fixed all "undefined index" / "undefined variable" notices I
	  found (thanks Chris, aka youcantryreachingme (what a nick))

	- turning on notices also found me another bug:
	  $ips[$nr] was $ips[$i] in deity_details but I never set $i.
	  so I've been checking for the 0th element all the time instead
	  of checking them all.

	  People, turn on your notices!  (error_reporting(E_ALL))


Visitors 1.1, 2005-03-31

	three new languages, referrer log statistics, visible flag

	- from now on, Visitors will contain four language files.
	  The english one, a german one (translated by tluft),
	  a dutch one (translated by tonynl) and a danish one
	  (translated by MosMensk)

	- this version introduces a referrer host drill up / down
	 
	- last but not least: you can change the visibility of the
	  "logged in visitors" box. Just change $visible in
	  conf/config.php


visitors 1.0: 2005-02-09

	drill up / down

	- you can drill up and down your site-hits as well as user-visits
	  from this version on. as soon as you click the visitors icon in
	  the controlpanel, it will lead you to the hits of the current
	  month. you can click on the days there to get deeper or on
	  the cookie crumb to get higher up.

	  you can switch between user and hit-view by clicking it in the
	  top menu.


visitors 0.4.1: 2005-01-28

	bugfix

	- the user summary of the deity box got translated "twice". So,
	  visitors would insert stuff like "Me and 2 guests", "Me and 3 guests"
	  and so on in the database, instead of "[var1] and [var2] guests".

	  thanks Sharon for noticing!


visitors 0.4: 2005-01-26

	two statistics, a primitive clickpath and nicer messages

	- user visit statistic and referer statistic, can both be found in
	  Control Panel. Show frequency of referer-host-access, first and
	  last time of a certain user access as well as average time a
	  user visits our site per day.

	- rewrite of guestbox-messages, different messages depending
	  on how much members / guests are online

	- primitive clickpath: show, where the user came (first referrer)
	  as well as a list of things he has visited in the current
	  session. right now, visitors knows (partially) about the
	  following modules, others and a better clickpath management
	  will follow:

	    . article (Article Manager)
	    . calendar
	    . controlpanel
	    . documents
	    . fatcat
	    . linkman
	    . pagemaster
	    . photoalbum
	    . phpwsbb
	    . visitors

	  visitors will show you the title of the currently active
	  item (for example, the current article). if the user is
	  browsing a particular photo, visitors will show you its
	  thumbnail.
	


visitors 0.3.3: 2005-01-15

	bugfixes

	- realip() and showDeityBox() now honour the "unknown"
	  IP that can occur within HTTP_X_FORWARDED_TO


visitors 0.3.2: 2005-01-11

	bugfixes

	- realip() didn't initialize the count-variable for HTTP_X_FORWARDED_FOR
	  correctly. Thus, if HTTP_X_FORWARDED_FOR contained more than one ip,
	  visitors crashed. I've changed that.

	- the details-page now tries to lookup every single address of
	  HTTP_X_FORWARDED_FOR


visitors 0.3.1: 2005-01-09

	bugfixes

	- realip() now honors the ip-hack

	- the details page now shows all three possible http-header
	  fields containing ips (REMOTE_ADDR, CLIENT_IP, HTTP_X_FORWARDED_FOR)
	  if they are different to the realip() found and tries to resolve
	  them

	- the retrieval of the session data has been rewritten. theres now
	  one request for every session-id that retrieves the record of that
	  session with the highest id. this leads to an more accurate display
	  of the current user module.
	  


visitors 0.3: 2005-01-09

	detail page, bugfixes, refinement

	- uncluttered deity view. it now shows only the username,
	  together with the resolved hostname and the current module
	  of that user.

	- new details page. if logged in as deity, there will be a
	  link to a details page near every username. the details
	  page shows as of now:

	  . real ip as well as resolved hostname

	  . user agent

	  . username and user_id

	  . current module

	  . current uri (page address)


	- BUGFIX: didn't return the generated html for the guest box.
	  that's why guests had no visitors-box.


visitors 0.2: 2005-01-09

	refinement

	- started coding a automated spider search. doesn't work as
	  of yet.

	- various cosmetic changes in the output.


visitors 0.1: 2005-01-08

	initial version

	- stores user name, user id, session id, real ip address,
	  timestamp and the following HTTP fields for every hit to our
	  website:

	  REMOTE_ADDR, REMOTE_PORT, HTTP_CLIENT_IP, HTTP_X_FORWARDED_FOR,
	  HTTP_USER_AGENT, HTTP_REFERER, HTTP_ACCEPT_LANGUAGE, QUERY_STRING,
	  HTTP_HOST, REQUEST_URI

	- shows number of members and guests to guests

	- shows names of members and number of guests to members

	- shows the following to deities:

	  Username with ID (if member), "Guest" otherwise.

	  first part of HTTP_USER_AGENT. For example "Mozilla/5.0" or
	  "msnbot/0.3".

	  real ip address of user. honors forwarding.

	  hostname of that ip address, if it can be looked up.

