<?php 

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2013 The Textpattern Development Team
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
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Inspects the current HTTP request.
 *
 * Handles content negotiations and extracting data
 * from headers safely on different web servers.
 *
 * @since   4.6.0
 * @package HTTP
 */

class Textpattern_Http_Request
{
	/**
	 * Protocol-port map.
	 *
	 * @var array
	 */

	private $protocolMap = array(
		'http'  => 80,
		'https' => 443,
	);

	/**
	 * Stores headers.
	 *
	 * @var array
	 */

	private $headers;

	/**
	 * Magic quotes GCP.
	 *
	 * @var bool
	 */

	private $magicQuotesGpc = false;

	/**
	 * Stores referer.
	 *
	 * @var string
	 */

	private $referer;

	/**
	 * Content types accepted by the client.
	 *
	 * @var array
	 */

	private $acceptedTypes;

	/**
	 * Formats mapping.
	 *
	 * @var array
	 */

	private $acceptsFormats = array(
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
	 * Constructor.
	 */

	public function __construct()
	{
		$this->magicQuotesGpc = Txp::get('ServerVar')->getMagicQuotesGpc();
	}

	/**
	 * Checks whether the client accepts a certain response format.
	 *
	 * By default discards formats with quality factors below an arbitrary threshold
	 * as jQuery adds a wildcard content-type with quality of '0.01' to the 'Accept'
	 * header for XHR requests.
	 *
	 * Supplied format of 'html', 'txt', 'js', 'css', 'json', 'xml', 'rdf', 'atom' or 'rss'
	 * is autocompleted and matched againsts multiple valid MIMEs.
	 *
	 * Both of the following will return MIME for JSON if 'json' format is
	 * supported:
	 *
	 * <code>
	 * echo Txp::get('HttpRequest')->getAcceptedType('json');
	 * echo Txp::get('HttpRequest')->getAcceptedType('application/json');
	 * </code>
	 *
	 * The method can also be used to check an array of types:
	 *
	 * <code>
	 * echo Txp::get('HttpRequest')->getAcceptedType(array('application/xml', 'application/x-xml'));
	 * </code>
	 *
	 * Stops on first accepted format.
	 *
	 * @param  string|array $formats   Format to check
	 * @param  float        $threshold Quality threshold
	 * @return string|bool  Supported type, or FALSE if not
	 */

	public function getAcceptedType($formats, $threshold = 0.1)
	{
		if ($this->acceptedTypes === null)
		{
			$this->acceptedTypes = $this->getAcceptsMap(Txp::get('ServerVar')->HTTP_ACCEPT);
		}

		foreach ((array) $formats as $format)
		{
			if (isset($this->acceptsFormats[$format]))
			{
				$format = $this->acceptsFormats[$format];
			}

			foreach ((array) $format as $type)
			{
				if (isset($this->acceptedTypes[$type]) && $this->acceptedTypes[$type] >= $threshold)
				{
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
	 * echo Txp::get('HttpRequest')->getAcceptedLanguage('fi-FI');
	 * </code>
	 *
	 * The above will return 'fi-FI' as long as the Accept-Language header
	 * contains an indentifier that matches Finnish, such as 'fi-fi', 'fi-Fi'
	 * or 'fi'.
	 *
	 * @param  string|array $languages Languages to check
	 * @param  float        $threshold Quality threshold
	 * @return string|bool  Accepted language, or FALSE
	 */

	public function getAcceptedLanguage($languages = null, $threshold = 0.1)
	{
		$accepts = $this->getAcceptsMap(Txp::get('ServerVar')->HTTP_ACCEPT_LANGUAGE);

		if ($languages === null)
		{
			$accepts = array_keys($accepts);
			return array_shift($accepts);
		}

		$top = 0;
		$acceptedLanguage = false;

		foreach ((array) $languages as $language)
		{
			$search = array($language);

			if ($identifiers = Txp::get('L10nLocale')->getLocaleIdentifiers($language))
			{
				$search = array_map('strtolower', array_merge($search, $identifiers));
			}

			foreach ($accepts as $accept => $quality)
			{
				if (in_array(strtolower($accept), $search, true) && $quality >= $threshold && $quality >= $top)
				{
					$top = $quality;
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
	 * if (Txp::get('HttpRequest')->getAcceptedEncoding('gzip'))
	 * {
	 * 	echo 'Client accepts gzip.';
	 * }
	 * </code>
	 *
	 * @param  string|array $encodings Encoding
	 * @param  float        $threshold Quality threshold
	 * @return string|bool  Encoding method, or FALSE
	 */

	public function getAcceptedEncoding($encodings = null, $threshold = 0.1)
	{
		$accepts = $this->getAcceptsMap(Txp::get('ServerVar')->HTTP_ACCEPT_ENCODING);

		if ($encodings === null)
		{
			$accepts = array_keys($accepts);
			return array_shift($accepts);
		}

		foreach ((array) $encodings as $encoding)
		{
			if (isset($accepts[$encoding]) && $accepts[$encoding] >= $threshold)
			{
				return $encoding;
			}
		}

		return false;
	}

	/**
	 * Gets an absolute URL pointing to the requested document.
	 *
	 * <code>
	 * echo Txp::get('HttpRequest')->getUrl();
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

		if (($portNumber = $this->getPort()) !== false && strpos($this->getHost(), ':') === false)
		{
			$port = ':'.$portNumber;
		}

		return $this->getProtocol().'://'.$this->getHost().$port.$this->getUri();
	}

	/**
	 * Gets the server hostname.
	 *
	 * @return string The host
	 */

	public function getHost()
	{
		return (string) Txp::get('ServerVar')->HTTP_HOST;
	}

	/**
	 * Gets the port, if not default.
	 *
	 * This method returns FALSE, if the port is the request protocol's
	 * default. Neither '80' or 443 for HTTPS are returned.
	 *
	 * @return int|bool Port number, or FALSE
	 */

	public function getPort()
	{
		$port = (int) Txp::get('ServerVar')->SERVER_PORT;
		$protocol = $this->getProtocol();

		if ($port && (!isset($this->protocolMap[$protocol]) || $port !== $this->protocolMap[$protocol]))
		{
			return $port;
		}

		return false;
	}

	/**
	 * Gets the client IP address.
	 *
	 * This method supports proxies and uses 'X_FORWARDED_FOR'
	 * HTTP header if deemed necessary.
	 *
	 * @return string The IP address
	 */

	public function getIp()
	{
		$ip = Txp::get('ServerVar')->REMOTE_ADDR;
		$proxy = $this->getHeader('X-Forwarded-For');

		if ($proxy && ($ip === '127.0.0.1' || $ip === '::1' || $ip === '::ffff:127.0.0.1' || $ip === Txp::get('ServerVar')->SERVER_ADDR))
		{
			$ips = explode(',', $proxy);
			$ip = trim($ips[0]);
		}

		return $ip;
	}

	/**
	 * Gets client hostname.
	 *
	 * This method resolves client's hostname. It uses Textpattern's
	 * visitor logs as a cache layer.
	 *
	 * <code>
	 * echo Txp::get('HttpRequest')->getRemoteHostname();
	 * </code>
	 *
	 * @return string|bool The hostname, or FALSE on failure
	 */

	public function getRemoteHostname()
	{
		$ip = $this->getIp();

		if (($host = safe_field('host', 'txp_log', "ip = '".doSlash($ip)."' limit 1")) !== false)
		{
			return $host;
		}

		if ($host = @gethostbyaddr($ip))
		{
			if ($host !== $ip && @gethostbyname($host) !== $ip)
			{
				return $ip;
			}

			return $host;
		}

		return false;
	}

	/**
	 * Gets the request protocol.
	 *
	 * <code>
	 * echo Txp::get('HttpRequest')->getProtocol();
	 * </code>
	 *
	 * @return string Either 'http' or 'https'
	 */

	public function getProtocol()
	{
		if (($https = Txp::get('ServerVar')->HTTPS) && $https !== 'off')
		{
			return 'https';
		}

		if (($https = $this->getHeader('Front-End-Https')) && strtolower($https) === 'on')
		{
			return 'https';
		}

		if (($https = $this->getHeader('X-Forwarded-Proto')) && strtolower($https) === 'https')
		{
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
	 * echo Txp::get('HttpRequest')->getReferer();
	 * </code>
	 *
	 * @return string|bool Referer, or FALSE if not available
	 */

	public function getReferer()
	{
		if ($this->referer === null)
		{
			$protocol = $this->referer = false;

			if ($referer = Txp::get('ServerVar')->HTTP_REFERER)
			{
				if (strpos($referer, '://'))
				{
					$referer = explode('://', $referer);
					$protocol = array_shift($referer);
					$referer = join('://', $referer);
				}

				if (!$protocol || ($protocol === 'https' && $this->getProtocol() !== 'https://'))
				{
					return false;
				}

				if (preg_match('/^[^\.]*\.?'.preg_quote(preg_replace('/^www\./', '', $this->getHost()), '/').'/i', $referer))
				{
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
	 * @return string The URI
	 */

	public function getUri()
	{
		return (string) Txp::get('ServerVar')->REQUEST_URI;
	}

	/**
	 * Gets an array map of raw request headers.
	 *
	 * This method is web server agnostic.
	 *
	 * The following:
	 *
	 * <code>
	 * print_r(Txp::get('HttpRequest')->getHeaders());
	 * </code>
	 *
	 * Returns:
	 *
	 * <code>
	 * Array
	 * (
	 * 	[Host] => example.test
	 * 	[Connection] => keep-alive
	 * 	[Cache-Control] => max-age=0
	 * 	[User-Agent] => User-Agent
	 * 	[Referer] => http://example.test/textpattern/index.php
	 * 	[Accept-Encoding] => gzip,deflate,sdch
	 * 	[Accept-Language] => en-US,en;q=0.8,fi;q=0.6
	 * 	[Cookie] => toggle_show_spam=1
	 * )
	 * </code>
	 *
	 * @return array An array of HTTP request headers
	 */

	public function getHeaders()
	{
		if ($this->headers !== null)
		{
			return $this->headers;
		}

		if (function_exists('apache_request_headers'))
		{
			if ($this->headers = apache_request_headers())
			{
				return $this->headers;
			}
		}

		$this->headers = array();

		foreach ($_SERVER as $name => $value)
		{
			if (strpos($name, 'HTTP_') === 0 && is_scalar($value))
			{
				$parts = explode('_', $name);
				array_shift($parts);

				foreach ($parts as &$part)
				{
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
	 * echo Txp::get('HttpRequest')->getHeader('User-Agent');
	 * </code>
	 *
	 * @param  string      $name The header name
	 * @return string|bool The header value, or FALSE on failure
	 */

	public function getHeader($name)
	{
		if ($headers = $this->getHeaders())
		{
			if (isset($headers[$name]))
			{
				return $headers[$name];
			}
		}

		return false;
	}

	/**
	 * Gets an array of HTTP cookies.
	 *
	 * @return array
	 */

	public function getCookies()
	{
		$out = array();

		if ($_COOKIE)
		{
			foreach ($_COOKIE as $name => $value)
			{
				$out[$name] = $this->getCookie($name);
			}
		}

		return $out;
	}

	/**
	 * Gets a HTTP cookie.
	 *
	 * @param  string $name The cookie name
	 * @return string
	 */

	public function getCookie($name)
	{
		if (isset($_COOKIE[$name]))
		{
			if ($this->magicQuotesGpc)
			{
				return doStrip($_COOKIE[$name]);
			}

			return $_COOKIE[$name];
		}

		return '';
	}

	/**
	 * Gets a query string.
	 *
	 * <code>
	 * print_r(Txp::get('HttpRequest')->getQuery());
	 * </code>
	 *
	 * If requesting "?event=article&amp;step=save", the above returns:
	 *
	 * <code>
	 * Array
	 * (
	 * 	[event] => article
	 * 	[step] => save
	 * )
	 * </code>
	 *
	 * @return array An array of parameters
	 */

	public function getQuery()
	{
		$out = array();

		if ($_GET)
		{
			foreach ($_GET as $name => $value)
			{
				$out[$name] = $this->getParam($name);
			}
		}

		if ($_POST)
		{
			foreach ($_POST as $name => $value)
			{
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
		if (isset($_GET[$name]))
		{
			$out = $_GET[$name];

			if ($this->magicQuotesGpc)
			{
				$out = doStrip($out);
			}

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

		if (isset($_POST[$name]))
		{
			$out = $_POST[$name];

			if ($this->magicQuotesGpc)
			{
				$out = doStrip($out);
			}
		}

		return doArray($out, 'deNull');
	}

	/**
	 * Builds a content-negotiation accepts map from the given value.
	 *
	 * Keys are the accepted type and the value is the quality. If client doesn't
	 * specify quality, defaults to 1.0. Values are sorted by the quality,
	 * from the highest to the lowest.
	 *
	 * This method can be used to parse Accept, Accept-Charset, Accept-Encoding and
	 * Accept-Language header values.
	 *
	 * @param  string $header The header string
	 * @return array  Accepts map
	 */

	protected function getAcceptsMap($header)
	{
		$types = explode(',', $header);
		$accepts = array();

		foreach ($types as $type)
		{
			if ($type = trim($type))
			{
				if (preg_match('/(.*)\s*;\s*q=([.0-9]+)/', $type, $m))
				{
					$accepts[$m[1]] = floatval($m[2]);
				}
				else
				{
					$accepts[$type] = 1.0;
				}
			}
		}

		arsort($accepts);

		return $accepts;
	}
}
