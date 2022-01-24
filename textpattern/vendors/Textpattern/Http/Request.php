<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Inspects the current HTTP request.
 *
 * Handles content negotiations and extracting data from headers safely on
 * different web servers.
 *
 * @since   4.6.0
 * @package HTTP
 */

namespace Textpattern\Http;

class Request
{
    /**
     * Protocol-port map.
     *
     * @var array
     */

    protected $protocolMap = array(
        'http'  => 80,
        'https' => 443,
    );

    /**
     * Stores headers.
     *
     * @var array
     */

    protected $headers;

    /**
     * Stores referer.
     *
     * @var string
     */

    protected $referer;

    /**
     * Content types accepted by the client.
     *
     * @var array
     */

    protected $acceptedTypes;

    /**
     * Formats mapping.
     *
     * @var array
     */

    protected $acceptsFormats = array(
        'html' => array('text/html', 'application/xhtml+xml', '*/*'),
        'txt'  => array('text/plain', '*/*'),
        'js'   => array('application/javascript', 'application/x-javascript', 'text/javascript', 'application/ecmascript', 'application/x-ecmascript', '*/*'),
        'css'  => array('text/css', '*/*'),
        'json' => array('application/json', 'application/x-json', '*/*'),
        'xml'  => array('text/xml', 'application/xml', 'application/x-xml', '*/*'),
        'rdf'  => array('application/rdf+xml', '*/*'),
        'atom' => array('application/atom+xml', '*/*'),
        'rss'  => array('application/rss+xml', '*/*'),
    );

    /**
     * Raw request data.
     *
     * Wraps around PHP's $_SERVER variable.
     *
     * @var \Textpattern\Server\Config
     */

    protected $request;

    /**
     * Resolved hostnames.
     *
     * @var array
     */

    protected $hostNames = array();

    /**
     * Constructor.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request', new Abc_Custom_Request_Data)->getHostName();
     * </code>
     *
     * @param \Textpattern\Server\Config|null $request The raw request data, defaults to the current request body
     */

    public function __construct(\Textpattern\Server\Config $request = null)
    {
        if ($request === null) {
            $this->request = \Txp::get('\Textpattern\Server\Config');
        } else {
            $this->request = $request;
        }
    }

    /**
     * Checks whether the client accepts a certain response format.
     *
     * By default discards formats with quality factors below an arbitrary
     * threshold as jQuery adds a wildcard content-type with quality of '0.01'
     * to the 'Accept' header for XHR requests.
     *
     * Supplied format of 'html', 'txt', 'js', 'css', 'json', 'xml', 'rdf',
     * 'atom' or 'rss' is autocompleted and matched against multiple valid MIMEs.
     *
     * Both of the following will return MIME for JSON if 'json' format is
     * supported:
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getAcceptedType('json');
     * echo Txp::get('\Textpattern\Http\Request')->getAcceptedType('application/json');
     * </code>
     *
     * The method can also be used to check an array of types:
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getAcceptedType(array('application/xml', 'application/x-xml'));
     * </code>
     *
     * Stops on first accepted format.
     *
     * @param  string|array $formats   Format to check
     * @param  float        $threshold Quality threshold
     * @return string|bool Supported type, or FALSE if not
     */

    public function getAcceptedType($formats, $threshold = 0.1)
    {
        if ($this->acceptedTypes === null) {
            $this->acceptedTypes = $this->getAcceptsMap($this->request->getVariable('HTTP_ACCEPT'));
        }

        foreach ((array) $formats as $format) {
            if (isset($this->acceptsFormats[$format])) {
                $format = $this->acceptsFormats[$format];
            }

            foreach ((array) $format as $type) {
                if (isset($this->acceptedTypes[$type]) && $this->acceptedTypes[$type]['q'] >= $threshold) {
                    return $type;
                }
            }
        }

        return false;
    }

    /**
     * Gets accepted language.
     *
     * If $languages is NULL, returns client's favoured language. If
     * string, checks whether the language is supported and
     * if an array, returns the language that the client favours the most.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getAcceptedLanguage('fi-FI');
     * </code>
     *
     * The above will return 'fi-FI' as long as the Accept-Language header
     * contains an identifier that matches Finnish, such as 'fi-fi', 'fi-Fi'
     * or 'fi'.
     *
     * @param  string|array $languages Languages to check
     * @param  float        $threshold Quality threshold
     * @return string|bool Accepted language, or FALSE
     */

