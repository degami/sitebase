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

use App\Base\Abstracts\OAuth2\AbstractOAuth2ApiClient;
use App\Base\Interfaces\Commerce\ShipmentTrackerInterface;
use App\Base\Models\OrderShipment;
use Degami\Basics\Exceptions\BasicException;

class UPSTracker extends AbstractOAuth2ApiClient implements ShipmentTrackerInterface
{
    private const CARRIER_CODE = 'ups';

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

        $response = $this->apiRequest("/track/v1/details/{$trackingNumber}");

        return (array)$response;
    }

    public function updateShipmentStatus(OrderShipment $shipment): void
    {
        /*
        {
            "trackResponse": {
                "shipment": [
                    {
                        "inquiryNumber": "1Z023E2X0214323462",
                        "package": [
                            {
                                "accessPointInformation": {
                                    "pickupByDate": "string"
                                },
                                "activity": [
                                    {
                                        "date": null,
                                        "gmtDate": null,
                                        "gmtOffset": null,
                                        "gmtTime": null,
                                        "location": null,
                                        "status": null,
                                        "time": null
                                    }
                                ],
                                "additionalAttributes": [
                                    "SENSOR_EVENT"
                                ],
                                "additionalServices": [
                                    "ADULT_SIGNATURE_REQUIRED",
                                    "SIGNATURE_REQUIRED",
                                    "ADDITIONAL_HANDLING",
                                    "CARBON_NEUTRAL",
                                    "UPS_PREMIER_SILVER",
                                    "UPS_PREMIER_GOLD",
                                    "UPS_PREMIER_PLATINUM"
                                ],
                                "alternateTrackingNumber": [
                                    {
                                        "number": null,
                                        "type": null
                                    }
                                ],
                                "currentStatus": {
                                    "code": "SR",
                                    "description": "Your package was released by the customs agency.",
                                    "simplifiedTextDescription": "Delivered",
                                    "statusCode": "003",
                                    "type": "X"
                                },
                                "deliveryDate": [
                                    {
                                        "date": null,
                                        "type": null
                                    }
                                ],
                                "deliveryInformation": {
                                    "deliveryPhoto": {
                                        "isNonPostalCodeCountry": null,
                                        "photo": null,
                                        "photoCaptureInd": null,
                                        "photoDispositionCode": null
                                    },
                                    "location": "Front Door",
                                    "receivedBy": "",
                                    "signature": {
                                        "image": null
                                    },
                                    "pod": {
                                        "content": null
                                    }
                                },
                                "deliveryTime": {
                                    "endTime": "string",
                                    "startTime": "string",
                                    "type": "string"
                                },
                                "dimension": {
                                    "height": "string",
                                    "length": "string",
                                    "unitOfDimension": "string",
                                    "width": "string"
                                },
                                "isSmartPackage": true,
                                "milestones": [
                                    {
                                        "category": null,
                                        "code": null,
                                        "current": null,
                                        "description": null,
                                        "linkedActivity": null,
                                        "state": null,
                                        "subMilestone": null
                                    }
                                ],
                                "packageAddress": [
                                    {
                                        "address": null,
                                        "attentionName": null,
                                        "name": null,
                                        "type": null
                                    }
                                ],
                                "packageCount": 2,
                                "paymentInformation": [
                                    {
                                        "amount": null,
                                        "currency": null,
                                        "id": null,
                                        "paid": null,
                                        "paymentMethod": null,
                                        "type": null
                                    }
                                ],
                                "referenceNumber": [
                                    {
                                        "number": null,
                                        "type": null
                                    }
                                ],
                                "service": {
                                    "code": "518",
                                    "description": "UPS Ground",
                                    "levelCode": "011"
                                },
                                "statusCode": "string",
                                "statusDescription": "string",
                                "suppressionIndicators": "DETAIL",
                                "trackingNumber": "string",
                                "ucixStatus": "string",
                                "weight": {
                                    "unitOfMeasurement": "string",
                                    "weight": "string"
                                }
                            }
                        ],
                        "userRelation": "MYCHOICE_HOME",
                        "warnings": [
                            {
                                "code": "string",
                                "message": "string"
                            }
                        ]
                    }
                ]
            }
        }
        */

        $data = $this->fetchTrackingData($shipment);

        $status = $data['trackResponse']['shipment'][0]['package'][0]['currentStatus']['description'] ?? null;

        // UPS API does not provide latitude and longitude in this response
        $latitude = null;
        $longitude = null;

        // use geocoder class to get lat/lon from location if needed
        $geocoder = $this->getGeocoder();

        try {
            $position = $geocoder->geocode(
                $data['trackResponse']['shipment'][0]['package'][0]['packageAddress'][0]['address'] ?? ''
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
