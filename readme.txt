=== Plugin Name ===
Contributors: YYengine
Donate link: http://yyengine.jp/
Tags: event, concert, sport, theater listing
Requires at least: 3.8
Tested up to: 4.3.1
Stable tag: 1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enables you to show a list of Events, Concerts, Sports and Theater Listings.

== Description ==

Enables you to show a list of Events, Concerts, Sports and Theater Listings.

= Current Features =
* Admin pages to view/create/manage/modify events
* Available event data fields: event title, date, open/start time, price, place, actors, contact, infomation, image, event details
* Select Beginning events view, old evetns view and all events view, calendar view.
* WordPress's WYSIWYG editor for the event details.
* Events list view widget box. 


== Installation ==

1. Upload `/yy-events/` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

Add the shortcode [yyevents] in the content of any pages.

future event
[yyevents]

old event(evetnt per page = 5)
[yyevents pagenum=5 show="old"]

calendar event
[yyevents type=calendar]

Override shortcode settings:

type=list|calendar - default list

(only type=list)
pagenum=int - default 10
singlelink=on|off - evetn detail page link
image=on|off - default on
show=now|old|all - view event

== Screenshots ==

1. event management
2. shotcode add
3. shotcode add (old event)
4. event page
5. event input

== Changelog ==

= 1.4 =
* event end date add

= 1.3 =
* calendar type add

= 1.2 =
* bug fix : event list pagination

= 1.1 =
* add Event list widget.
* localize en and jp.

= 1.0 =
* first commit.

