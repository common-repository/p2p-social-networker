<?php
/*
Plugin Name: P2P Social Networker
Plugin URI: http://metapep.wordpress.com/p2p-social-networker/
Description: This plugin enhances your blog with social networking features without relying on a central server like Myspace and Facebook do.
Version: 1.1
Author: Pepijn de Vos
Author URI: http://metapep.wordpress.com
*/
/*  Copyright 2009  Pepijn de Vos  (email : pepijndevos@gmail.com)

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
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $wpdb;

define("P2PSN_TABLE_FRIENDS", $wpdb->prefix . "p2psn");
define("P2PSN_TABLE_MESSAGES", $wpdb->prefix . "p2psn_messages");
define("P2PSN_TABLE_EVENTS", $wpdb->prefix . "p2psn_friend_events");

function P2PSN_activate() {
    global $wpdb;
    $wpdb->show_errors();
    
    //add_option('P2PSN_thumb_size', '70');
    add_option('P2PSN_widget_friends', '3');
    add_option('P2PSN_page_friends', '20');
    add_option('P2PSN_nr_feeds', '10');
    add_option('P2PSN_nr_messages', '10');
    add_option('P2PSN_refresh_feeds', 'daily');
    
    add_option('P2PSN_feed', '');
    wp_schedule_event(time(), 'daily', 'get_feeds');
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS " . P2PSN_TABLE_FRIENDS . " (
        friend_id bigint(20) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        image VARCHAR(100) NOT NULL,
        url VARCHAR(200) NOT NULL,
        feed VARCHAR(200) NOT NULL,
        UNIQUE KEY friend_id (friend_id)
    );");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS " . P2PSN_TABLE_MESSAGES . " (
        message_id bigint(20) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        image VARCHAR(100) NOT NULL,
        url VARCHAR(200) NOT NULL,
        date timestamp NOT NULL DEFAULT NOW(),
        message text NOT NULL,
        UNIQUE KEY message_id (message_id)
    );");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS " . P2PSN_TABLE_EVENTS . " (
        event_id bigint(20) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        image VARCHAR(100) NOT NULL,
        url VARCHAR(200) NOT NULL,
        feed VARCHAR(200) NOT NULL,
        date timestamp NOT NULL DEFAULT NOW(),
        UNIQUE KEY event_id (event_id)
    );");
}

function P2PSN_deactivate() {
	wp_clear_scheduled_hook('get_feeds');
}

function list_friends($limit = 3) {
    global $wpdb;
    
    $html = "<ul class=\"friends\">";
    $friends = $wpdb->get_results("SELECT name, image, url FROM " . P2PSN_TABLE_FRIENDS, ARRAY_A);
    if(is_array($friends) && count($friends) > 1) {
        foreach(array_rand($friends, min(count($friends), $limit)) as $key) {
            $friend = $friends[$key];
            $html.= "<li><a href=\"" . $friend['url'] . "\" class=\"friend\"><img style=\"max-width: 100px; max-height: 100px;\" src=\"" . $friend['image'] . "\" alt=\"avatar\" class=\"avatar\" /><div class=\"name\">" . $friend['name'] . "</div></a></li>";
        }
    } else if(is_array($friends)) {
        $friend = $friends[0];
        $html.= "<li><a href=\"" . $friend['url'] . "\" class=\"friend\"><img style=\"max-width: 100px; max-height: 100px;\" src=\"" . $friend['image'] . "\" alt=\"avatar\" class=\"avatar\" /><div class=\"name\">" . $friend['name'] . "</div></a></li>";
    } else {
        return "<div class=\"notice\">No friends to show... Poor you.</div>";
    }
    $html.= "</ul>";
    
    return $html;
}

class friends_widget extends WP_Widget {
    /** constructor */
    function friends_widget() {
        parent::WP_Widget(false, $name = 'Friends');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {	
        global $wpdb;	
        extract( $args );
        ?>
              <?php echo $before_widget; ?>
                  <?php echo $before_title . $instance['title'] . " (" . $wpdb->get_var("SELECT COUNT(name) FROM " . P2PSN_TABLE_FRIENDS) . ")" . $after_title; ?>
                  <?php echo list_friends(get_option('P2PSN_widget_friends')); ?>
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php 
    }
}

