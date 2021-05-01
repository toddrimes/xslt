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
        echo($output);
    } catch(Exception $e){
        echo $e;
    }
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); //convert JSON into array

$xmlFile = "";
$xslFile = "";

$milliseconds = getTimeString();

if (filter_var($input["xml"], FILTER_VALIDATE_URL)) {
    // Use basename() function to return the base name of file
    $file_name = basename($input["xml"]);
    $xmlFile = $milliseconds. ".xml";
    // Use file_get_contents() function to get the file
    // from url and use file_put_contents() function to
    // save the file by using base name
    if(file_put_contents( $xmlFile,file_get_contents($input["xml"]))) {
        // echo "XML downloaded successfully";
    }
    else {
        echo "XML downloading failed.";
    }
} else {
    if (!copy($input["xml"], $milliseconds. ".xml")) {
        echo "failed to copy " . $input['xml'] . "...\n";
    }
    $xmlFile = $milliseconds. ".xml";
}

if (filter_var($input["xsl"], FILTER_VALIDATE_URL)) {
    // Use basename() function to return the base name of file
    $file_name = basename($input["xsl"]);
    $xslFile = $milliseconds. ".xsl";

    // Use file_get_contents() function to get the file
    // from url and use file_put_contents() function to
    // save the file by using base name
    if(file_put_contents( $xslFile,file_get_contents($input["xsl"]))) {
        // echo "XSL downloaded successfully";
    }
    else {
        echo "XSL downloading failed.";
    }
} else {
    if (!copy($input["xsl"], $milliseconds. ".xsl")) {
        echo "failed to copy " . $input['xsl'] . "...\n";
    }
    $xslFile = $milliseconds. ".xsl";
}

transformLocal($xmlFile,$xslFile,$milliseconds);