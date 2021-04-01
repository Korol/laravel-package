<?php

namespace App\Http\Controllers;

set_time_limit(0);

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler;


class ParserController extends Controller
{
    public $request_url = 'http://kved.ukrstat.gov.ua/cgi-bin/kv-query.exe?kv10=';

    public $timeout = 1;

    public $stored_data = [];

    public $test_mode = true;

    public $errors = [
        'json_empty' => 'JSON data is empty or isn\'t an array!',
        'no_response' => 'Guzzle havn\'t response from KVED server!',
        'html_empty' => 'Response HTML is empty!',
        'h2_empty' => 'KVED header is empty!',
        'default' => 'Something went wrong!',
    ];

    public function __construct()
    {

    }

    public function index()
    {
        $this->parse(['62.01']); // test
    }

    public function parseOne($id)
    {
        $this->parse([$id]);
    }

    public function parser(Request $request)
    {
        $json_data = json_decode($request->json_data);
        if(!empty($json_data) && is_array($json_data)) {
            $this->parse($json_data);
            $this->returnSuccess();
        }
        else {
            $this->returnError('json_empty');
        }
    }

    public function parse($ids = [])
    {
        $client = new Client();

        foreach ($ids as $id) {
            $response = $client->request(
                'GET',
                $this->request_url . $id
            );

            if(!empty($response) && !empty($response->getBody())) {
                $html = $response->getBody()->getContents();
                if(!empty($html)) {
                    $this->crawlHtml($id, $html);
                }
                else {
                    $this->returnError('html_empty');
                }
            }
            else {
                $this->returnError('no_response');
            }
            sleep($this->timeout);
        }
    }

    public function crawlHtml($id, $html = '')
    {
        $crawler = new DomCrawler\Crawler($html);
        $h2_text = $crawler->filterXPath('//body/h2')->first()->text();
        if(!empty($h2_text)) {
            $this->prepareStoredData($id, $h2_text);
        }
        else {
            $this->returnError('h2_empty');
        }
    }

    public function prepareStoredData($id, $text = '')
    {
        $text = mb_convert_encoding($text, 'UTF-8');
        if (mb_strpos($text, $id, 0, 'UTF-8') !== false) {
            $name = str_replace($id, '', $text);
            $this->stored_data[] = [
                'id' => $id,
                'name' => trim($name),
            ];
            dd($this->stored_data);
        }
        else {
            dd($id, $text, mb_detect_encoding($text));
            $this->returnError('Text isn\'t contain a KVED ID!');
        }
    }



    private function returnError($code) {
        return response()->json([
            'error' => (!empty($this->errors[$code]) ? $this->errors[$code] : $this->errors['default']),
        ]);
        die();
    }

    private function returnSuccess() {
        dd('Success');
        return response()->json([
            'error' => false,
            'stored_data' => $this->stored_data,
        ]);
        die();
    }
}