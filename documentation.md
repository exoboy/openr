# OPENR USAGE
It is assumed that you already know how to include files using the include and require PHP function. https://www.w3schools.com/php/php_includes.asp  

&nbsp;  

### PURPOSE
The purpose of this document is to help PHP programmers understand how to implement OPENR notation in an array object's property values in order to easily and programmatically create a cross-indexing template array that can be used to pull property values from multiple arrays, coming from one or more sources or API's.  

&nbsp;  

### USE CASE 1
The destination array your programmer creates is basically a template array and is generally used as an internal array in your development project.  

By cross-referencing it with one or more external sources, we can normalize the property names and array structure to whatever internal configuration your code needs for more efficient execution, while also minimizing the number of array data sources that require iterating over one ore more times.  

A good use case for this would be a company that uses a shipping platform to get quotes for shipping orders.

This company would need the ability to get quotes from multiple shippers, all of which tend to use different property names and API results array structures.

In this case, OPENR gives the platform's programmer the ability to quickly adapt to new or changing shipper API result objects as well as minimizing downtime caused by having to hard-code these cross-reference array properties in their source code.

This is also a case where the developer would benefit from creating a simple user interface to define and organize their cross-indexing templates then store them in a database, file, or as part of their source code.  

&nbsp;  

### USE CASE 2
A second use case would be if your system required you to work with multiple API's which have different credential submission object structures.

By creating a library of API request objects, and normalizing the data into an internal credential array, it saves the programmer the effort of hard-coding a new credential object for every new system where API access is needed.

Your internal, array property names can be consistent and never change due to changing result properties contained in the external API's result objects.

This potentially saves re-compilation time and avoids delays due to waiting for your project's next code push to production.  

&nbsp;  

## USAGE
Below are example source and destination arrays referenced in this document. All property actions usages are described below. It is recommended that you read this document thoroughly before implementing it in your own code.  If your actions are not properly defined they can cause you application to throw an error.  

&nbsp;  

### Source Arrays
```
// Merge multiple arrays into a single source array, but make sure to give it a unqiue root property name and reference that property name in the path for your destination array.

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
		"number_2" => "2",
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
		),
		"array_1" => array( 1,2,3,4,5,6,7,8,9 ),
		"array_2" => "1:2:3:4:5:6:7:8:9"
	);

	$sources['source_2'] = array(
		"source_2" => "source_2_val",
		"source_2_prop" => "source_2_prop_val"
	);
```  
&nbsp; 

### Multiple Sources  
If you want to get data from multiple source arrays, you will need to add them as a property to a single array.  

Be sure to be consistent with the name for each additional source array since you will need to use its root property name to start the paths to all of your property actions.  

Essentially, you are just merging arrays into a single array, however giving each a unique and permanent reference name will help avoid possible data overwrites when adding many arrays.  

&nbsp;  

