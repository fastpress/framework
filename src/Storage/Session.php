<?php declare(strict_types=1);
/**
 * HTTP session object
 *
 * PHP version 7.0
 *
 * @category   fastpress
 *
 * @author     https://github.com/samayo
 * @copyright  Copyright (c) samayo
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @version    0.1.0
 */
namespace Fastpress\Storage;
/**
 * HTTP session object
 *
 * @category   fastpress
 *
 * @author     https://github.com/samayo
 */
class Session implements \ArrayAccess
{
    public function __construct(array $conf)
    {
        if (!headers_sent() && !session_id()) {
            session_start();
        }
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function setFlash($identifier, $message)
    {
        $_SESSION[$identifier] = $message;
    }

    public function getFlash($identifier)
    {
        if (array_key_exists($identifier, $_SESSION)) {
            $keep = $_SESSION[$identifier];
            $this->delete($identifier);

            return $keep;
        }
    }

    public function get($key)
    {
        if ($this->has($key)) {
            return $_SESSION[$key];
        }
    }

    public function has($key)
    {
        return array_key_exists($key, $_SESSION);
    }

    public function delete($key)
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }

    public function destroy()
    {
        session_destroy();
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }
}
