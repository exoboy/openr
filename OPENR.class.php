<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * ----------------------------------------------------------------------------------------
 * OPENR - Object Property Extended Notation (Array) - OPENR
 * ----------------------------------------------------------------------------------------
**/
 class OPENR {

	/**
	 * INIT: initialize any static variables here
	 */
	static function __init()
	{
		// do nothing for now
	}

	/**
	 * MULTIDIMENSIONAL ARRAY PROPERTY VALUE GETTER
	 * Uses a string path, separated by "." to locate a matching property and return its value
	 *
	 * @param	array	$source	the array to search in 
	 * @return	multi	$source	returns the value at that location, string/array/integer
	 * 
	 * */
	static function get_property( $source, $path ) {

		// break out path into an array to traverse
		$keys = explode('.', $path);

		// searches only the array elements in our path, does not iterate through ALL properties!
		foreach($keys as $key) {

			// if the next element in our path does not exist, then it is a bad path, return null
			if (!array_key_exists($key, $source)) {
				// not found
				return NULL; 
			}

			// capture the next array element value and keep checking until the end of path
			$source = $source[$key];
		}

		// returns the last value found, not the original array
		return $source;
	}

	/**
	 * MULTIDIMENSIONAL ARRAY PROPERTY VALUE SETTER
	 * Uses a string path, separated by "." to locate a matching property and SET its value
	 * 
	 * @param	array	$source	the array to search in, is passed as a pointer, so we can alter the value of our target property
	 * @return	mixed	$source could be an array, integer, or string. Returns NULL if not matching property was found.
	 * 
	 * */
	static function set_property( &$source, $path, $value ) {

		// break out path into an array to traverse
		$keys = explode('.', $path);

		// searches only the array elements in our path, does not iterate through ALL properties!
		foreach($keys as $key) {

			// if the next element in our path does not exist, then it is a bad path, return null
			if (!array_key_exists($key, $source)) {
				// not found
				return NULL; 
			}

			// capture the next array element value and keep checking until the end of path
			$source = &$source[$key];
		}

		$source = $value;

		// returns the last value found, not the original array
		return $source;
	}

	/**
	 * PARSE PROPERTY ACTIONS INTO THEIR CONSTITUENT PARTS
	 * 
	 * @param	string	$action	action string that needs to be broken down into commands, parameters, sources, etc.
	 * 
	 * example action: {{openr->command::source.path.to-data}}
	 * 
	 * openr = name of our class
	 * command = the action type you want to perform (our verb)
	 * :: = separator for the command and data source path
	 * source.path.to-data = path to the data property we want to retrieve a value from in the source array(s)
	 * 
	 * */
	static function parse_action( $action ) {

		if( self::is_action( $action ) === false ) {
			// combine into an action object
			$result = array( 
				"action" => "",
				"depth" => 0,
				"params" => array(),
				"src" => array(),
				"dest" => array(),
				"original" => ""
			);

			return $result;
		}

		// -----------------------------------------------
		// be sure to keep a copy of the original action
		$original = $action;

		preg_match( "/\{{2}!{0,}\bopenr\b(\([0-9]\)){0,}->(.*?)*\}{2}/", $action, $matches );
		$action = !empty( $matches[0] ) ? $matches[0] : "";

		// -----------------------------------------------
		// see if they passed an optional __dest in the parameters
		$temp = preg_replace( "/(\,{0,1}\s{0,1}`{0,1}\b__dest\b\`{0,1})/", "", $action );

		if( $temp != $action ) {
			// we found an optional __dest which tells us to pull our source value from the destination array
			$destination = "__dest";
		} else {
			$destination = "__sources";
		}

		$action = $temp;

		// -----------------------------------------------
		// capture the remaining openr parameters (if any)
		preg_match( "/\{\{!{0,}\bopenr\b((\((.*?)?)\))->/", $action, $matches );

		// -----------------------------------------------
		// DEPTH: this will always be the depth that we want to execute this action at (default is 0)
		$depth = !empty( $matches[ count( $matches ) - 1 ] ) ? $matches[ count( $matches ) - 1 ] : 0;

		// now remove the depth from the action string
		$action = preg_replace( "/\{\{!{0,}\bopenr\b[\(0-9\)]*->/", "", $action );

		// -----------------------------------------------
		// VERB PARAMETERS: check for verb parameters before the ::
		preg_match( "/\((.*?)?\)::/", $action, $matches );

		if( count( $matches ) > 0 ) {
			// optional parameters found

			// split it into a single string of optional parameters
			$count = !empty( count( $matches ) -1 ) ? count( $matches ) -1 : 0;

			if( $count < 0 && count( $matches ) <= 0 ) {
				$params = array();
			} else {
				preg_match_all( "/`(.*?)*`/", $matches[ $count ], $params );
			}

			// trim all "`" opening and closing characters
			if( count( $params ) >= 1 ) {

				$params = $params[0];

				foreach( $params as $index => &$param ) {

					$param = trim( $param, "`" );
				}

			} else {
				// no optional parameters found
				$params = array();
			}

		} else {

			// no optional parameters found
			$params = array();
		}

		// then remove the params from our action string and replace with ::
		$action = preg_replace( "/\((.*?)?\)::/", "::", $action );

		// remove closing braces
		$action = preg_replace( "/\}{2}$/", "", $action );

		// now, separate the action head from the body at "::"
		$parts = explode( "::", $action );

		if( empty( $parts[0] ) ) {
			$parts[0] = "";
		}

		// make sure our action verbs are lowercase!
		$action = strtolower( $parts[0] );
		$action = trim($action);

		// now split our source path strings by comman
		if( empty( $parts[1] ) ) {
			$parts[1] = "";
		}

		$sources = explode( ",", $parts[1] );

		// trim off any beginning or trailing whitespace
		foreach( $sources as $index => &$source ) {
			$source = trim( $source );
		}

		if( $destination == "__dest" ) {
			// make our source and destination the same array for this action
			$dest = "__dest";
		} else {
			$dest = "__sources";
		}

		// combine into an action object
		$result = array( 
			'action' => $action,
			'depth' => $depth,
			'params' => $params,
			'src' => $sources,
			'dest' => $destination,
			'original' => $original
		);

		return $result;
	}

	/**
	 * PROPERTY ACTIONS COUNT - see how many unprocessed property actions we have in an array
	 * 
	 * this DOES NOT count the template property actions! Only the ones that are ready to be executed
	 * 
	 * @param	array	An array to search for valid property actions
	 * 
	 * @return	integer	a numeric count of the actions left in the array
	 * */
	static function action_count( $array ) {

		$count = 0;

		// use pointer so we can update the value
		array_walk_recursive( $array, function( $val, $key ) use (&$count) {

			// see if this value is an action
			if( self::is_action( $val ) === "action" ) {
				$count += 1;
			}

		});

		return $count;
	}

	/**
	 * PROPERTY TEMPLATES COUNT - see how many templates we have in our array
	 * 
	 * 
	 * @param	array	An array to search for valid property templates
	 * 
	 * @return	integer	a numeric count of the templates left in the array
	 * */
	static function template_count( $array ) {

		$count = 0;

		// use pointer so we can update the value
		array_walk_recursive( $array, function( $val, $key ) use (&$count) {

			// see if this value is an action
			if( self::is_action( $val ) === "template" ) {
				$count += 1;
			}

		});

		return $count;
	}

	/**
	 * EXPAND TEMPLATES	
	 * look for properties usnig templates and expand them for easier and complete value retrieval
	 * 
	 * 
	 * */
	static function expand_templates( $sources, $dest ) {

		// -------------------------------------
		// get an array of template property names
		// use pointer so we can update the value

		$template_props = array();

		array_walk_recursive( $dest, function( &$dest_val, $dest_key ) use ( $sources,$dest, &$template_props ) {

			$action = self::parse_action( $dest_val );

			if( !empty( $action['action'] ) && $action['action'] == "template" ) {
				array_push( $template_props, $action['src'][0] );
			}

		});

		// -------------------------------------
		// EXPAND ALL CHILD TEMPLATES - use do loop since it always runs at least ONCE
		array_walk_recursive( $dest, function( &$dest_val, $dest_key ) use ( $sources,$dest ) {

			$action = self::parse_action( $dest_val );

			if( !empty( $action['action'] ) && $action['action'] == "template" && in_array( "child", $action['params'] ) !== false ) {

				// store our processed template copies here
				$template = self::get_property( $dest, $action['src'][0] );
				$dest_val = self::set_property( $dest, $action['src'][0], $template );
			}

			if( is_array( $dest_val ) ) {

				// recursively walk through our template to assign an index value to any properties that need it
				array_walk_recursive( $dest_val, function( &$temp_val, $temp_key ) use ( $sources, $dest ) {

					$action = self::parse_action( $temp_val );

					if( !empty( $action['action'] ) && $action['action'] == "template" && in_array( "child", $action['params'] ) !== false ) {

						// store our processed template copies here
						$template = self::get_property( $dest, $action['src'][0] );
						$temp_val = self::set_property( $dest, $action['src'][0], $template );
					}
				});
			}
		});

		// -------------------------------------
		// EXPAND ALL TEMPLATES - use do loop since it always runs at least ONCE
		$timeout = 0;
		
		do {

			// use pointer so we can update the value
			array_walk_recursive( $dest, function( &$dest_val, $dest_key ) use ( $sources,$dest, $template_props ) {

				$action = self::parse_action( $dest_val );

				if( !empty( $action['action'] ) && $action['action'] == "template" ) {
					
					// set our destination property value to our new template copy
					$template_original = self::get_property( $dest, $action['src'][0] );
					$data = self::get_property( $sources, $action['src'][1] );

					// store our processed template copies here
					$blanks = array();

					// this should be an array of original data entries
					foreach( $data as $index => $entry ) {

						// replace the [] in each entry with our index #?
						$template = $template_original;
						
						// recursively walk through our template to assign an index value to any properties that need it
						array_walk_recursive( $template, function( &$temp_val, $temp_key ) use ( $index ) {

							if( !is_array( $temp_val ) ) {
								// now remove the ! ignore flag from our next entry
								$temp_val = preg_replace( "/\{\{!{0,1}/", "{{", $temp_val );
								$temp_val = preg_replace( "/(\[\])/", $index, $temp_val );
							}
						});

						array_push( $blanks, $template );
					}

					$dest_val = self::set_property( $dest, $action['src'][0], $blanks );

				}

			});

			// check to see if we have any left over actions
			$count = self::template_count( $dest );

			// increment a counter that aborts further iterations if we try too many times.
			$timeout++;

		} while( $count > 0 && $timeout < 20 );

		return $dest;
	}

	/**
	 * RUN ALL PROPERTY ACTIONS - run ALL porperty actions in our destination array
	 * 
	 **/
	static function run_all_actions( $sources, $dest, $depth = 0 ) {

		// -------------------------------------
		// first, make sure all templates have been expanded
		$dest = self::expand_templates( $sources, $dest );

		// -------------------------------------
		// use this value to prevent our do-while from getting into an endless loop
		$fail_count = 16;

		// -------------------------------------
		// CHECK for ACTIONS - use do loop since it always runs at least ONCE
		do {

			// use pointer so we can update the value
			array_walk_recursive( $dest, function( &$val, $key ) use ($sources,$dest, $depth) {

				// see if this value is an action
				if( self::is_action( $val ) === "action" ) {

					// get our depth setting so we can make sure we are supposed to execute it
					preg_match( "/openr(\((.*?)?\)){0,}/", $val, $matches );

					// see if we are at the right depth
					$this_depth = !empty( $matches[2] ) ? $matches[2] : 0;

					if( $depth > $this_depth || $depth == 0 ) {

						// remove the depth from our action
						$raw_action = preg_replace( "/\bopenr\b(\([0-9]\)){0,}/", "openr", $val );

						// now, parse our action string into its components
						$action = self::parse_action( $raw_action, $depth );

						// execute the action!
						$result = self::run_action( $action, $sources, $dest, $depth );

						// see if we should replace our original action with the new value
						if( is_string( $result ) ) {
							$val = str_replace( $action['original'], $result, $val  ); 
						} else {
							$val = $result;
						}
						
					}
				}
			
			});

			// check to see if we have any left over actions
			$count = self::action_count( $dest );

			// increas the depth count
			$depth++;

		} while( $count > 0 && $depth < $fail_count );// && $depth < $fail_count 

		return $dest;
	}

	/**
	 * SINGLE PROPERTY ACTION: execute a SINGLE property action
	 * 
	 * get new property values based on the requested property action notation in property values
	 * 
	 * @param	array	$action	action object with important details on how to find the data we need
	 * @param	array	$source	one ore more arrays to draw data from
	 * @param	array	$dest	an array that will be our final deliverable object, also contains templates
	 * 
	 * @return	mixed	$val	can be an array, integer, fp number, string
	 * 
	 * REMEMBER: any "openr->" that is preceeded by a "!" will be ignore so we can process it as a template without resorting to checking for special cases or having hard-wired property names to indicate templates. Use should be able to name templates anything they want.
	 * */
	static function run_action( $action, $sources, $dest, $depth = 0 ) {

		// see which array to use as out destination for this action
		if( $action['dest'] == "__dest" ) {
			$this_src = $dest;
		} else {
			$this_src = $sources;
		}

		switch (true) {

			case $action['action'] == "regexp":

				// use a regular expresion on a property

				// remove the opening and closing "/" so we can make sure it is there and there are no more than one of them
				foreach( $action['params'] as &$param ) {
					$regexp = trim( $param, "/" );
					$regexp = "/" . $regexp . "/";
				}

				// use the reggexp on this source property value - we can nly have one source
				$source_prop = array_shift( $action['src'] );
				$subject = self::get_property( $this_src, $source_prop );

				// remove the tick marks from our replacement string - the replacement is ALWAYS at the end of the list of of sources
				$replacement = array_pop( $action['src'] );
				$replacement = trim( $replacement, "`" );

				$val = preg_replace( $regexp, $replacement, $subject );
				break;

			case $action['action'] == "get":
				// get a value from our source and place it in our destination
				$val = self::get_property( $this_src, $action['src'][0] );
				break;

			case $action['action'] == "timestamp":
				// get epoch timestamp in microseconds
				$time = round( microtime(true) );

				if( empty( $action['src'][0] ) || $action['src'][0] == "epoch" ) {
					$val = $time;
				} else {
					// allow user to pass any date string format they want
					$val = date( $action['src'][0], $time );
				}
				break;

			case $action['action'] == "join":

				// get multiple values and join them using a supplied delimiter
				$val = array();
				
				foreach( $action['src'] as $index => $path ) {
					array_push( $val, self::get_property( $this_src, $path ) );
				}

				// when all done, join together!
				$val = implode( $action['params'][0], $val );
				break;

			case $action['action'] == "add":

				// get multiple values and mathematically ADD them together
				$val = array();
				
				foreach( $action['src'] as $index => $path ) {

					$num = self::get_property( $this_src, $path );

					if( is_array( $num ) ) {

						foreach( $num as $add_key => $add_num ) {
							// arrays of integers
							$add_num = floatval( $add_num );
							array_push( $val, $add_num );
						}

					} else {
						// single values - make sure the value is a number and NOT a string
						$num = floatval( $num );
						array_push( $val, $num );
					}
				}

				// add together our array of integers/floating point numbers
				$val = array_sum( $val );

				break;

			case $action['action'] == "subtract":

				// get multiple values and mathematically ADD them together
				$val = array();
				
				// shift our first element, this is the number to subtract the array from
				$total = array_shift( $action['src'] );

				foreach( $action['src'] as $index => $path ) {

					$num = self::get_property( $this_src, $path );

					if( is_array( $num ) ) {

						foreach( $num as $add_key => $add_num ) {
							// arrays of integers
							$add_num = floatval( $add_num );
							array_push( $val, $add_num );
						}
	
					} else {
						// single values - make sure the value is a number and NOT a string
						$num = floatval( $num );
						array_push( $val, $num );
					}
				}

				// subtract an array of integers/floating point numbers
				$val = self::array_subtract( $val, $total );

				break;

			case $action['action'] == "explode":

				// user wants to create an indexed array of elements from a string
				$str = self::get_property( $this_src, $action['src'][0] );

				$val = explode( $action['params'][0], $str );
				break;

			case $action['action'] == "implode":

				// user wants to create a string from an indexed array
				$str = self::get_property( $this_src, $action['src'][0] );
				$val = implode( $action['params'][0], $str );
				break;

			default:
				// if the action could not be executed, return the original value so it can possibly be run again later
				$val = $action['original'];
		}

		return $val;
	}

	/**
	 * SUBTRACT NUMBERS IN ARRAY PROPERTIES
	 * 
	 * take an array of numbers and substract each entry from one another
	 * 
	 * @param	array	$array	array of integers/floating point numbers
	 * 
	 * @return	float	$result	floating point product of the subtractions
	 * 
	 **/
	static function array_subtract( $array, $total = 0 ) {

		// reset our array's internal pointer position and get the first value in the array
		$result = $total;//reset($array);

		foreach( array_slice( $array, 1) as $value ) {
			$result -= $value;
		}

		// always return a positive number, since 10 - 5 is not the same as 5 - 10 and we want to know the DIFFERENCE only
		return abs( $result );

	}

	/**
	 * CHECK FOR PRESENCE OF ACTION IN PROPERTY VALUE
	 * all we want to do is check to see if this value is an action or not!
	 * 
	 * @param	string	$action	a string of the possible action
	 * @return	boolean	true = action, false = non-action (string/integer/array)
	 * */
	static function is_action( $action ) {

		switch (true) {

			case is_array( $action ) || is_object( $action ) || empty( $action ):
				return false;
				break;

			case preg_match( "/\{{2}!{0,}\bopenr\b(\([0-9]\))?->template/", $action ) !== 0 && preg_match( "/\}{2}$/", $action ) !== 0:
				// this is a template action!
				return "template";
				
			case preg_match( "/\{{2}!{0}\bopenr\b(\([0-9]\)){0,}->(.*?)*(.*)*\}{2}/", $action ) !== 0:
				
				// this is a general action
				return "action";

			default:
				return false;
			}
	}

} // end of OPENR class definition


// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// END CLASS DEFINITION AREA - BEGIN EXAMPLE TESTS   !!!!!!!!!!!!!!!!!!!!!!
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

/**
 * 
 * TESTING CODE BELOW!!!!!!!!!!!!!!!!!!
 * 
 * Use some test data to show how the process works.
 * */

 // -------------------------------------
// Example array
$array = array(
	'foo' => 'Foo Value',
	'bar' => array(
		'baz' => 'Baz Value',
		'qux1' => array(
			'nestedKey' => 'Nested Value1'
		),
		'qux2' => array(
			'nestedKey' => 'Nested Value2'
		)
		),
	'bing' => array(
			'nestedKey' => 'Nested Value3'
	)
);


// -------------------------------------
// GET multidimensional v alue using path string
$result = OPENR::get_property( $array, "bar.qux2.nestedKey" );

echo "--------------------------------------------<br /><br />";
echo "EXAMPLE GET:";//var_dump( $result );
echo "<pre>".print_r( $array, true )."</pre>";
echo "--------------------------------------------<br /><br />";

// -------------------------------------
// SET multidimensional v alue using path string
$result = OPENR::set_property( $array, "bar.qux2.nestedKey", "XXX-23" );

echo "EXAMPLE SET:";//var_dump( $result );
echo "<pre>".print_r( $array, true )."</pre>";
echo "--------------------------------------------<br /><br />";


