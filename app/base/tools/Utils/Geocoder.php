<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */


namespace App\Base\Tools\Utils;

use App\Base\Abstracts\ContainerAwareObject;
use Degami\Basics\Exceptions\BasicException;

class Geocoder extends ContainerAwareObject
{
    /**
     * Geocoding using default provider (Nominatim)
     *
     * @param string $address
     * @param string $provider nominatim|google|opencage
     * @param array $options provider-specific options (apikey etc)
     * @return array|null ['lat' => float, 'lon' => float]
     * @throws BasicException
     */
    public function geocode(string $address, string $provider = 'nominatim', array $options = []): ?array
    {
        return match ($provider) {
            'google'    => $this->geocodeGoogle($address, $options['key'] ?? null),
            'opencage'  => $this->geocodeOpenCage($address, $options['key'] ?? null),
            default     => $this->geocodeNominatim($address),
        };
    }


    /**
     * ───────────────────────────────────────────────────────────
     * 1) NOMINATIM (OpenStreetMap) — NO API KEY
     * ───────────────────────────────────────────────────────────
     */
    public function geocodeNominatim(string $address): ?array
    {
        $url = "https://nominatim.openstreetmap.org/search";

        $reqOptions = [
            'query' => [
                'format' => 'json',
                'q'      => $address,
            ],
            'headers' => [
                'User-Agent' => 'Sitebase-Geocoder/1.0',
            ],
        ];

        $response = $this->getUtils()->httpRequest($url, 'GET', $reqOptions);

        if (!$response || !isset($response[0])) {
            return null;
        }

        return [
            'lat' => (float) $response[0]->lat,
            'lon' => (float) $response[0]->lon,
        ];
    }


    /**
     * ───────────────────────────────────────────────────────────
     * 2) GOOGLE GEOCODING API
     * Requires an API key
     * ───────────────────────────────────────────────────────────
     */
    public function geocodeGoogle(string $address, ?string $apiKey): ?array
    {
        if (!$apiKey) {
            throw new BasicException("Google Geocoding API key missing");
        }

        $url = "https://maps.googleapis.com/maps/api/geocode/json";

        $reqOptions = [
            'query' => [
                'address' => $address,
                'key'     => $apiKey,
            ],
        ];

        $response = $this->getUtils()->httpRequest($url, 'GET', $reqOptions);

        if (!$response || ($response->status ?? '') !== 'OK') {
            return null;
        }

        $location = $response->results[0]->geometry->location;

        return [
            'lat' => (float) $location->lat,
            'lon' => (float) $location->lng,
        ];
    }


    /**
     * ───────────────────────────────────────────────────────────
     * 3) OPENCAGE GEOCODING
     * Requires an API Key
     * ───────────────────────────────────────────────────────────
     */
    public function geocodeOpenCage(string $address, ?string $apiKey): ?array
    {
        if (!$apiKey) {
            throw new BasicException("OpenCage API key missing");
        }

        $url = "https://api.opencagedata.com/geocode/v1/json";

        $reqOptions = [
            'query' => [
                'q'   => $address,
                'key' => $apiKey,
            ],
        ];

        $response = $this->getUtils()->httpRequest($url, 'GET', $reqOptions);

        if (!$response || empty($response->results)) {
            return null;
        }

        $geometry = $response->results[0]->geometry;

        return [
            'lat' => (float) $geometry->lat,
            'lon' => (float) $geometry->lng,
        ];
    }
}
