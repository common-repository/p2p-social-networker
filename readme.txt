=== Plugin Name ===
Contributors: pepijndevos
Donate link: http://metapep.wordpress.com/
Tags: p2p, peer to peer, rss, xmlrpc, social network, hyves, facebook, myspace
Requires at least: 2.8
Tested up to: 2.9-rare
Stable tag: trunk

P2P social networking in between blogs, no central server involved.

== Description ==

**This plugin is not longer developed by me, I think it still works, but YMMV.**

**You'll want P2P social networker if you**

* Own a Wordpress.org blog
* Live and breath social networking
* Feel limited by your regular social network page
* Have the strange feeling in you stomach that you might have some extra spam after your subscription
* Are tired of site maintenance and/or downtime of regular social networks
* Prefer people who have something to blog about over teenagers who seem to be able to talk about nothing forever

**P2P social networker offers you**

* The social network functionality you're used to, like friends, personal messages, updates about friends, etc...
* All the Wordpress functionality you love
* Peer to peer social networking, which means
* All your information stored on your own server
* If one server goes down, the rest stays up
* A network of bloggers without central server intervention

P2P social networker is a social networking plugin to turn your very blog into the centre of your networking needs. Based on open standards like RSS and XMLRPC, P2P social networker allows you to communicate and keep track of what your friends are doing.

P2P social networker makes use of powerful tools included in Wordpress, such as Askimet spam filtering and automattic Gravatar images.

P2P social networker is now in a usable state. I will not actively continue development, but I will fix bugs and add requested features, so keep your requests and bugs coming! Visit my blog for more information.

== Installation ==

Just the basic stuff:

1. Upload `p2p-social-networker` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. [optional] Add the widget to your sidebar
1. Go to your dashboard and start adding and messaging friends!

== Frequently Asked Questions ==

= Where is the admin page! =

There is none. All management is done at the Actions tab at the admin dashboard. There are a few extra setting under 'reading' where you can set how many friends/messages/events you want to show.

= Can I add friends from other social networks? =

Sure, as long as they have an RSS feed you can add them manually.

= Is there a P2P Social Networker version for &lt;your non-Wordpress website here&gt;? =

Not as far as I know. But as long as your site has an RSS feed and supports a bunch of XMLRPC methods, you're good to go.

= Which methods? =

That sounds good! Interested?
I only use two methods at the moment:

`xmlrpc_encode_request('P2PSN.sendMessage', array(
    name => "name",
    image => "image",
    url => "website",
    message => "message"));

xmlrpc_encode_request('P2PSN.requestUser', array(
    name => get_bloginfo('name'),
    image => "http://www.gravatar.com/avatar.php?gravatar_id=".md5( strtolower(get_option("admin_email") ) ),
    url => get_bloginfo('wpurl'),
    feed => get_bloginfo('rss2_url')));`

*Note:* Your email address is not shared, only the MD5 hash (this is the same stuff Wordpress uses to save your passwords) is outputted to the browser.

= Does P2P social networker have &lt;your feature here&gt;? =

I doubt it, but feel free to send me a request.

== Screenshots ==

1. This is the sidebar widget, I left everything unstyled for now. Front end style is up to the theme I think.
2. The new and shiny admin dashboard showing everything you want to know, and if you don't, collapse then with one click.

== Requirements ==

Everything you need to run Wordpress 2.8 (which you need for the widget API) plus:

* cURL - PHP 4.0.2
* PHP 5 is advised
