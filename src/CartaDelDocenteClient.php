<?php

namespace Datomatic\CartaDelDocente;

use Datomatic\CartaDelDocente\Exceptions\RequestException;
use Exception;
use SoapClient;

class CartaDelDocenteClient
{
    protected string $location;

    public function __construct(protected string $wsdlPath, protected string $certificatePath, protected string $certificatePassword, bool $production = true)
    {
        if ($production) {
            $this->location = 'https://ws.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher';
        } else {
            $this->location = 'https://wstest.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher';
        }
    }

    /**
     * @return SoapClient
     * @throws \SoapFault
     */
    public function getSoapClient()
    {
        return new SoapClient(
            $this->wsdlPath,
            [
                'local_cert' => $this->certificatePath,
                'location' => $this->location,
                'passphrase' => $this->certificatePassword,
                'stream_context' => stream_context_create(
                    [
                        'http' => [
                            'user_agent' => 'PHP/SOAP',
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                    ]
                ),
            ]
        );
    }

    /**
     * @param string $operationType
     * @param string $voucher
     * @param float $amount
     * @return bool
     */
    public function confirm(string $operationType, string $voucher, float $amount): bool
    {
        $response = $this->request('Confirm', [$operationType, $voucher, $amount]);

        return new CartaDelDocenteResponse(
            name: $response->checkResp->nominativoBeneficiario,
            vatId: $response->checkResp->partitaIvaEsercente,
            scope: $response->checkResp->ambito,
            good: $response->checkResp->bene,
            amount: floatval($response->checkResp->importo),
        );
    }

    /**
     * @return CartaDelDocenteResponse
     */
    public function merchantActivation(): CartaDelDocenteResponse
    {
        return $this->check('1', '11aa22bb');
    }

    /**
     * @param string $operationType
     * @param string $voucher
     * @return CartaDelDocenteResponse
     */
    public function check(string $operationType, string $voucher): CartaDelDocenteResponse
    {
        $response = $this->request('Check', [$operationType, $voucher]);

        return new CartaDelDocenteResponse(
            name: $response->checkResp->nominativoBeneficiario,
            vatId: $response->checkResp->partitaIvaEsercente,
            scope: $response->checkResp->ambito,
            good: $response->checkResp->bene,
            amount: floatval($response->checkResp->importo),
        );
    }

    /**
     * @param string $function
     * @param ...$args
     * @return object
     */
    public function request(string $function, ...$args): object
    {
        try {
            return $this->getSoapClient()->$function($args);
        } catch (Exception $e) {
            throw new RequestException(
                code: $e->detail ? $e->detail->FaultVoucher->exceptionCode : $e->faultcode,
                message: $e->detail ? $e->detail->FaultVoucher->exceptionMessage : $e->faultstring
            );
        }
    }
}
