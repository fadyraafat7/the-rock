<?php
/** Local authenticated HTTP MCP client and optional stdio-to-HTTP bridge. */
if (PHP_SAPI !== 'cli') exit(2);
$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'figma-htmx-site.test';
$_SERVER['REQUEST_SCHEME'] = 'http';
ob_start();
require dirname(__DIR__) . '/wp-load.php';
ob_end_clean();
if (home_url() !== 'http://figma-htmx-site.test' || !class_exists('WSP_MCP_Auth')) exit(1);
$key = WSP_MCP_Auth::get_api_key();
$session = '';
$counter = 0;
$rpc = static function (array $request) use ($key, &$session) {
    $curl = curl_init('http://figma-htmx-site.test/wp-json/wsp-mcp/v1/mcp');
    $headers = ['Content-Type: application/json','Accept: application/json, text/event-stream','Authorization: Bearer ' . $key,'MCP-Protocol-Version: 2025-06-18'];
    if ($session !== '') $headers[] = 'Mcp-Session-Id: ' . $session;
    curl_setopt_array($curl, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode($request), CURLOPT_HTTPHEADER=>$headers,
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>30, CURLOPT_PROXY=>'', CURLOPT_HEADERFUNCTION=>static function ($curl,$line) use (&$session) {
            if (stripos($line,'Mcp-Session-Id:') === 0) $session = trim(substr($line,15));
            return strlen($line);
        }]);
    $raw = curl_exec($curl);
    if ($raw === false) throw new RuntimeException('Local MCP HTTP request failed: ' . curl_error($curl));
    $status = curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($status < 200 || $status >= 300) throw new RuntimeException('MCP HTTP status ' . $status);
    if (trim($raw) === '' && !array_key_exists('id',$request)) return null;
    $result = json_decode($raw,true,512,JSON_THROW_ON_ERROR);
    if (isset($result['error'])) throw new RuntimeException(json_encode($result['error']));
    return $result;
};
if (in_array('--stdio',$argv,true)) {
    // MCP clients launch this process locally; the API key stays in memory.
    while (($line = fgets(STDIN)) !== false) {
        $request = json_decode($line,true);
        if (!is_array($request)) continue;
        try {
            $result = $rpc($request);
            if (array_key_exists('id',$request)) { echo json_encode($result,JSON_UNESCAPED_SLASHES) . "\n"; flush(); }
        } catch (Throwable $error) {
            if (array_key_exists('id',$request)) { echo json_encode(['jsonrpc'=>'2.0','id'=>$request['id'],'error'=>['code'=>-32603,'message'=>$error->getMessage()]]) . "\n"; flush(); }
        }
    }
    exit;
}
$call = static function ($method,$params=[]) use (&$counter,$rpc) {
    return $rpc(['jsonrpc'=>'2.0','id'=>++$counter,'method'=>$method,'params'=>$params ?: new stdClass()])['result'];
};
$init = $call('initialize',['protocolVersion'=>'2025-06-18','capabilities'=>new stdClass(),'clientInfo'=>['name'=>'Codex local SEO client','version'=>'1.0']]);
$rpc(['jsonrpc'=>'2.0','method'=>'notifications/initialized']);
echo 'Connected via MCP: ' . $init['serverInfo']['name'] . ' ' . $init['serverInfo']['version'] . "\n";
$tools = array_column($call('tools/list')['tools'],'name');
sort($tools);
if ($tools !== ['portable_seo_get','portable_seo_update']) throw new RuntimeException('Unexpected tool scope: ' . implode(',',$tools));
echo 'MCP tools/list: ' . implode(', ',$tools) . "\n";
$tool = static function($name,$arguments) use ($call) {
    $result = $call('tools/call',['name'=>$name,'arguments'=>$arguments]);
    if (!empty($result['isError'])) throw new RuntimeException($result['content'][0]['text']);
    return json_decode($result['content'][0]['text'],true,512,JSON_THROW_ON_ERROR);
};
$copy = json_decode(file_get_contents(dirname(__DIR__) . '/tmp/seo-content-hostinger.json'),true,512,JSON_THROW_ON_ERROR);
$report = ['transport'=>'MCP Streamable HTTP','server'=>$init['serverInfo'],'tools'=>$tools,'pages'=>[]];
foreach ($copy['pages'] as $page) {
    $before = $tool('portable_seo_get',['post_id'=>$page['id']]);
    if (in_array('--apply-seo',$argv,true)) {
        $saved = $tool('portable_seo_update',['post_id'=>$page['id'],'title'=>$page['title'],'description'=>$page['description']]);
        if (empty($saved['success'])) throw new RuntimeException('MCP save failed.');
    }
    $after = $tool('portable_seo_get',['post_id'=>$page['id']]);
    if ($after['title'] !== $page['title'] || $after['description'] !== $page['description']) throw new RuntimeException('MCP read-back mismatch.');
    $report['pages'][] = ['post_id'=>$page['id'],'url'=>$after['url'],'saved_via_mcp'=>isset($saved),'changed'=>$saved['changed'] ?? false];
    echo 'PASS MCP ' . $page['path'] . (isset($saved) ? ' saved and read back' : ' read back') . "\n";
}
file_put_contents(dirname(__DIR__) . '/tmp/seo-mcp-report.json', json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
