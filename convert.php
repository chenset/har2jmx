<?php
$json = json_decode(base64_decode($_POST['text']), true);
if ($json === null) {
    die('JSON 无法解析');
}

if (!isset($json['log']['entries'])) {
    die('JSON格式不正确');
}

$dom = new \DOMDocument('1.0', 'UTF-8');
$root = $dom->createElement("jmeterTestPlan");
$dom->appendChild($root);
$root->setAttribute('version', '1.2');
$root->setAttribute('properties', '4.0');
$root->setAttribute('jmeter', '4.0');
$hashTree = $dom->createElement('hashTree');
$root->appendChild($hashTree);

foreach ($json['log']['entries'] as $entry) {
    $HTTPSamplerProxy = $dom->createElement('HTTPSamplerProxy');
    $hashTree->appendChild($HTTPSamplerProxy);
    $HTTPSamplerProxy->setAttribute('guiclass', 'HttpTestSampleGui');
    $HTTPSamplerProxy->setAttribute('testclass', 'HTTPSamplerProxy');
    $HTTPSamplerProxy->setAttribute('testname', $entry['request']['method'] . '  ' . explode('?', $entry['request']['url'])[0]);
    $HTTPSamplerProxy->setAttribute('enabled', 'true');
    $elementProp = $dom->createElement('elementProp');
    $HTTPSamplerProxy->appendChild($elementProp);
    $elementProp->setAttribute('name', 'HTTPsampler.Arguments');
    $elementProp->setAttribute('elementType', 'Arguments');
    $elementProp->setAttribute('guiclass', 'HTTPArgumentsPanel');
    $elementProp->setAttribute('testclass', 'Arguments');
    $elementProp->setAttribute('testname', 'User Defined Variables');
    $elementProp->setAttribute('enabled', 'true');

    $collectionProp = $dom->createElement('collectionProp');
    $elementProp->appendChild($collectionProp);
    $collectionProp->setAttribute('name', 'Arguments.arguments');

    //for CSV
    $csvVariableNames = [];
    $increaseVariableName = 'a';

    //HTTP body
    if (isset($entry['request']['postData']['text']) && (in_array(strtolower($entry['request']['method']), ['put', 'patch']) || null !== json_decode(stripslashes(urldecode($entry['request']['postData']['text'])), true))) {
        //PUT & PATCH or JSON HTTP body
        //using postBodyRaw
        $boolProp = $dom->createElement('boolProp');
        $boolProp->setAttribute('name', 'HTTPSampler.postBodyRaw');
        $boolProp->nodeValue = 'true';
        $HTTPSamplerProxy->appendChild($boolProp);

        //variables
        $elementProp = $dom->createElement('elementProp');
        $elementProp->setAttribute('name', '');
        $elementProp->setAttribute('elementType', 'HTTPArgument');
        $boolProp = $dom->createElement('boolProp');
        $boolProp->setAttribute('name', 'HTTPArgument.always_encode');
        $boolProp->nodeValue = 'false';
        $elementProp->appendChild($boolProp);
        $stringProp = $dom->createElement('stringProp');
        $stringProp->setAttribute('name', 'Argument.value');
        $stringProp->appendChild($dom->createCDATASection(urldecode($entry['request']['postData']['text'])));
        $elementProp->appendChild($stringProp);
        $stringProp = $dom->createElement('stringProp');
        $stringProp->setAttribute('name', 'Argument.metadata');
        $stringProp->nodeValue = '=';
        $elementProp->appendChild($stringProp);
        $collectionProp->appendChild($elementProp);
    } else if (isset($entry['request']['postData']['params'])) {
        //POST
        foreach ($entry['request']['postData']['params'] as $param) {
            $elementProp = $dom->createElement('elementProp');
            $elementProp->setAttribute('name', urldecode($param['name']));
            $elementProp->setAttribute('elementType', 'HTTPArgument');
            $boolProp = $dom->createElement('boolProp');
            $boolProp->setAttribute('name', 'HTTPArgument.always_encode');
            $boolProp->nodeValue = 'true';
            $elementProp->appendChild($boolProp);
            $stringProp = $dom->createElement('stringProp');
            $stringProp->setAttribute('name', 'Argument.value');
            if (isset($_POST['type']) && $_POST['type'] == 2) {
                $stringProp->appendChild($dom->createCDATASection('${' . $increaseVariableName . '}'));
            } else {
                $stringProp->appendChild($dom->createCDATASection(urldecode($param['value'])));
            }
            $elementProp->appendChild($stringProp);
            $stringProp = $dom->createElement('stringProp');
            $stringProp->setAttribute('name', 'Argument.metadata');
            $stringProp->nodeValue = '=';
            $elementProp->appendChild($stringProp);
            $boolProp = $dom->createElement('boolProp');
            $boolProp->setAttribute('name', 'HTTPArgument.use_equals');
            $boolProp->nodeValue = 'true';
            $elementProp->appendChild($boolProp);
            $stringProp = $dom->createElement('stringProp');
            $stringProp->setAttribute('name', 'Argument.name');
            $stringProp->nodeValue = urldecode($param['name']);
            $elementProp->appendChild($stringProp);
            $collectionProp->appendChild($elementProp);

            $csvVariableNames[] = $increaseVariableName++;
        }
    }

//GET
    if (isset($entry['request']['queryString']) && strtolower($entry['request']['method']) === 'get') {
        foreach ($entry['request']['queryString'] as $queryString) {
            $elementProp = $dom->createElement('elementProp');
            $elementProp->setAttribute('name', urldecode($queryString['name']));
            $elementProp->setAttribute('elementType', 'HTTPArgument');
            $boolProp = $dom->createElement('boolProp');
            $boolProp->setAttribute('name', 'HTTPArgument.always_encode');
            $boolProp->nodeValue = 'true';
            $elementProp->appendChild($boolProp);
            $stringProp = $dom->createElement('stringProp');
            $stringProp->setAttribute('name', 'Argument.value');
            if (isset($_POST['type']) && $_POST['type'] == 2) {
                $stringProp->appendChild($dom->createCDATASection('${' . $increaseVariableName . '}'));
            } else {
                $stringProp->appendChild($dom->createCDATASection(urldecode($queryString['value'])));
            }
            $elementProp->appendChild($stringProp);
            $stringProp = $dom->createElement('stringProp');
            $stringProp->setAttribute('name', 'Argument.metadata');
            $stringProp->nodeValue = '=';
            $elementProp->appendChild($stringProp);
            $boolProp = $dom->createElement('boolProp');
            $boolProp->setAttribute('name', 'HTTPArgument.use_equals');
            $boolProp->nodeValue = 'true';
            $elementProp->appendChild($boolProp);
            $stringProp = $dom->createElement('stringProp');
            $stringProp->setAttribute('name', 'Argument.name');
            $stringProp->nodeValue = urldecode($queryString['name']);
            $elementProp->appendChild($stringProp);
            $collectionProp->appendChild($elementProp);

            $csvVariableNames[] = $increaseVariableName++;
        }
    }

    $url = parse_url($entry['request']['url']);

    $stringProp = $dom->createElement('stringProp');
    $HTTPSamplerProxy->appendChild($stringProp);
    $stringProp->setAttribute('name', 'HTTPSampler.domain');
    $stringProp->nodeValue = $url['host'];

    $stringProp = $dom->createElement('stringProp');
    $HTTPSamplerProxy->appendChild($stringProp);
    $stringProp->setAttribute('name', 'HTTPSampler.port');
    $stringProp->nodeValue = isset($url['port']) ? $url['port'] : (strtolower($url['scheme']) === 'https' ? 443 : 80);

    $stringProp = $dom->createElement('stringProp');
    $HTTPSamplerProxy->appendChild($stringProp);
    $stringProp->setAttribute('name', 'HTTPSampler.protocol');
    $stringProp->nodeValue = $url['scheme'];

    $stringProp = $dom->createElement('stringProp');
    $HTTPSamplerProxy->appendChild($stringProp);
    $stringProp->setAttribute('name', 'HTTPSampler.contentEncoding');
    $stringProp->nodeValue = 'utf-8';

    $stringProp = $dom->createElement('stringProp');
    $HTTPSamplerProxy->appendChild($stringProp);
    $stringProp->setAttribute('name', 'HTTPSampler.path');
    $stringProp->nodeValue = $url['path'];

    $stringProp = $dom->createElement('stringProp');
    $HTTPSamplerProxy->appendChild($stringProp);
    $stringProp->setAttribute('name', 'HTTPSampler.method');
    $stringProp->nodeValue = $entry['request']['method'];

    $boolProp = $dom->createElement('boolProp');
    $HTTPSamplerProxy->appendChild($boolProp);
    $boolProp->setAttribute('name', 'HTTPSampler.follow_redirects');
    $boolProp->nodeValue = 'false';

    $boolProp = $dom->createElement('boolProp');
    $HTTPSamplerProxy->appendChild($boolProp);
    $boolProp->setAttribute('name', 'HTTPSampler.auto_redirects');
    $boolProp->nodeValue = 'false';

    $boolProp = $dom->createElement('boolProp');
    $HTTPSamplerProxy->appendChild($boolProp);
    $boolProp->setAttribute('name', 'HTTPSampler.use_keepalive');
    $boolProp->nodeValue = 'true';

//todo 判断
    $boolProp = $dom->createElement('boolProp');
    $HTTPSamplerProxy->appendChild($boolProp);
    $boolProp->setAttribute('name', 'HTTPSampler.DO_MULTIPART_POST');
    $boolProp->nodeValue = 'false';

    $stringProp = $dom->createElement('stringProp');
    $HTTPSamplerProxy->appendChild($stringProp);
    $stringProp->setAttribute('name', 'HTTPSampler.embedded_url_re');
    $stringProp->nodeValue = '';

    $stringProp = $dom->createElement('stringProp');
    $HTTPSamplerProxy->appendChild($stringProp);
    $stringProp->setAttribute('name', 'HTTPSampler.connect_timeout');
    $stringProp->nodeValue = '';

    $stringProp = $dom->createElement('stringProp');
    $HTTPSamplerProxy->appendChild($stringProp);
    $stringProp->setAttribute('name', 'HTTPSampler.response_timeout');
    $stringProp->nodeValue = '';

    $hashTree2 = $dom->createElement('hashTree');
    $hashTree->appendChild($hashTree2);

    $HeaderManager = $dom->createElement('HeaderManager');
    $hashTree2->appendChild($HeaderManager);
    $HeaderManager->setAttribute('guiclass', 'HeaderPanel');
    $HeaderManager->setAttribute('testclass', 'HeaderManager');
    $HeaderManager->setAttribute('testname', 'HTTP Header Manager');
    $HeaderManager->setAttribute('enabled', 'true');

    $collectionProp = $dom->createElement('collectionProp');
    $HeaderManager->appendChild($collectionProp);
    $collectionProp->setAttribute('name', 'HeaderManager.headers');

    foreach ($entry['request']['headers'] as $header) {
        $elementProp = $dom->createElement('elementProp');
        $collectionProp->appendChild($elementProp);
        $elementProp->setAttribute('name', '');
        $elementProp->setAttribute('elementType', 'Header');

        $stringProp = $dom->createElement('stringProp');
        $elementProp->appendChild($stringProp);
        $stringProp->setAttribute('name', 'Header.name');
        $stringProp->appendChild($dom->createCDATASection($header['name']));

        $stringProp = $dom->createElement('stringProp');
        $elementProp->appendChild($stringProp);
        $stringProp->setAttribute('name', 'Header.value');
        $stringProp->appendChild($dom->createCDATASection($header['value']));
    }

//CSV Data Set Config, 'put/patch' does not support
    if (isset($_POST['type']) && $_POST['type'] == 2 && !in_array(strtolower($entry['request']['method']), ['put', 'patch'])) {
        $hashTree2->appendChild($dom->createElement('hashTree'));//empty node

        $CSVDataSet = $dom->createElement('CSVDataSet');
        $hashTree2->appendChild($CSVDataSet);
        $CSVDataSet->setAttribute('guiclass', 'TestBeanGUI');
        $CSVDataSet->setAttribute('testclass', 'CSVDataSet');
        $CSVDataSet->setAttribute('testname', 'CSV Data Set Config');
        $CSVDataSet->setAttribute('enabled', 'true');

        $stringProp = $dom->createElement('stringProp');
        $CSVDataSet->appendChild($stringProp);
        $stringProp->setAttribute('name', 'delimiter');
        $stringProp->nodeValue = ',';

        $stringProp = $dom->createElement('stringProp');
        $CSVDataSet->appendChild($stringProp);
        $stringProp->setAttribute('name', 'fileEncoding');
        $stringProp->nodeValue = 'UTF-8';

//        $stringProp = $dom->createElement('stringProp');
//        $CSVDataSet->appendChild($stringProp);
//        $stringProp->setAttribute('name', 'filename');
//        $stringProp->appendChild($dom->createCDATASection('C:\data.csv'));

        $boolProp = $dom->createElement('boolProp');
        $CSVDataSet->appendChild($boolProp);
        $boolProp->setAttribute('name', 'ignoreFirstLine');
        $boolProp->nodeValue = 'false';

        $boolProp = $dom->createElement('boolProp');
        $CSVDataSet->appendChild($boolProp);
        $boolProp->setAttribute('name', 'quotedData');
        $boolProp->nodeValue = 'true';

        $boolProp = $dom->createElement('boolProp');
        $CSVDataSet->appendChild($boolProp);
        $boolProp->setAttribute('name', 'recycle');
        $boolProp->nodeValue = 'true';

        $stringProp = $dom->createElement('stringProp');
        $CSVDataSet->appendChild($stringProp);
        $stringProp->setAttribute('name', 'shareMode');
        $stringProp->nodeValue = 'shareMode.thread';

        $boolProp = $dom->createElement('boolProp');
        $CSVDataSet->appendChild($boolProp);
        $boolProp->setAttribute('name', 'stopThread');
        $boolProp->nodeValue = 'false';

        $stringProp = $dom->createElement('stringProp');
        $CSVDataSet->appendChild($stringProp);
        $stringProp->setAttribute('name', 'variableNames');
        $stringProp->appendChild($dom->createCDATASection(implode(',', $csvVariableNames)));


        $hashTree2->appendChild($dom->createElement('hashTree'));//empty node
    }
}

//header control
$now = gmdate("D, d M Y H:i:s");
header("Last-Modified: {$now} GMT");

// force download
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");

// disposition / encoding on response body
header("Content-type:application/xml");
header("Content-Disposition: attachment;filename=" . date('Y-m-d H:i:s') . ".jmx");

//output
echo $dom->saveXML();