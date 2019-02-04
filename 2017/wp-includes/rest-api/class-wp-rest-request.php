<?php
/**
 * REST API: WP_REST_Request class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.4.0
 */

/**
 * Core class used to implement a REST request object.
 *
 * Contains data from the request, to be passed to the callback.
 *
 * Note: This implements ArrayAccess, and acts as an array of parameters when
 * used in that manner. It does not use ArrayObject (as we cannot rely on SPL),
 * so be aware it may have non-array behaviour in some cases.
 *
 * Note: When using features provided by ArrayAccess, be aware that WordPress deliberately
 * does not distinguish between arguments of the same name for different request methods.
 * For instance, in a request with `GET id=1` and `POST id=2`, `$request['id']` will equal
 * 2 (`POST`) not 1 (`GET`). For more precision between request methods, use
 * WP_REST_Request::get_body_params(), WP_REST_Request::get_url_params(), etc.
 *
 * @since 4.4.0
 *
 * @see ArrayAccess
 */
class WP_REST_Request implements ArrayAccess {

	/**
	 * HTTP method.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	protected $method = '';

	/**
	 * Parameters passed to the request.
	 *
	 * These typically come from the `$_GET`, `$_POST` and `$_FILES`
	 * superglobals when being created from the global scope.
	 *
	 * @since 4.4.0
	 * @var array Contains GET, POST and FILES keys mapping to arrays of data.
	 */
	protected $params;

	/**
	 * HTTP headers for the request.
	 *
	 * @since 4.4.0
	 * @var array Map of key to value. Key is always lowercase, as per HTTP specification.
	 */
	protected $headers = array();

	/**
	 * Body data.
	 *
	 * @since 4.4.0
	 * @var string Binary data from the request.
	 */
	protected $body = null;

	/**
	 * Route matched for the request.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	protected $route;

	/**
	 * Attributes (options) for the route that was matched.
	 *
	 * This is the options array used when the route was registered, typically
	 * containing the callback as well as the valid methods for the route.
	 *
	 * @since 4.4.0
	 * @var array Attributes for the request.
	 */
	protected $attributes = array();

	/**
	 * Used to determine if the JSON data has been parsed yet.
	 *
	 * Allows lazy-parsing of JSON data where possible.
	 *
	 * @since 4.4.0
	 * @var bool
	 */
	protected $parsed_json = false;

	/**
	 * Used to determine if the body data has been parsed yet.
	 *
	 * @since 4.4.0
	 * @var bool
	 */
	protected $parsed_body = false;

	/**
	 * Constructor.
	 *
	 * @since 4.4.0
	 *
	 * @param string $method     Optional. Request method. Default empty.
	 * @param string $route      Optional. Request route. Default empty.
	 * @param array  $attributes Optional. Request attributes. Default empty array.
	 */
	public function __construct( $method = '', $route = '', $attributes = array() ) {
		$this->params = array(
			'URL'   => array(),
			'GET'   => array(),
			'POST'  => array(),
			'FILES' => array(),

			// See parse_json_params.
			'JSON'  => null,

			'defaults' => array(),
		);

		$this->set_method( $method );
		$this->set_route( $route );
		$this->set_attributes( $attributes );
	}

	/**
	 * Retrieves the HTTP method for the request.
	 *
	 * @since 4.4.0
	 *
	 * @return string HTTP method.
	 */
	public function get_method() {
		return $this->method;
	}

	/**
	 * Sets HTTP method for the request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $method HTTP method.
	 */
	public function set_method( $method ) {
		$this->method = strtoupper( $method );
	}

	/**
	 * Retrieves all headers from the request.
	 *
	 * @since 4.4.0
	 *
	 * @return array Map of key to value. Key is always lowercase, as per HTTP specification.
	 */
	public function get_headers() {
		return $this->headers;
	}

	/**
	 * Canonicalizes the header name.
	 *
	 * Ensures that header names are always treated the same regardless of
	 * source. Header names are always case insensitive.
	 *
	 * Note that we treat `-` (dashes) and `_` (underscores) as the same
	 * character, as per header parsing rules in both Apache and nginx.
	 *
	 * @link https://stackoverflow.com/q/18185366
	 * @link https://www.nginx.com/resources/wiki/start/topics/tutorials/config_pitfalls/#missing-disappearing-http-headers
	 * @link https://nginx.org/en/docs/http/ngx_http_core_module.html#underscores_in_headers
	 *
	 * @since 4.4.0
	 * @static
	 *
	 * @param string $key Header name.
	 * @return string Canonicalized name.
	 */
	public static function canonicalize_header_name( $key ) {
		$key = strtolower( $key );
		$key = str_replace( '-', '_', $key );

		return $key;
	}

	/**
	 * Retrieves the given header from the request.
	 *
	 * If the header has multiple values, they will be concatenated with a comma
	 * as per the HTTP specification. Be aware that some non-compliant headers
	 * (notably cookie headers) cannot be joined this way.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Header name, will be canonicalized to lowercase.
	 * @return string|null String value if set, null otherwise.
	 */
	public function get_header( $key ) {
		$key = $this->canonicalize_header_name( $key );

		if ( ! isset( $this->headers[ $key ] ) ) {
			return null;
		}

		return implode( ',', $this->headers[ $key ] );
	}