### Destination Array
```
// Merge multiple arrays into a single destination array, but make sure to give it a unqiue root property name and reference that property name in the paths for your destination array.

	$dest = array(
		"dest_foo_test" => "{{openr->get(__dest)::dest_foo}}",
		'dest_foo' => '{{openr->get::foo}}',
		'dest_bar' => array(
			'dest_baz' => '{{openr->get::bar.baz}}',
			'dest_qux1' => array(
				'dest_nestedKey' => '{{openr->get(__dest)::dest_foo}}'
				),
				'dest_qux2' => array(
					'dest_nestedKey' => '{{openr->get::bar.qux2.nestedKey}}'
				)
			),
		'dest_bing' => array(
				'dest_nestedKey' => '{{OPENR->get::bing.nestedKey}}'
			),
		"dest_empty" => "",
		"dest_joined" => "{{openr->join(, )::bar.qux1.nestedKey,bing.nestedKey}}",
		"dest_not_action" => "I'm not an action!",
		"dest_timestamp" => "{{openr->timestamp::epoch}}",
		"dest_add_numbers" => "{{openr->add::number_1,number_2}}",
		"dest_subtract_numbers" => "{{openr->subtract::100, number_3, number_2, number_1}}",

		"child_template_1" => array(
			"temp_more_1" => "{{!openr->get::additional.[].more}}",
			"temp_less_1" => "{{!openr->get::additional.[].less}}",
			"temp_embedded" => "{{!openr->template(child)::child_template_2,additional}}"
		),

		"child_template_2" => array(
			"temp_more_2" => "{{!openr->get::additional.[].more}}",
			"temp_less_2" => "{{!openr->get::additional.[].less}}",
		),

		"dest_products_template" => array(
			"template-type" => "{{!openr->get::products.[].type}}",
			"template-make" => "{{!openr->get::products.[].make}}",
			"template-model" => "{{!openr->get::products.[].model}}",
			"template-more" => "{{!openr->get::additional.[].more}}",
			"template_nested" => "{{!openr->template(child)::child_template_1,additional}}"
		),

		"dest_products" => "{{openr->template::dest_products_template,products}}",

		"dest_replace" => "XX {{openr->get::foo}} is also {{openr->get::bar.baz}} XX",

		"dest_array_1" => "{{openr->implode(,)::array_1}}",
		"dest_array_2" => "{{openr->explode(:)::array_2}}",

		"source_2_dest" => "{{openr->get::source_2.source_2}}",
		"source_2_prop_dest" => "{{openr->get::source_2.source_2_prop}}",

		"regexp_destination" => "{{openr->regexp(/[0-9]{1,}/)::products.1.model,`okay`}}",
		"regexp_2" => "{{openr->regexp(/[0-9]{1,}/)::products.1.model,`okay`}} SO....?",
		"regexp_3" => "I don't know about this: {{openr->regexp(/[0-9]{1,}/)::products.1.model,`okay`}}"
	);
```
&nbsp;  

### Multiple Destinations  
If you want to create multiple destination arrays, you can also merge them into a single array using the same method as indicated in the "Multiple Sources" section, above.  

&nbsp;  

## CREATING ACTIONS    
All property actions follow a consistent format, with some optional parameters.  

&nbsp;  

#### Example
```
	{{openr->get::foo}}
```  
* Action property destinations: properties that contain actions are considered the DESTINATION property.

* Opening and closing actions is always achieved by using double left "{{" and right "}}" braces.  

* The head of an action is the first part of all actions up to, but not including the double colon "::" divider. Everything after this is source, destination, or template paths, separated by commas.  

* Action heads are case-insensitive, but action paths and optional parameters are all case-sensitive.  

* All actions require the "openr->" prefix to ensure OPENR knows it is a valid action string and avoids the possibility of property values being accidentally mistaken for actions.  
  
* Action verbs are what you will use to specify what kind of action you want to perform. In this case our verb is "get". Every action requires a verb to properly execute.  

* Optional parameters can be passed to action by enclosing them in "()" parenthesis. Sending a get request with an optional parameter would look like this: {{openr->get(option)::foo}}  

* Optional parameters can only be strings. Separate individual parameters with "," commas. Example: {{openr->get(option_1, option_2, option_3)::foo}}  

* Some preset parameters can be passed as optional parameters, but they are treated as strings and cannot be assigned values. They are matched up to their hard-coded internal values in the OPENR class. One such constant is "__dest".  

* Precede an action with "!" and this will prevent OPENR from executing that action. This is done to indicate to OPENR which actions are template actions, which we never want to process. The ! indicator can be used on any property. Example: "{{!openr->get(option)::foo}}" will tell OPENR to ignore this action.  


&nbsp;  

> &nbsp;  
>  
> ### Parameter Presets
> ### "__dest" 
> 
> A note about OPENR's use of some optional parameter presets. If for some, reason, you want to perform an action on a property contained in the **DESTINATION** array, you will need to pass the optional parameter "__dest" to the action's verb.  
>
> &nbsp;    
> Example:  {{openr->get(__dest)::foo}}
> &nbsp;    
> &nbsp;    

