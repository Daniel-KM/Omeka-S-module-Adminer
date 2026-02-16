<?php

/** Hide connection parameters (server, username, db) from browser urls
 * Useful when Adminer is embedded and always connects to a fixed database.
 * The host application must inject the parameters into $_GET before Adminer
 * loads so that internal routing still works.
 *
 * @link https://www.adminer.org/plugins/#use
 * @author Daniel Berthereau
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
 */
class AdminerCleanUrls extends Adminer\Plugin {
	protected $extraParams;

	/**
	 * @param list<string> $extraParams additional URL parameters to strip besides server, username and db
	 */
	function __construct(array $extraParams = array()) {
		$this->extraParams = $extraParams;
	}

	function headers() {
		$driver = Adminer\DRIVER;
		$server = urlencode(Adminer\SERVER);
		$username = urlencode(isset($_GET["username"]) ? $_GET["username"] : '');
		$db = urlencode(Adminer\DB);
		// Strip connection params from all HTML output (links, forms, etc.).
		// Both HTML-entity-encoded (&amp;) and raw (&) variants are handled.
		$search = array(
			"$driver=$server&amp;username=$username&amp;db=$db&amp;" => '',
			"$driver=$server&amp;username=$username&amp;db=$db" => '',
			"$driver=$server&username=$username&db=$db&" => '',
			"$driver=$server&username=$username&db=$db" => '',
		);
		ob_start(function ($output) use ($search) { return strtr($output, $search); });
	}

	function head() {
		$params = json_encode(array_merge(array(Adminer\DRIVER, 'username', 'db'), $this->extraParams));
		// Clean the URL bar after Adminer HTTP redirects (not caught by output buffering).
		echo "<script>if(history.replaceState){var u=new URL(location.href);$params.forEach(function(p){u.searchParams.delete(p)});u.href!==location.href&&history.replaceState(null,'',u)}</script>\n";
	}

	protected $translations = array(
		'cs' => array('' => 'Skryje přihlašovací údaje z adresního řádku prohlížeče'),
		'de' => array('' => 'Verbindungsparameter aus der Browser-Adressleiste ausblenden'),
		'fr' => array('' => 'Masquer les paramètres de connexion dans la barre d’adresse du navigateur'),
		'ja' => array('' => '接続パラメータをブラウザのアドレスバーから非表示'),
	);
}
