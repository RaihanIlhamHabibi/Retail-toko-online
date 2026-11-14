<?php

namespace Aimeos\MShop\Service\Provider\Payment;

use Aimeos\MShop\Common\Helper\Form\Standard as Form;
use Aimeos\MShop\Order\Item\Base as OrderBase;
use Aimeos\MShop\Order\Item\Iface as OrderItem;
use Aimeos\MShop\Order\Item\Service\Base as OrderServiceBase;
use Aimeos\MShop\Service\Exception as ServiceException;
use Aimeos\MShop\Service\Provider\Payment\Base;
use Aimeos\MShop\Service\Provider\Payment\Iface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Midtrans extends Base implements Iface
{
    public function isAvailable(OrderItem $basket): bool
    {
        return $this->serverKey() !== '' && $basket->getPrice()->getCurrencyId() === 'IDR';
    }

    public function process(OrderItem $order, array $params = []): ?\Aimeos\MShop\Common\Helper\Form\Iface
    {
        $price = $order->getPrice();

        if ($price->getCurrencyId() !== 'IDR') {
            throw new ServiceException('Midtrans payments require IDR prices. Add IDR prices to the products first.');
        }

        $orderId = (string) $order->getId();
        if (!preg_match('/^[A-Za-z0-9._~-]{1,50}$/', $orderId)) {
            throw new ServiceException('The order ID is not valid for Midtrans.');
        }

        $amount = (int) $this->getAmount($price, true, true, 0);
        if ($amount < 1) {
            throw new ServiceException('The order total must be greater than zero for Midtrans.');
        }

        $this->configureGateway();

        try {
            $transaction = \Midtrans\Snap::createTransaction([
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount,
                ],
                'credit_card' => ['secure' => true],
            ]);
        } catch (\Exception $exception) {
            $this->context()->logger()->error($exception->getMessage(), 'service/payment/midtrans');
            throw new ServiceException('Midtrans could not start the payment. Check the sandbox credentials and try again.');
        }

        $redirectUrl = (string) ($transaction->redirect_url ?? '');
        $expectedHost = config('services.midtrans.is_production')
            ? 'app.midtrans.com'
            : 'app.sandbox.midtrans.com';

        if (parse_url($redirectUrl, PHP_URL_SCHEME) !== 'https'
            || strtolower((string) parse_url($redirectUrl, PHP_URL_HOST)) !== $expectedHost
        ) {
            throw new ServiceException('Midtrans returned an invalid payment URL.');
        }

        $service = $this->getBasketService(
            $order,
            OrderServiceBase::TYPE_PAYMENT,
            $this->getServiceItem()->getCode()
        );
        $service->addAttributeItems($this->attributes(['order_id' => $orderId], 'midtrans/tx'));

        return new Form($redirectUrl, 'GET', [], true);
    }

    public function updateSync(ServerRequestInterface $request, OrderItem $order): OrderItem
    {
        return $this->refreshStatus($order);
    }

    public function query(OrderItem $order): OrderItem
    {
        return $this->refreshStatus($order);
    }

    public function updatePush(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $payload = $request->getParsedBody();

        if (is_object($payload)) {
            $payload = (array) $payload;
        }

        if (!is_array($payload)) {
            $payload = json_decode((string) $request->getBody(), true);
        }

        $orderId = is_array($payload) ? (string) ($payload['order_id'] ?? '') : '';
        if (!preg_match('/^[A-Za-z0-9._~-]{1,50}$/', $orderId)) {
            return $response->withStatus(400, 'Invalid Midtrans order ID');
        }

        $orderManager = \Aimeos\MShop::create($this->context(), 'order');

        try {
            $order = $orderManager->get($orderId, ['order/service']);
            $this->getBasketService(
                $order,
                OrderServiceBase::TYPE_PAYMENT,
                $this->getServiceItem()->getCode()
            );
            $orderManager->save($this->refreshStatus($order));
        } catch (\Aimeos\MShop\Order\Exception $exception) {
            return $response->withStatus(404, 'Order not found');
        } catch (\Aimeos\MShop\Service\Exception $exception) {
            return $response->withStatus(400, 'Order does not use this payment service');
        } catch (\Exception $exception) {
            $this->context()->logger()->error(
                sprintf('Midtrans notification update failed for order %s: %s', $orderId, $exception->getMessage()),
                'service/payment/midtrans'
            );

            return $response->withStatus(502, 'Unable to verify Midtrans payment status');
        }

        return $response->withStatus(200);
    }

    public function isImplemented(int $what): bool
    {
        return $what === Base::FEAT_QUERY;
    }

    private function refreshStatus(OrderItem $order): OrderItem
    {
        $orderId = (string) $order->getId();
        $this->configureGateway();

        try {
            $transaction = \Midtrans\Transaction::status($orderId);
        } catch (\Exception $exception) {
            $this->context()->logger()->error($exception->getMessage(), 'service/payment/midtrans');
            throw new ServiceException('Unable to verify the payment status with Midtrans.');
        }

        if ((string) ($transaction->order_id ?? '') !== $orderId
            || strtoupper((string) ($transaction->currency ?? '')) !== 'IDR'
        ) {
            throw new ServiceException('The Midtrans response does not match this order.');
        }

        $expectedAmount = (int) $this->getAmount($order->getPrice(), true, true, 0);
        $paidAmount = (int) round((float) ($transaction->gross_amount ?? 0));

        if ($expectedAmount !== $paidAmount) {
            throw new ServiceException('The Midtrans payment amount does not match the order total.');
        }

        $status = strtolower((string) ($transaction->transaction_status ?? ''));

        return match ($status) {
            'settlement' => $order->setStatusPayment(OrderBase::PAY_RECEIVED),
            'capture' => strtolower((string) ($transaction->fraud_status ?? '')) === 'accept'
                ? $order->setStatusPayment(OrderBase::PAY_RECEIVED)
                : $order->setStatusPayment(OrderBase::PAY_PENDING),
            'pending', 'authorize' => $order->setStatusPayment(OrderBase::PAY_PENDING),
            'deny', 'cancel', 'expire', 'failure' => $order->setStatusPayment(OrderBase::PAY_CANCELED),
            'refund', 'partial_refund', 'chargeback', 'partial_chargeback' => $order->setStatusPayment(OrderBase::PAY_REFUND),
            default => throw new ServiceException('Midtrans returned an unsupported transaction status.'),
        };
    }

    private function configureGateway(): void
    {
        $serverKey = $this->serverKey();

        if ($serverKey === '') {
            throw new ServiceException('Set MIDTRANS_SERVER_KEY in the local .env file before enabling Midtrans.');
        }

        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = (bool) config('services.midtrans.is_production', false);
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }

    private function serverKey(): string
    {
        return trim((string) config('services.midtrans.server_key', ''));
    }
}
