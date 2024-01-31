<?php
//URL of the website you want to scrape
$url = 'https://www.mobiledokan.com/samsung/';

// Initialize cURL session
$curl = curl_init();

// Set the cURL options
curl_setopt( $curl, CURLOPT_URL, $url );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true ); // Follow any redirects
curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER,
	false ); // Skip SSL verification (not recommended in production)

// Execute the cURL session
$response = curl_exec( $curl );

// Check for errors
if ( curl_errno( $curl ) ) {
	echo 'Curl error: ' . curl_error( $curl );
}

// Close the cURL session
curl_close( $curl );

////// Save the HTML content to a file in the current directory
//$file = fopen('links.html', 'w'); // Open a file for writing ('w' creates the file if it doesn't exist)
//fwrite($file, $response); // Write the HTML content to the file
//fclose($file); // Close the file

//// Open the saved HTML file
//$htmlFile = 'links.html';
//$htmlContent = file_get_contents( $htmlFile );

// Parse the HTML content using DOMDocument
$dom = new DOMDocument();
@$dom->loadHTML( $response );

// Initialize DOMXPath
$xpath = new DOMXPath( $dom );

// Use XPath query to select <a> tags inside <td> elements
$links = $xpath->query( "//td//a" );

$base_url  = 'https://www.mobiledokan.com/samsung/';
$all_links = [];
// Loop through the selected links and print their href attributes
foreach ( $links as $link ) {
	$query_var   = trim( $link->getAttribute( "href" ) );
	$full_url    = $base_url . $query_var;
	$all_links[] = $full_url;
}

$all_data = [];

foreach ( $all_links as $link ) {
	$data = [];
// URL of the website you want to scrape
//$url = 'https://www.mobiledokan.com/samsung/samsung-galaxy-s22-ultra/';

// Initialize cURL session
	$curl = curl_init();

// Set the cURL options
	curl_setopt( $curl, CURLOPT_URL, $link );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true ); // Follow any redirects
	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER,
		false ); // Skip SSL verification (not recommended in production)

// Execute the cURL session
	$response = curl_exec( $curl );

// Check for errors
	if ( curl_errno( $curl ) ) {
		echo 'Curl error: ' . curl_error( $curl );
	}

// Close the cURL session
	curl_close( $curl );

	// Parse the HTML content using DOMDocument
	$dom = new DOMDocument();
	@$dom->loadHTML( $response );

// Initialize DOMXPath
	$xpath = new DOMXPath( $dom );

// Find the specific element by class name
	$elements = $xpath->query( '//h1[@class="post-title entry-title"]' );

// Extract header text if element exists
	$name = '';
	if ( $elements->length > 0 ) {
		$name = $elements[0]->textContent;
	}
	$data['name'] = $name;

// Query the <figure> element
	$figure1= $xpath->query( '//figure[@class="wp-block-table aligncenter u-full-width"]' )
		        ->item( 0 );

// Find all <tr> elements within the <figure> element
	$cells = $xpath->query( './/tr', $figure1 );

// Loop through each <tr> element and output its text content
	$prices    = [];
	$_variants = [];
	foreach ( $cells as $cell ) {
		$price_array   = [];
		$tag_node      = $xpath->query( './/td[1]/strong', $cell )->item( 0 );
		$price_nodes   = $xpath->query( './/td[2]/strong', $cell );
		$variant_nodes = $xpath->query( './/td[2]/sub', $cell );

		foreach ( $price_nodes as $price_node ) {
			$prices[] = array(
				'name'  => $tag_node->textContent,
				'price' => intval( str_replace( [ '৳', ',' ], '',
					$price_node->textContent ) ),
			);
		}

		foreach ( $variant_nodes as $variant_node ) {
			if ( $variant_node ) {
				$_variant    = $variant_node->textContent;
				$_variants[] = trim( $variant_node->textContent );
			}
		}
	}

	$variants = array_filter( $_variants, function ( $variant ) {
		return strstr( $variant, '*', true ) !== '';
	} );

//Reindexing variants
	$variants = array_values( $variants );

// Check if the number of elements in $prices and $variants are the same
	if ( count( $prices ) === count( $variants ) ) {
		// Iterate over both arrays simultaneously
		for ( $i = 0; $i < count( $prices ); $i ++ ) {
			// Add each element from $variants into $prices with the key 'variant'
			$prices[ $i ]['variant'] = $variants[ $i ];
		}
	}

	$data['price'] = $prices;

// Query the <figure> element
	$figure2 = $xpath->query( '//figure[@class="wp-block-table aligncenter"]' )
	                 ->item( 0 );

// Find all <tr> elements within the <figure> element
	$specs = $xpath->query( './/tr', $figure2 );

	foreach ( $specs as $spec ) {
		$spec_name = '';
		$spec_desc = '';

		$spec_name_node = $xpath->query( './/td[1]', $spec )->item( 0 );
		$spec_desc_node = $xpath->query( './/td[2]', $spec )->item( 0 );

		if ( $spec_name_node ) {
			$spec_name = trim( strtolower( str_replace( ' ', '-',
				$spec_name_node->textContent ) ) );
		}

		if ( $spec_desc_node ) {
			$spec_desc = trim( $spec_desc_node->textContent );
		}

		$data[ $spec_name ] = $spec_desc;
	}

	$data = array_filter( $data, function ( $key, $value ) {
		return $key !== '';
	}, ARRAY_FILTER_USE_BOTH );

	foreach ( $data as $key => $value ) {

		$tick_key  = "\u2705";
		$cross_key = "\u2716";
		// Convert the Unicode code point to the actual character
		$tick  = json_decode( '"' . $tick_key . '"' );
		$cross = json_decode( '"' . $cross_key . '"' );

		if ( $value === $tick ) {
			$data[ $key ] = true;
		}

		if ( $value === $cross ) {
			$data[ $key ] = false;
		}

		if ( 'models' === $key ) {
			if ( strpos( $value, '&' ) !== false ) {
				$data['models'] = trim( strstr( $value, '&', true ) );
			}
		}

		if ( 'announced' === $key ) {
			$data['announced'] = strtotime( $value );
		}

		if ( 'first-release' === $key ) {
			$data['first-release'] = strtotime( $value );
		}
	}

	foreach ( $data as $key => $value ) {
		$tick_key = "\u2705";
		// Convert the Unicode code point to the actual character
		$tick = json_decode( '"' . $tick_key . '"' );

		if ( ( 'price' !== $key ) ) {
			if ( strpos( $value, $tick ) !== false ) {
				$data[ $key ] = trim( str_replace( $tick, '', $value ) );
			}
		}
	}

	$all_data[] = $data;
}

echo "<pre>";
print_r( $all_data );
echo "</pre>";



