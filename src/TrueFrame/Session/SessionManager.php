<?php

namespace TrueFrame\Session;

class SessionManager
{
    /**
     * The flash data key.
     */
    protected const FLASH_KEY = '_flash';

    /**
     * The old input key.
     */
    protected const OLD_INPUT_KEY = '_old_input';

    /**
     * Start the session.
     *
     * @return void
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Get an item from the session.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Put an item in the session.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Check if an item exists in the session.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove an item from the session.
     *
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Flash a key-value pair to the session.
     * This data will only be available for the next request.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function flash(string $key, mixed $value): void
    {
        $flash = $this->get(self::FLASH_KEY, []);
        $flash[$key] = $value;
        $this->put(self::FLASH_KEY, $flash);
    }

    /**
     * Get a flashed item from the session.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        return $this->get(self::FLASH_KEY, [])[$key] ?? $default;
    }

    /**
     * Remove all flash data from the session.
     *
     * @return void
     */
    public function clearFlash(): void
    {
        $this->forget(self::FLASH_KEY);
    }

    /**
     * Store input for the next request.
     *
     * @param array $input
     * @return void
     */
    public function flashInput(array $input): void
    {
        $this->put(self::OLD_INPUT_KEY, $input);
    }

    /**
     * Get old input from the session.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getOldInput(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->get(self::OLD_INPUT_KEY, []);
        }
        return $this->get(self::OLD_INPUT_KEY, [])[$key] ?? $default;
    }

    /**
     * Remove old input from the session.
     *
     * @return void
     */
    public function clearOldInput(): void
    {
        $this->forget(self::OLD_INPUT_KEY);
    }

    /**
     * Regenerate the session ID.
     *
     * @param bool $destroy
     * @return bool
     */
    public function regenerate(bool $destroy = false): bool
    {
        return session_regenerate_id($destroy);
    }

    /**
     * Destroy the session.
     *
     * @return void
     */
    public function destroy(): void
    {
        session_destroy();
        $_SESSION = [];
    }
}