function events_page() {
    global $wpdb;
    $wpdb->show_errors(); ?>
<div class="p2psn p2psn_events">
    <?php echo get_option("P2PSN_feed"); ?>
</div>
<?php }

function friends_page() {
    global $wpdb;
    $wpdb->show_errors(); ?>
<div class="p2psn p2psn_friends">
    <?php echo list_friends(get_option('P2PSN_page_friends')); ?>
</div>
<?php }

function messages_page() {
    global $wpdb;
    $wpdb->show_errors(); ?>
<div class="p2psn p2psn_messages">
    <ul class="messages">
    <?php
        $messages = $wpdb->get_results("SELECT name, image, url, date, message FROM " . P2PSN_TABLE_MESSAGES . " ORDER BY date DESC LIMIT 0, " . get_option('P2PSN_nr_messages'), ARRAY_A);
        if(is_array($messages)) {
            foreach($messages as $message) {
                echo "<li><img class=\"message-avatar\" style=\"max-width: 100px; max-height: 100px;\" src=\"" . $message['image'] . "\" alt=\"avatar\"  /><h4 class=\"message-title\"><a href=\"" . $message['url'] . "\">" . $message['name'] . "</a></h4><div class=\"message-content\">" . $message['message'] . "</div><div class=\"message-date\">" . $message['date'] . "</div></li>";
            }
        } else {
            echo "<div class=\"notice\">You currently have no messages.</div>";
        }
    ?>
    </ul>
</div>
<?php }

