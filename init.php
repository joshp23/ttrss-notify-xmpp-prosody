<?php
class notify_xmpp_prosody extends Plugin {
	private $host;
	function about() {
		return array("1.0.1",
			"Notifications to an XMPP Prosody server",
			"joshu@unfettered.net",
			"https://github.com/joshp23/ttrss-notify-xmpp-prosody");
	}

	function init($host) {
		$this->host = $host;
		$host->add_hook($host::HOOK_PREFS_TAB, $this);
		$host->add_hook($host::HOOK_ARTICLE_FILTER_ACTION, $this);
		$host->add_filter_action($this, "xmpp_send_prosody", "Send XMPP message to Prosody");
	}

	function save() {
		$test_xmpp = true;
		$cfg = array(
			'xmpp_tag'		=> $_POST['xmpp_tag'],
			'xmpp_url'		=> $_POST['xmpp_url'],
			'xmpp_user'		=> $_POST['xmpp_user'],
			'xmpp_pass'		=> $_POST['xmpp_pass'],
			'xmpp_notify'	=> $_POST['xmpp_notify']
		);
		
		foreach ($cfg as $k => $v) {
			$this->host->set($this, $k, $v);
			if (empty($v)) $test_xmpp = false;
		}

		if ($test_xmpp) {
			$this->_send($cfg, 'Congrats, your settings work. Now you are ready to receive notifications.');
			echo "Settings saved, test message sent.";
		} else {
			echo "Incomplete settings saved.";
		}
	}

	function hook_prefs_tab($args) {
		if ($args != "prefPrefs") return;
		
		$cfg = $this->_config(false);
		if (empty($cfg['xmpp_tag'])) $cfg['xmpp_tag'] = 'notify_xmpp_prosody';
		
		?>
		
		 <div dojoType='dijit.layout.AccordionPane' 
					title=" <i class='material-icons'>chat</i><?= __("XMPP: Prosody Notification Settings") ?>" >
		
		    <p>After setting up your account information, you may invoke this plugin in your filter rules in order to receive notifications. The tag will be automatically assigned after sending and should be unique.</p>
		
		    <form dojoType="dijit.form.Form">
		        <?= \Controls\pluginhandler_tags($this, "save") ?>
		        <script type="dojo/method" event="onSubmit" args="evt">
			        evt.preventDefault();
			        if (this.validate()) {
			            Notify.progress('Saving Notify: XMPP configuration...', true);
						    xhr.post("backend.php", this.getValues(), (reply) => {
							    Notify.info(reply);
						    })
			        }
		        </script>
		        This plugin requires a <strong>separate XMPP account</strong> for sending notifications. Using the same account for sending and receiving might cause conflicts.
		        <table width="100%" class="prefPrefsList">
		        <tr>
		            <td width="40%"><?= __("Article Tag") ?></td>
					<td class="prefValue"><input dojoType="dijit.form.ValidationTextBox" required="1" name="xmpp_tag"  type="text" value="<?= $cfg['xmpp_tag'] ?>" placeholder="notify_xmpp_prosody"></td>
		        </tr>
		        <tr>
		            <td width="40%"><?= __("Prosody Url") ?></td>
					<td class="prefValue"><input dojoType="dijit.form.ValidationTextBox" required="1" name="xmpp_url" regExp='^(http|https)://.*' value="<?= $cfg['xmpp_url'] ?>" placeholder="example.org" ></td>
		        </tr>
		        <tr>
		            <td width="40%"><?= __("XMPP Username") ?></td>
					<td class="prefValue"><input dojoType="dijit.form.ValidationTextBox" required="1" name="xmpp_user" type="text" value="<?= $cfg['xmpp_user'] ?>" placeholder="username" ></td>
		        </tr>
		        <tr>
		            <td width="40%"><?= __("XMPP Password") ?></td>
					<td class="prefValue"><input dojoType="dijit.form.ValidationTextBox" required="1" name="xmpp_pass" type="text" value="<?= $cfg['xmpp_pass'] ?>" placeholder="password" ></td>
		        </tr>
		        <tr>
		            <td width="40%"><?= __("Recipient") ?></td>
					<td class="prefValue"><input dojoType="dijit.form.ValidationTextBox" required="1" name="xmpp_notify" type="text" value="<?= $cfg['xmpp_notify'] ?>" placeholder="you@example.org" ></td>
		        </tr>
	            </table>
		        <?= \Controls\submit_tag(__("Save")) ?>
	        </form>
		</div>
		
		<?php
	}

	function hook_article_filter_action($article, $action) {
		if ($action == 'xmpp_send_prosody') {
			$cfg = $this->_config();
			$tags = (is_array($article["tags"])) ? array_flip($article["tags"]) : array();
			if (is_array($cfg) && !isset($tags[$cfg['xmpp_tag']])) {
				$msg = array();
				if (!empty($article["title"]) || !empty($article["author"])) {
					$line = trim(html_entity_decode(strip_tags($article["title"])));
					if (!empty($article["author"])) {
						if (!empty($line)) $line .= "\n";
						$line .= trim(html_entity_decode(strip_tags($article["author"])));
					}
					$msg[] = $line;
				}
				if (!empty($article["link"])) $msg[] = $article["link"];
				if (!empty($article["content"])) {
					$text = html_entity_decode(strip_tags($article["content"]));
					if (strlen($text) > 512) $text = substr($text, 0, 512).'...';
					$msg[] = trim($text);
				}
				$this->_send($this->_config(), trim(implode("\n\n", $msg)));
				$tags = array_keys($tags);
				$tags[] = $cfg['xmpp_tag'];
				$article["tags"] = $tags;
			}
		}
		return $article;
	}

	function api_version() {
		return 2;
	}
	
	private function _send($cfg, $msg) {
		if (!is_array($cfg) || empty($msg)) return;
		$url = $cfg['xmpp_url'] .'/'. $cfg['xmpp_notify'];
		$headers = array(
						'Content-Type:text/plain',
						'Authorization: Basic '. base64_encode($cfg['xmpp_user'].':'.$cfg['xmpp_pass']) // <---
					);
		if (function_exists('curl_init')) {
			$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, FALSE);
			$return = curl_exec($ch);
				curl_close($ch);
		}
	}

	private function _config($check = true) {
		$cfg = array(
			'xmpp_tag'		=> $this->host->get($this, 'xmpp_tag'),
			'xmpp_url'		=> $this->host->get($this, 'xmpp_url'),
			'xmpp_user'		=> $this->host->get($this, 'xmpp_user'),
			'xmpp_pass'		=> $this->host->get($this, 'xmpp_pass'),
			'xmpp_notify'	=> $this->host->get($this, 'xmpp_notify')
		);

		if ($check) foreach ($cfg as $k => $v) {
			if (empty($v)) return NULL;
		}
		return $cfg;
	}
}
?>
