# TT-Rss: Notify XMPP - Prosody
A [TT-RSS](https://tt-rss.org/) plugin for sending XMPP notifications via [Prosody](https://prosody.im/) using [mod_post_msg](https://modules.prosody.im/mod_post_msg.html)

### Requires:
1. A [Prosody](https://prosody.im/) XMPP server
2. [mod_post_msg](https://modules.prosody.im/mod_post_msg.html) installed and configured on the prosody server
3. php-curl installed on the ttrss host.

### Installing the plugin:
1. Clone this repo or just grab the [latest release](https://github.com/joshp23/ttrss-notify-xmpp-prosody/releases/latest) and extract the notify_xmpp_prosody folder into the `plugins.local` folder of ttrss.
2. Install PHP Curl
	```
	sudo apt-get install php-curl
	```
3. Enable and configure in Preferences. 
4. Create a filter for the articles you want notifications from, and have the filter invoke this plugin.
5. Enjoy!

### Tips
Dogecoin: DARhgg9q3HAWYZuN95DKnFonADrSWUimy3