&nbsp;    

### Property Paths for Actions
Single string paths can be used to guide the OPENR to nested values in multidimensional arrays.  

Simple specify your pathway by using the source, or destination, property names and separate them using dot "." notation.  

&nbsp;  

### Target Property
Find the property you want to capture values from in your sources array. Then create a path to use in your action.  

```
array(
	"parent" => array(
		"child" => array(
			"target" => "value"
		)
	)
)
```

&nbsp;  

#### Example Path  
```
{{openr->get::parent.child.target}}
```  

You can also use the destination array as a target by using the "__dest" preset parameter like this:
```
{{openr->get(__dest)::parent.child.target}}
```  

&nbsp;  
&nbsp;  

## ACTION VERBS  
Currently, the following action verbs are supported:   
* get
* join
* timestamp
* add
* subtract
* implode
* explode 
* regexp  
* template   

&nbsp;  

### 1. "get" = Setting Property Values  
In this example, we want to use the OPENR action verb "get". This will look for the property value designated by a path from your destination array telling OPENR where the source value is in your sources array.  

&nbsp;  

#### Example  
```
	'dest_foo' => '{{openr->get::root.foo}}',
```  

* "get" looks for a value to set in the current property. So, any property that contains this action will replace its action string with the value found at the location specified by the path. In essence, we are telling OPENR to go and "get" a value for the current location and use it instead if the action string.  

* The source path to the property we want is "root.foo". This will look in the source array, starting at the root-level property called "root", then checking for the existence of the "foo" property among that property's child properties.  You can chain as many property names together as are required to find your desired property value.  
  
* get(__dest): by passing the optional parameter "__dest", you are telling the verb to look for the specified path in the DESTINATION array. OPENR looks in the source array by default. Example: {{openr->get(__dest)::root.foo}}

&nbsp;  

### 2. "join" = Combining Multiple String Values  
The join verb allows you to take two or more property values and merge them into a single string, separated by a delimiter of your choice.  

&nbsp;  

#### Example
```
'dest_foo' => '{{openr->join(, )::prop1, prop2, prop3}}',
```  

&nbsp;  

#### Result
```
'dest_foo' => 'prop1-value, prop2-value, prop3-value'
```  

&nbsp;  

> NOTE:&nbsp;  You can specify any string you want to use as a delimiter, except for an action formatted string like:
> 
> {{openr->get({{openr->get()::foo}})::foo}}
> 
> This will cause an error and could cause your application to throw an error.&nbsp;  &nbsp;  

&nbsp;  

### 3. "timestamp": Getting current date/time  
Timestamp accepts a date-time string for how you want your date string formatted in your property.  

* Any valid format that can be used with PHP's date() function can be used here.  
* If you want an epoch timestamp in milliseconds, then simply use the word "epoch" as your action body parameter.  
* If no action body parameter is passed to the timestamp verb, then an epoch timestamp in milliseconds is returned.  
* The parameter for this verb is NOT passed using (), see example, below.  

&nbsp;  

### Example  
```
"dest_timestamp" => "{{openr->timestamp::epoch}}",

// or

"dest_timestamp" => "{{openr->timestamp::}}",	
```

&nbsp;  

### Result  
```
"dest_timestamp" => "1687903781.2712",
```  

&nbsp;  

### 4. "add": Adding Multiple Property Values  
If there is a time when you need to merge two or more numeric property values into a single property, you can use the "add" verb.  

* Add can accept a property value that is a single numeric number.  
* Add can also accept a property value that is an array of numbers.  

&nbsp;  

### Example  
```
"dest_add_numbers" => "{{openr->add::number_1,number_2}}",
```

&nbsp;  

