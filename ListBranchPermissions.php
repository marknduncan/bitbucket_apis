<?php

require_once "vendor/autoload.php";

use GuzzleHttp\Client;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = (new Dotenv())->usePutenv()->bootEnv('.env');
$repos = explode(',', getenv('BB_REPOS'));

foreach ($repos as $repo) {
  echo "Running repo:" . $repo . "\r\n";
  $arr_body = getNextRequest(null, $repo);
}

function getNextRequest($uri = null, $repo) {

  $client = new Client([
    // Base URI is used with relative requests
    'base_uri' => 'https://api.bitbucket.org',
  ]);

  $authorization = [
    getenv('BB_USER'),
    getenv('BB_PASS')
  ];

  if (empty($uri)) {
    $response = $client->request('GET', '/2.0/repositories/' . getenv('BB_WORKSPACE') . '/' . $repo . '/branch-restrictions', [
      'auth' => $authorization
    ]);

  } else {
    $response = $client->request('GET', $uri, [
        'auth' => $authorization
      ]
    );
  }

  $body = $response->getBody();
  $array_response = (array)json_decode($body);

  //write response for this request
  writeResponseToFile($body, $repo, $array_response['page']);

  //get next field if it exists
  $next = !empty($array_response['next']) ? $array_response['next'] : null;

  if (!empty($next)) {
    getNextRequest($next, $repo);
  }

}

function writeResponseToFile($array_data, $repo, $page) {

  $directory_string = 'branch_permissions_reports/' . $repo . '_' . date('m-y-d');
  @mkdir($directory_string, 0755, true);

  // write out array_body to json file
  $fp = fopen($directory_string . '/' . $page . '_' . time() . '.json', 'a');//opens file in append mode
  fwrite($fp, $array_data);
  fclose($fp);

}
