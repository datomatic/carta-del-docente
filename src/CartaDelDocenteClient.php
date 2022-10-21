<?php

namespace Datomatic\CartaDelDocente;

use Datomatic\CartaDelDocente\Exceptions\RequestException;
use Exception;
use SoapClient;
use SoapFault;

class CartaDelDocenteClient
{
    protected SoapClient $client;

    /**
     * @throws SoapFault
     */
    public function __construct(string $certificatePath, string $certificatePassword, bool $production = true)
    {
        $this->client = $this->getSoapClient($certificatePath, $certificatePassword, $production);
    }

    /**
     * @return SoapClient
     * @throws SoapFault
     */
    private function getSoapClient(string $certificatePath, string $certificatePassword, bool $production): SoapClient
    {
        if ($production) {
            $location = 'https://ws.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher';
        } else {
            $location = 'https://wstest.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher';
        }

        return new SoapClient(
            __DIR__.'/Wsdl/VerificaVoucher.wsdl',
            [
                'local_cert' => $certificatePath,
                'location' => $location,
                'passphrase' => $certificatePassword,
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

    public function client(): SoapClient
    {
        return $this->client;
    }

    /**
     * @param string $operationType
     * @param string $voucher
     * @param float $amount
     * @return bool
     */
    public function confirm(string $operationType, string $voucher, float $amount): bool
    {
        $response = $this->request('Confirm', [
            'checkReq' => [
                'tipoOperazione' => $operationType,
                'codiceVoucher' => $voucher,
                'importo' => $amount,
            ],
        ]);

        return $response->checkResp->esito === "OK";
    }

    /**
     * @param string $operationType
     * @param string $voucher
     * @return CartaDelDocenteResponse
     */
    public function check(string $operationType, string $voucher): CartaDelDocenteResponse
    {
        $response = $this->request('Check', [
            'checkReq' => [
                'tipoOperazione' => $operationType,
                'codiceVoucher' => $voucher,
            ],
        ]);

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
     * @param string $function
     * @param array $options
     * @return object
     */
    public function request(string $function, array $options): object
    {
        try {
            return $this->client()->$function($options);
        } catch (Exception $e) {
            throw new RequestException(
                code: $e->detail ? $e->detail->FaultVoucher->exceptionCode : $e->faultcode,
                message: $e->detail ? $e->detail->FaultVoucher->exceptionMessage : $e->faultstring
            );
        }
    }
}
