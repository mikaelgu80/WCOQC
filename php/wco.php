<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\TblWidth;

\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);


$operation = $_POST['operation'];
$files = $_FILES;

$lib = '../uploads/library.xlsx';
$ois = '../uploads/OIS.xlsx';
move_uploaded_file($files['library']['tmp_name'], $lib);
move_uploaded_file($files['OIS']['tmp_name'], $ois);

$settingsFile = file_get_contents('settings.json');
$settings = json_decode($settingsFile, true);
$keysToKeep = array_merge($settings['libcolumns'], $settings['oiscolumns']);

$LIBreader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($lib);
$LIBreader->setLoadSheetsOnly(['DataSet 1.2, 1.3, 1.4']);
$LIBreader->setReadDataOnly(true);
$LIBworksheet = $LIBreader->load($lib);
$OISreader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($ois);
$OISreader->setLoadSheetsOnly(['OIS']);
$OISreader->setReadDataOnly(true);
$OISworksheet = $OISreader->load($ois);

foreach ($LIBworksheet->getAllSheets() as $sheet) {
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();
    $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

    $headerdata = $sheet->rangeToArray('A2:' . Coordinate::stringFromColumnIndex($highestColumnIndex - 12) . '2');

    $items = $sheet->rangeToArray('A3:' . Coordinate::stringFromColumnIndex($highestColumnIndex - 12) . $highestRow);
    foreach ($items as $value) {
        $LIBdata[] = array_combine($headerdata[0], $value);
    }
    foreach ($LIBdata as &$dataItem) {
        $dataItem = array_intersect_key($dataItem, array_flip($keysToKeep));
    }
    unset($dataItem);

    foreach ($LIBdata as $item) {
        $ID = empty($item['Attr. ID']) ? $item['Class ID'] : $item['Attr. ID'];
        $type = empty($item['Attr. ID']) ? 'class' : 'attribute';
        $name = empty($item['Attr. ID']) ? $item['Class Name'] : $item['Attribute Name'];
        $definition = $item['Definition'];
        $format = $item['Format'];

        $uniqueData[strval($ID)]['type'] = $type;
        $uniqueData[strval($ID)]['CDT'] = $item['Core Data Type'];
        $uniqueData[strval($ID)]['name'] = $name;
        $uniqueData[strval($ID)]['definition'] = $definition;
        $uniqueData[strval($ID)]['format'] = $format;

        $attrIDOccurrencesLIB[$ID]['ID'] = strval($ID);
        $attrIDOccurrencesLIB[$ID]['names'][$name] = $name;
        $attrIDOccurrencesLIB[$ID]['definitions'][$definition] = $definition;
        $attrIDOccurrencesLIB[$ID]['formats'][$format] = $format;
    }
    $multiples = array_filter($attrIDOccurrencesLIB, function ($value) {
        return count(array_unique($value['names'])) > 1 || count(array_unique($value['definitions'])) > 1 || count(array_unique($value['formats'])) > 1;
    });
}

