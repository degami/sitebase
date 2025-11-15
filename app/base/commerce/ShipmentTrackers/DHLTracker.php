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

namespace App\Base\Commerce\ShipmentTrackers;

use App\App;
use App\Base\Abstracts\ContainerAwareObject;
use Psr\Container\ContainerInterface;
use App\Base\Abstracts\OAuth2\AbstractOAuth2ApiClient;
use App\Base\Interfaces\Commerce\ShipmentTrackerInterface;
use App\Base\Models\OrderShipment;
use Degami\Basics\Exceptions\BasicException;

class DHLTracker extends AbstractOAuth2ApiClient implements ShipmentTrackerInterface
{
    private const CARRIER_CODE = 'dhl';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct(
            $container,
            App::getInstance()->getSiteData()->getConfigValue('commerce/shipment_trackers/dhl/client_id'),
            App::getInstance()->getSiteData()->getConfigValue('commerce/shipment_trackers/dhl/client_secret'),
            'shipment:read',
            'https://api.dhl.com/oauth/token',
            'https://api.dhl.com'
        );
    }

    public function supports(string $carrier): bool
    {
        return strtolower($carrier) === self::CARRIER_CODE;
    }

    public function fetchTrackingData(OrderShipment $shipment): array
    {
        $trackingNumber = $shipment->getShipmentCode();
        if (!$this->supports($shipment->getShippingMethod())) {
            throw new BasicException("Unsupported carrier: " . $shipment->getShippingMethod());
        }

        $response = $this->apiRequest("/track/shipments", "GET", [
            'query' => ['trackingNumber' => $trackingNumber],
        ]);

        return (array) $response;
    }

    public function updateShipmentStatus(OrderShipment $shipment): void
    {

        /*
        {
        "url": "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&limit=10",
        "firstUrl": "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&limit=10",
        "prevUrl": "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&limit=10",
        "nextUrl": "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&limit=10",
        "lastUrl": "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&limit=10",
        "shipments": [
            {
            "id": "GMDBD8E9CCE94842E495B7",
            "service": "ecommerce",
            "origin": {
                "address": {
                "countryCode": "US",
                "postalCode": "84003",
                "addressLocality": "AMERICAN FORK"
                }
            },
            "destination": {
                "address": {
                "countryCode": "GB",
                "postalCode": "S87FA",
                "addressLocality": "SHEFFIELD"
                }
            },
            "status": {
                "timestamp": "2023-01-29T16:02:00",
                "location": {
                "address": {
                    "countryCode": "UK",
                    "postalCode": "HEATHROW",
                    "addressLocality": "HEATHROW, GB"
                }
                },
                "statusCode": "transit",
                "status": "ARRIVED AT CUSTOMS"
            },
            "details": {
                "product": {
                "productName": "DHL Parcel Intl Standard"
                },
                "weight": {
                "value": 0.831,
                "unitText": "LB"
                },
                "references": [
                {
                    "number": "GMDBD8E9CCE94842E495B7",
                    "type": "customer-confirmation-number"
                },
                {
                    "number": "2042200157621303",
                    "type": "ecommerce-number"
                },
                {
                    "number": "H01PQA0010751022",
                    "type": "local-tracking-number"
                }
                ]
            },
            "events": [
                {
                "timestamp": "2023-01-29T16:02:00",
                "location": {
                    "address": {
                    "countryCode": "UK",
                    "postalCode": "HEATHROW",
                    "addressLocality": "HEATHROW, GB"
                    }
                },
                "statusCode": "transit",
                "status": "ARRIVED AT CUSTOMS"
                },
                {
                "timestamp": "2023-01-23T08:52:15",
                "location": {
                    "address": {
                    "countryCode": "US",
                    "postalCode": "90601",
                    "addressLocality": "Whittier, CA, US"
                    }
                },
                "statusCode": "unknown",
                "status": "SCANNED INTO SACK/CONTAINER"
                },
                {
                "timestamp": "2023-01-23T08:52:14",
                "location": {
                    "address": {
                    "countryCode": "US",
                    "postalCode": "90601",
                    "addressLocality": "Whittier, CA, US"
                    }
                },
                "statusCode": "transit",
                "status": "PROCESSING COMPLETED AT ORIGIN"
                },
                {
                "timestamp": "2023-01-23T08:50:36",
                "location": {
                    "address": {
                    "countryCode": "US",
                    "postalCode": "90601",
                    "addressLocality": "Whittier, CA, US"
                    }
                },
                "statusCode": "unknown",
                "status": "CLOSE BAG"
                },
                {
                "timestamp": "2023-01-20T14:04:18",
                "location": {
                    "address": {
                    "countryCode": "US",
                    "postalCode": "90601",
                    "addressLocality": "Whittier, CA, US"
                    }
                },
                "statusCode": "transit",
                "status": "PROCESSED"
                },
                {
                "timestamp": "2023-01-20T10:21:25",
                "statusCode": "pre-transit",
                "status": "DHL ECOMMERCE CURRENTLY AWAITING SHIPMENT AND TRACKING WILL BE UPDATED WHEN RECEIVED"
                },
                {
                "timestamp": "2023-01-18T09:39:28",
                "location": {
                    "address": {
                    "countryCode": "US",
                    "postalCode": "90601",
                    "addressLocality": "Whittier, CA, US"
                    }
                },
                "statusCode": "transit",
                "status": "PACKAGE RECEIVED AT DHL ECOMMERCE DISTRIBUTION CENTER"
                },
                {
                "timestamp": "2023-01-11T11:58:34",
                "statusCode": "unknown",
                "status": "LABEL CREATED"
                }
            ]
            }
        ],
        "possibleAdditionalShipmentsUrl": [
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=freight",
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=dgf",
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=parcel-de",
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=parcel-nl",
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=parcel-pl",
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=express",
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=post-de",
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=sameday",
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=parcel-uk",
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=ecommerce-apac",
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=ecommerce-europe",
            "/track/shipments?trackingNumber=GMDBD8E9CCE94842E495B7&service=post-international"
        ]
        */

        $data = $this->fetchTrackingData($shipment);

        $status = $data['shipments'][0]['status']['status'] ?? null;
        $location = $data['shipments'][0]['status']['location']['address'] ?? null;

        // DHL API does not provide latitude and longitude in this response
        $latitude = null;
        $longitude = null;

        // use geocoder class to get lat/lon from location if needed
        $geocoder = $this->getGeocoder();

        try {
            $position = $geocoder->geocode(
                trim(
                    ($location['addressLocality'] ?? '') . ' ' .
                    ($location['postalCode'] ?? '') . ' ' .
                    ($location['countryCode'] ?? '')
                )
            );

            $latitude = $position['lat'] ?? null;
            $longitude = $position['lon'] ?? null;
        } catch (BasicException $e) {
            // log error
        }


        $shipment->setStatus($status)->persist();

        if ($latitude !== null && $longitude !== null) {
            $shipment->updatePosition($latitude, $longitude);
        }
    }
}
