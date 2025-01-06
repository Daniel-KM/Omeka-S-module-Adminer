<?php declare(strict_types=1);
/**
 * @see https://www.adminer.org/en/extension
 * @see https://docs.adminerevo.org/#to-use-a-plugin
 */
class AdminerOmeka
{
    /**
     * Custom name in title and heading.
     *
     * @todo A long title does not work with some designs.
     */
    /*
    public function name() {
        // TODO Translate title "Adminer for Omeka.'
        return 'Adminer for Omeka';
    }
     */

    /**
     * Key used for permanent login.
     * @todo To be unique but stable. See controller.
     */
    public function permanentLogin($create = false)
    {
        $authData = $this->getAuthData();
        return $authData['adminer_key'] ?? null;
    }

    /**
     * Server, username and password for connecting to database.
     */
    public function credentials()
    {
        $authData = $this->getAuthData();
        if (empty($authData['server'])) {
            return null;
        }
        return [
            $authData['server'] ?? null,
            $authData['username'] ?? null,
            $authData['password'] ?? null,
        ];
    }

    /**
     * Database name, will be escaped by Adminer.
     */
    public function database()
    {
        $authData = $this->getAuthData();
        return $authData['db'] ?? null;
    }

    /**
     * Validate user submitted credentials.
     */
    public function login($login, $password)
    {
        $authData = $this->getAuthData();
        return !empty($authData['username'])
            && !empty($authData['password'])
            && $login === $authData['username']
            && $password === $authData['password'];
    }

    public function css()
    {
        $return = [];
        if (array_key_exists($_SESSION['design'], listDesigns())) {
            $return[] = $_SESSION['design'];
            return $return;
        }

        $filename = dirname(__DIR__, 4) . '/asset/vendor/adminer/adminer.css';
        if (file_exists($filename)) {
            // Relative to the Omeka admin route.
            $return[] = '../modules/Adminer/asset/vendor/adminer/adminer.css?v=' . crc32(file_get_contents($filename));
        }
        return $return;
    }

    protected function getAuthData()
    {
        global $adminerAuthData;
        return $adminerAuthData;
    }
}