	/**
	 * Retrieves header values from the request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Header name, will be canonicalized to lowercase.
	 * @return array|null List of string values if set, null otherwise.
	 */
	public function get_header_as_array( $key ) {
		$key = $this->canonicalize_header_name( $key );

		if ( ! isset( $this->headers[ $key ] ) ) {
			return null;
		}

		return $this->headers[ $key ];
	}

	/**
	 * Sets the header on request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key   Header name.
	 * @param string $value Header value, or list of values.
	 */
	public function set_header( $key, $value ) {
		$key = $this->canonicalize_header_name( $key );
		$value = (array) $value;

		$this->headers[ $key ] = $value;
	}

	/**
	 * Appends a header value for the given header.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key   Header name.
	 * @param string $value Header value, or list of values.
	 */
	public function add_header( $key, $value ) {
		$key = $this->canonicalize_header_name( $key );
		$value = (array) $value;

		if ( ! isset( $this->headers[ $key ] ) ) {
			$this->headers[ $key ] = array();
		}

		$this->headers[ $key ] = array_merge( $this->headers[ $key ], $value );
	}

	/**
	 * Removes all values for a header.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Header name.
	 */
	public function remove_header( $key ) {
		$key = $this->canonicalize_header_name( $key );
		unset( $this->headers[ $key ] );
	}

	/**
	 * Sets headers on the request.
	 *
	 * @since 4.4.0
	 *
	 * @param array $headers  Map of header name to value.
	 * @param bool  $override If true, replace the request's headers. Otherwise, merge with existing.
	 */
	public function set_headers( $headers, $override = true ) {
		if ( true === $override ) {
			$this->headers = array();
		}

		foreach ( $headers as $key => $value ) {
			$this->set_header( $key, $value );
		}
	}

	/**
	 * Retrieves the content-type of the request.
	 *
	 * @since 4.4.0
	 *
	 * @return array Map containing 'value' and 'parameters' keys.
	 */
	public function get_content_type() {
		$value = $this->get_header( 'content-type' );
		if ( empty( $value ) ) {
			return null;
		}

		$parameters = '';
		if ( strpos( $value, ';' ) ) {
			list( $value, $parameters ) = explode( ';', $value, 2 );
		}

		$value = strtolower( $value );
		if ( strpos( $value, '/' ) === false ) {
			return null;
		}

		// Parse type and subtype out.
		list( $type, $subtype ) = explode( '/', $value, 2 );

		$data = compact( 'value', 'type', 'subtype', 'parameters' );
		$data = array_map( 'trim', $data );

		return $data;
	}

	/**
	 * Retrieves the parameter priority order.
	 *
	 * Used when checking parameters in get_param().
	 *
	 * @since 4.4.0
	 *
	 * @return array List of types to check, in order of priority.
	 */
	protected function get_parameter_order() {
		$order = array();

		$content_type = $this->get_content_type();
		if ( $content_type['value'] === 'application/json' ) {
			$order[] = 'JSON';
		}

		$this->parse_json_params();

		// Ensure we parse the body data.
		$body = $this->get_body();

		if ( 'POST' !== $this->method && ! empty( $body ) ) {
			$this->parse_body_params();
		}

		$accepts_body_data = array( 'POST', 'PUT', 'PATCH', 'DELETE' );
		if ( in_array( $this->method, $accepts_body_data ) ) {
			$order[] = 'POST';
		}

		$order[] = 'GET';
		$order[] = 'URL';
		$order[] = 'defaults';

		/**
		 * Filters the parameter order.
		 *
		 * The order affects which parameters are checked when using get_param() and family.
		 * This acts similarly to PHP's `request_order` setting.
		 *
		 * @since 4.4.0
		 *
		 * @param array           $order {
		 *    An array of types to check, in order of priority.
		 *
		 *    @param string $type The type to check.
		 * }
		 * @param WP_REST_Request $this The request object.
		 */
		return apply_filters( 'rest_request_parameter_order', $order, $this );
	}

	/**
	 * Retrieves a parameter from the request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Parameter name.
	 * @return mixed|null Value if set, null otherwise.
	 */
	public function get_param( $key ) {
		$order = $this->get_parameter_order();

		foreach ( $order as $type ) {
			// Determine if we have the parameter for this type.
			if ( isset( $this->params[ $type ][ $key ] ) ) {
				return $this->params[ $type ][ $key ];
			}
		}

		return null;
	}

	/**
	 * Sets a parameter on the request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key   Parameter name.
	 * @param mixed  $value Parameter value.
	 */
	public function set_param( $key, $value ) {
		$order = $this->get_parameter_order();
		$this->params[ $order[0] ][ $key ] = $value;
	}

