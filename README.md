# Bliss

Because reading my private, mostly NSFW RSS Feeds makes me a happy puppy.

fka rssReader. see [link](http://claus.beerta.de/blog/09ce5c79e6426fcb5cbacf2b714c4edf) for my first release.

Version: 2.0.0

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
* [SimplePie](https://github.com/simplepie) (as git submodule)
* PHP 5.3 or later
* Shell Access to execute PHP
	
# INSTALLATION:
	
* Untar the archive into your webservers directory. 
* Open the `config.ini` in you favorite editor and follow the instructions in that file.
* You need to set `data_dir` and `cache_dir` and, of course, your `sources`.
* Then run `php index.php --update` from the commandline
* After you've run the update you can open the page in your browser.
	
# TODO:
	
* A Simple way to add new feeds
* Keyboard navigation to hop between articles (prev and next atleast)
* Load posts that are pulled via update while site is opened
* and/or inform user about new posts (maybe easier, and less intrusive if you're reading something and content is popped to the top)
* Article Expire (for now a little `find data/ -type f -mtime +32` will have to suffice)
* Article Flagging / Starring
* With the flagging or starring there needs to be a way to retrieve these. And obviously never expire them either.
* An Archive page of some sort, for direct access to posts. Goes along with the starring/flagging.
* Search?
* Should probably cache `Reader::filelist()`. it goes over every file on every reload. For now though, the fs-cache does a good job.

# BUGS

* If the initial page doesn't exceed the browsers bottom end, the autoload thingy won't work. Should probably do the initial load with jquery and just load until it starts scrolling or something.
* It probably doesn't look good in Internet Explorer or Opera.
* Fast scrolling causes multiple parallel pulls for content, and then alot of duplicates.

Probably alot more. Tested with a couple of feeds, but there are probably alot that don't work correct.

# LICENSE:

Released under the [MIT License](http://www.opensource.org/licenses/mit-license.php)