    public function getAcceptedLanguage($languages = null, $threshold = 0.1)
    {
        $accepts = $this->getAcceptsMap($this->request->getVariable('HTTP_ACCEPT_LANGUAGE'));

        if ($languages === null) {
            $accepts = array_keys($accepts);

            return array_shift($accepts);
        }

        $top = 0;
        $acceptedLanguage = false;

        foreach ((array) $languages as $language) {
            $search = array($language);

            if ($identifiers = \Txp::get('\Textpattern\L10n\Locale')->getLocaleIdentifiers($language)) {
                $search = array_map('strtolower', array_merge($search, $identifiers));
            }

            foreach ($accepts as $accept => $params) {
                if (in_array(strtolower($accept), $search, true) && $params['q'] >= $threshold && $params['q'] >= $top) {
                    $top = $quality; // FIXME: $quality is made out of thin air.
                    $acceptedLanguage = $language;
                }
            }
        }

        return $acceptedLanguage;
    }

    /**
     * Gets accepted encoding.
     *
     * Negotiates a common encoding between the client and the server.
     *
     * <code>
     * if (Txp::get('\Textpattern\Http\Request')->getAcceptedEncoding('gzip')) {
     *     echo 'Client accepts gzip.';
     * }
     * </code>
     *
     * @param  string|array $encodings Encoding
     * @param  float        $threshold Quality threshold
     * @return string|bool Encoding method, or FALSE
     */

    public function getAcceptedEncoding($encodings = null, $threshold = 0.1)
    {
        $accepts = $this->getAcceptsMap($this->request->getVariable('HTTP_ACCEPT_ENCODING'));

        if ($encodings === null) {
            $accepts = array_keys($accepts);

            return array_shift($accepts);
        }

        foreach ((array) $encodings as $encoding) {
            if (isset($accepts[$encoding]) && $accepts[$encoding]['q'] >= $threshold) {
                return $encoding;
            }
        }

        return false;
    }

    /**
     * Gets an absolute URL pointing to the requested document.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getUrl();
     * </code>
     *
     * The above will return URL pointing to the requested
     * page, e.g. http://example.test/path/to/subpage.
     *
     * @return string The URL
     */

    public function getUrl()
    {
        $port = '';

        if (($portNumber = $this->getPort()) !== false && strpos($this->getHost(), ':') === false) {
            $port = ':'.$portNumber;
        }

        return $this->getProtocol().'://'.$this->getHost().$port.$this->getUri();
    }

    /**
     * Gets the server hostname.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getHost();
     * </code>
     *
     * Returns 'example.com' if requesting
     * http://example.test/path/to/subpage.
     *
     * @return string The host
     */

    public function getHost()
    {
        return (string) $this->request->getVariable('HTTP_HOST');
    }

    /**
     * Gets the port, if not default.
     *
     * This method returns FALSE, if the port is the request protocol's default.
     * Neither '80' or 443 for HTTPS are returned.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getPort();
     * </code>
     *
     * Returns '8080' if requesting http://example.test:8080/path/to/subpage.
     *
     * @return int|bool Port number, or FALSE
     */

    public function getPort()
    {
        $port = (int) $this->request->getVariable('SERVER_PORT');
        $protocol = $this->getProtocol();

        if ($port && (!isset($this->protocolMap[$protocol]) || $port !== $this->protocolMap[$protocol])) {
            return $port;
        }

        return false;
    }

    /**
     * Gets the client IP address.
     *
     * This method supports proxies and uses 'X_FORWARDED_FOR' HTTP header if
     * deemed necessary.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getIp();
     * </code>
     *
     * Returns the IP address the request came from, e.g. '0.0.0.0'.
     * Can be either IPv6 or IPv4 depending on the request.
     *
     * @return string The IP address
     */

    public function getIp()
    {
        $ip = $this->request->getVariable('REMOTE_ADDR');
        $proxy = $this->getHeader('X-Forwarded-For');

        if ($proxy && ($ip === '127.0.0.1' || $ip === '::1' || $ip === '::ffff:127.0.0.1' || $ip === $this->request->getVariable('SERVER_ADDR'))) {
            $ips = explode(',', $proxy);
            $ip = trim($ips[0]);
        }

        return $ip;
    }

