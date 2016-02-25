<?php
/**
* Blue Skies Turn Black Scraper
*/

namespace Shows\Scraper;

use \Goutte\Client;

class BstbScraper extends AbstractScraper {

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
            'http://blueskiesturnblack.com/shows?page=0',
            'http://blueskiesturnblack.com/shows?page=1'
        ];
        $events = [];
        $client = new Client();

        foreach ($links as $link) {
            $client
                ->request('GET', $link)
                ->filter('.view-shows > .view-content > .views-row')
                ->each(function ($node) use (&$events) {
                    // This node contains date & time and artists, nothing else interests us
                    // Date is relatively straight forward stuff
                    $date = trim($node->filter('.views-field-nothing .date-display-single')->text());
                    // For some reason, they are displaying times in 24h format WITH 12h periods
                    // Lazy removal, could break things
                    $time = str_replace(['am','pm'], '', trim($node->filter('.views-field-nothing .showtime')->text()));
                    // Generating a DateTime object using the combination of $time and $date string
                    $datetime = new \DateTime($date . ', ' . $time);

                    // Fetching artists
                    $artists = [];
                    // 4 artists or less are output, but sometimes are simply empty markup
                    $node->filter('.views-field-nothing [class^="act"] a')->each(function ($artist_node) use (&$artists) {
                        $artists[] = [
                            'name' => trim($artist_node->text())
                        ];
                    });

                    // This node contains location and pricing
                    // Location is tricky since there is nearly always a Google Maps link somewhere in there
                    $location_array = preg_split('/<br[^>]*>/i', $node->filter('.views-field-nothing-1 .location')->html());
                    $location = trim(current($location_array));

                    // Fetching price
                    // Opinionated decision to treat door price as official price
                    // Gotta skirt around that annoying <strong> around the label. Goutte and a nice regex have got us covered
                    $price = preg_replace('/[^\d,\.]/', '', trim($node->filter('.views-field-nothing-1 .doorPrice')->text()));
                    $price = preg_replace('/,(\d{2})$/', '.$1', $price);

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