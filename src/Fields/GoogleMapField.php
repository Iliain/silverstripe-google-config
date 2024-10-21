<?php

namespace Iliain\GoogleConfig\Fields;

use SilverStripe\Core\Environment;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\DatalessField;

class GoogleMapField extends DatalessField
{
    /**
     * The type of map to be displayed
     * @var string
     */
    protected $mapType; 

    /**
     * An array of URL parameters to be appended to the Google Maps API URL
     * @var array
     */
    protected $urlParams;

    const TYPE_DEFAULT = 'default';

    const TYPE_PLACES = 'places';

    /**
     * @param string $name
     * @param string $mapType
     * @param array $urlParams
     */
    public function __construct($name, $mapType = null, $urlParams = [])
    {
        $this->setMapType($mapType);
        $this->setUrlParams($urlParams);

        parent::__construct($name);
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setMapType($mapType)
    {
        if ($mapType) {
            $this->mapType = $mapType;
        } else {
            $this->mapType = self::TYPE_DEFAULT;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getMapType()
    {
        return $this->mapType;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setUrlParams($urlParams)
    {
        $urlParams['loading'] = 'async';
        $urlParams['callback'] = 'initMap';
        $urlParams['v'] = 'weekly';

        if ($this->getMapType() === self::TYPE_PLACES) {
            $urlParams['libraries'] = 'places,marker';
        }

        $this->urlParams = $urlParams;

        return $this;
    }

    /**
     * @return array
     */
    public function getUrlParams()
    {
        return $this->urlParams;
    }

    public function Field($properties = [])
    {
        Requirements::css('iliain/silverstripe-google-config: client/css/map-field.css');

        switch ($this->getMapType()) {
            case self::TYPE_PLACES:
                Requirements::javascript('iliain/silverstripe-google-config: client/javascript/places-map.js', ['defer' => true]);
                break;
            default:
                Requirements::javascript('iliain/silverstripe-google-config: client/javascript/default-map.js', ['defer' => true]);
                break;
        }

        Requirements::javascript($this->getMapURL(), ['async' => true, 'defer' => true]);

        $properties['MapType'] = $this->getMapType();

        // Get the place ID from object loading this field
        $properties['PlaceID'] = $this->getForm()->getRecord()->PlaceID;

        return parent::Field($properties);
    }

    /**
     * Returns the URL for the Google Maps API with relevant parameters
     * @return string
     */
    public function getMapURL()
    {
        if (!Environment::getEnv('GOOGLE_MAPS_API_KEY')) {
            throw new \Exception('No Google Maps API key set');
        }

        $url = 'https://maps.googleapis.com/maps/api/js?key=' . Environment::getEnv('GOOGLE_MAPS_API_KEY');

        if ($this->getUrlParams()) {
            $url .= '&' . http_build_query($this->getUrlParams());
        }

        return $url;
    }
}