	/**
	 * Retrieves merged parameters from the request.
	 *
	 * The equivalent of get_param(), but returns all parameters for the request.
	 * Handles merging all the available values into a single array.
	 *
	 * @since 4.4.0
	 *
	 * @return array Map of key to value.
	 */
	public function get_params() {
		$order = $this->get_parameter_order();
		$order = array_reverse( $order, true );

		$params = array();
		foreach ( $order as $type ) {
			// array_merge / the "+" operator will mess up
			// numeric keys, so instead do a manual foreach.
			foreach ( (array) $this->params[ $type ] as $key => $value ) {
				$params[ $key ] = $value;
			}
		}

		return $params;
	}

	/**
	 * Retrieves parameters from the route itself.
	 *
	 * These are parsed from the URL using the regex.
	 *
	 * @since 4.4.0
	 *
	 * @return array Parameter map of key to value.
	 */
	public function get_url_params() {
		return $this->params['URL'];
	}

	/**
	 * Sets parameters from the route.
	 *
	 * Typically, this is set after parsing the URL.
	 *
	 * @since 4.4.0
	 *
	 * @param array $params Parameter map of key to value.
	 */
	public function set_url_params( $params ) {
		$this->params['URL'] = $params;
	}

	/**
	 * Retrieves parameters from the query string.
	 *
	 * These are the parameters you'd typically find in `$_GET`.
	 *
	 * @since 4.4.0
	 *
	 * @return array Parameter map of key to value
	 */
	public function get_query_params() {
		return $this->params['GET'];
	}

	/**
	 * Sets parameters from the query string.
	 *
	 * Typically, this is set from `$_GET`.
	 *
	 * @since 4.4.0
	 *
	 * @param array $params Parameter map of key to value.
	 */
	public function set_query_params( $params ) {
		$this->params['GET'] = $params;
	}

	/**
	 * Retrieves parameters from the body.
	 *
	 * These are the parameters you'd typically find in `$_POST`.
	 *
	 * @since 4.4.0
	 *
	 * @return array Parameter map of key to value.
	 */
	public function get_body_params() {
		return $this->params['POST'];
	}

	/**
	 * Sets parameters from the body.
	 *
	 * Typically, this is set from `$_POST`.
	 *
	 * @since 4.4.0
	 *
	 * @param array $params Parameter map of key to value.
	 */
	public function set_body_params( $params ) {
		$this->params['POST'] = $params;
	}

	/**
	 * Retrieves multipart file parameters from the body.
	 *
	 * These are the parameters you'd typically find in `$_FILES`.
	 *
	 * @since 4.4.0
	 *
	 * @return array Parameter map of key to value
	 */
	public function get_file_params() {
		return $this->params['FILES'];
	}

	/**
	 * Sets multipart file parameters from the body.
	 *
	 * Typically, this is set from `$_FILES`.
	 *
	 * @since 4.4.0
	 *
	 * @param array $params Parameter map of key to value.
	 */
	public function set_file_params( $params ) {
		$this->params['FILES'] = $params;
	}

	/**
	 * Retrieves the default parameters.
	 *
	 * These are the parameters set in the route registration.
	 *
	 * @since 4.4.0
	 *
	 * @return array Parameter map of key to value
	 */
	public function get_default_params() {
		return $this->params['defaults'];
	}

	/**
	 * Sets default parameters.
	 *
	 * These are the parameters set in the route registration.
	 *
	 * @since 4.4.0
	 *
	 * @param array $params Parameter map of key to value.
	 */
	public function set_default_params( $params ) {
		$this->params['defaults'] = $params;
	}

	/**
	 * Retrieves the request body content.
	 *
	 * @since 4.4.0
	 *
	 * @return string Binary data from the request body.
	 */
	public function get_body() {
		return $this->body;
	}

	/**
	 * Sets body content.
	 *
	 * @since 4.4.0
	 *
	 * @param string $data Binary data from the request body.
	 */
	public function set_body( $data ) {
		$this->body = $data;

		// Enable lazy parsing.
		$this->parsed_json = false;
		$this->parsed_body = false;
		$this->params['JSON'] = null;
	}

	/**
	 * Retrieves the parameters from a JSON-formatted body.
	 *
	 * @since 4.4.0
	 *
	 * @return array Parameter map of key to value.
	 */
	public function get_json_params() {
		// Ensure the parameters have been parsed out.
		$this->parse_json_params();

		return $this->params['JSON'];
	}

	/**
	 * Parses the JSON parameters.
	 *
	 * Avoids parsing the JSON data until we need to access it.
	 *
	 * @since 4.4.0
	 * @since 4.7.0 Returns error instance if value cannot be decoded.
	 * @return true|WP_Error True if the JSON data was passed or no JSON data was provided, WP_Error if invalid JSON was passed.
	 */
	protected function parse_json_params() {
		if ( $this->parsed_json ) {
			return true;
		}

		$this->parsed_json = true;

		// Check that we actually got JSON.
		$content_type = $this->get_content_type();

		if ( empty( $content_type ) || 'application/json' !== $content_type['value'] ) {
			return true;
		}

		$body = $this->get_body();
		if ( empty( $body ) ) {
			return true;
		}

		$params = json_decode( $body, true );

		/*
		 * Check for a parsin