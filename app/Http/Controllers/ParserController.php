<?php

namespace App\Http\Controllers;

set_time_limit(0);

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler;


class ParserController extends Controller
{
    public $request_url = 'http://kved.ukrstat.gov.ua/cgi-bin/kv-query.exe?kv10=';

    public $timeout = 1;

    public $stored_data = [];

    public $test_mode = true;

    public $errors = [
        'data_empty' => 'Data is empty or isn\'t an array!',
        'no_response' => 'Guzzle haven\'t response from KVED server!',
        'html_empty' => 'Response HTML is empty!',
        'h2_empty' => 'KVED header is empty!',
        'default' => 'Something went wrong!',
    ];

    public $parser_data = [
        "01.13","01.50","10.83","13.92","14.13","14.19","16.24","16.29","18.12","18.13","18.20","20.11",
        "23.56","23.61","23.69","23.70","31.01","31.02","31.03","31.09","32.13","32.99","33.12","33.20",
        "35.14","41.10","41.20","43.11","43.12","43.21","43.22","43.29","43.31","43.32","43.33","43.34",
        "43.35","43.39","43.91","43.99","45.20","45.31","45.32","45.40","46.11","46.13","46.14","46.15",
        "46.16","46.18","46.19","46.21","46.30","46.39","46.41","46.42","46.43","46.44","46.45","46.47",
        "46.49","46.51","46.52","46.62","46.65","46.69","46.73","46.74","46.75","46.76","46.90","47.11",
        "47.19","47.21","47.22","47.23","47.24","47.25","47.29","47.41","47.42","47.43","47.51","47.52",
        "47.53","47.54","47.59","47.62","47.63","47.65","47.71","47.72","47.74","47.75","47.78","47.79",
        "47.81","47.82","47.89","47.91","47.99","49.20","49.31","49.32","49.39","49.41","52.29","53.20",
        "55.10","56.10","56.21","56.29","56.30","58.11","58.12","58.14","58.19","58.21","58.29","59.11",
        "59.12","59.13","59.14","59.20","60.10","60.20","62.01","62.02","62.03","62.09","63.10","63.11",
        "63.12","63.91","63.99","64.99","65.11","66.21","66.22","66.29","68.10","68.20","68.31","69.10",
        "69.20","70.10","70.21","70.22","71.11","71.12","72.10","72.11","72.19","72.20","72.21","72.30",
        "73.11","73.12","73.20","74.10","74.20","74.30","74.90","77.11","77.22","77.31","77.33","77.39",
        "77.40","78.10","78.20","78.30","79.11","79.12","79.90","81.10","81.21","81.22","81.30","82.11",
        "82.19","82.20","82.30","82.99","84.13","85.10","85.31","85.51","85.52","85.59","85.60","85.89",
        "86.10","86.21","86.22","86.90","88.91","88.99","90.01","90.03","93.12","93.19","93.29","94.99",
        "95.11","95.12","95.21","95.24","96.02","96.04","96.09",
    ];
    
    public $parser_errors = [];

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

    public function parser()
    {
        if(!empty($this->parser_data) && is_array($this->parser_data)) {
            $this->parse($this->parser_data);
            $this->returnSuccess();
        }
        else {
            $this->pushError('data_empty');
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
                    $this->pushError('html_empty', $id);
                }
            }
            else {
                $this->pushError('no_response', $id);
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
            $this->pushError('h2_empty', $id);
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
        }
        else {
            $this->pushError('Text isn\'t contain a KVED ID!', $id);
        }
    }


    private function pushError($code, $id = '') {
        $this->parser_errors[] = [
            'id' => $id,
            'error' => (!empty($this->errors[$code]) ? $this->errors[$code] : $this->errors['default']),
        ];
    }

    private function returnSuccess() {
        echo json_encode([
            'errors' => $this->parser_errors,
            'stored_data' => $this->stored_data,
        ]);
        die();
    }
}