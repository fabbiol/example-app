<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Densidade da brita (t/m³)
    |--------------------------------------------------------------------------
    |
    | Usada para converter entre metro cúbico e tonelada nas expedições.
    | Valor típico de brita/basalto compactado no caminhão: 1,45 t/m³.
    |
    */

    'stone_density' => (float) env('STONE_DENSITY', 1.45),

    /*
    |--------------------------------------------------------------------------
    | Capacidade padrão da concha (m³)
    |--------------------------------------------------------------------------
    |
    | Usada no carregamento estimado por número de conchas da pá carregadeira.
    | O operador pode informar outra capacidade no momento do lançamento.
    |
    */

    'default_bucket_capacity_m3' => (float) env('BUCKET_CAPACITY_M3', 1.5),

];
