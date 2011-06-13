<?php
/*
Copyright (C) 2003 Claus Beerta <claus@beerta.de>

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
/* $Id: config.inc.php,v 1.9 2004/01/07 08:56:44 claus Exp $ */

/**
* Configuration is done with this file
*/

$datadir = '/var/www/html/data';	// Point this to the datadirectory. The rssFetch.php script needs write access here.
$smartyCompileDir = '/tmp/rssreader_c';	// Point this to a directory where the HTTP/PHP process can put files into 
$daysToDisplay = 3; // Number of days to display on the page.


// Add all your RDF/RSS feeds here.
$sources[] = 'http://www.advogato.org/person/hadess/rss.xml';
$sources[] = 'http://www.burtonini.com/blog/?flav=rss';
$sources[] = 'http://kniebes.net/mk/RSS2.0/full';
$sources[] = 'http://primates.ximian.com/~rml/blog/index.rdf';
#	$sources[] = 'http://claus.beerta.de/rdf.php';  // lol, my own feed sucks balls.
$sources[] = 'http://linuxart.com/sitenews.xml';
#	$sources[] = 'http://art.gnome.org/backend.php';   //broken ...
$sources[] = 'http://codeblogs.ximian.com/blogs/evolution/index.rdf';
#	$sources[] = 'http://slashdot.org/index.rss'; // too busy.
$sources[] = 'http://news.css-technik.de/rss.xml';
$sources[] = 'http://www.kerneltrap.org/node/feed';
$sources[] = 'http://www.php.net/news.rss';


// Your OPML file, leave empty to use no opml source.
$opml_src = '';
	
