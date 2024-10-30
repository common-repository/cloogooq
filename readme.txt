=== Cloud-O-Google Queries ===
Contributors: tmb
Tags: tag, heat, cloud, queries, statistics,
Requires at least: 2.1
Tested up to: 3.1
Stable tag: 1.1

Cloud-O-Google Queries is a filter to display keyword clouds based on search engine queries.

== Description ==

I guess I'm just curious by nature. After installing Anders Holte Nielsens Counterize plug-in, I noticed quite a few google query hits. I wanted to know which keywords led people to my site. I did a quick search but couldn't find somethink like I had in mind (Update: A reader pointed out, that there is a plugin called Search Phrases, which has a slightly more minimalistic aim).
Since Counterize keeps track of the referer, it was just a matter of applying some regular expressions and preparing a tag-like keyword heat cloud, which we've all gotten used to.


== Installation ==

You will need **PHP5** and **Wordpress2.1** or higher to use this plugin. Otherwise there is nothing more to set up, just <a href="http://downloads.wordpress.org/plugin/cloogooq.zip">download</a>, extract into `/wordpress/wp-content/plugins` and activate. Check the admin panel "Options->CloOGoQ" for further options.

= Requirements =
As mentioned earlier, this is rather a plug-in plug-in. Apart from your WordPress blog, you'll need to have some way to track traffic on your site (more specifically you need some sql-table with your referer history. See FAQ below for some more discussion on that point). The plugin has been positively test with the following statistics databases (please keep reporting):
+ Counterize
+ Counterize II (prior to v2.13) `[Table/Key: "wp_Counterize_Referers"/"name", Local Table/Key: "wp_Counterize_Pages"/"url"]`
+ Search Phrases `[Table/Key: "wp_gwj_searchphrases"/"referer"]`
+ SlimStat
+ StatTrack

== Frequently Asked Questions ==

= I keep getting the `[wrong or empty table: wp_Counterize_Referers >> name ?]` error, whats wrong? =
**A:** There's a couple possibilities. First of all you should check, that you have v0.b5.4 or higher. Then you should double check that you have the correct table/key-name set in the CloOGooQ preference panel. Also check the limiter settings (they may be to high if it's a fresh blog).

= How about other search site queries? =
**A:** CloOGooQ currently looks for `"q="` matches in the referer-string. Hence any search engine using this notation will be counted (which in my eyes is the majority). Other search engines using `query=` or `qkw=` or any such string are [currently] being ignored.<br />
**A:** As of version v0.b4.4 CloOGooQ is able to handle pretty much any search engine. The user may now enter any search engine key he thinks worthy of attention. The most common ones in my blog are: `q=, as_q=, p=, query=, qkw=, key=, su=`. If you have other search engines referring to you site, just enter the appropriate key in the list provided in the options panel. Be ware of faulty or double entries, they may seriously mess up your keyword clouds.

= How about compatibility to my so-much-better statistics plugin? =
**A:** CloOGooQ will work with any statistics plugin that keeps track of the referer.

== Screenshots ==
1. keyword heat cloud.
2. phrases heat cloud.
3. basic setup
4. filter setup
5. styling setup

== Usage ==

This is quite a simple filter. There's two ways to use it:

**function call** [for use in templates]

+ `<?php if(function_exists(cloogooq)) cloogooq(keywords); ?>`
+ `<?php if(function_exists(cloogooq)) cloogooq(phrases); ?>`

**token filter** [for use in posts]

+ `[CloOGooQ_KEYWORDS]`
+ `[CloOGooQ_PHRASES]`

Either way will generate one of these two keyword clouds <a href="http://wordpress.org/extend/plugins/cloogooq/screenshots/">below</a>. The first one disassembles the search string and counts each word for it's one. The second phrase cloud leaves search strings intact - just like they got entered by the user.


== License ==
This WordPress plug is released under the <a href="http://www.gnu.org/licenses/gpl.html">GPL</a> and is provided with absolutely no warranty (as if?). For support leave a comment and we'll see what the community has to say.
