<?php

namespace Pargo\CustomShipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Pargo\CustomShipping\Helper\Config;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\Interceptor as Order;
use Magento\Sales\Model\Order\Shipment;

class ProcessShipment implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $helper;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var TrackFactory
     */
    protected $track;

    /**
     * ProcessShipment constructor.
     * @param Config $helper
     * @param Curl $curl
     * @param TrackFactory $track
     */
    public function __construct(
        Config $helper,
        Curl $curl,
        TrackFactory $track
    ) {
        $this->helper = $helper;
        $this->curl = $curl;
        $this->track = $track;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();

        if ($order->getShippingMethod() !== 'pargo_customshipping_pargo_customshipping') {
            return;
        }

        $shippingAddress = $order->getShippingAddress()->getData();
        $billingAddress = $order->getBillingAddress()->getData();
        $pickUpPointCode = explode('-', $shippingAddress['company'])[1];
        $this->submitShipment($order, $billingAddress, $pickUpPointCode, $shipment);
    }

    /**
     * @param Order $order
     * @param array $billingAddress
     * @param string $pickUpPointCode
     * @param Shipment $shipment
     */
    private function submitShipment($order, $billingAddress, $pickUpPointCode, $shipment)
    {
        $token = $this->authenticate();

        if (!$token) {
            $order->addStatusHistoryComment("Pargo authentication failed");
            $order->save();

            return;
        }

        $data = [
            'data' => [
                'type' => 'W2P',
                'attributes' => [
                    'warehouseAddressCode' => '',
                    'returnAddressCode' => '',
                    'trackingCode' => '',
                    'externalReference' => $order->getIncrementId(),
                    'pickupPointCode' => $pickUpPointCode,
                    'courierCode' => '',
                    'consignee' => [
                        'firstName' => $billingAddress['firstname'],
                        'lastName' => $billingAddress['lastname'],
                        'email' => $billingAddress['email'],
                        'phoneNumbers' => [
                            $billingAddress['telephone']
                        ]
                    ]
                ]
            ]
        ];
        $url = $this->helper->getUrl();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url . '/orders',
            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $order->addStatusHistoryComment("Pargo consignment export failed");
            $order->save();

            return;
        } else {
            $response = json_decode($response);

            $data = [
                'carrier_code' => 'custom',
                'title' => 'Pargo Tracking Code',
                'number' => $response->data->attributes->orderData->trackingCode, // Replace with your tracking number
            ];

            $track = $this->track->create()->addData($data);
            $shipment->addTrack($track)->save();
            $message = "Success! Created waybill <a href='" . $response->data->attributes->orderData->orderLabel . "' target='_blank'>" . $response->data->attributes->orderData->trackingCode . "</a>";
            $order->addStatusHistoryComment($message);
            $order->save();
        }
    }

    /**
     * @return bool
     */
    private function authenticate()
    {
        $url = $this->helper->getUrl();
        $username = $this->helper->getUsername();
        $password = $this->helper->getPassword();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url . '/auth',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode(array('username' => $username, 'password' => $password)),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return false;
        } else {
            $response = json_decode($response);

            return $response->access_token;
        }
    }
}
