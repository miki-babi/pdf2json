<?php
require 'vendor/autoload.php';
use Smalot\PdfParser\Parser;

function parsePdf($filePath) {
    $parser = new Parser();
    $pdf = $parser->parseFile($filePath);
    return $pdf->getText();
}

// input pdf file
$input_file_path = './input_file.pdf';
//output json file
$output_file_path = './output_file.json';


if (!file_exists($input_file_path)) {
    die("Input file not found.");
}


$text = parsePdf($input_file_path);

$lines = explode("\n", $text);

// Keywords to search for 
$keywords = ["No", "NAME", "FNAME", "GFNAME", "REQUEST"];

// Function to check if a line contains the keyword letter by letter in sequence
function contains_keyword($line, $keyword) {
    $line = strtolower($line);
    $keyword = strtolower($keyword);
    $j = 0;
    for ($i = 0; $i < strlen($line); $i++) {
        if ($line[$i] == $keyword[$j]) {
            $j++;
        }
        if ($j == strlen($keyword)) {
            return true;
        }
    }
    return false;
}

// Variables to keep track of the line with the most keywords
$max_keywords_count = 0;
$line_number_with_most_keywords = -1;

// Find the line with the most keywords and determine where to start
foreach ($lines as $lineNumber => $line) {
    // Count the number of keywords in the current line
    $keywords_count = 0;
    foreach ($keywords as $keyword) {
        if (contains_keyword($line, $keyword)) {
            $keywords_count++;
        }
    }

    // Update if this line has more keywords than the previous maximum
    if ($keywords_count > $max_keywords_count) {
        $max_keywords_count = $keywords_count;
        $line_number_with_most_keywords = $lineNumber;
    }
}

// Check if we found the line with the most keywords
if ($line_number_with_most_keywords != -1) {
    // Find the line that starts with "1" after the line with the most keywords
    $data_start_index = -1;
    for ($i = $line_number_with_most_keywords + 1; $i < count($lines); $i++) {
        if (trim(substr($lines[$i], 0, 1)) === '1') {
            $data_start_index = $i;
            break;
        }
    }

    // Check if it is 
    if ($data_start_index === -1) {
        die("Data section not found.");
    }

    // Extract lines 
    $data_lines = array_slice($lines, $data_start_index);

    // an empty array for JSON data
    $json_data = [];

    // Process each line
    foreach ($data_lines as $line) {
        // Skip empty lines
        if (trim($line) === '') {
            continue;
        }

        // Split the line by spaces (assuming consistent spacing between fields)
        $parts = preg_split('/\s+/', trim($line));

        // Skip lines that don't have enough parts
        if (count($parts) < 5) {
            continue;
        }


        if (isset($parts[1]) && isset($parts[2]) && isset($parts[3]) && isset($parts[4])) {
            // Map to JSON fields
            $json_data[] = [
                'name' => $parts[1],
                'fname' => $parts[2],
                'lname' => $parts[3],
                'rnum' => $parts[4],
            ];
        }
    }

    // Convert the array to JSON 
    $json_output = json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Write the JSON to the file
    file_put_contents($output_file_path, $json_output);


    echo "JSON data has been written to $output_file_path\n";
} else {
    echo "No suitable data section found.\n";
}
?>
