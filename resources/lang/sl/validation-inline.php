<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'        => 'Polje mora biti sprejeto.',
    'active_url'      => 'URL ni veljaven.',
    'after'           => 'Datum mora biti kasneje kot :date.',
    'after_or_equal'  => 'Datum mora bit enak ali kasneje kot :date.',
    'alpha'           => 'Polje lahko vsebuje le črke.',
    'alpha_dash'      => 'Polje lahko vsebuje le črke, številke, pomišljaje in podčrtaje.',
    'alpha_num'       => 'Polje lahko vsebuje le črke in številke.',
    'array'           => 'Polje mora biti lista.',
    'before'          => 'Datum mora biti pred :date.',
    'before_or_equal' => 'Datum mora biti pred ali enak :date.',
    'between'         => [
        'numeric' => 'Vrednost mora biti med :min in :max.',
        'file'    => 'Datoteka mora biti med :min in :max kilobajti.',
        'string'  => 'Vnos mora biti dolg od :min do :max znakov.',
        'array'   => 'Polje mora imeti od :min do :max elementov.',
    ],
    'boolean'        => 'Polje mora biti da ali ne.',
    'confirmed'      => 'Potrditev se ne ujema.',
    'date'           => 'Datum ni veljaven.',
    'date_equals'    => 'Datum mora biti enak :date.',
    'date_format'    => 'Datum ne ustreza formatu :format.',
    'different'      => 'Vrednost mora biti drugačna od :other.',
    'digits'         => 'Vnos mora vsebovati :digits števk.',
    'digits_between' => 'Vnos mora vsebovati od :min do :max števk.',
    'dimensions'     => 'Slika ni pravilnih dimenzij.',
    'distinct'       => 'Polje ima podvojeno vrednost.',
    'email'          => 'Elektronski naslov mora biti veljaven.',
    'ends_with'      => 'Vnos se mora končati z eno od naslednjih vrednosti: :values.',
    'exists'         => 'Izbrana vrednost ni veljavna.',
    'file'           => 'Vsebina mora biti datoteka.',
    'filled'         => 'Polje mora biti izpolnjeno.',
    'gt'             => [
        'numeric' => 'Vrednost mora biti večja od :value.',
        'file'    => 'Velikost datoteke mora biti večja od :value kilobajtov.',
        'string'  => 'Vnos mora biti daljši od :value znakov.',
        'array'   => 'Polje mora imeti več kot :value vrednosti.',
    ],
    'gte' => [
        'numeric' => 'Vrednost mora biti najmanj :value.',
        'file'    => 'Velikost datoteke mora biti najmanj :value kilobajtov.',
        'string'  => 'Vnos mora biti dolg najmanj :value znakov.',
        'array'   => 'Polje mora vsebovati najmanj :value elementov.',
    ],
    'image'    => 'To polje mora biti slika.',
    'in'       => 'Izbrana vrednost ni veljavna.',
    'in_array' => 'Izbrana vrednost ne obstaja v :other.',
    'integer'  => 'To polje mora biti številka.',
    'ip'       => 'To polje mora biti veljaven IP naslov.',
    'ipv4'     => 'To polje mora biti veljaven IPv4 naslov.',
    'ipv6'     => 'To polje mora biti veljaven IPv6 naslov.',
    'json'     => 'To polje mora biti veljaven JSON vnos.',
    'lt'       => [
        'numeric' => 'Vrednost mora biti manjša od :value.',
        'file'    => 'Velikost datoteke mora biti manjša od :value kilobajtov.',
        'string'  => 'Vnos mora biti krajši od :value znakov.',
        'array'   => 'Polje mora vsebovati manj kot :value elementov.',
    ],
    'lte' => [
        'numeric' => 'Vrednost ne sme biti večja od :value.',
        'file'    => 'Velikost datoteke ne sme presegati :value kilobajtov.',
        'string'  => 'Vnos ne sme biti daljši od :value znakov.',
        'array'   => 'Polje ne sme vsebovati več kot :value elementov.',
    ],
    'max' => [
        'numeric' => 'Vrednost ne sme biti večja od :max.',
        'file'    => 'Velikost datoteke ne sme presegati :max kilobajtov.',
        'string'  => 'Vnos ne sme biti daljši od :max znakov.',
        'array'   => 'Polje ne sme vsebovati več kot :max elementov.',
    ],
    'mimes'     => 'Datoteka mora biti tipa: :values.',
    'mimetypes' => 'Datoteka mora biti tipa: :values.',
    'min'       => [
        'numeric' => 'Vrednost je lahko najmanj :min.',
        'file'    => 'Velikost datoteke je lahko najmanj :min kilobajtov.',
        'string'  => 'Vnos ima lahko najmanj :min znakov.',
        'array'   => 'Polje mora vsebovati najmanj :min elementov.',
    ],
    'multiple_of'          => 'Vrednost mora biti večkratnik :value',
    'not_in'               => 'Izbrana vrednost ni veljavna.',
    'not_regex'            => 'Oblika vnosa ni veljavna.',
    'numeric'              => 'Vnešena mora biti številka.',
    'password'             => 'Geslo ni pravilno.',
    'present'              => 'Polje mora biti izpolnjeno.',
    'regex'                => 'Oblika vnosa ni veljavna.',
    'required'             => 'Polje je obvezno.',
    'required_if'          => 'Polje je obvezno, ko je v polju :other izbrana vrednost :value.',
    'required_unless'      => 'Polje je obvezno, razen če je v polju :other izbrana vrednost :values.',
    'required_with'        => 'Polje je obvezno, ko je vnešena vrednost :values.',
    'required_with_all'    => 'Polje je obvezno, ko so vnešene vrednosti :values.',
    'required_without'     => 'Polje je obvezno, ko ni vnešena vrednost :values.',
    'required_without_all' => 'Polje je obvezno, če niso vnešene vrednosti :values.',
    'same'                 => 'Vrednost polja se mora ujemati z vrednostjo polja :other.',
    'size'                 => [
        'numeric' => 'Vrednost mora biti :size.',
        'file'    => 'Datoteka mora biti velika :size kilobajtov.',
        'string'  => 'Vnos mora biti dolg :size znakov.',
        'array'   => 'Polje mora vsebovati :size elementov.',
    ],
    'starts_with' => 'Vrednost se mora začeti z eno od naslednjih možnosti: :values.',
    'string'      => 'Vnos mora biti besedilo.',
    'timezone'    => 'Časovni pas mora biti veljaven.',
    'unique'      => 'Vnešeno je že zasedeno.',
    'uploaded'    => 'Prenos ni bil uspešen.',
    'url'         => 'Oblika ni pravilna.',
    'uuid'        => 'Vnos mora biti veljaven UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

];
