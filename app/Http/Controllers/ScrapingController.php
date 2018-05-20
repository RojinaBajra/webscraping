<?php

namespace App\Http\Controllers;

use Goutte\Client;
use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;

class ScrapingController extends Controller
{
    public function getSweetIn()
    {

        $pagenumbers = [];
        $client      = new Client();
        $tds=[];
        $crawler = $client->request('GET', "https://www.sweetinn.com/lisbon");
        $pages = $crawler->filter('div[class="pn-pages"]');
        foreach ($pages as $m => $n)
        {
            $numbers = new Crawler($n);
            foreach ($numbers->filter('button[class="pn-page"]') as $u => $v)
            {
                $p = new Crawler($v);
                array_push($pagenumbers, $p->text());
            }

        }
        array_pop($pagenumbers);
        array_pop($pagenumbers);
        $addOne = "1";
        array_unshift($pagenumbers, $addOne);

        foreach ($pagenumbers as $eachpagenumber)
        {
            $crawler = $client->request('GET', "https://www.sweetinn.com/lisbon?page=".$eachpagenumber);
            $a = $crawler->filter('div[class="city-map-properties-list"]');
            foreach ($a as $i => $content)
            {
                $services = [];
                $crawler  = new Crawler($content);
                $filter   = [ 'h3', 'div[class="pi-location"]' ];
                foreach ($filter as $key => $val)
                {
                    foreach ($crawler->filter('div[class="cmpl-item"]') as $i => $node)
                    {
                        $g = new Crawler($node);
                        foreach ($g as $j => $nod)
                        {
                            $t             = new Crawler($nod);
                            $linkInn       = $t->filter('a[class="properties-item"]')->attr('href');
                            $crawlerForInn = $client->request('GET', "https://www.sweetinn.com" . $linkInn);
                            $facilities    = $crawlerForInn->filter('div[class="amenities-wrapper"]');
                            foreach ($facilities as $x => $y)
                            {
                                $services = [];
                                $wrapper  = new Crawler($y);
                                foreach ($wrapper->filter('div[class="amenity-name"]') as $key => $value)
                                {
                                    $k = new Crawler($value);
                                    //                                    $services = $k->text();
                                    array_push($services, $k->text());
                                }
                            }
                            $finalArray['locator']  = $t->filter('h3')->text();
                            $finalArray["location"] = $t->filter('div[class="pi-location"]')->text();
                            $accomodation           = $t->filter('div[class="pi-values"]')->text();
                            $number                 = str_replace([ '+', '-' ], '', filter_var($accomodation, FILTER_SANITIZE_NUMBER_INT));
                            $guests                 = array_map('intval', str_split((int) $number));

                            $finalArray['guests']     = $guests[0];
                            $finalArray['facilities'] = implode(",",$services);
                            array_push($tds, $finalArray);
                        }
                    }
                }
            }

        }
        return $tds;
    }
}
