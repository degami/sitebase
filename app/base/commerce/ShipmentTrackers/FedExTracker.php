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

class FedExTracker extends AbstractOAuth2ApiClient implements ShipmentTrackerInterface
{
    private const CARRIER_CODE = 'fedex';

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


        $response = $this->apiRequest("/track/v1/trackingnumbers", "POST", [
            'json' => [
                'trackingInfo' => [
                    [
                        'trackingNumberInfo' => [
                            'trackingNumber' => $trackingNumber
                        ]
                    ]
                ]
            ]
        ]);

        return (array)$response;
    }

    public function updateShipmentStatus(OrderShipment $shipment): void
    {
        /*
        {
        "transactionId": "624deea6-b709-470c-8c39-4b5511281492",
        "customerTransactionId": "AnyCo_order123456789",
        "output": {
            "completeTrackResults": [
            {
                "trackingNumber": "123456789012",
                "trackResults": [
                {
                    "trackingNumberInfo": {
                    "trackingNumber": "128667043726",
                    "carrierCode": "FDXE",
                    "trackingNumberUniqueId": "245822~123456789012~FDEG"
                    },
                    "additionalTrackingInfo": {
                    "hasAssociatedShipments": false,
                    "nickname": "shipment nickname",
                    "packageIdentifiers": [
                        {
                        "type": "SHIPPER_REFERENCE",
                        "value": "ASJFGVAS",
                        "trackingNumberUniqueId": "245822~123456789012~FDEG"
                        }
                    ],
                    "shipmentNotes": "shipment notes"
                    },
                    "distanceToDestination": {
                    "units": "KM",
                    "value": 685.7
                    },
                    "consolidationDetail": [
                    {
                        "timeStamp": "2020-10-13T03:54:44-06:00",
                        "consolidationID": "47936927",
                        "reasonDetail": {
                        "description": "Wrong color",
                        "type": "REJECTED"
                        },
                        "packageCount": 25,
                        "eventType": "PACKAGE_ADDED_TO_CONSOLIDATION"
                    }
                    ],
                    "meterNumber": "8468376",
                    "returnDetail": {
                    "authorizationName": "Sammy Smith",
                    "reasonDetail": [
                        {
                        "description": "Wrong color",
                        "type": "REJECTED"
                        }
                    ]
                    },
                    "serviceDetail": {
                    "description": "FedEx Freight Economy.",
                    "shortDescription": "FL",
                    "type": "FEDEX_FREIGHT_ECONOMY"
                    },
                    "destinationLocation": {
                    "locationId": "SEA",
                    "locationContactAndAddress": {
                        "address": {
                        "addressClassification": "BUSINESS",
                        "residential": false,
                        "streetLines": [
                            "1043 North Easy Street",
                            "Suite 999"
                        ],
                        "city": "SEATTLE",
                        "stateOrProvinceCode": "WA",
                        "postalCode": "98101",
                        "countryCode": "US",
                        "countryName": "United States"
                        }
                    },
                    "locationType": "FEDEX_SHIPSITE"
                    },
                    "latestStatusDetail": {
                    "scanLocation": {
                        "addressClassification": "BUSINESS",
                        "residential": false,
                        "streetLines": [
                        "1043 North Easy Street",
                        "Suite 999"
                        ],
                        "city": "SEATTLE",
                        "stateOrProvinceCode": "WA",
                        "postalCode": "98101",
                        "countryCode": "US",
                        "countryName": "United States"
                    },
                    "code": "PU",
                    "derivedCode": "PU",
                    "ancillaryDetails": [
                        {
                        "reason": "15",
                        "reasonDescription": "Customer not available or business closed",
                        "action": "Contact us at <http://www.fedex.com/us/customersupport/call/index.html> to discuss possible delivery or pickup alternatives.",
                        "actionDescription": "Customer not Available or Business Closed"
                        }
                    ],
                    "statusByLocale": "Picked up",
                    "description": "Picked up",
                    "delayDetail": {
                        "type": "WEATHER",
                        "subType": "SNOW",
                        "status": "DELAYED"
                    }
                    },
                    "serviceCommitMessage": {
                    "message": "No scheduled delivery date available at this time.",
                    "type": "ESTIMATED_DELIVERY_DATE_UNAVAILABLE"
                    },
                    "informationNotes": [
                    {
                        "code": "CLEARANCE_ENTRY_FEE_APPLIES",
                        "description": "this is an informational message"
                    }
                    ],
                    "error": {
                    "code": "TRACKING.TRACKINGNUMBER.EMPTY",
                    "parameterList": [
                        {
                        "value": "value",
                        "key": "key"
                        }
                    ],
                    "message": "Please provide tracking number."
                    },
                    "specialHandlings": [
                    {
                        "description": "Deliver Weekday",
                        "type": "DELIVER_WEEKDAY",
                        "paymentType": "OTHER"
                    }
                    ],
                    "availableImages": [
                    {
                        "size": "LARGE",
                        "type": "BILL_OF_LADING"
                    }
                    ],
                    "deliveryDetails": {
                    "receivedByName": "Reciever",
                    "destinationServiceArea": "EDDUNAVAILABLE",
                    "destinationServiceAreaDescription": "Appointment required",
                    "locationDescription": "Receptionist/Front Desk",
                    "actualDeliveryAddress": {
                        "addressClassification": "BUSINESS",
                        "residential": false,
                        "streetLines": [
                        "1043 North Easy Street",
                        "Suite 999"
                        ],
                        "city": "SEATTLE",
                        "stateOrProvinceCode": "WA",
                        "postalCode": "98101",
                        "countryCode": "US",
                        "countryName": "United States"
                    },
                    "deliveryToday": false,
                    "locationType": "APARTMENT_OFFICE",
                    "signedByName": "Reciever",
                    "officeOrderDeliveryMethod": "Courier",
                    "deliveryAttempts": "0",
                    "deliveryOptionEligibilityDetails": [
                        {
                        "option": "INDIRECT_SIGNATURE_RELEASE",
                        "eligibility": "INELIGIBLE"
                        }
                    ]
                    },
                    "scanEvents": [
                    {
                        "date": "2018-02-02T12:01:00-07:00",
                        "derivedStatus": "Picked Up",
                        "scanLocation": {
                        "addressClassification": "BUSINESS",
                        "residential": false,
                        "streetLines": [
                            "1043 North Easy Street",
                            "Suite 999"
                        ],
                        "city": "SEATTLE",
                        "stateOrProvinceCode": "WA",
                        "postalCode": "98101",
                        "countryCode": "US",
                        "countryName": "United States"
                        },
                        "locationId": "SEA",
                        "locationType": "CUSTOMS_BROKER",
                        "exceptionDescription": "Package available for clearance",
                        "eventDescription": "Picked Up",
                        "eventType": "PU",
                        "derivedStatusCode": "PU",
                        "exceptionCode": "A25",
                        "delayDetail": {
                        "type": "WEATHER",
                        "subType": "SNOW",
                        "status": "DELAYED"
                        }
                    }
                    ],
                    "dateAndTimes": [
                    {
                        "dateTime": "2007-09-27T00:00:00",
                        "type": "ACTUAL_DELIVERY"
                    }
                    ],
                    "packageDetails": {
                    "physicalPackagingType": "BARREL",
                    "sequenceNumber": "45",
                    "undeliveredCount": "7",
                    "packagingDescription": {
                        "description": "FedEx Pak",
                        "type": "FEDEX_PAK"
                    },
                    "count": "1",
                    "weightAndDimensions": {
                        "weight": [
                        {
                            "unit": "LB",
                            "value": "22222.0"
                        }
                        ],
                        "dimensions": [
                        {
                            "length": 100,
                            "width": 50,
                            "height": 30,
                            "units": "CM"
                        }
                        ]
                    },
                    "packageContent": [
                        "wire hangers",
                        "buttons"
                    ],
                    "contentPieceCount": "100",
                    "declaredValue": {
                        "currency": "USD",
                        "value": 56.8
                    }
                    },
                    "goodsClassificationCode": "goodsClassificationCode",
                    "holdAtLocation": {
                    "locationId": "SEA",
                    "locationContactAndAddress": {
                        "address": {
                        "addressClassification": "BUSINESS",
                        "residential": false,
                        "streetLines": [
                            "1043 North Easy Street",
                            "Suite 999"
                        ],
                        "city": "SEATTLE",
                        "stateOrProvinceCode": "WA",
                        "postalCode": "98101",
                        "countryCode": "US",
                        "countryName": "United States"
                        }
                    },
                    "locationType": "FEDEX_SHIPSITE"
                    },
                    "customDeliveryOptions": [
                    {
                        "requestedAppointmentDetail": {
                        "date": "2019-05-07",
                        "window": [
                            {
                            "description": "Description field",
                            "window": {
                                "begins": "2021-10-01T08:00:00",
                                "ends": "2021-10-15T00:00:00-06:00"
                            },
                            "type": "ESTIMATED_DELIVERY"
                            }
                        ]
                        },
                        "description": "Redirect the package to the hold location.",
                        "type": "REDIRECT_TO_HOLD_AT_LOCATION",
                        "status": "HELD"
                    }
                    ],
                    "estimatedDeliveryTimeWindow": {
                    "description": "Description field",
                    "window": {
                        "begins": "2021-10-01T08:00:00",
                        "ends": "2021-10-15T00:00:00-06:00"
                    },
                    "type": "ESTIMATED_DELIVERY"
                    },
                    "pieceCounts": [
                    {
                        "count": "35",
                        "description": "picec count description",
                        "type": "ORIGIN"
                    }
                    ],
                    "originLocation": {
                    "locationId": "SEA",
                    "locationContactAndAddress": {
                        "address": {
                        "addressClassification": "BUSINESS",
                        "residential": false,
                        "streetLines": [
                            "1043 North Easy Street",
                            "Suite 999"
                        ],
                        "city": "SEATTLE",
                        "stateOrProvinceCode": "WA",
                        "postalCode": "98101",
                        "countryCode": "US",
                        "countryName": "United States"
                        }
                    }
                    },
                    "recipientInformation": {
                    "address": {
                        "addressClassification": "BUSINESS",
                        "residential": false,
                        "streetLines": [
                        "1043 North Easy Street",
                        "Suite 999"
                        ],
                        "city": "SEATTLE",
                        "stateOrProvinceCode": "WA",
                        "postalCode": "98101",
                        "countryCode": "US",
                        "countryName": "United States"
                    }
                    },
                    "standardTransitTimeWindow": {
                    "description": "Description field",
                    "window": {
                        "begins": "2021-10-01T08:00:00",
                        "ends": "2021-10-15T00:00:00-06:00"
                    },
                    "type": "ESTIMATED_DELIVERY"
                    },
                    "shipmentDetails": {
                    "contents": [
                        {
                        "itemNumber": "RZ5678",
                        "receivedQuantity": "13",
                        "description": "pulyurethane rope",
                        "partNumber": "RK1345"
                        }
                    ],
                    "beforePossessionStatus": false,
                    "weight": [
                        {
                        "unit": "LB",
                        "value": "22222.0"
                        }
                    ],
                    "contentPieceCount": "3333",
                    "splitShipments": [
                        {
                        "pieceCount": "10",
                        "statusDescription": "status",
                        "timestamp": "2019-05-07T08:00:07",
                        "statusCode": "statuscode"
                        }
                    ]
                    },
                    "reasonDetail": {
                    "description": "Wrong color",
                    "type": "REJECTED"
                    },
                    "availableNotifications": [
                    "ON_DELIVERY",
                    "ON_EXCEPTION"
                    ],
                    "shipperInformation": {
                    "address": {
                        "addressClassification": "BUSINESS",
                        "residential": false,
                        "streetLines": [
                        "1043 North Easy Street",
                        "Suite 999"
                        ],
                        "city": "SEATTLE",
                        "stateOrProvinceCode": "WA",
                        "postalCode": "98101",
                        "countryCode": "US",
                        "countryName": "United States"
                    }
                    },
                    "lastUpdatedDestinationAddress": {
                    "addressClassification": "BUSINESS",
                    "residential": false,
                    "streetLines": [
                        "1043 North Easy Street",
                        "Suite 999"
                    ],
                    "city": "SEATTLE",
                    "stateOrProvinceCode": "WA",
                    "postalCode": "98101",
                    "countryCode": "US",
                    "countryName": "United States"
                    }
                }
                ]
            }
            ],
            "alerts": "TRACKING.DATA.NOTFOUND -  Tracking data unavailable"
        }
        }
        */
        $data = $this->fetchTrackingData($shipment);

        $status = $data['output']['completeTrackResults'][0]['trackResults'][0]['latestStatusDetail']['description'] ?? null;

        // FedEx API does not provide latitude and longitude in this response
        $latitude = null;
        $longitude = null;

        // use geocoder class to get lat/lon from location if needed
        $geocoder = $this->getGeocoder();

        try {
            $position = $geocoder->geocode(
                trim(
                    ($data['output']['completeTrackResults'][0]['trackResults'][0]['destinationLocation']['locationContactAndAddress']['address']['city'] ?? '') . ' ' .
                    ($data['output']['completeTrackResults'][0]['trackResults'][0]['destinationLocation']['locationContactAndAddress']['address']['postalCode'] ?? '') . ' ' .
                    ($data['output']['completeTrackResults'][0]['trackResults'][0]['destinationLocation']['locationContactAndAddress']['address']['countryCode'] ?? '')
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