    /**
     * Gets client hostname.
     *
     * This method resolves client's hostname.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getRemoteHostname();
     * </code>
     *
     * @return string|bool The hostname, or FALSE on failure
     */

    public function getRemoteHostname()
    {
        $ip = $this->getIp();

        if (isset($this->hostNames[$ip])) {
            return $this->hostNames[$ip];
        }

        if ($host = @gethostbyaddr($ip)) {
            if ($host !== $ip && @gethostbyname($host) !== $ip) {
                $host = $ip;
            }

            return $this->hostNames[$ip] = $host;
        }

        return false;
    }

    /**
     * Gets the request protocol.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getProtocol();
     * </code>
     *
     * Returns 'https' if requesting https://example.test:8080/path/to/subpage.
     *
     * @return string Either 'http' or 'https'
     */

    public function getProtocol()
    {
        if (($https = $this->request->getVariable('HTTPS')) && $https !== 'off') {
            return 'https';
        }

        if (($https = $this->getHeader('Front-End-Https')) && strtolower($https) === 'on') {
            return 'https';
        }

        if (($https = $this->getHeader('X-Forwarded-Proto')) && strtolower($https) === 'https') {
            return 'https';
        }

        return 'http';
    }

    /**
     * Gets referer.
     *
     * Returns referer header if it does not originate from the current
     * hostname or come from a HTTPS page to a HTTP page.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getReferer();
     * </code>
     *
     * Returns full URL such as 'http://example.com/referring/page.php?id=12'.
     *
     * @return string|bool Referer, or FALSE if not available
     */

    public function getReferer()
    {
        if ($this->referer === null) {
            $protocol = $this->referer = false;

            if ($referer = $this->request->getVariable('HTTP_REFERER')) {
                if (strpos($referer, '://')) {
                    $referer = explode('://', $referer);
                    $protocol = array_shift($referer);
                    $referer = join('://', $referer);
                }

                if (!$protocol || ($protocol === 'https' && $this->getProtocol() !== 'https://')) {
                    return false;
                }

                if (preg_match('/^[^\.]*\.?'.preg_quote(preg_replace('/^www\./', '', $this->getHost()), '/').'/i', $referer)) {
                    return false;
                }

                $this->referer = $protocol.'://'.$referer;
            }
        }

        return $this->referer;
    }

    /**
     * Gets requested URI.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getUri();
     * </code>
     *
     * Returns '/some/requested/page?and=query' if requesting
     * http://example.com/some/requested/page?and=query.
     *
     * @return string The URI
     */

    public function getUri()
    {
        return (string) $this->request->getVariable('REQUEST_URI');
    }

    /**
     * Gets an array map of raw request headers.
     *
     * This method is web server agnostic.
     *
     * The following:
     *
     * <code>
     * print_r(Txp::get('\Textpattern\Http\Request')->getHeaders());
     * </code>
     *
     * Returns:
     *
     * <code>
     * Array
     * (
     *     [Host] => example.test
     *     [Connection] => keep-alive
     *     [Cache-Control] => max-age=0
     *     [User-Agent] => User-Agent
     *     [Referer] => http://example.test/textpattern/index.php
     *     [Accept-Encoding] => gzip,deflate,sdch
     *     [Accept-Language] => en-US,en;q=0.8,fi;q=0.6
     *     [Cookie] => toggle_show_spam=1
     * )
     * </code>
     *
     * @return array An array of HTTP request headers
     */

