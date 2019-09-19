<?php declare(strict_types=1);
/**
 * HTTP request object.
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

namespace Fastpress\Http;

/**
 * HTTP request object.
 *
 * @category   fastpress
 *
 * @author     https://github.com/samayo
 */
class Request implements \ArrayAccess
{
    protected $get = [];
    protected $post = [];
    protected $server = [];
    protected $cookies = [];

    public function __construct(array $_get, array $_post, array $_server, array $_cookies)
    {
        $this->get = $_get;
        $this->post = $_post;
        $this->server = $_server;
        $this->cookies = $_cookies;
    }

    /**
     * Checks if request method is type GET.
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Checks if request method type POST.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Checks if request method is type PUT.
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * Checks if request methos is type DELETE.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Fetch value from GET array.
     *
     * @param string $var
     * @param [type] $filter
     */
    public function get(string $var, $filter = null)
    {
        return $this->filter($this->get, $var, $filter);
    }

    /**
     * Fetch value from POST array.
     *
     * @param string $var
     * @param [type] $filter
     */
    public function post(string $var, $filter = null)
    {
        return $this->filter($this->post, $var, $filter);
    }

    /**
     * fetch value from SERVER array.
     *
     * @param string $var
     * @param [type] $filter
     */
    public function server(string $var, $filter = null)
    {
        return $this->filter($this->server, $var, $filter);
    }

    /**
     * GET the current URI.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->filter($this->server, 'REQUEST_URI');
    }

    /**
     * GET http referer.
     *
     * @return string
     */
    public function getReferer(): string
    {
        return $this->filter($this->server, 'HTTP_REFERER');
    }

    /**
     * GET request method type.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->filter($this->server, 'REQUEST_METHOD');
    }

    /**
     * Check if connection is secure.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return array_key_exists('HTTPS', $this->server)
        && $this->server['HTTPS'] !== 'off'
      ;
    }

    /**
     * Check if connection is made with XMLHttpRequest.
     *
     * @return bool
     */
    public function isXhr(): bool
    {
        return $this->filter($this->server, 'HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * A util function to filter values via filter_var().
     *
     * @param array  $input
     * @param string $var
     * @param [type] $filter
     */
    protected function filter(array $input, string $var, $filter = null)
    {
        $value = $input[$var] ?? false;

        if (!$filter) {
            return $value;
        }

        return filter_var($value, $filter);
    }

    /**
     * Returns superglobal arrays.
     *
     * @return array
     */
    public function requestGlobals(): array
    {
        return [
          'get' => $this->get,
          'post' => $this->post,
          'server' => $this->server,
        ];
    }

    /**
     * Builds a URL.
     */
    public function build_url()
    {
        return  parse_url(
          $this->server('REQUEST_SCHEME').'://'.
          $this->server('SERVER_NAME').
          $this->server('REQUEST_URI')
        );
    }

    /**
     * Call class methods from array context.
     *
     * @param [type] $offset
     */
    public function offsetGet($offset)
    {
        if (in_array($offset, ['isGet', 'isPut', 'isPost', 'isDelete', 'isXhr', 'isSecure'])) {
            return $this->$offset();
        }
    }

    /**
     * Set values.
     *
     * @param [type] $offset
     * @param [type] $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Undocumented function.
     *
     * @param [type] $offset
     */
    public function offsetExists($offset)
    {
    }

    /**
     * Undocumented function.
     *
     * @param [type] $offset
     */
    public function offsetUnset($offset)
    {
    }
}
