<?php

namespace Iliain\GoogleConfig\Models;

use Exception;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Forms\ToggleCompositeField;
use Iliain\GoogleConfig\Fields\GoogleMapField;

class GooglePlace extends DataObject 
{
    /**
     * @var string
     */
    private static $table_name= 'GooglePlace';

    /**
     * @var array
     */
    private static $db = [
        'PlaceID'           => 'Varchar(255)',
        'PlaceData'         => 'Text',
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'getCMSImage'       => 'Image',
        'Title'             => 'Title',
        'getPlaceRating'    => 'Rating',
        'PlaceID'           => 'Place ID'
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'PlaceID'
    ];

    public function onBeforeWrite(): void
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

    /**
     * @return mixed
     */
    public function getTitle(): mixed
    {
        return $this->getPlaceField('name');
    }

    /**
     * @return FieldList
     */
    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->removeByName(['GoogleConfigID', 'PlaceData', 'PlaceID', 'PlaceTitle']);

        $fields->addFieldsToTab('Root.Main', [
            HeaderField::create('ReviewHeader', 'Configure Reviews'),
            TextField::create('PlaceTitle', 'Place Title', $this->getPlaceField('name'))->setReadonly(true),
            TextField::create('PlaceID', 'Place ID'),
            LiteralField::create('Message', '<div class="message notice"><p>Use the map below to find your location, then copy the Place ID into the field above.</p></div>'),
            // ToggleCompositeField is causing issues with the elements not rendering before the script executes
            ToggleCompositeField::create('PlaceMap', 'Map', [
                GoogleMapField::create('MapFrame', 'places'),
            ])->setHeadingLevel(4)->setStartClosed($this->PlaceID ? true : false),
        ]);

        // If place data is present, construct badge
        $placeData = $this->PlaceData;
        if ($placeData) {
            $reviewData = $this->getReviewData();

            $fields->addFieldsToTab('Root.Main', [
                LiteralField::create('ReviewFeed', $this->customise($reviewData)->renderWith('Iliain\\GoogleConfig\\Models\\GoogleConfigReviews')),
            ]);
        }

        $fields->addFieldsToTab('Root.Config', [
            TextareaField::create('PlaceData', 'Data')->setRows(20)
        ]);

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * Get the API URL for the place with the following fields
     *  - name, rating, reviews, icon, photos, url, user_ratings_total
     * @param string|null $placeID
     * @return string|null
     */
    public function getAPIURL($placeID): ?string
    {
        $key = Environment::getEnv('GOOGLE_MAPS_API_KEY');

        if (!$key || !$placeID) {
            return null;
        }

        return 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . $placeID . '&fields=name,rating,reviews,icon,photos,url,user_ratings_total&key=' . $key;
    }

    /**
     * Get the rating for the place
     * @return string
     */
    public function getPlaceRating(): string
    {
        return $this->getPlaceField('rating') . ' star' . ($this->getPlaceField('rating') != '1' ? 's' : '');
    }

    /**
     * Get the URL for the place photo
     * @return string|null
     */
    public function getPlacePhoto(): mixed
    {
        $key = Environment::getEnv('GOOGLE_MAPS_API_KEY');
        $data = $this->PlaceData;

        if (!$key || !$data) {
            return null;
        }

        $data = json_decode($data);

        if (isset($data->photos) && isset($data->photos[0])) {
            $url = 'https://maps.googleapis.com/maps/api/place/photo' .
            '?maxwidth=400' .
            '&photoreference=' . $data->photos[0]->photo_reference .
            '&key=' . $key;
        } else {
            $url = $data->icon;
        }

        return $url;
    }

    /**
     * Populate the place data from the Google API
     * @param string|null $placeID
     * @return string|null
     */
    public function populatePlaceData($placeID = null): mixed
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

    /**
     * Get a specified field from the stored place data
     * @param string $field
     * @return mixed
     */
    public function getPlaceField($field): mixed
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
    public function getPlaceReviews(): ?ArrayList
    {
        $data = $this->PlaceData;
        if (!$data) {
            return null;
        }

        $data = json_decode($data);

        if ($data && isset($data->reviews)) {
            $reviews = ArrayList::create();

            foreach ($data->reviews as $review) {
                $stars = $this->convertRatingNumberToArray($review->rating);

                $textField = DBText::create('Text');
                $textField->setValue($review->text);

                $reviews->push([
                    'Author'    => $review->author_name,
                    'AuthorURL' => $review->author_url,
                    'Photo'     => $review->profile_photo_url,
                    'Stars'     => $stars,
                    'Text'      => $textField,
                    'Time'      => $review->relative_time_description,
                ]);
            }

            return $reviews;
        }

        return null;
    }

    /**
     * Get the thumbnail image for the place
     * @return DBHTMLText
     */
    public function getCMSImage(): DBHTMLText
    {
        $text = DBHTMLText::create('Thumbnail');
        $text->setValue('<img src="' . $this->getPlacePhoto() . '" style="width: 100px; height: 100px; object-fit: cover;" />');
        return $text;
    }

    /**
     * Convert the rating number to an array of stars
     * @param string $rating
     * @return ArrayList
     */
    public function convertRatingNumberToArray($rating): ArrayList
    {
        $ratingArr = explode('.', $rating);
            
        if (count($ratingArr) > 1) {
            $decimal = $ratingArr[1];
        } else {
            $decimal = '0';
        }

        $stars = ArrayList::create();

        for ($i = 0; $i < $ratingArr[0]; $i++) {
            $stars->push(['Value' => 'full']);
        }

        if ($decimal != '0') {
            $stars->push(['Value' => 'half']);
        }

        $remainingStars = 5 - $stars->count();

        for ($i = 0; $i < $remainingStars; $i++) {
            $stars->push(['Value' => 'empty']);
        }

        return $stars;
    }

    /**
     * Structure the place data
     * @return array
     */
    public function getReviewData(): array
    {
        $rating = $this->getPlaceField('rating');
        $stars = $this->convertRatingNumberToArray($rating);

        $data = [
            'Title'     => $this->getPlaceField('name'),
            'PlaceID'   => $this->PlaceID,
            'Link'      => $this->getPlaceField('url'),
            'Image'     => $this->getPlacePhoto(),
            'Reviews'   => $this->getPlaceReviews(),
            'Total'     => $this->getPlaceField('user_ratings_total'),
            'Rating'    => $this->getPlaceField('rating'),
            'Stars'     => $stars
        ];

        return $data;
    }

    /**
     * Returns a rendered list of reviews
     * @return DBHTMLText|null
     */
    public function getReviewsList(): ?DBHTMLText
    {
        $reviews = $this->getPlaceReviews();

        if ($reviews) {
            return $this->customise([
                'Reviews' => $reviews
            ])->renderWith('Iliain\\GoogleConfig\\Models\\ReviewsList');
        }

        return null;
    }

    /**
     * Returns a rendered badge of the place
     * @return DBHTMLText
     */
    public function getBadge(): DBHTMLText
    {
        return $this->customise($this->getReviewData())->renderWith('Iliain\\GoogleConfig\\Models\\ReviewBadge');
    }
}
