<?php
/**
* Atom Heart Scraper
*/

namespace Shows\Scraper;

use \Goutte\Client;

class AtomheartScraper extends AbstractScraper {

    private $_events;

    /**
     * Scrape the pages!
     * @return  [array]  $events  Array of events found in the scraped content
     */
    public function events() {

        if (!empty($this->_events)) {
            return $this->_events;
        }

        $links = [];
        $events = [];
        $client = new Client();

        $client
            ->request('GET', 'http://www.atomheart.ca/wordpress/category/billets-tickets/')
            ->filter('#content-archive > .category-billets-tickets > .post-title > a')
            ->each(function ($node) use (&$links) {
                $links[] = $node->attr('href');
            });

        foreach ($links as $link) {
            $client
                ->request('GET', $link)
                ->filter('#content > .post > .post-entry > p')
                ->each(function ($node) use (&$events) {

                    // Okay, let's try to sort that
                    $split_content = preg_split('/<br[^>]*>/i', $node->html());

                    // Majority of shows are 4 lines. If less or more, fuck that, not dealing with you
                    // Or worse yet, format has changed. Abandon ship
                    if (count($split_content) === 4) {

                        // Splitting line 1 (bilingual date strings)
                        $date_array = preg_split('/\|/', $split_content[0]);
                        // We'll use the english one for simplicity's sake (normally at end)
                        $date = end($date_array);
                        // Sometime months will be in the wrong language anyways
                        $date = str_replace(
                        [
                            'janvier',
                            'février',
                            'mars',
                            'avril',
                            'mai',
                            'juin',
                            'juillet',
                            'août',
                            'septembre',
                            'octobre',
                            'novembre',
                            'décembre'
                        ],
                        [
                            'january',
                            'february',
                            'march',
                            'april',
                            'may',
                            'june',
                            'july',
                            'august',
                            'september',
                            'october',
                            'november',
                            'december'
                        ], $date);

                        // Splitting line 2 (artists)
                        $artists_array = preg_split('/\|/', $split_content[1]);
                        $artists = [];

                        foreach ($artists_array as $artist) {
                            $artists[] = [
                                'name' => trim($artist)
                            ];
                        }

                        // Splitting line 3 (location location location)
                        // For now, I don't give a shit about adresses
                        $location_array = preg_split('/(:)/', $split_content[2]);
                        $location = trim(current($location_array));

                        // Splitting line 4 (price, time)
                        // Usually, index [1] is a useless string
                        // Just in case it's not there, we'll get around it by fetching both extremities
                        $details_array = preg_split('/\|/', $split_content[3]);
                        $price = preg_replace('/[^\d,\.]/', '', trim(current($details_array)));
                        $price = preg_replace('/,(\d{2})$/', '.$1', $price);
                        $time = trim(str_replace(['Doors','Portes','@'], '', end($details_array)));

                        // Generating a DateTime object using the combination of $time and date string
                        $datetime = new \DateTime($date . ' ' . $time);

                        $events[] = [
                            'timestamp' => $datetime->getTimestamp(),
                            'date' => $datetime->format('d F Y'),
                            'time' => $datetime->format('H:i'),
                            'artists_string' => $split_content[1],
                            'artists' => $artists,
                            'location' => $location,
                            'price' => $price
                        ];
                    }
                });
        }

        $this->_events = $events;

        return $events;
    }
}