// -------------------------------------
// EXAMPLE SOURCE ARRAY(S)
$sources = array(
	'foo' => 'Foo Value',
	'bar' => array(
		'baz' => 'Baz Value',
		'qux1' => array(
			'nestedKey' => 'Nested Value1'
		),
		'qux2' => array(
			'nestedKey' => 'Nested Value2'
		)
		),
	'bing' => array(
			'nestedKey' => 'Nested Value3'
	),
	"number_1" => "1",
	"number_2" => array( "2" ),
	"number_3" => "3",
	"products" => array(
		array(
			"type" => "SUV",
			"make" => "Ford",
			"model" => "Wonder Wheel"
		),
		array(
			"type" => "TRUCK",
			"make" => "Ford",
			"model" => "F-150"
		),
		array(
			"type" => "TRUCK 2",
			"make" => "Ford 2",
			"model" => "F-150 2"
		)
	),
	"additional" => array(
		array(
			"more" => "SUV-more0",
			"less" => "SUV-less0"
		),
		array(
			"more" => "SUV-more1",
			"less" => "SUV-less1"
		),
		array(
			"more" => "SUV-more2",
			"less" => "SUV-less2"
		)
	),
	"array_1" => array( 1,2,3,4,5,6,7,8,9 ),
	"array_2" => "1:2:3:4:5:6:7:8:9"
);

