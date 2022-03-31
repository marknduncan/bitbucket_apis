<?php

require_once "vendor/autoload.php";

use GuzzleHttp\Client;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$repos = explode(',',getenv('BB_REPOS'));

foreach($repos as $repo){
  echo "Running repo:\r\n" . $repo;
  $arr_body = getNextRequest(null,$repo);
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
    $response = $client->request('GET', '/2.0/repositories/'. getenv('BB_WORKSPACE'). '/'.$repo.'/commits', [
      'query' => [
//        'q' => 'values.date>=2021-03-03,values.date<=2022-03-03',
        'fields' => '-values.repository.*,-values.links.*,-values.parents.*,-values.author.*',
        'sort'=>'-values.date'
      ],
      'auth' => $authorization
    ]);

    $body = $response->getBody();
    writeResponseToFile($body,$repo);

  } else {
    $response = $client->request('GET', $uri, [
        'auth' => $authorization
      ]
    );
    $body = $response->getBody();
  }

  //get next field
  $array_response = (array)json_decode($body);
  $next = $array_response['next'];

  if (!empty($next)) {

    $body = $response->getBody();

    $body_arr = json_decode($body,true);

    $commit_date = new DateTime($body_arr['values'][0]['date']);

    //filter routine - filter out if commit is not within desired date range since API doesnt support this yet,
    if(
      $commit_date >= new DateTime('2021-03-31') &&
      $commit_date <= new DateTime('2022-04-01'))
    {
      writeResponseToFile($body,$repo);
      getNextRequest($next, $repo);
    }

  }

}

function writeResponseToFile($array_data, $repo) {

  $directory_string = 'commits_reports/'. $repo .'_' . date('m-y-d');
  @mkdir($directory_string, 0755, true);

  // write out array_body to json file
  $fp = fopen(  $directory_string .'/commits_'.time().'.json','a');//opens file in append mode
  fwrite($fp, $array_data);
  fclose($fp);

}
