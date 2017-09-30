<?php
/**
 * Returns an array of Kirby URIs by letter of field
 */
function sortPagesBy( $source, $userOptions=NULL ) {

	// Make sure we have a valid source
	if (!method_exists($source, 'slice')) {
		throw new Exception('pagesByDate requires `$pages` or a similar Kirby Pages object as first argument');
	}

	// Merge options
	$defaults = array(
		'order' =>   c::get('sortpagesby.order', 'asc'),
		'limit' =>   c::get('sortpagesby.limit', 100),
		'offset' =>  c::get('sortpagesby.offset', 0),
		'max' =>     c::get('sortpagesby.max', time()),
		'min' =>     c::get('sortpagesby.min', 0),
		'group' =>   c::get('sortpagesby.group', 'none')
	);
	$options = is_array($userOptions) ? array_merge($defaults, $userOptions) : $defaults;

	// Normalize some options
	if (!is_int($options['limit'])) $options['limit'] = $defaults['limit'];
	if (!is_int($options['offset'])) $options['offset'] = $defaults['offset'];
	if (is_string($options['max'])) $options['max'] = strtotime($options['max']);
	if (is_string($options['min'])) $options['min'] = strtotime($options['min']);

	// Order source content
	$source = $source->sortBy('title', $options['order']);

	// We'll return $results in the end
	$temp = array();
	$results = array();

	// 1. Validate each page based on status metadata and integer dates
	foreach ($source as $page) {
		$status = $page->status() ? strtolower($page->status()) : 'unknown';
		$date = $page->date(); // false when no date, integer timestamp otherwise
		if (
			$status !== 'archive' && $status !== 'draft' && $status !== 'ignore'
			&& $date !== false && $date >= $options['min'] && $date <= $options['max']
		) {
			$temp[] = $page;
		}
	}

	// 2. Apply offset/limit on the validated pages
	$temp = array_slice($temp, $options['offset'], $options['limit']);

	// 3. Populate $results with page URIs
	if ($options['group'] == 'letter') {
		// Grouping by field
		foreach ($temp as $page) {
			$letter = substr($page->title()->value(), 0, 1);
			if (!array_key_exists($letter, $results)) {
				$results[$letter] = array();
			}
			$results[$letter][] = $page->uri();
		}
		return $results;
	}
	elseif ($options['group'] != 'none') {
		// Grouping by field
		foreach ($temp as $page) {
			$f = $options['group'];
			$field = $page->$f()->value();
			if (!array_key_exists($field, $results)) {
				$results[$field] = array();
			}
			$results[$field][] = $page->uri();
		}
		return $results;
	}
	else {
		// Returning URIs from page objects in $temp as-is.
		foreach ($temp as $page) {
			$results[] = $page->uri();
		}
		return $results;
	}
}

?>