### Result  
```
"dest_add_numbers" => "3", // or some other numeric value
```  
In this example, we took the numeric values, "1" and "2", found in property "number_1" and added it to the numeric value found in property "numeric_2" and placed the result in the "dest_add_numbers" property.  

&nbsp;  

### 5. "subtract": Subtract Multiple Property Values  
Similar to the "add" verb, it requires numeric values to work with. Unlike, the add verb, however, this one depends on an order of operation.  

When performing the subtraction, the order of operation matters. In the two examples below you can see the results that simply changing the order of operation causes.  

* Subtraction can accept a property value that is a single numeric number or it can accept an array of numbers.  

&nbsp;  

#### Example 1  
```
	"dest_add_numbers" => "{{openr->add::number_1,number_2}}",

	// this would perform: number_1 - number_2
	// so, if the numbers were 10 and 5, respectively, we would get: 5
```

&nbsp;  

#### Example 2  
```
	"dest_add_numbers" => "{{openr->add::number_2,number_1}}",

	// if the order was reversed, this would perform: number_2 - number_1
	// so, if the numbers were 5 and 10, respectively, we would get: -5
```

&nbsp;  

> ### Always double-check your order of operations when using the subtract verb!  

&nbsp;  

### 6. "implode": Turn an array into a string  
If you want to take a source property value that is an indexed array, and turn it into a single string, use the implode action.  

Just specify a delimiter as the parameter of the implode action.  

Example
```
{{openr->implode(,)::array_1}}
```  

&nbsp;  

### 7. "explode": Turn a string into an array  
If you want to take a source property value that is a string, and turn it into an indexed array, use the explode action.  

Just specify a delimiter as the parameter of the explode action and your string will be split wherever the delimiter is found in the source string.  

&nbsp;  

Example
```
"regexp_destination" => "{{openr->regexp(/[0-9]{1,}/)::products.1.model,`okay`}}",
```  

&nbsp;  

### 8. "regexp": Regular Expressions  
You can use a standard regular expression pattern to search for and replace strings inside your source property values as long as they are strings.  

The regexp pattern should be passed as the optional parameter, while the source property path comes first, then the destination property name, and then the replacement string at the end.  

Replacement strings should be enclosed in "`" tick marks and not in single or double quotes.  

Any quotes or special characters inside the replacement string should be escaped in order to not break the action string itself.  

&nbsp;  

> WARNING!  
>Be sure to always test your regular expression pattern before using it in OPENR. If it fails to compile or has an error, it may cause your application to throw and error.  

&nbsp;  

Example
```
{{openr->explode(:)::array_2}}
```  
&nbsp;  
&nbsp;  

## TEMPLATES  
Since templates are much more involved than then other action verbs, it has been given its own section.  

The example below contains three template properties. One is the parent and the other two are children properties.  

Templates can be nested inside of other templates by passing the "child" optional parameter.  

&nbsp;  

#### Example  
```
"child_template_1" => array(
		"temp_more_1" => "{{!openr->get::additional.[].more}}",
		"temp_less_1" => "{{!openr->get::additional.[].less}}",
		"temp_embedded" => "{{!openr->template(child)::child_template_2,additional}}"
	),

	"child_template_2" => array(
		"temp_more_2" => "{{!openr->get::additional.[].more}}",
		"temp_less_2" => "{{!openr->get::additional.[].less}}",
	),

	"dest_products_template" => array(
		"template-type" => "{{!openr->get::products.[].type}}",
		"template-make" => "{{!openr->get::products.[].make}}",
		"template-model" => "{{!openr->get::products.[].model}}",
		"template-more" => "{{!openr->get::additional.[].more}}",
		"template_nested" => "{{!openr->template(child)::child_template_1,additional}}"
	),

"dest_products" => "{{openr->template::dest_products_template,products}}",
```  

&nbsp;  

> ### ALL template actions must be indicated with our ignore "!" flag. This prevents OPENR from executing that action so it can be used as a template over-and-over again.  

&nbsp;  

