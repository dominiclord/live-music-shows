<?php
/**
* Corona Theatre Scraper
*/

namespace Shows\Scraper;

use \Goutte\Client;

class CoronatheatreScraper extends AbstractScraper {

    private $_events;

    /**
     * Scrape the pages!
     * @return  [array]  $events  Array of events found in the scraped content
     */
    public function events() {

        if (!empty($this->_events)) {
            return $this->_events;
        }

        $links = [
            'http://www.theatrecoronavirginmobile.com/calendar/'
        ];
        $events = [];
        $client = new Client();

        foreach ($links as $link) {
            $client
                ->request('GET', $link)
                ->filter('.event_list > .shortpost')
                ->each(function ($node) use (&$events) {
                    // `shortpost_date` node contains date and nothing else
                    //
                    $date_content = trim($node->filter('.shortpost_date')->html());
                    // It's in an HTML comment? regex shall do
                    preg_match('#\d{10,11}#', $date_content, $date_timestamp);
                    $date_object = new \DateTime();
                    $date_object->setTimestamp(current($date_timestamp));
                    // Remember to ignore the time, it's not good within the timestamp
                    $date = $date_object->format('d F Y');

                    // Main artist is easy enough to fetch
                    $artists = [
                        [
                            'name' => $node->filter('.shortpost_details h1')->text()
                        ]
                    ];

                    // Supporting artists are a little harder. They are merged with prices and times
                    $details = [];
                    // Split those details by node
                    $node->filter('.shortpost_details .shortpost_with')->each(function ($detail_node) use (&$details) {
                        $details[] = trim(str_replace('Avec :','',$detail_node->text()));
                    });
                    // The node order seems to be respected throughout. However, if no supportings, there are 3 `.shortpost_with` nodes instead of 4
                    // Harcoding that count, living with the fear of failures!
                    if (count($details) === 4) {
                        // Splitting artists by comma. Risky.
                        $artists_array = preg_split('/\,/', array_shift($details));

                        foreach ($artists_array as $artist) {
                            $artists[] = [
                                'name' => trim($artist)
                            ];
                        }
                    }

                    // Index 1 contains show time (24h clock), contains pesky `h` character
                    $time = str_replace(['h','H'], '', $details[1]);
                    // Generating a DateTime object using the combination of $time and $date string
                    $datetime = new \DateTime($date . ', ' . $time);

                    // Index 2 contains pricing. We'll need to do a bit of comparing.
                    // A majority of the time, they output both prices in the string.
                    // We'll strip down the string -
                    $price_string = str_replace(['Prix : À L\'avance', 'À L\'avance', 'Jour du spectacle', 'À l\'avance', 'À L’avance', ':'], '', $details[2]);
                    $price_string = str_replace(',', '.', $price_string);
                    // Match any strings that look like prices -
                    preg_match_all('/\d+(?:\.\d{1,2})?/', $price_string, $price_array);
                    // And use end index since it's logically the larger number. More potential failure!
                    $price = end(end($price_array));

                    $location = 'Corona Theatre';

                    $events[] = [
                        'timestamp' => $datetime->getTimestamp(),
                        'date' => $datetime->format('d F Y'),
                        'time' => $datetime->format('H:i'),
                        'artists' => $artists,
                        'location' => $location,
                        'price' => $price
                    ];

                });
        }

        $this->_events = $events;

        return $events;
    }
}