function actions_page() {
    global $wpdb;
    $wpdb->show_errors(); ?>
<div class="p2psn p2psn_actions">
    <?php if($messages = $wpdb->get_var("SELECT name FROM " . P2PSN_TABLE_FRIENDS) != "") { ?>
    <h4>Send message</h4>
    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST" class="message-form">
        <label for="p2psn_friend">Select a friend to post your message to.
        <select name="p2psn_friend">
            <?php
            $friends = $wpdb->get_results("SELECT name, url FROM " . P2PSN_TABLE_FRIENDS . " ORDER BY name", ARRAY_A);
            foreach($friends as $friend) {
                echo "<option value=\"" . $friend['url'] . "\">" . $friend['name'] . "</option>";
            }
            ?>
        </select></label>
        <label for="p2psn_message">Your message<textarea name="p2psn_message" rows="5" cols="30"><?php echo $_POST['p2psn_message']; ?></textarea></label>
        <input type="hidden" name="p2psn_action" value="message" />
        <input type="submit" value="Send!" />
    </form>
    <?php } ?>
    <h4>Add friend</h4>
    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST" class="add-form">
        <label for="p2psn_url">Website/Blog<input type="text" name="p2psn_url" value="<?php echo $_POST['p2psn_url']; ?>" /></label>
        <input type="hidden" name="p2psn_action" value="add" />
        <input type="submit" value="Add!" />
    </form>
    <h4>Add friend from different network</h4>
    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST" class="add-form">
        <label for="p2psn_name">Name<input type="text" name="p2psn_name" value="<?php echo $_POST['p2psn_name']; ?>" /></label>
        <label for="p2psn_image">Email(gravatar) or image url<input type="text" name="p2psn_image" value="<?php echo $_POST['p2psn_image']; ?>" /></label>
        <label for="p2psn_url">Website/Blog<input type="text" name="p2psn_url" value="<?php echo $_POST['p2psn_url']; ?>" /></label>
        <label for="p2psn_feed">RSS feed<input type="text" name="p2psn_feed" value="<?php echo $_POST['p2psn_feed']; ?>" /></label>
        <input type="hidden" name="p2psn_action" value="add" />
        <input type="submit" value="Add!" />
    </form>
    <?php if($messages = $wpdb->get_var("SELECT name FROM " . P2PSN_TABLE_FRIENDS) != "") { ?>
    <h4>Edit friend (empty = ignored)</h4>
    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST" class="edit-form">
        <label for="p2psn_friend">Select a friend to edit.
        <select name="p2psn_friend">
            <?php
            $friends = $wpdb->get_col("SELECT name FROM " . P2PSN_TABLE_FRIENDS . " ORDER BY name");
            foreach($friends as $friend) {
                echo "<option value=\"" . $friend . "\">" . $friend . "</option>";
            }
            ?>
        </select></label>
        <label for="p2psn_name">Name<input type="text" name="p2psn_name" value="<?php echo $_POST['p2psn_name']; ?>" /></label>
        <label for="p2psn_image">Email(gravatar) or image url<input type="text" name="p2psn_image" value="<?php echo $_POST['p2psn_image']; ?>" /></label>
        <label for="p2psn_url">Website/Blog<input type="text" name="p2psn_url" value="<?php echo $_POST['p2psn_url']; ?>" /></label>
        <label for="p2psn_feed">RSS feed<input type="text" name="p2psn_feed" value="<?php echo $_POST['p2psn_feed']; ?>" /></label>
        <input type="hidden" name="p2psn_action" value="edit" />
        <input type="submit" value="Edit" />
    </form>
    <h4>Remove friend</h4>
    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST" class="remove-form">
        <label for="p2psn_friend">Select a friend to remove.
        <select name="p2psn_friend">
            <?php
            $friends = $wpdb->get_col("SELECT name FROM " . P2PSN_TABLE_FRIENDS . " ORDER BY name");
            foreach($friends as $friend) {
                echo "<option value=\"" . $friend . "\">" . $friend . "</option>";
            }
            ?>
        </select></label>
        <input type="hidden" name="p2psn_action" value="remove" />
        <input type="submit" value="Remove" />
    </form>
    <?php } ?>
</div>
<?php }

function widget_css() { ?>
<style type="text/css">
.p2psn ul.friends li {
    float: left;
    width: 100px;
    height: 120px;
    text-align: center;
}

.p2psn ul.friends {
    overflow: hidden;
    clear: both;
}

.p2psn ul.messages .message-avatar {
    float: left;
    margin-right: 1em;
}

.p2psn ul.messages li {
    min-height: 100px;
}

.p2psn label {
    display: block;
    clear: both;
    overflow: hidden;
    border-bottom: 1px solid #cecece;
    margin-bottom: 1em;
}

.p2psn label input, .p2psn label textarea, .p2psn label select {
    float: right;
    width: 50%;
}

.p2psn form {
    margin-bottom: 2em;
}
</style>
<?php }

function receiveMessage($args) {
    global $wpdb;
    $defaults = array(
        name => "Anonymous",
        image => "",
        url => $_SERVER["HTTP_referer"],
        date => time()
    );
    $args = array_merge($defaults, $args);
    
    if(get_option('wordpress_api_key') && function_exists("akismet_http_post")) {
        $query_string = "blog=" . urlencode(get_bloginfo('wpurl')) . "&user_ip=" . urlencode($_SERVER['REMOTE_ADDR']) . "?user_agent=" . urlencode($_SERVER['HTTP_USER_AGENT']) . "&referrer=" . urlencode($_SERVER['HTTP_REFERER']) . "&comment_content=" . urlencode($args['message']) . "&comment_author=" . urlencode($args['name']);
        $response = akismet_http_post($query_string, get_option('wordpress_api_key') . '.rest.akismet.com', '/1.1/comment-check');
        if($response[1] == 'true') {
            return "Your message has been marked as spam, sorry!";
        }
    }
    
    $wpdb->query("INSERT INTO " . P2PSN_TABLE_MESSAGES . " (name, image, url, message) VAlUES ('" . $wpdb->escape($args['name']) . "', '" . $wpdb->escape($args['image']) . "', '" . $wpdb->escape($args['url']) . "', '" . $wpdb->escape($args['message']) . "')");
    
    return "Thank you, your message has successfuly been posted to my blog.";
}