### Working with Templates  
To specify a template to be used on the data in an indexed array, you will need to tell OPENR which property in your destination array is the template source and which property in your sources array holds the data.  
&nbsp;  
#### Example
```
{{openr->template::dest_products_template,products}}
```  
&nbsp;  

### Template Child Properties  
Templates are just properties with an associative array as their value. This array contains properties which hold the individual template actions.  

All child properties MUST be preceded by a "!" or else OPNR will execute them and replace them with the result.  

Regardless of whether and action is part of a template property or not, if it starts with a "!" then OPENR knows to avoid ever executing these actions.

```
"dest_property" => "{{openr->template::template,products}}",
"template" => "{{!openr->get::products.1.type}}"
```  
The above example, "template" holds our template's child actions. In the "dest_property" we can see the template verb, then the template to use, then the source property to draw a value from to populate the template.  

&nbsp;  
### Nested Templates  
You can nest templates as deeply as needed inside of other templates. However, you need to specify any nested templates as "child" templates by passing it as an optional parameter.  
&nbsp;  
#### Example  
```
{{!openr->template(child)::template_name,source_array}}
```  
Adding this "child" parameter will prevent OPENR from overwriting the original template in our destination array.  

&nbsp;  

### Templates and Indexed Arrays  
When using a template on an indexed array, you will need to let OPENR know where the index number for that entry should be inserted into the path to our source data.  

Do this by using "[]" in the path.  
&nbsp;  
#### Example  
```
{{!openr->get::products.[].type}}
```  
This tells OPENR to use the nTH index of the source property "products" and get the value of the "type" child property.  

You can, however, explicitly assign an index number to our path so that it always get the nTH array value from our source array by using an integer.  
&nbsp;  
#### Example  
```  
{{!openr->get::products.1.type}}
```
By explicitly stating the number "1" here we are telling OPENR to get the source property "products", which is an indexed array, and get the value of "type" from the second array element (indexes go from 0>, so the second index would be 1 and the first would be 0).  

&nbsp;  
&nbsp;  

## ADDITIONAL BEHAVIORS  

&nbsp;  

### Single String Replacement  
You can embed any valid action string into the string value of a destination properties. For example, if we wanted to precede our new string value with "XX" and end it with "YY". The destination property would look something like this:  
	
```
"destination_property" => "XX {{openr->get::foo}} YY",
```

If the source value retrieved was "Foo Bar", it would result in the following:  

```
"XX Foo Bar YY"
```  
This is designed to help remove limitations where you might want to embed a source value in a a destination property like:
```
"Cars in Lot: 24."
```  
Where "24" is the value pulled from the source arrays. That action string might look like this:
```
"Cars in Lot: {{openr->get::cars}}.",
```  

&nbsp;  

### Multiple String Replacement  
You can also embed multiple actions in your strings to add values from multiple properties in your source array(s).
```
"dest_replace" => "XX {{openr->get::foo}} is also {{openr->get::bar.baz}} XX",
```  
Would yield:
```
"XX prop_value_1 is also prop_value_2 XX",
```  

&nbsp;  

### Destination as Source Properties  
You can tell OPENR to use a property value from our destination array as a source property by passing the "__dest" parameter.  
	
When no destination parameter is supplied, OPENR defaults to the source data coming from the sources array.

#### Example
```
{{openr->get(__dest)::dest_foo}}
```  

&nbsp;  

### Working with Indexed Arrays
When specifying a source path for your action, you can explicitly assign an index number to it when the source is an indexed array.  

This example looks in the array "products" and gets the nTH array element.  
&nbsp;  
### Example
```
{{!openr->get::products.1.type}}
```  
		
By explicitly stating the number "1" here we are telling OPENR to get the source property "products", which is an indexed array, and get the value of "type" from the second array element (indexes go from 0>, so the second index would be 1 and the first would be 0 ).



&nbsp;  
&nbsp;  
&nbsp;  
END.