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

use App\Base\Interfaces\Commerce\ShipmentTrackerInterface;
use App\Base\Models\OrderShipment;
use App\Base\Abstracts\ContainerAwareObject;
use Degami\Basics\Exceptions\BasicException;

class GLSTracker extends ContainerAwareObject implements ShipmentTrackerInterface
{
    private const CARRIER_CODE = 'gls';

    protected string $apiUsername;
    protected string $apiPassword;
    protected string $apiBaseUrl;


    public function __construct(
        protected $container,
    ) {
        parent::__construct($container);

        $this->apiBaseUrl = $this->getSiteData()->getConfigValue('commerce/shipment_trackers/gls/api_base', 'https://api.gls-group.eu');
        $this->apiUsername = $this->getSiteData()->getConfigValue('commerce/shipment_trackers/gls/api_username', '');
        $this->apiPassword = $this->getSiteData()->getConfigValue('commerce/shipment_trackers/gls/api_password', '');
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

        $utils = $this->getUtils();
        $response = $utils->httpRequest(
            "{$this->apiBaseUrl}/tracking/references/{$trackingNumber}",
            "GET",
            [
                'headers' => [
                    'Accept-Language' => 'en',
                    'Accept-Encoding' => 'gzip,deflate',
                    'Authorization' => 'Basic ' . base64_encode($this->apiUsername.':'.$this->apiPassword),
                    'Content-Type' => 'application/json',
                ]
            ]
        );

        return (array)$response;
    }

    public function updateShipmentStatus(OrderShipment $shipment): void
    {
        /*
        {
        "parcels": [
            {
            "timestamp": "2019-01-21T12:22:11",
            "status": "DELIVERED",
            "trackid": "xxx",
            "events": [
                {
                "timestamp": "2019-01-21T12:22:11",
                "description": "Das Paket wurde erfolgreich zugestellt.",
                "location": "Mainz-Hechtsheim",
                "country": "DE",
                "code": "3.0"
                },
                {
                "timestamp": "2019-01-21T12:22:10",
                "description": "Das Paket wurde an GLS übergeben.",
                "location": "Mainz-Hechtsheim",
                "country": "DE",
                "code": "0.0"
                },
                {
                "timestamp": "2019-01-21T11:51:37",
                "description": "Die Paketdaten wurden im GLS IT-System erfasst; das Paket wurde noch nicht an GLS übergeben.",
                "location": "Wesel",
                "country": "DE",
                "code": "0.100"
                }
            ],
            "references": [
                {
                "type": "UNITNO",
                "name": "Paketnummer:",
                "value": "xxx"
                },
                {
                "type": "UNIQUENO",
                "name": "Track ID",
                "value": "xxx"
                },
                {
                "type": "CUSTREF",
                "name": "Kundeneigene Empfängernummer.",
                "value": "Abed-01"
                },
                {
                "type": "CUSTREF",
                "name": "Kundeneigene Referenznummer - pro TU",
                "value": "Cancellation_02_2"
                }
            ]
            }
        ]
        }
        */
        $data = $this->fetchTrackingData($shipment);
        $status = $data['parcels'][0]['status'] ?? null;
        $latitude = null;
        $longitude = null;
        // GLS API does not provide latitude and longitude in this response

        $shipment->setStatus($status)->persist();
    }
}
