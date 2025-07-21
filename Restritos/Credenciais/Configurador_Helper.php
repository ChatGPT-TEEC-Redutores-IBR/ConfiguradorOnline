<?php
function codigo_amigavel(string $codigo): string {
    static $map = [
        // Redutores Quadrados
        '1.Q'   => 'IBRQ',
        '1.QDR' => 'IBRQDR',
        '1.QP'  => 'IBRQP',

        // Linha Coaxial
        '1.C'  => 'IBRC',
        '1.H'  => 'IBRH',
        '1.M'  => 'IBRM',
        '1.P'  => 'IBRP',
        '1.R'  => 'IBRR',
        '1.X'  => 'IBRX',

        // Linha F Series
        '1.FFA' => 'IBRPFFA',
        '1.FKA' => 'IBRXFKA',
        '1.FR'  => 'IBRCFR',

        // Linha Motorredutores
        '2.I'    => 'IBRSTANDARD',
        '3.I'    => 'IBRALTORENDIMENTO',
        '3.W'    => 'WEGALTORENDIMENTO',
        '3.APM'  => 'ANTICORROSIVOSAPM',
        '3.SPM'  => 'ANTICORROSIVOSSPM',

        // Linha Planetares
        '3.PB'  => 'IBRPB',
        '3.PBL' => 'IBRPBL',
        '3.SA'  => 'IBRSA',
        '3.SB'  => 'IBRSB',
        '3.SBL' => 'IBRSBL',
        '3.SD'  => 'IBRSD',

        // Linha V
        '1.V'   => 'IBRV',

        // Linha Anticorrosivos
        '1.I'   => 'IBRI',
        '1.Z'   => 'IBRZ',

        // Linha Extrusoras
        '3.GR'  => 'IBRGR',
        '3.GS'  => 'IBRGS',
        '3.RIC' => 'IBRRIC',

        // Linha Inversores
        '4.K'   => 'IBRK',

        // Fallbacks using códigos base
        'QU'    => 'IBRQ',
        'QUDR'  => 'IBRQDR',
        'HY'    => 'IBRC',
        'FX'    => 'IBRPFFA',
        'MO'    => 'IBRSTANDARD',
        'PL'    => 'IBRPB',
        'VA'    => 'IBRV',
        'AC'    => 'IBRI',
        'AE'    => 'IBRGR',
        'IN'    => 'IBRK',
    ];

    return $map[$codigo] ?? $codigo;
}