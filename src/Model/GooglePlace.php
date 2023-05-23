<?php

namespace Iliain\GoogleConfig\Models;

use Exception;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Iliain\GoogleConfig\Models\GoogleConfig;
use SilverStripe\Forms\ToggleCompositeField;

class GooglePlace extends DataObject 
{
    private static $table_name= 'GooglePlace';

    private static $db = [
        'PlaceID'           => 'Varchar(255)',
        'PlaceData'         => 'Text',
    ];

    private static $has_one = [
        'GoogleConfig'      => GoogleConfig::class
    ];

    private static $summary_fields = [
        'getCMSImage'       => 'Image',
        'Title'             => 'Title',
        'getPlaceRating'    => 'Rating',
        'PlaceID'           => 'Place ID'
    ];

    private static $searchable_fields = [
        'PlaceID'
    ];

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->PlaceID) {
            if (!$this->PlaceData || $this->isChanged('PlaceID')) {
                $this->PlaceData = $this->populatePlaceData($this->PlaceID);
            }
        } else {
            $this->PlaceData = null;
        }
    }

    public function getTitle()
    {
        return $this->getPlaceField('name');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName(['GoogleConfigID', 'PlaceData', 'PlaceID', 'PlaceTitle']);

        $fields->addFieldsToTab('Root.Main', [
            HeaderField::create('ReviewHeader', 'Configure Reviews'),
            TextField::create('PlaceTitle', 'Place Title', $this->getPlaceField('name'))->setReadonly(true),
            TextField::create('PlaceID', 'Place ID'),
            LiteralField::create('Message', '<div class="message notice"><p>Use the map below to find your location, then copy the Place ID into the field above.</p></div>'),
            ToggleCompositeField::create('PlaceMap', 'Map', [
                // Use map listed in the guides. If it stops working, we'll have to figure out how best to get the place ID
                LiteralField::create('SelectorMap', '<iframe src="https://geo-devrel-javascript-samples.web.app/samples/places-placeid-finder/app/dist/" allow="fullscreen; " style="width: 100%; height: 400px;" loading="lazy"></iframe>'),
            ])->setHeadingLevel(4)->setStartClosed($this->PlaceID ? true : false),
        ]);

        // If place data is present, construct badge
        $placeData = $this->PlaceData;
        if ($placeData) {
            $placeData = json_decode($placeData);
            
            $rating = $this->getPlaceField('rating');
            $ratingArr = explode('.', $rating);
            
            if (count($ratingArr) > 1) {
                $decimal = $ratingArr[1];
            } else {
                $decimal = '0';
            }

            $stars = ArrayList::create();
            for ($i = 0; $i < $ratingArr[0]; $i++) {
                $stars->push(['Full' => true]);
            }
            if ($decimal != '0') {
                $stars->push(['Full' => false]);
            }

            $fields->addFieldsToTab('Root.Main', [
                LiteralField::create('ReviewFeed', $this->customise([
                    'Title'     => $this->getPlaceField('name'),
                    'PlaceID'   => $this->PlaceID,
                    'Link'      => $this->getPlaceField('url'),
                    'Image'     => $this->getPlacePhoto(),
                    'Reviews'   => $this->getPlaceReviews(),
                    'Total'     => $this->getPlaceField('user_ratings_total'),
                    'Rating'    => $this->getPlaceField('rating'),
                    'Decimal'   => $decimal != '0' ? true : false,
                    'Stars'     => $stars
                ])->renderWith('Iliain\\GoogleConfig\\Models\\GoogleConfigReviews')),
            ]);
        }

        // @TODO show carousel of reviews

        $fields->addFieldsToTab('Root.Config', [
            TextareaField::create('PlaceData', 'Data')->setRows(20)
        ]);

        return $fields;
    }

    /**
     * Get the API URL for the place with the following fields
     *  - name, rating, reviews, icon, photos, url, user_ratings_total
     * @param string|null $placeID
     * @return string|null
     */
    public function getAPIURL($placeID)
    {
        $key = Environment::getEnv('GOOGLE_MAPS_API_KEY');

        if (!$key || !$placeID) {
            return null;
        }

        return 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . $placeID . '&fields=name,rating,reviews,icon,photos,url,user_ratings_total&key=' . $key;
    }

    public function getPlaceRating()
    {
        return $this->getPlaceField('rating') . ' star' . ($this->getPlaceField('rating') != '1' ? 's' : '');
    }

    /**
     * Get the URL for the place photo
     * @return string|null
     */
    public function getPlacePhoto()
    {
        $key = Environment::getEnv('GOOGLE_MAPS_API_KEY');
        $data = $this->PlaceData;

        if (!$key || !$data) {
            return null;
        }

        $data = json_decode($data);

        $url = 'https://maps.googleapis.com/maps/api/place/photo' .
            '?maxwidth=400' .
            '&photoreference=' . $data->photos[0]->photo_reference .
            '&key=' . $key;

        return $url;
    }

    /**
     * Populate the place data from the Google API
     * @param string|null $placeID
     * @return string|null
     */
    public function populatePlaceData($placeID = null)
    {
        try {
            if (!$placeID) {
                $placeID = $this->PlaceID;
            }

            if (!$placeID) {
                return null;
            }

            $url = $this->getAPIURL($placeID);

            if ($url) {
                $json = file_get_contents($url);
                $obj = json_decode($json);

                if ($obj && isset($obj->result)) {
                    return json_encode($obj->result);
                }
            }
            
            return null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getPlaceField($field)
    {
        $data = $this->PlaceData;
        if (!$data) {
            return null;
        }

        $data = json_decode($data);

        if ($data && isset($data->$field)) {
            return $data->$field;
        }

        return null;
    }

    /**
     * Get the reviews for this place from our stored data
     * @return ArrayList|null
     */
    public function getPlaceReviews()
    {
        $data = $this->PlaceData;
        if (!$data) {
            return null;
        }

        $data = json_decode($data);

        if ($data && isset($data->reviews)) {
            $reviews = ArrayList::create();

            foreach ($data->reviews as $review) {
                $reviews->push([
                    'Author'    => $review->author_name,
                    'AuthorURL' => $review->author_url,
                    'Photo'     => $review->profile_photo_url,
                    'Rating'    => $review->rating,
                    'Text'      => $review->text,
                    'Time'      => $review->relative_time_description,
                ]);
            }

            return $reviews;
        }

        return null;
    }

    public function getCMSImage()
    {
        $text = DBHTMLText::create('Thumbnail');
        $text->setValue('<img src="' . $this->getPlacePhoto() . '" style="width: 100px; height: 100px; object-fit: cover;" />');
        return $text;
    }
}