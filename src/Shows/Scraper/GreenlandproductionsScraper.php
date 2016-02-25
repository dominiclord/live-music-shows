<?php
/**
* Greenland Productions Scraper
*/

namespace Shows\Scraper;

use \Goutte\Client;

class GreenlandproductionsScraper extends AbstractScraper {

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
            ->request('GET', 'http://www.greenland.ca/event')
            ->filter('#main_content > .listing > a')
            ->each(function ($node) use (&$links) {
                $links[] = $node->attr('href');
            });

        // Using this to manage the fact that the year is not listed in the show date
        $static_datetime = new \DateTime('today');

        foreach ($links as $link) {
            //var_dump($link);
            $node = $client
                ->request('GET', $link)
                ->filter('.event_details');

            // Main artist might sometimes be TWO artists
            $artists = [];
            $node
                ->filter('h2')
                ->each(function ($artist_node) use (&$artists) {
                    $artists[] = [
                        'name' => trim($artist_node->text())
                    ];
                });

            // And then we have supporting artists
            $node
                ->filter('.openers span')
                ->each(function ($artist_node) use (&$artists) {
                    $name = trim($artist_node->text());
                    // Yeahhhhh, I'm just gonna try an early "GUEST ARTIST?!?! OMG" filter prototype
                    if (!preg_match('/'.implode('|', ['invité','guest','gue5t']).'/', $name)) {
                        $artists[] = [
                            'name' => $name
                        ];
                    }
                });

            // Location is pretty simple to extract. For some reason, they list the town underneath.
            // Other town possible?! Who knows. Wait till it breaks.
            // Split on line break, use first index
            $location_array = preg_split('/<br[^>]*>/i', $node->filter('.venue a')->html());
            $location = current($location_array);

            // Easy enough date to extract
            $date = trim($node->filter('.date')->text());
            // Time seems to always follow the same format. Taking advantage of that.
            // Doors: 7:30 PM // Show: 8:30 PM
            $time_array = explode('//', $node->filter('.doors')->text());
            $time = trim(str_replace(['Show:','Doors:'], '', end($time_array)));

            // Generating a DateTime object using the combination of $time and $date string
            // However we're missing a year, so we'll use today's year
            $datetime = new \DateTime($date . ' ' . $static_datetime->format('Y') . ', ' . $time);
            // If $datetime is smaller than $static_datetime, it means we've changed year!
            if ($datetime < $static_datetime) {
                // Set $datetime's year as next year
                $datetime->modify('+1 year');
                // OVerwrite $static_datetime as $datetime
                $static_datetime = $datetime;
            }

            // Price is buried deep, with no classes
            // Always after `.doors` though!
            $price_string = trim($node->filter('.doors')->nextAll()->eq(0)->text());

            //$price_string = str_replace(',', '.', $price_string);
            // Match any strings that look like prices
            preg_match_all('/\d+(?:\.\d{1,2})?/', $price_string, $price_array);
            // And use end index since it's logically the larger number. More potential failure!
            $price = end(end($price_array));

            $events[] = [
                'timestamp' => $datetime->getTimestamp(),
                'date' => $datetime->format('d F Y'),
                'time' => $datetime->format('H:i'),
                'artists' => $artists,
                'location' => $location,
                'price' => $price
            ];
        }

        $this->_events = $events;

        return $events;
    }
}