$sources['source_2'] = array(
	"source_2" => "source_2_val",
	"source_2_prop" => "source_2_prop_val"
);

// -------------------------------------
// EXAMPLE DESTINATION ARRAY(S)
$dest = array(
	"dest_foo_test" => "{{openr->get(__dest)::dest_foo}}",
	'dest_foo' => '{{openr->get()::foo}}',
	'dest_bar' => array(
		'dest_baz' => '{{openr->get()::bar.baz}}',
		'dest_qux1' => array(
			'dest_nestedKey' => '{{openr->get(`__dest`)::dest_foo}}'
			),
			'dest_qux2' => array(
				'dest_nestedKey' => '{{openr->get()::bar.qux2.nestedKey}}'
			)
		),
	'dest_bing' => array(
			'dest_nestedKey' => '{{openr->get()::bing.nestedKey}}'
		),
	"dest_empty" => "",
	"dest_joined" => "{{openr->join(`, `)::bar.qux1.nestedKey,bing.nestedKey}}",
	"dest_not_action" => "I'm not an action!",
	"dest_timestamp" => "{{openr->timestamp()::epoch}}",
	"dest_add_numbers" => "{{openr->add()::number_1,number_2}}",
	"dest_subtract_numbers" => "{{openr->subtract()::100, number_3, number_2, number_1}}",

	"child_template_1" => array(
		"temp_more_1" => "{{!openr->get()::additional.[].more}}",
		"temp_less_1" => "{{!openr->get()::additional.[].less}}",
		"temp_embedded" => "{{!openr->template(`child`)::child_template_2,additional}}"
	),

	"child_template_2" => array(
		"temp_more_2" => "{{!openr->get()::additional.[].more}}",
		"temp_less_2" => "{{!openr->get()::additional.[].less}}",
	),

	"dest_products_template" => array(
		"template-type" => "{{!openr->get()::products.[].type}}",
		"template-make" => "{{!openr->get()::products.[].make}}",
		"template-model" => "{{!openr->get()::products.[].model}}",
		"template-more" => "{{!openr->get()::additional.[].more}}",
		"template_nested" => "{{!openr->template(`child`)::child_template_1,additional}}"
	),

	"dest_products" => "{{openr->template()::dest_products_template,products}}",

	"dest_replace" => "XX {{openr->get()::foo}} is also {{openr->get::bar.baz}} XX",

	"dest_array_1" => "{{openr->implode(`,`)::array_1}}",
	"dest_array_2" => "{{openr->explode(`:`)::array_2}}",

	"source_2_dest" => "{{openr->get()::source_2.source_2}}",
	"source_2_prop_dest" => "{{openr->get()::source_2.source_2_prop}}",

	"regexp_destination" => "{{openr->regexp(`/[0-9]{1,}/`)::products.1.model,`okay`}}",
	"regexp_2" => "{{openr->regexp(`/[0-9]{1,}/`)::products.1.model,`okay`}} SO....?",
	"regexp_3" => "I don't know about this: {{openr->regexp(`/[0-9]{1,}/`)::products.1.model,`okay`}}"
);

echo "EXAMPLE CROSS-INDEX SOURCE:";//var_dump( $result );
echo "<pre>".print_r( $sources, true )."</pre>";
echo "--------------------------------------------<br /><br />END";

echo "EXAMPLE CROSS-INDEX DESTINATION:";//var_dump( $result );
echo "<pre>".print_r( $dest, true )."</pre>";
echo "--------------------------------------------<br /><br />END";

// -------------------------------------
// RUN - execute ALL of our property actions in our destination array
echo "EXAMPLE CROSS-INDEX RESULT:";//var_dump( $result );

$result = OPENR::run_all_actions( $sources, $dest );

echo "<pre>".print_r( $result, true )."</pre>";
echo "--------------------------------------------<br /><br />END";

?>
