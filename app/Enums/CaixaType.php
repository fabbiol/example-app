<?php

namespace App\Enums;

enum CaixaType: string
{
    case Entrada = 'entrada';
    case Saida = 'saida';
    case Cartao = 'cartao';
    case Cheque = 'cheque';
    case Boleto = 'boleto';
    case Deposito = 'deposito';
    case Pix = 'pix';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Saida => 'Saída',
            self::Cartao => 'Cartão',
            self::Cheque => 'Cheque',
            self::Boleto => 'Boleto',
            self::Deposito => 'Depósito',
            self::Pix => 'Pix',
        };
    }
}
