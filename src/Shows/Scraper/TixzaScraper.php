<?php
/**
* Tixza Scraper
*/

namespace Shows\Scraper;

use \Goutte\Client;

class TixzaScraper extends AbstractScraper {

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
            ->request('GET', 'http://www.en.tixza.com/event')
            ->filter('#content > .listing > a')
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

            // Artists seem to be stores in first h2 and h3
            $artists = [];
            $node
                ->filter('h2, h3')
                ->each(function ($artist_node) use (&$artists) {
                    $artists[] = [
                        'name' => trim($artist_node->text())
                    ];
                });

            // Rest of the details are pretty sketchy to access, all of em in unmarked <p>
            // Make sure we get them starting after the last possible artist
            // Their order stays constant though!
            $details = $node->filter('h2, h3')->last()->nextAll();

            // Location is first
            $location = trim($details->eq(0)->text());

            // Date and time are second and third
            // Easy enough date to extract
            $date = trim($details->eq(1)->text());
            // Time seems to always follow the same format. Taking advantage of that.
            // Show: 21h30
            $time = trim(str_replace(['Spectacle:','Show:'], '', $details->eq(3)->text()));

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

            // Price is fourth
            $price_string = trim($details->eq(4)->text());
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