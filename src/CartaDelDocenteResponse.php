<?php

namespace Datomatic\CartaDelDocente;

class CartaDelDocenteResponse
{
    public function __construct(
        public string $name,
        public string $vatId,
        public string $scope,
        public string $good,
        public float $amount
    ) {
    }
}
