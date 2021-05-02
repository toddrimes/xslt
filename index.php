<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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


/**
 * @param SimpleXMLElement $xml
 * @return array
 */
/*function xmlToArray(SimpleXMLElement $xml)
{
    $parser = function (SimpleXMLElement $xml, array $collection = []) use (&$parser) {
        $nodes = $xml->children();
        $attributes = $xml->attributes();

        if (0 !== count($attributes)) {
            foreach ($attributes as $attrName => $attrValue) {
                $collection['attributes'][$attrName] = strval($attrValue);
            }
        }

        if (0 === $nodes->count()) {
            $collection['value'] = strval($xml);
            return $collection;
        }

        foreach ($nodes as $nodeName => $nodeValue) {
            if (count($nodeValue->xpath('../' . $nodeName)) < 2) {
                $collection[$nodeName] = $parser($nodeValue);
                continue;
            }

            $collection[$nodeName][] = $parser($nodeValue);
        }

        return $collection;
    };

    return [
        $xml->getName() => $parser($xml)
    ];
}*/

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
    while ( preg_match_all('/<(p|h1|h2|h3|figure).?>(.+?)<\/(p|h1|h2|h3|figure)>/', $str, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $result = new stdClass();
        $offset = $m[0][1] + strlen($m[0][0]);
        $result->tag = $m[1][$i][0];
        $result->content = $m[2][$i][0];
        $i++;
        yield $result;
    }
}

function splitStories($timeString) {
    $json = file_get_contents($timeString. "-OUT.json", FILE_USE_INCLUDE_PATH);
    $objectFeed = json_decode($json);
    foreach ($objectFeed->channel->item as &$story) {
        $cleanStoryId = stripslashes($story->identifier);
        preg_match("/\/(.+)$/",$cleanStoryId,$matches);
        $guid=$matches[1];
        $guid = ltrim($guid, '/');
        $guid = str_replace('/','_',$guid);
        $components = array();
        foreach ($story->components->element as $component) {
            $name = "@attributes";
            if (!$component->$name) {
                array_push($components, $component);
            } else {
                $html = $component->html;
                foreach( htmlIterator($html) as $element) {
                    $newComponent = stdClass();
                    switch($element->tag) {
                        case 'h1':
                            $newComponent->role="heading1";
                            $newComponent->text=$element->content;
                            break;
                        case 'h2':
                            $newComponent->role="heading2";
                            $newComponent->text=$element->content;
                            break;
                        case 'h3':
                            $newComponent->role="heading3";
                            $newComponent->text=$element->content;
                            break;
                        case 'figure':
                            $newComponent->role="figure";
                            preg_match_all('/\b(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[-A-Z0-9+&@#\/%=~_|$?!:,.]*[A-Z0-9+&@#\/%=~_|$]/i', $element[1], $result, PREG_PATTERN_ORDER);
                            $newComponent->URL=$result->content;
                            break;
                        default:
                            $newComponent->role="body";
                            $newComponent->text=$element->content;
                            break;
                    }
                    array_push($components, $newComponent);
                }
            }
        }
        $story->components = $components;
        file_put_contents($guid,json_encode($story));
    }
}

function cleanup($timeString) {
    unlink($timeString . ".xml");
    unlink($timeString . ".xsl");
    unlink($timeString . "-OUT.xml");
}

$xmlFile = setXmlFile();
$xslFile = setXslFile();
transformLocal($xmlFile,$xslFile,$milliseconds);
cleanup($milliseconds);
splitStories($milliseconds);