function sendUser($args) {
    global $wpdb;
    $wpdb->query("INSERT INTO " . P2PSN_TABLE_EVENTS . " (name, image, url, feed) VALUES ('" . $wpdb->escape($args['name']) . "', '" . $wpdb->escape($args['image']) . "', '" . $wpdb->escape($args['url']) . "', '" . $wpdb->escape($args['feed']) . "')");
    return array(name => get_bloginfo('name'), image => "http://www.gravatar.com/avatar.php?gravatar_id=".md5( strtolower(get_option("admin_email") ) ), url => get_bloginfo('wpurl'), feed => get_bloginfo('rss2_url'));
}
 
function attach_new_xmlrpc($methods) {
    $methods['P2PSN.sendMessage'] = 'receiveMessage';
    $methods['P2PSN.requestUser'] = 'sendUser';
    return $methods;
}

function sendMessage($destination, $message) {
    $xml = "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>
<methodCall>
<methodName>P2PSN.sendMessage</methodName>
<params>
 <param>
  <value>
   <struct>
    <member>
     <name>name</name>
     <value>
      <string>".get_option("p2psn_name")."</string>
     </value>
    </member>
    <member>
     <name>image</name>
     <value>
      <string>http://www.gravatar.com/avatar.php?gravatar_id=".md5( strtolower(get_option("admin_email") ) )."</string>
     </value>
    </member>
    <member>
     <name>url</name>
     <value>
      <string>".get_bloginfo('wpurl')."</string>
     </value>
    </member>
    <member>
     <name>message</name>
     <value>
      <string>".$message."</string>
     </value>
    </member>
   </struct>
  </value>
 </param>
</params>
</methodCall>";

    return send_xmlrpc($destination . "/xmlrpc.php", $xml);
}

function requestUser($destination) {
    $xml = "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>
<methodCall>
<methodName>P2PSN.sendMessage</methodName>
<params>
 <param>
  <value>
   <struct>
    <member>
     <name>name</name>
     <value>
      <string>".get_bloginfo('name')."</string>
     </value>
    </member>
    <member>
     <name>image</name>
     <value>
      <string>http://www.gravatar.com/avatar.php?gravatar_id=".md5( strtolower(get_option("admin_email") ) )."</string>
     </value>
    </member>
    <member>
     <name>url</name>
     <value>
      <string>".get_bloginfo('wpurl')."</string>
     </value>
    </member>
    <member>
     <name>feed</name>
     <value>
      <string>".get_bloginfo('rss2_url')."</string>
     </value>
    </member>
   </struct>
  </value>
 </param>
</params>
</methodCall>";

    return send_xmlrpc($destination . "/xmlrpc.php", $xml);
}