    public function getHeaders()
    {
        if ($this->headers !== null) {
            return $this->headers;
        }

        $this->headers = array();

        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0 && is_scalar($value)) {
                $parts = explode('_', $name);
                array_shift($parts);

                foreach ($parts as &$part) {
                    $part = ucfirst(strtolower($part));
                }

                $this->headers[join('-', $parts)] = (string) $value;
            }
        }

        return $this->headers;
    }

    /**
     * Gets a raw HTTP request header value.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getHeader('User-Agent');
     * </code>
     *
     * Will return the client's User-Agent header, if it has any. If the client
     * didn't send User-Agent, the method returns FALSE.
     *
     * @param  string $name The header name
     * @return string|bool The header value, or FALSE on failure
     */

    public function getHeader($name)
    {
        if ($headers = $this->getHeaders()) {
            if (isset($headers[$name])) {
                return $headers[$name];
            }
        }

        return false;
    }

    /**
     * Gets an array of HTTP cookies.
     *
     * <code>
     * print_r(Txp::get('\Textpattern\Http\Request')->getHeaders());
     * </code>
     *
     * Returns:
     *
     * <code>
     * Array(
     *     [foobar] => value
     * )
     * </code>
     *
     * Returned cookie values are processed properly for you, and will not
     * contain runtime quoting slashes or be URL encoded. Just pick and choose.
     *
     * @return array An array of cookies
     */

    public function getCookies()
    {
        $out = array();

        if ($_COOKIE) {
            foreach ($_COOKIE as $name => $value) {
                $out[$name] = $this->getCookie($name);
            }
        }

        return $out;
    }

    /**
     * Gets a HTTP cookie.
     *
     * <code>
     * echo Txp::get('\Textpattern\Http\Request')->getCookie('foobar');
     * </code>
     *
     * @param  string $name The cookie name
     * @return string The value
     */

    public function getCookie($name)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }

        return '';
    }

    /**
     * Gets a query string.
     *
     * <code>
     * print_r(Txp::get('\Textpattern\Http\Request')->getQuery());
     * </code>
     *
     * If requesting "?event=article&amp;step=save", the above returns:
     *
     * <code>
     * Array
     * (
     *     [event] => article
     *     [step] => save
     * )
     * </code>
     *
     * @return array An array of parameters
     */

    public function getQuery()
    {
        $out = array();

        if ($_GET) {
            foreach ($_GET as $name => $value) {
                $out[$name] = $this->getParam($name);
            }
        }

        if ($_POST) {
            foreach ($_POST as $name => $value) {
                $out[$name] = $this->getPost($name);
            }
        }

        return $out;
    }

    /**
     * Gets a HTTP query string parameter.
     *
     * @param  $name The parameter name
     * @return mixed
     */

    public function getParam($name)
    {
        if (isset($_GET[$name])) {
            $out = $_GET[$name];
            $out = doArray($out, 'deCRLF');

            return doArray($out, 'deNull');
        }

        return $this->getPost($name);
    }

    /**
     * Gets a HTTP post parameter.
     *
     * @param  string $name The parameter name
     * @return mixed
     */

    public function getPost($name)
    {
        $out = '';

        if (isset($_POST[$name])) {
            $out = $_POST[$name];
        }

        return doArray($out, 'deNull');
    }

    /**
     * Builds a content-negotiation accepts map from the given value.
     *
     * Keys are the accepted type and the value are the params. If client
     * doesn't specify quality, defaults to 1.0. Values are sorted by the
     * quality, from the highest to the lowest.
     *
     * This method can be used to parse Accept, Accept-Charset, Accept-Encoding
     * and Accept-Language header values.
     *
     * <code>
     * print_r(Txp::get('\Textpattern\Http\Request')->getAcceptsMap('en-us;q=1.0,en;q=0.9'));
     * </code>
     *
     * Returns:
     *
     * <code>
     * Array
     * (
     *     [en-us] => Array
     *     (
     *         [q] => 1.0
     *     )
     *     [en] => Array
     *     (
     *         [q] => 0.9
     *     )
     * )
     * </code>
     *
     * @param  string $header The header string
     * @return array Accepts map
     */

    public function getAcceptsMap($header)
    {
        $types = explode(',', $header);
        $accepts = array();
        $sort = array();

        foreach ($types as $type) {
            if ($type = trim($type)) {
                if ($parts = explode(';', $type)) {
                    $type = array_shift($parts);

                    $params = array(
                        'q' => 1.0,
                    );

                    foreach ($parts as $value) {
                        if (strpos($value, '=') === false) {
                            $params[$value] = true;
                        } else {
                            $value = explode('=', $value);
                            $params[array_shift($value)] = join('=', $value);
                        }
                    }

                    $params['q'] = floatval($params['q']);
                    $accepts[$type] = $params;
                    $sort[$type] = $params['q'];
                }
            }
        }

        array_multisort($sort, SORT_DESC, $accepts);

        return $accepts;
    }
}
