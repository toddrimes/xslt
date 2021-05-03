<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$fieldOrder = array(
    "version",
    "identifier",
    "title",
    "language",
    "layout",
    "subtitle",
    "metadata",
    "documentStyle",
    "components",
    "componentTextStyles",
    "componentLayouts"
);

$opts = array(
    'http' => array(
        'user_agent' => 'PHP libxml agent',
    )
);

$context = stream_context_create($opts);
libxml_set_streams_context($context);

function getTimeString() {
    return "" . intval(microtime(true) * 1000);
}

function transformLocal($xmlFile, $xslFile, $timeString) {
    $xml = new DomDocument();
    $file = file_get_contents($xmlFile, FILE_USE_INCLUDE_PATH);
    $xml->loadXML($file);

    $xsl = new DomDocument();
    $file = file_get_contents($xslFile, FILE_USE_INCLUDE_PATH);
    $xsl->loadXML($file);

    transform($xml,$xsl,$timeString);
}

function transform($xml,$xsl,$timeString) {
    $xslt = new XSLTProcessor();
    $xslt->importStylesheet($xsl);
    header('Content-Type: application/xml; charset=utf-8');
    try{
        $output = $xslt->transformToXml($xml);
        file_put_contents($timeString. "-OUT.xml",$output);
        $output=preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $output);

        $json = json_encode(simplexml_load_string($output));

        // $newArr = json_decode($xml, true);
        echo($json);
        file_put_contents($timeString. "-OUT.json",$json);
        //print_r($newArr);

    } catch(Exception $e){
        echo $e;
    }
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); //convert JSON into array

$xmlFile = "";
$xslFile = "";

$milliseconds = getTimeString();

function setXmlFile() {
    global $milliseconds, $input;
    $file_name = $milliseconds. ".xml";
    if (filter_var($input["xml"], FILTER_VALIDATE_URL)) {
        if(!file_put_contents( $file_name,file_get_contents($input["xml"]))) {
            echo "XML downloading failed.";
        }
    } else {
        if (!copy($input["xml"], $file_name)) {
            echo "failed to copy " . $input['xml'] . "...\n";
            return false;
        }
    }
    return $file_name;
}

function setXslFile() {
    global $milliseconds, $input;
    $file_name = $milliseconds. ".xsl";
    if (filter_var($input["xsl"], FILTER_VALIDATE_URL)) {
        if(!file_put_contents( $file_name,file_get_contents($input["xsl"]))) {
            echo "XSL downloading failed.";
        }
    } else {
        if (!copy($input["xsl"], $file_name)) {
            echo "failed to copy " . $input['xsl'] . "...\n";
            return false;
        }
    }
    return $file_name;
}

function htmlIterator($str)
{
    $offset = 0;
    $i = 0;
    preg_match_all('/<(p|h1|h2|h3|figure).?>(.+?)<\/(p|h1|h2|h3|figure)>/', $str, $m, PREG_OFFSET_CAPTURE, $offset);
    return $m;
}

function splitStories($timeString) {
    global $fieldOrder;
    $json = file_get_contents($timeString. "-OUT.json", FILE_USE_INCLUDE_PATH);
    $objectFeed = json_decode($json);
    foreach ($objectFeed->channel->item as &$story) {
        $cleanStoryId = stripslashes($story->identifier);
        $guid = substr($cleanStoryId, strrpos($cleanStoryId, '/') + 1);
        $components = array();
        foreach ($story->components->element as $component) {
            $name = "@attributes";
            if (!$component->$name) {
                array_push($components, $component);
            } else {
                $html = $component->html;
                $htmlMap = htmlIterator($html);
                $len = sizeof($htmlMap[1]);
                for($i=0;$i<$len;$i++) {
                    $newComponent = new stdClass();
                    switch($htmlMap[1][$i][0]) {
                        case 'h1':
                            $newComponent->role="heading1";
                            $newComponent->text=str_replace('\u00a0', " ",$htmlMap[2][$i][0]);
                            $newComponent->format="html";
                            break;
                        case 'h2':
                            $newComponent->role="heading2";
                            $newComponent->text=str_replace('\u00a0', " ",$htmlMap[2][$i][0]);
                            $newComponent->format="html";
                            break;
                        case 'h3':
                            $newComponent->role="heading3";
                            $newComponent->text=str_replace('\u00a0', " ",$htmlMap[2][$i][0]);
                            $newComponent->format="html";
                            break;
                        case 'figure':
                            $newComponent->role="figure";
                            preg_match_all('/\b(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[-A-Z0-9+&@#\/%=~_|$?!:,.]*[A-Z0-9+&@#\/%=~_|$]/i', $htmlMap[2][$i][0], $result, PREG_PATTERN_ORDER);
                            $newComponent->URL=$result[0][0];
                            break;
                        default:
                            $newComponent->role="body";
                            $newComponent->text=str_replace('\u00a0', " ",$htmlMap[2][$i][0]);
                            $newComponent->layout="bodyLayout";
                            $newComponent->textStyle="bodyStyle";
                            $newComponent->format="html";
                            break;
                    }
                    array_push($components, $newComponent);
                }
            }
        }
        $story->components = $components;
        $storyArray = (array) $story;
        $orderedStory = array();
        foreach($fieldOrder as $currentPiece) {
            $orderedStory[$currentPiece] = $storyArray[$currentPiece];
        }
        $orderedStory["version"] = "REPLACE_THIS_VERSION";
        if (!mkdir($guid, 0777, true)) {
            //die('Failed to create folders...');
        }
        $lastChance = json_encode($orderedStory, JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK);
        $lastChance = str_replace('REPLACE_THIS_VERSION', "1.0",$lastChance);
        $lastChance = str_replace('&nbsp;', " ",$lastChance);
        $lastChance = preg_replace('/\xc2\xa0/', '', $lastChance);
        file_put_contents( "$guid/article.json",$lastChance);
    }
}

function cleanup($timeString) {
    unlink($timeString . ".xml");
    unlink($timeString . ".xsl");
    unlink($timeString . "-OUT.xml");
}


// ACTION
$xmlFile = setXmlFile();
$xslFile = setXslFile();
transformLocal($xmlFile,$xslFile,$milliseconds);
cleanup($milliseconds);
splitStories($milliseconds);