function send_xmlrpc($destination, $request) {
    $req = curl_init($destination);
    
    $headers = array();
    array_push($headers,"Content-Type: text/xml");
    array_push($headers,"Content-Length: ".strlen($request));
    array_push($headers,"\r\n");
    
    curl_setopt($req, CURLOPT_POST, 1);
    curl_setopt($req, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($req, CURLOPT_HTTPHEADER, $headers );
    curl_setopt($req, CURLOPT_POSTFIELDS, $request );
    curl_setopt($req, CURLOPT_VERBOSE, 1 );
    
    $response = curl_exec($req);
    echo curl_error($req);
    curl_close($req);
    
    $xml = new SimpleXMLElement($response);
    $xml = $xml->params->param->value->struct;
    
    $return_values = array('name'    => (string) $xml[0]->member->value->string,
		      'image'   => (string) $xml[1]->member->value->string,
		      'url'     => (string) $xml[2]->member->value->string,
		      'message' => (string) $xml[3]->member->value->string,
		      'feed'    => (string) $xml[3]->member->value->string,);
    
    //return xmlrpc_decode($response);
    //return $response;
    return $return_values;
}

function handle_forms() {
    global $wpdb;
    if(isset($_POST['p2psn_action']) && is_user_logged_in()) {
        if($_POST['p2psn_action'] == "message" && $_POST['p2psn_friend'] != "" && $_POST['p2psn_message'] != "") {
            echo "<div id=\"message\" class=\"updated fade\"><p>Your message has been sent. The other side responded with:<br />";
            echo sendMessage($_POST['p2psn_friend'], $_POST['p2psn_message']);
            echo "</p></div>";
        } else if($_POST['p2psn_action'] == "add" && $_POST['p2psn_name'] != "" && $_POST['p2psn_image'] != "" && $_POST['p2psn_url'] != "") {
            if(preg_match("/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/", $_POST['p2psn_image'])) {
            $_POST['p2psn_image'] = "http://www.gravatar.com/avatar.php?".
                                    "gravatar_id=".md5( strtolower($_POST['p2psn_image']) ).
                                    //"&default=".urlencode($default).
                                    "&size=100";
            }
            $wpdb->query("INSERT INTO " . P2PSN_TABLE_FRIENDS . " (name, image, url, feed) VALUES ('" . $wpdb->escape($_POST['p2psn_name']) . "', '" . $wpdb->escape($_POST['p2psn_image']) . "', '" . $wpdb->escape($_POST['p2psn_url']) . "', '" . $wpdb->escape($_POST['p2psn_feed']) . "')");
            echo "<div id=\"message\" class=\"updated fade\"><p>Congratulations, a new friend has been added to your list.</p></div>";
        } else if($_POST['p2psn_action'] == "add" && $_POST['p2psn_url'] != "") {
            $friend = requestUser($_POST['p2psn_url']);
            $wpdb->query("INSERT INTO " . P2PSN_TABLE_FRIENDS . " (name, image, url, feed) VALUES ('" . $wpdb->escape($friend['name']) . "', '" . $wpdb->escape($friend['image']) . "', '" . $wpdb->escape($friend['url']) . "', '" . $wpdb->escape($friend['feed']) . "')");
            echo "<div id=\"message\" class=\"updated fade\"><p>Congratulations, a new friend has been added to your list</p></div>";
            print_r($friend);
        } else if($_POST['p2psn_action'] == "edit" && $_POST['p2psn_friend'] != "") {
            if($_POST['p2psn_name'] != "") {
                $wpdb->query("UPDATE " . P2PSN_TABLE_FRIENDS . " SET name='" . $wpdb->escape($_POST['p2psn_name']) . "' WHERE name='" . $wpdb->escape($_POST['p2psn_friend']) . "'");
            }
            if($_POST['p2psn_image'] != "") {
                if(preg_match("/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/", $_POST['p2psn_image'])) {
                    $_POST['p2psn_image'] = "http://www.gravatar.com/avatar.php?".
                                   "gravatar_id=".md5( strtolower($_POST['p2psn_image']) ).
                                   //"&default=".urlencode($default).
                                   "&size=100";
                }
                $wpdb->query("UPDATE " . P2PSN_TABLE_FRIENDS . " SET image='" . $wpdb->escape($_POST['p2psn_image']) . "' WHERE name='" . $wpdb->escape($_POST['p2psn_friend']) . "'");
            }
            if($_POST['p2psn_url'] != "") {
                $wpdb->query("UPDATE " . P2PSN_TABLE_FRIENDS . " SET url='" . $wpdb->escape($_POST['p2psn_url']) . "' WHERE name='" . $wpdb->escape($_POST['p2psn_friend']) . "'");
            }
            if($_POST['p2psn_feed'] != "") {
                $wpdb->query("UPDATE " . P2PSN_TABLE_FRIENDS . " SET feed='" . $wpdb->escape($_POST['p2psn_feed']) . "' WHERE name='" . $wpdb->escape($_POST['p2psn_friend']) . "'");
            }
            echo "<div id=\"message\" class=\"updated fade\"><p>" . $_POST['p2psn_friend'] . "'s information has been updated.</p></div>";
        } else if($_POST['p2psn_action'] == "remove" && $wpdb->get_var("SELECT name FROM " . P2PSN_TABLE_FRIENDS . " WHERE name = '" . $wpdb->escape($_POST['p2psn_friend']) . "'")) {
            $wpdb->query("DELETE FROM " . P2PSN_TABLE_FRIENDS . " WHERE name = '" . $wpdb->escape($_POST['p2psn_friend']) . "'");
            echo "<div id=\"message\" class=\"updated fade\"><p>" . $_POST['p2psn_friend'] . " has been removed from your friends.</p></div>";
        } else {
            echo "<div id=\"message\" class=\"error\"><p>An error has occurred! Please check your information (did you fill in all the fields?), or contact the site admin.</p></div>";
        }
        cache_feeds();
    }
}

function sort_feeds($a, $b) {
    if ($a['date'] == $b['date']) {
        return 0;
    }
    return ($a['date'] > $b['date']) ? -1 : 1;
}

function cache_feeds() {
    global $wpdb;
    include_once(ABSPATH . WPINC . '/rss.php');
    $friends = $wpdb->get_results("SELECT name, feed FROM " . P2PSN_TABLE_FRIENDS . " WHERE feed != ''", ARRAY_A);
    $feeds = array();
    $month = array("Jan" => 1, "Feb" => 2, "Mar" => 3, "Apr" => 4, "May" => 5, "Jun" => 6, "Jul" => 7, "Aug" => 8, "Sep" => 9, "Okt" => 10, "Nov" => 11, "Dec" => 12);
    if(is_array($friends)) {
        foreach($friends as $friend) {
            //echo $friend['feed'];
            $feed = fetch_rss($friend['feed']);
            if(is_array($feed->items)) {
                foreach($feed->items as $item) {
                    //int mktime ($hour, $minute, $second, $month, $day, $year)
                    //0123456789012345678901234567890123456789
                    //Thu, 18 Jun 2009 21:05:39 +0000
                    $date = mktime(substr($item['pubdate'], 17, 2), substr($item['pubdate'], 20, 2), substr($item['pubdate'], 23, 2), $month[substr($item['pubdate'], 8, 3)], substr($item['pubdate'], 5, 2), substr($item['pubdate'], 12, 4));
                    //echo $item['pubdate'] ." == ". date("r", $date) ."<br />";
                    $feeds[] = array(name => $friend['name'], title => $item['title'], url => $item['guid'], date => $date);
                }
            }
        }
    }
    $feeds = array_merge($feeds, $wpdb->get_results("SELECT name, 'You have been added by this user' as title, url, UNIX_TIMESTAMP(date) as date FROM " . P2PSN_TABLE_EVENTS, ARRAY_A));
    usort($feeds, 'sort_feeds');
    
    $html ="<ul>";
    foreach(array_slice($feeds, 0, get_option('P2PSN_nr_feeds')) as $item) {
    //foreach($feeds as $item) {
        $html .= "<li><strong class=\"feed-outhor\">" . $item['name'] . ":</strong> <a href=\"" . $item['url'] . "\">" . $item['title'] . "</a></li>";
    }
    $html .= "</ul>";
    
    update_option("P2PSN_feed", $html);
}

function admin_page() {
    add_settings_section('p2psn_setting_section', 'P2P social networker settings', 'p2psn_section', 'reading');
    
    add_settings_field('P2PSN_widget_friends', 'Friends shown in widget', 'p2psn_widget_friends', 'reading', 'p2psn_setting_section');
    add_settings_field('P2PSN_page_friends', 'Friends shown in admin dashboard', 'p2psn_page_friends', 'reading', 'p2psn_setting_section');
    add_settings_field('P2PSN_nr_feeds', 'Events shown in admin dashboard', 'p2psn_nr_feeds', 'reading', 'p2psn_setting_section');
    add_settings_field('P2PSN_refresh_feeds', 'Refresh feeds', 'p2psn_refresh_feeds', 'reading', 'p2psn_setting_section');
    add_settings_field('P2PSN_nr_messages', 'Messages shown in admin dashboard', 'p2psn_nr_messages', 'reading', 'p2psn_setting_section');
    
    register_setting('reading','P2PSN_widget_friends');
    register_setting('reading','P2PSN_page_friends');
    register_setting('reading','P2PSN_nr_feeds');
    register_setting('reading','P2PSN_refresh_feeds', 'cron_option');
    register_setting('reading','P2PSN_nr_messages');
}

function cron_option($text) {
    wp_clear_scheduled_hook('get_feeds');
    wp_schedule_event(time(), $text, 'get_feeds');
    return $text;
}

function p2psn_section() {
    echo "<p>Hallo!</p>";
}

function p2psn_widget_friends() {
    echo "<input type=\"text\" id=\"P2PSN_widget_friends\" name=\"P2PSN_widget_friends\" value=\"" . get_option("P2PSN_widget_friends") . "\" />";
}

function p2psn_page_friends() {
    echo "<input type=\"text\" id=\"P2PSN_page_friends\" name=\"P2PSN_page_friends\" value=\"" . get_option("P2PSN_page_friends") . "\" />";
}

function p2psn_nr_feeds() {
    echo "<input type=\"text\" id=\"P2PSN_nr_feeds\" name=\"P2PSN_nr_feeds\" value=\"" . get_option("P2PSN_nr_feeds") . "\" />";
}

function p2psn_refresh_feeds() {
    echo "<select id=\"P2PSN_refresh_feeds\" name=\"P2PSN_refresh_feeds\">";
    echo "<option value=\"daily\" " . (get_option("P2PSN_refresh_feeds") == "daily" ? "selected=\"selected\"" : "") . ">Daily</option>";
    echo "<option value=\"twicedaily\" " . (get_option("P2PSN_refresh_feeds") == "twicedaily" ? "selected=\"selected\"" : "") . ">Twice daily</option>";
    echo "<option value=\"hourly\" " . (get_option("P2PSN_refresh_feeds") == "hourly" ? "selected=\"selected\"" : "") . ">Hourly</option>";
    echo "</select>";
}

function p2psn_nr_messages() {
    echo "<input type=\"text\" id=\"P2PSN_nr_messages\" name=\"P2PSN_nr_messages\" value=\"" . get_option("P2PSN_nr_messages") . "\" />";
}

function p2psn_dashboard_widgets() {
    wp_add_dashboard_widget("actions_widget", "Actions", "actions_page");
    wp_add_dashboard_widget("friends_widget", "Friends", "friends_page");
    wp_add_dashboard_widget("events_widget", "Events", "events_page");
    wp_add_dashboard_widget("messages_widget", "Messages", "messages_page");
}

add_action('get_feeds', 'cache_feeds');
add_action('xmlrpc_methods', 'attach_new_xmlrpc');
register_deactivation_hook(__FILE__, 'P2PSN_deactivate');
register_activation_hook(__FILE__, 'P2PSN_activate' );
add_action('widgets_init', create_function('', 'return register_widget("friends_widget");'));
add_action('wp_dashboard_setup', 'p2psn_dashboard_widgets');
add_action('admin_notices', 'handle_forms');
add_action('admin_head', 'widget_css');
add_action('admin_init', 'admin_page');
?>