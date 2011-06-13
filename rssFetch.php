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


/* $Id: rssFetch.php,v 1.10 2004/01/12 19:17:51 claus Exp $ */

require_once "libs/RSS.php";
include_once "config.inc.php";

$updates = 0;

/* OPML Support added by Fabian Grümer */
if( !empty($opml_src) ) {
    $fh = join("",file( $opml_src ));
    preg_match_all("=<outline (.+)/>=sU", $fh, $items);
    foreach ($items[1] as $item) {
        preg_match("/xmlUrl=\"(.+)\"/U", $item, $temp);
        $sources[] = $temp[1];
    }
}

foreach ( $sources as $source ) {
	print "Working on: ".$source."\n";

	$rss =& new XML_RSS($source);
	$rss->parse();
	
	$channelInfo = $rss->getChannelInfo();
	foreach ($rss->getItems() as $item) {

		if ( !empty($item['dc:date']) ) {
			/* Try to find the date for stoopid W3C format */
			/* FIXME: Need to take Timezones into account ... To lazy to do that, though. */
			/* Most feeds don't carry Timezone Information, so it is pretty useless anyway.*/
			$date = $item['dc:date'];
			preg_match("/^([0-9][0-9][0-9][0-9])\-([0-9][0-9])\-([0-9][0-9])(T([0-9][0-9])\:([0-9][0-9]))*/", $date, $matches);
			$date = mktime($matches[5], $matches[6], 0, $matches[2], $matches[3], $matches[1]);
		} else if ( !empty($item['pubdate']) ) {
			$date = strtotime($item['pubdate'], 0);
		} else {
			/* When we are unable to get a date, the feed is unusable... */
			print " + No date for feed: $source found.\n";
			break;
		}


		if ( $date < 0 ) {
			/* Time Conversion failed, or is invalid. We can't deal with wrong times. So ignore the feed */
			print " + Invalid date for feed: $source\n";
			break;
		}
		

		/**
		 * Traverse through the different methods of putting content into the feed,
		 * stop when finding the most useful source.
		 */
		$content = '';
		if ( !empty($item['description']) )			$content = $item['description'];
		if ( !empty($item['content:encoded']) )		$content = $item['content:encoded'];
		if ( empty($content) ) {
			/* Feeds without content are not really usefull ... */
			print " + No Content for feed: $source found.\n";
			break;
		}

		$yyyy = date('Y', $date);
		$mm   = date('m', $date);
		$dd   = date('d', $date);

		if ( !is_dir($datadir.'/'.$yyyy) ) mkdir($datadir.'/'.$yyyy); 
		if ( !is_dir($datadir.'/'.$yyyy.'/'.$mm) ) mkdir($datadir.'/'.$yyyy.'/'.$mm);
		if ( !is_dir($datadir.'/'.$yyyy.'/'.$mm.'/'.$dd) ) mkdir($datadir.'/'.$yyyy.'/'.$mm.'/'.$dd);

		$destfilename = $datadir.'/'.$yyyy.'/'.$mm.'/'.$dd.'/entry-'.md5($source).'-'.$date;
		if ( !file_exists($destfilename) ) {
			/* Here we write the entries of all feeds to disk */
			print " + Creating file: ".$destfilename."\n";
			$outfile = fopen($destfilename, 'w') or die ("Unable to create $destfilename\n");
			fwrite($outfile, $channelInfo['title']."\n");
			fwrite($outfile, $item['title']."\n");
			fwrite($outfile, $item['link']."\n");
			fwrite($outfile, $content);
			fclose($outfile);
			$updates++;
		} 
	}
}

print "Added $updates new Articles.\n";



