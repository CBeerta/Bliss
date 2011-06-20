# Bliss

Because reading my private, mostly NSFW RSS Feeds makes me a happy puppy.

fka rssReader. see [link](http://claus.beerta.de/blog/09ce5c79e6426fcb5cbacf2b714c4edf) for my first release.

# DESCRIPTION:

Bliss is a simple application which can be used to gather a set of RSS/RDF feeds, 
and compile them into one single Page ordered by Date.

I build it because i liked PlanetGnome (http://www.gnome.org/~jdub/planetgnome/) alot, but
didn't like the Python Spycyroll (http://spycyroll.sourceforge.net/).

Recently it got a slight update (as in complete rewrite) to something a bit more up-to-date.

# BUT WHY?

* I have feeds that i don't want Google Reader to know about. (The NSFW, and authenticated kind)
* tt-rss needs MySQL, i'd like to get rid of that dependency for my VPS
* I don't want a Desktop Reader.
* And, as always: Because i can, that's why!

# REQUIREMENTS:

* [Smarty](http://www.smarty.net/) (It is included under `vendor/`)
* [Flight Framework](https://github.com/mikecao/flight)
* [SimplePie](https://github.com/simplepie)
* PHP 5.3 or later
* Shell Access to execute PHP
	
# INSTALLATION:
	
* Untar the archive into your webservers directory. 
* Pull submodoles: `git submodule init` then `git submodule update`. Or Download manually and put them into the `vendor` directory.
* Open the `config.ini.sample` in your favorite editor and follow the instructions in that file. Save as `config.ini`
* Then run `php index.php --update` from the commandline
* After you've run the update you can open the page in your browser.

# ADDING FEEDS:

There is 3 ways to do add feeds to Bliss:

* Add them to `config.ini`
* Create an OPML File, and add that to `config.ini`
* Add them via the Options Menu pulldown

Bliss uses SimplePie for feed retrieval. SimplePie has Feed autodetection, so you shouldn't have to 
worry about adding the RSS Feed as URL. Usually just adding a Page that has a Feed is sufficient.

# KEYBOARD NAVIGATION:

These Keyboard Commands exist currently:

* `n` for Next Article
* `p` for Previois Article
* `r` to Reload all Articles
	
# TODO:

* Keyboard Nav needs work. We need to make sure a loadNext event loads enough content to allow scrolling to the next item. Maybe we can trick with padding here?
* Expire of the simplepie cache
* For the gallery there needs to be a way to go from picture to post somehow. SimplePie makes this incredible hard though. We can go to feed now atleast, maybe the step to a single item is more possible then.
* Filter Duplicate titles, show only newest.
* Search? (this is probably where i will regret most that i chose not to use a database)
* Should probably cache `Reader::filelist()`. it goes over every file on every reload. For now though, the fs-cache does a good job.
* Export all feeds to OPML, export images from gallery

# BUGS

* It will likely not look good in Internet Explorer or Opera.
* Fast scrolling causes the page to simply stop reloading.
* Keyboard Navigation needs more work

Probably alot more. Tested with a couple of feeds, but there are probably alot that don't work correct.

# LICENSE:

Released under the [MIT License](http://www.opensource.org/licenses/mit-license.php)

