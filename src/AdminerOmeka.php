<?php declare(strict_types=1);

/**
 * Manage auto-login, access to current database and default css.
 *
 * @see https://www.adminer.org/en/extension
 * @see https://docs.adminerevo.org/#to-use-a-plugin
 */
class AdminerOmeka
{
    /**
     * @var array
     */
    protected $designs;

    public function __construct(array $designs)
    {
        $this->designs = $designs;
    }

    /**
     * Custom name in title and heading.
     *
     * @return string HTML code
     */
    public function name(): string
    {
        $authData = $this->getAuthData();
        $title = $authData['installation_title'] ?? '';
        if ($title !== '') {
            return '<span id="h1">' . htmlspecialchars($title) . '</span>';
        }
        return '<span id="h1">Omeka S</span>';
    }

    /**
     * Key used for permanent login.
     * @todo To be unique but stable. See controller.
     */
    public function permanentLogin(bool $create = false): string
    {
        $authData = $this->getAuthData();
        return $authData['adminer_key'] ?? '';
    }

    /**
     * Server, username and password for connecting to database.
     * @return array{string, string, string}
     */
    public function credentials(): array
    {
        $authData = $this->getAuthData();
        return [
            $authData['server'] ?? '',
            $authData['username'] ?? '',
            $authData['password'] ?? '',
        ];
    }

    /**
     * Database name, will be escaped by Adminer.
     */
    public function database(): ?string
    {
        $authData = $this->getAuthData();
        return $authData['db'] ?? null;
    }

    /**
     * Show only current database in the interface (don't improve security).
     *
     * @see \AdminerDatabaseHide
     */
    public function databases(bool $flush = true): array
    {
        $authData = $this->getAuthData();
        return empty($authData['db']) ? [] : [$authData['db']];
    }

    /**
     * SSL connection options from database.ini driverOptions.
     *
     * @return array|null ["key" => filename, "cert" => filename, "ca" => filename, "verify" => bool]
     */
    public function connectSsl()
    {
        $authData = $this->getAuthData();
        $ssl = $authData['ssl'] ?? [];
        return $ssl ?: null;
    }

    /**
     * Validate user submitted credentials.
     */
    public function login(string $login, string $password)
    {
        $authData = $this->getAuthData();
        return !empty($authData['username'])
            && !empty($authData['password'])
            && $login === $authData['username']
            && $password === $authData['password'];
    }

    /**
     * Get URLs of the CSS files.
     * @return array key is URL, value is 'light', 'dark' or '' (both)
     */
    public function css(): array
    {
        $return = [];
        if (array_key_exists($_SESSION['design'], $this->designs)) {
            $return[$_SESSION['design']] = '';
            return $return;
        }

        $filename = dirname(__DIR__, 4) . '/asset/vendor/adminer/adminer.css';
        if (file_exists($filename)) {
            // Relative to the Omeka admin route.
            $file = file_get_contents($filename);
            $url = '../modules/Adminer/asset/vendor/adminer/adminer.css?v=' . crc32($file);
            $return[$url] = preg_match('~prefers-color-scheme:\s*dark~', $file) ? '' : 'light';
        }

        return $return;
    }

    /**
     * Print HTML into the <head>.
     *
     * @return null to let other plugins chain their head()
     */
    public function head(?bool $dark = null)
    {
        return null;
    }

    protected function getAuthData()
    {
        global $adminerAuthData;
        return $adminerAuthData;
    }
}
