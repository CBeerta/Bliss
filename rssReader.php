<?PHP
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


	/* $Id: rssReader.php,v 1.7 2004/01/07 08:40:07 claus Exp $ */

	include 'libs/Smarty.class.php';
	include_once 'config.inc.php';

	$tpl = new Smarty;
	$tpl->templates_dir = 'templates/';
	$tpl->compile_dir   = $smartyCompileDir;
	$tpl->debugging		= false;



	

	$tpl->assign("rssReaderBaseUrl", "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
	$tpl->assign("rfcnow", date("r"));

	$days = array();
	$entries = array();
	$dailyidx = 0;
	for ( $date = mktime() ; $date >= mktime() - ($daysToDisplay*24*60*60) ; $date -= 24*60*60 ) {
		$srcdir = $datadir.'/'.date('Y',$date).'/'.date('m',$date).'/'.date('d',$date);
		if ( is_dir($srcdir) ) {
			$dh = opendir($srcdir);
			$files = array();
			/* We are first going through the directory and pick up al relevant files */
			while (($file = readdir($dh)) !== false) {
				if ( preg_match('/^entry-(.*)-(.*)$/', $file, $matches) ) {
					/* The key of the array is the filename, content of the array is the timestamp */
					$files[$file] = $matches[2];
				}
			}
			closedir($dh);

			/* Sort the files array by date. */
			arsort($files);

			/* Now we can go through the sorted files array and load them up */
			foreach ( array_keys($files) as $file ) {
				$fh = fopen($srcdir.'/'.$file,'r');
				$rsstitle = rtrim(fgets($fh));
				$entrytitle = rtrim(fgets($fh));
				$entrylink = rtrim(fgets($fh));
				$content = '';
				while ( !feof($fh) ) 
					$content .= fgets($fh);

				$entries[$dailyidx][] = array(
												'filename' => $file,
												'time' => $files[$file], 
												'rsstitle' => $rsstitle, 
												'entrytitle' => $entrytitle,
												'entrycontent' => $content,
												'entrycontentstripped' => htmlentities(strip_tags($content)),
												'entrylink' => $entrylink,
												'entrytime' => date("F j, Y, H:i T", $files[$file]),
												'rfcdate' => date("r", $files[$file])
											);

				fclose($fh);
			}
			$days[$dailyidx] = array('date' => date("F j, Y", $date));
			$dailyidx++;
		}
	}

	$tpl->assign('daily', $days);
	$tpl->assign('entry', $entries);

	if ( file_exists($tpl->template_dir."/".str_replace("../","",$_GET['template'].".tpl.html")) ) {
		$tpl->display($_GET['template'].".tpl.html");
	} else {
		$tpl->display('index.tpl.html');
	}
?>
