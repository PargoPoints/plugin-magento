<?php

namespace Pargo\CustomShipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Pargo\CustomShipping\Helper\Config;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order;
use \Psr\Log\LoggerInterface;

class ProcessShipment implements ObserverInterface
{
    private $_objectManager;
    private $logger;
    protected $helper;
    protected $curl;
    protected $track;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        Config $helper,
        Curl $curl,
        TrackFactory $track, 
        LoggerInterface$logger
    ) {
        $this->_objectManager = $objectmanager;
        $this->helper = $helper;
        $this->curl = $curl;
        $this->track = $track;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->logger->info('Pargo: Execute Shipment');

        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        if ($order->getShippingMethod() !== 'pargo_customshipping_pargo_customshipping') {
            $this->logger->info('Pargo: Shipping method mismatch');
            $this->logger->info('Pargo: Shipping method set is ' . $order->getShippingMethod());
            return;
        }

        $this->logger->info('Pargo: Shipping method matched successfully');

        $shippingAddress = $invoice->getShippingAddress()->getData();
        $billingAddress = $invoice->getBillingAddress()->getData();
        // Fix start
        // we need to account for multiple dashes in the address and take the last item in array as this is the pup code
        // the fact that some pups have dashes in their names has brought out this code limitation.
        // remove
        // $pickUpPointCode = explode('-', $shippingAddress['company'])[1];
        // add
        $addressDetails = explode('-', $shippingAddress['company']);

        $this->logger->info('Pargo: Shipping Address Details' . $shippingAddress['company']);

        $size = sizeof($addressDetails);
        $pickUpPointCode = $addressDetails[$size-1];
        $this->logger->info('Pargo: Pickup Point Reference: ' . $pickUpPointCode);
        // Fix end
        
        $this->logger->info('Pargo: Submit Shipping');

        $this->submitShipment($order, $billingAddress, $pickUpPointCode);
    }

    /**
     * @param Order $order
     * @param array $billingAddress
     * @param string $pickUpPointCode
     */
    private function submitShipment($order, $billingAddress, $pickUpPointCode)
    {
        $this->logger->info('Pargo: Submit Shipment');

        $token = $this->authenticate();
        if (!$token) {

            $this->logger->error('Pargo: API Authentication Failed.');

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
            $this->logger->error('Pargo: Order API request failed.');
            $order->addStatusHistoryComment("Pargo consignment export failed");
            $order->save();

            return;
        } else {
            $response = json_decode($response);

            $this->logger->info('Pargo: Order tracking code: ' . $response->data->attributes->orderData->trackingCode);

            $message = "Success! Created waybill <a href='" . $response->data->attributes->orderData->orderLabel . "' target='_blank'>" . $response->data->attributes->orderData->trackingCode . "</a>";
            $order->addStatusHistoryComment($message);
            $order->save();

            $this->logger->info('Pargo: Create Shipment');

            $this->createShipment($order, $response->data->attributes->orderData->trackingCode); // Magento Shipment

            $this->logger->info('Pargo: Shipment Created');
        }
    }

    /**
     * @return bool
     */
    private function authenticate()
    {
        $this->logger->info('Pargo: Authenticating API');

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
            $this->logger->error('Pargo: Failed to authenticate API');
            return false;
        } else {
            $this->logger->info('Pargo: API Authentication successful');
            $response = json_decode($response);

            return $response->access_token;
        }
    }

    private function createShipment($order, $trackingCode)
    {
        $this->logger->info('Pargo: Shipment Check');

        // Check if order can be shipped or has already shipped
        if (! $order->canShip()) {

            $this->logger->info('Pargo: Order Cant Ship');

            throw new \Magento\Framework\Exception\LocalizedException(
                            __('You can\'t create an shipment.')
                        );
        }

        // Initialize the order shipment object
        $convertOrder = $this->_objectManager->create('Magento\Sales\Model\Convert\Order');
        $shipment = $convertOrder->toShipment($order);

        // Loop through order items
        foreach ($order->getAllItems() AS $orderItem) {
            // Check if order item has qty to ship or is virtual
            if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            $qtyShipped = $orderItem->getQtyToShip();

            // Create shipment item with qty
            $shipmentItem = $convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

            // Add shipment item to shipment
            $shipment->addItem($shipmentItem);
        }

        // Register shipment
        $shipment->register();
        $order->setIsInProcess(true);

        $data = array(
            'carrier_code' => 'pargo_customshipping',
            'title' => 'Pargo Tracking Code',
            'number' => $trackingCode, 
        );

        try {
            $this->logger->info('Pargo: Save Shipment');

            // Save created shipment and order
                $track = $this->_objectManager->create('Magento\Sales\Model\Order\Shipment\TrackFactory')->create()->addData($data);
                $shipment->addTrack($track)->save();
                $shipment->save();
                $order->save();
             
            // Send email
            $this->_objectManager->create('Magento\Shipping\Model\ShipmentNotifier')
                    ->notify($shipment);
             
                $shipment->save();

                $this->logger->info('Pargo: Shipment saved');

        } catch (\Exception $e) {

                $this->logger->info('Pargo: Could not save Shipment');

                throw new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );
        }
    }
}