$nameRules = [
    'Amount' => ['amount'],
    'Binary Object' => [''],
    'Code' => ['code'],
    'Date Time' => ['date/time'],
    'Identifier' => ['identifier'],
    'Indicator' => ['indicator'],
    'Measure' => ['tonnage', 'weight', 'volume', 'temperature'],
    'Numeric' => ['number', 'rate', 'percentage'],
    'Quantity' => ['quantity'], // or contains "number of" (handled separately).
    'Text' => ['']
];
$definitionRules = [
    'Amount' => ['Amount', 'Value'],
    'Binary Object' => [''],
    'Code' => ['Code specifying'],
    'Date Time' => ['Date and time'],
    'Identifier' => ['Identification of'],
    'Indicator' => ['Indicator'],
    'Measure' => [''],
    'Numeric' => ['Number', 'Rate', 'Percentage'],
    'Quantity' => ['Quantity specifying'],
    'Text' => ['']
];
switch ($operation) {
    case ('QC'):
        $settingsFile = file_get_contents('settings.json');
        $settings = json_decode($settingsFile, true);
        $columns = array_merge($settings['libcolumns'], $settings['oiscolumns']);
        $definitionErrors = [];
        $exceptionsPattern = implode('|', array_map('preg_quote', str_replace(' ', '-', $settings['exceptions']))); // Create regex-safe pattern
        $multiWordExceptions = array_filter($settings['exceptions'], function ($exception) { // Array for separately storing exceptions that are composed of multiple words (e.g. WCO Data Model)
            return strpos($exception, ' ') !== false; // Keep only exceptions with spaces
        });
        $multiWordExceptions = array_combine( // Modify the array so that the original values work as keys
            $multiWordExceptions,
            array_map(fn($exception) => str_replace(' ', '-', strtolower($exception)), $multiWordExceptions)
        );
        foreach ($uniqueData as $key => $value) {
            if (!str_ends_with($value['definition'], '.')) {
                $definitionErrors[$key] = ucfirst($value['type']) . '\' ' . $key . ' definition (' . $value['definition'] . ') does not end in a period.';
            }
            if ($value['type'] == 'class') {
                if (str_contains($value['name'], ' ')) {
                    $capitalization[$key] = 'Name for class ' . $key . ' (' . $value['name'] . ') contains spaces.';
                }
                if (!str_starts_with($value['definition'], 'Details related to')) {
                    $definitionErrors[$key] = 'Definition for class ' . $key . ' does not start with "Details related to".';
                }
            }
            if ($value['type'] == 'attribute') {
                foreach ($multiWordExceptions as $original => $modified) { // Replace the multi word exceptions into the strings
                    $value['name'] = str_replace($original, $modified, $value['name']);
                }
                $modifiedName = preg_replace_callback( // Replace exceptions with a placeholder to bypass checking them
                    "/\b($exceptionsPattern)\b/",
                    function ($matches) {
                        return strtolower($matches[0]); // Convert only matched exceptions to lowercase
                    },
                    $value['name']
                );
                $firstWord = explode(' ', $value['name'])[0]; // Extract the first word from the name
                if (in_array($firstWord, $settings['exceptions']) || in_array($firstWord, $multiWordExceptions)) {
                    $modifiedName = ucfirst($modifiedName); // Convert the first character to upper case
                }

                if (!preg_match('/^[A-Z][a-z\-\/\',().]*(\s\(?[a-z\-\/\',().]*\)?)*$/', $modifiedName)) { // Check that the name matches the defined pattern
                    $capitalization[$key] = 'Name for attribute ' . $key . ' (' . $value['name'] . ') has possible incorrect capitalization.';
                };
            }
            if (!empty($value['CDT'])) {
                $validName = false; // Assume it's invalid initially
                $validDefinition = false; // Assume it's invalid initially

                foreach ($nameRules[$value['CDT']] as $suffix) {
                    if (str_ends_with($value['name'], $suffix)) {
                        $validName = true; // Found a valid match
                        break; // No need to check further
                    }
                    if ($value['CDT'] == 'Quantity' && str_contains(strtolower($value['name']), 'number of')) { // "number of" does not have to be at the end and needs different processing.
                        $validName = true; // Found a valid match
                    }
                }
                foreach ($definitionRules[$value['CDT']] as $suffix) {
                    if (str_starts_with($value['definition'], $suffix)) {
                        $validDefinition = true; // Found a valid match
                        //break; // No need to check further
                    }
                }
                if (in_array($value['CDT'], ['Amount', 'Measure', 'Numeric', 'Quantity']) && substr($value['format'], 0, 1) != 'n') { // Similar processing for CDT Indicator once it has been agreed on
                    $formatErrors[$key] = 'Attribute\'s ' . $key . ' (' . $value['name'] . ', CDT: ' . $value['CDT'] . ') format (' . $value['format'] . ') might be conflicting.';
                }

                if (!$validName) {
                    $namingErrors[$key] = 'Attribute ' . $key . ' (' . $value['name'] . ') does not follow the rules [CDT: ' . $value['CDT'] . ' should end in ' . implode(', ', $nameRules[$value['CDT']]) . '].';
                }
                if (!$validDefinition) {
                    if (array_key_exists($key, $definitionErrors)) {
                        $definitionErrors[$key] = substr($definitionErrors[$key], 0, -1) . ' and it does not follow the rules [CDT: ' . $value['CDT'] . ' should begin with "' . implode('", "', $definitionRules[$value['CDT']]) . '"].';
                    } else {
                        $definitionErrors[$key] = 'Attribute\'s ' . $key . ' definition (' . $value['definition'] . ') does not follow the rules [CDT: ' . $value['CDT'] . ' should begin with "' . implode('", "', $definitionRules[$value['CDT']]) . '"].';
                    }
                }
            }
        }
        if (!empty($capitalization)) {
            ksort($capitalization);
            echo '<h2>Capitalization</h2>';
            echo implode('<br>', $capitalization);
        }
        if (!empty($namingErrors)) {
            ksort($namingErrors);
            echo '<h2>Naming discrepancies</h2>';
            echo implode('<br>', $namingErrors);
        }
        if (!empty($definitionErrors)) {
            ksort($definitionErrors);
            echo '<h2>Definition discrepancies</h2>';
            echo implode('<br>', $definitionErrors);
        }
        if (!empty($formatErrors)) {
            ksort($formatErrors);
            echo '<h2>Format discrepancies</h2>';
            echo implode('<br>', $formatErrors);
        }
        foreach ($OISworksheet->getAllSheets() as $sheet) {
            $highestRow = $sheet->getHighestRow();

            $headerdata = $sheet->rangeToArray('A3:G3');

            $items = $sheet->rangeToArray('A4:G' . $highestRow);
            foreach ($items as $value) {
                $OISdata[] = array_combine($headerdata[0], $value);
            }

            foreach ($OISdata as &$dataItem) {
                $dataItem = array_intersect_key($dataItem, array_flip($keysToKeep));
            }
            unset($dataItem);

            foreach ($OISdata as $item) {
                $ID = $item['ID'];
                $path = $item['UML Model Path'];
                $name = $item['Name'];
                $definition = $item['Definition'];
                $format = $item['Format'];

                $attrIDOccurrencesOIS[$ID]['ID'] = strval($ID);
                $attrIDOccurrencesOIS[$ID]['names'][$name] = $name;
                $attrIDOccurrencesOIS[$ID]['definitions'][$definition] = $definition;
                $attrIDOccurrencesOIS[$ID]['formats'][$format] = $format;
            }
            $OISmultiples = array_filter($attrIDOccurrencesOIS, function ($value) {
                return count(array_unique($value['names'])) > 1 || count(array_unique($value['definitions'])) > 1 || count(array_unique($value['formats'])) > 1;
            });
        }
        $overall = $attrIDOccurrencesLIB;

        foreach ($attrIDOccurrencesOIS as $key => $value) {
            if (isset($overall[$key])) {
                // Merge sub-arrays
                foreach ($value as $subKey => $subValue) {
                    if (is_array($subValue)) {
                        // Merge arrays inside the main array (names, definitions, formats)
                        $overall[$key][$subKey] = array_merge($overall[$key][$subKey], $subValue);
                    } else {
                        // Overwrite scalar values
                        $overall[$key][$subKey] = $subValue;
                    }
                }
            } else {
                // If the key doesn't exist, just add it
                $overall[$key] = $value;
            }
        }

        $discrepancies = array_filter($overall, function ($value) {
            return count(array_unique($value['names'])) > 1 || count(array_unique($value['definitions'])) > 1 || count(array_unique($value['formats'])) > 1;
        });

        $toRemove = [$OISmultiples, $multiples]; // Add all arrays to subtract

        foreach ($toRemove as $removalArray) {
            foreach ($removalArray as $key => $value) {
                if (isset($discrepancies[$key]) && $discrepancies[$key] === $value) {
                    unset($discrepancies[$key]);
                }
            }
        }
        displayDiscrepancyInfo("Library", $multiples);
        displayDiscrepancyInfo("Overall information structure", $OISmultiples);
        displayDiscrepancyInfo("Cross check between library and OIS", $discrepancies);
        break;
    case ('spellcheck'): // This is not used
        // Create new PHPWord object
        $phpWord = new PhpWord();

        // Add a new section
        $section = $phpWord->addSection();

        // Add table with headers
        $table = $section->addTable();

        // Add table headers
        $table->addRow();
        $table->addCell(3000)->addText('ID');
        $table->addCell(4000)->addText('Name');
        $table->addCell()->addText('Definition');

        // Add data rows
        foreach ($uniqueData as $id => $item) {
            // Add the row only if it has at least one valid value
            $table->addRow();
            $table->addCell()->addText($id);  // ID from key
            $table->addCell()->addText($item['name']);
            $table->addCell()->addText($item['definition']);
        }

        // Save the document
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $phpWord->save('output.docx', 'Word2007');
        echo 'output.docx';
        break;
}

function displayDiscrepancyInfo($title, $array)
{
    echo "<h2>$title</h2>";
    $count = count($array);

    if ($count > 0) {
        echo "$count " . ($count == 1 ? "discrepancy was" : "discrepancies were") . " discovered:";

        foreach ($array as $ID => $attributes) {
            echo '<h3>Data with ID <b>' . $ID . '</b>:</h3>';

            foreach ($attributes as $type => $attribute) {
                if (is_array($attribute) && count($attribute) > 1) {
                    echo '<p><b>Multiple ' . $type . ': <br></b><ol>';

                    $first = true; // Flag for the first item
                    foreach ($attribute as $value) {
                        $class = $first ? 'original' : 'changed'; // Assign class based on flag
                        echo '<li class="' . $class . '">' . htmlspecialchars($value) . '</li>';
                        $first = false; // After first iteration, mark flag as false
                    }

                    echo '</ol>';
                    //echo '<div class="diff1"></div>';
                }
            }
        }
    } else {
        echo "No discrepancies.";
    }
}