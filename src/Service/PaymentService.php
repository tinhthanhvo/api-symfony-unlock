<?php

namespace App\Service;

use App\Entity\PurchaseOrder;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class PaymentService
{

    /**
     * @var ContainerBagInterface
     */
    private $containerBag;

    /**
     * @param ContainerBagInterface $containerBag
     */
    public function __construct(ContainerBagInterface $containerBag)
    {
        $this->containerBag = $containerBag;
    }

    /**
     * @return ApiContext
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getApiContext(): ApiContext
    {
        $apiContext = new ApiContext(
            new OAuthTokenCredential($this->containerBag->get('app.paypal_client')
                , $this->containerBag->get('app.paypal_secret'))
        );

        $apiContext->setConfig([
            'mode' => true ? 'sandbox' : 'live'
        ]);

        return $apiContext;
    }

    /**
     * @param PurchaseOrder $order
     * @param ApiContext $apiContext
     * @param string $approveUrl
     * @param string $cancelUrl
     * @return Payment
     * @throws \Exception
     */
    public function createPayment(PurchaseOrder $order, ApiContext $apiContext, string $approveUrl, string $cancelUrl)
    {
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $currency = 'USD';
        $amountPayable = $order->getTotalPrice();
        $description = 'Paypal transaction';
        $invoiceNumber = uniqid();

        $amount = new Amount();
        $amount->setCurrency($currency)
            ->setTotal($amountPayable);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setDescription($description)
            ->setInvoiceNumber($invoiceNumber);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($approveUrl)
            ->setCancelUrl($cancelUrl);

        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setTransactions([$transaction])
            ->setRedirectUrls($redirectUrls);

        try {
            $payment->create($apiContext);
        } catch (\Exception $e) {
            throw new \Exception('Unable to create link for payment');
        }

        return $payment;
    }
}
