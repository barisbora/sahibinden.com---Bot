<?php

namespace App\Console\Commands;

use App\Car;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use File;

class FetchCars extends Command
{

    protected $signature = 'fetch:cars';

    protected $counter = 0;

    function handle ()
    {


        /**
         * Login Start
         */

        $cookie = new \GuzzleHttp\Cookie\CookieJar;

        $client = new Client( [
            'cookies'         => $cookie,
            'allow_redirects' => true,
            'debug'           => false,
            'headers'         => [
                'User-Agent'                => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
                'Upgrade-Insecure-Requests' => '1',
                'Accept-Encoding'           => 'gzip, deflate, br',
                'Accept-Language'           => 'tr-TR,tr;q=0.8,en-US;q=0.6,en;q=0.4',
                'Cache-Control'             => 'no-cache',
            ]
        ] );

        $client->get( 'https://secure.sahibinden.com/giris' );

        $res = $client->post( 'https://secure.sahibinden.com/giris', [

            'headers'     => [
                'Origin'  => 'https://secure.sahibinden.com',
                'Host'    => 'secure.sahibinden.com',
                'Referer' => 'https://secure.sahibinden.com/giris',
            ],
            'form_params' => [
                'username'    => 'SAHIBINDEN.COM KULLANICI ADI',
                'password'    => 'SAHIBINDEN.COM ŞİFRE'
            ]

        ] );

        $pattern = '/app-(.*)\.js/i';
        preg_match( $pattern, $res->getBody()->getContents(), $matches );

        if ( ! isset( $matches[ 1 ] ) ) dd( 'Sahibinden güncellenmiş bu script çöp oldu' );

        $js = $client->get( 'https://banaozel.sahibinden.com/assets/js/' . $matches[ 0 ] );

        $pattern = '/\"x-api-key\":\"(\w+)\"/i';
        preg_match( $pattern, $js->getBody()->getContents(), $matches );

        if ( ! isset( $matches[ 1 ] ) ) dd( 'Sahibinden güncellenmiş bu script çöp oldu' );

        $API_KEY = $matches[ 1 ];

        $notXCSRFToken1 = clone $cookie;
        $notXCSRFToken2 = clone $cookie;

        $client->get( 'https://banaozel.sahibinden.com/sahibinden-ral/rest/my/info', [
            'cookies' => $notXCSRFToken1,
            'headers' => [
                'Content-Type'     => 'application/json',
                'Host'             => 'banaozel.sahibinden.com',
                'Accept'           => 'application/json, text/plain, */*',
                'DNT'              => '1',
                'Host'             => 'banaozel.sahibinden.com',
                'x-api-key'        => $API_KEY,
                'x-client-profile' => 'Generic_v1.1',
                'Referrer'         => 'https://banaozel.sahibinden.com/',
            ]
        ] );

        $client->get( 'https://banaozel.sahibinden.com/sahibinden-ral/rest/my/preferences', [
            'cookies' => $notXCSRFToken2,
            'headers' => [
                'Content-Type'     => 'application/json',
                'Host'             => 'banaozel.sahibinden.com',
                'Accept'           => 'application/json, text/plain, */*',
                'DNT'              => '1',
                'Host'             => 'banaozel.sahibinden.com',
                'x-api-key'        => $API_KEY,
                'x-client-profile' => 'Generic_v1.1',
                'Referrer'         => 'https://banaozel.sahibinden.com/',
            ]
        ] );

        /**
         * Login End
         */

        $cookies = $notXCSRFToken2->toArray();

        $xCSRFToken = end( $cookies )[ 'Value' ];

        $res = $client->post( 'https://banaozel.sahibinden.com/sahibinden-ral/rest/my/classifieds/wizard', [
            'cookies' => $notXCSRFToken2,
            'headers' => [
                'Content-Type'     => 'application/json;charset=UTF-8',
                'Accept'           => 'application/json, text/plain, */*',
                'Referer'          => 'https://banaozel.sahibinden.com/ilan-ver/adim-1/?state=new',
                'x-api-key'        => $API_KEY,
                'x-client-profile' => 'Generic_v1.1',
                'x-xsrf-token'     => $xCSRFToken,
            ],
            'json'    => json_decode( '{"categorySpecializedFlowEnabled":"true","elementValues":{"CategoryLevel0":"3517","CategoryLevel1":"3530"},"id":""}', 1 )
        ] );

        $res = @json_decode( $res->getBody()->getContents(), true );

        if ( @$res[ 'success' ] != 'true' ) dd( 'Die!' );

        $elements = end( $res[ 'response' ][ 'sections' ][ 0 ][ 'elements' ] )[ 'enumValues' ];

        $years = collect();

        foreach ( $elements as $element )
        {
            $years->push( $element[ 'id' ] );
        }

        //son on yıl manuel
       // $years = range( 2007, 2017 );

        foreach ( $years as $year )
        {

            $res = $client->post( 'https://banaozel.sahibinden.com/sahibinden-ral/rest/my/classifieds/wizard', [
                'cookies' => $notXCSRFToken2,
                'headers' => [
                    'Content-Type'     => 'application/json;charset=UTF-8',
                    'Accept'           => 'application/json, text/plain, */*',
                    'Referer'          => 'https://banaozel.sahibinden.com/ilan-ver/adim-1/?state=new',
                    'x-api-key'        => $API_KEY,
                    'x-client-profile' => 'Generic_v1.1',
                    'x-xsrf-token'     => $xCSRFToken,
                ],
                'json'    => [
                    'categorySpecializedFlowEnabled' => 'true',
                    'id'                             => '',
                    'elementValues'                  => [
                        'CategoryLevel0' => '3517',
                        'CategoryLevel1' => '3530',
                        'Cars_ModelYear' => $year,
                    ],

                ]
            ] );

            $brands = collect( end( json_decode( $res->getBody()->getContents(), true )[ 'response' ][ 'sections' ][ 0 ][ 'elements' ] )[ 'enumValues' ] )->transform( function ( $item ) {

                return [
                    'id'    => $item[ 'id' ],
                    'label' => $item[ 'label' ],
                ];

            } )->values();

            foreach ( $brands as $brand )
            {


                $res = $client->post( 'https://banaozel.sahibinden.com/sahibinden-ral/rest/my/classifieds/wizard', [
                    'cookies' => $notXCSRFToken2,
                    'headers' => [
                        'Content-Type'     => 'application/json;charset=UTF-8',
                        'Accept'           => 'application/json, text/plain, */*',
                        'Referer'          => 'https://banaozel.sahibinden.com/ilan-ver/adim-1/?state=new',
                        'x-api-key'        => $API_KEY,
                        'x-client-profile' => 'Generic_v1.1',
                        'x-xsrf-token'     => $xCSRFToken,
                    ],
                    'json'    => [
                        'categorySpecializedFlowEnabled' => 'true',
                        'id'                             => '',
                        'elementValues'                  => [
                            'CategoryLevel0' => '3517',
                            'CategoryLevel1' => '3530',
                            'Cars_ModelYear' => $year,
                            'Cars_Brand'     => $brand[ 'id' ]
                        ],
                    ]
                ] );

                $series = collect( end( json_decode( $res->getBody()->getContents(), true )[ 'response' ][ 'sections' ][ 0 ][ 'elements' ] )[ 'enumValues' ] )->transform( function ( $item ) {

                    return [
                        'id'    => $item[ 'id' ],
                        'label' => $item[ 'label' ],
                    ];

                } )->values();

                foreach ( $series as $serie )
                {

                    $res = $client->post( 'https://banaozel.sahibinden.com/sahibinden-ral/rest/my/classifieds/wizard', [
                        'cookies' => $notXCSRFToken2,
                        'headers' => [
                            'Content-Type'     => 'application/json;charset=UTF-8',
                            'Accept'           => 'application/json, text/plain, */*',
                            'Referer'          => 'https://banaozel.sahibinden.com/ilan-ver/adim-1/?state=new',
                            'x-api-key'        => $API_KEY,
                            'x-client-profile' => 'Generic_v1.1',
                            'x-xsrf-token'     => $xCSRFToken,
                        ],
                        'json'    => [
                            'categorySpecializedFlowEnabled' => 'true',
                            'id'                             => '',
                            'elementValues'                  => [
                                'CategoryLevel0' => '3517',
                                'CategoryLevel1' => '3530',
                                'Cars_ModelYear' => $year,
                                'Cars_Brand'     => $brand[ 'id' ],
                                'Cars_Series'    => $serie[ 'id' ],
                            ],
                        ]
                    ] );

                    $fuels = collect( end( json_decode( $res->getBody()->getContents(), true )[ 'response' ][ 'sections' ][ 0 ][ 'elements' ] )[ 'enumValues' ] )->transform( function ( $item ) {

                        return [
                            'id'    => $item[ 'id' ],
                            'label' => $item[ 'label' ],
                        ];

                    } )->values();

                    foreach ( $fuels as $fuel )
                    {

                        $res = $client->post( 'https://banaozel.sahibinden.com/sahibinden-ral/rest/my/classifieds/wizard', [
                            'cookies' => $notXCSRFToken2,
                            'headers' => [
                                'Content-Type'     => 'application/json;charset=UTF-8',
                                'Accept'           => 'application/json, text/plain, */*',
                                'Referer'          => 'https://banaozel.sahibinden.com/ilan-ver/adim-1/?state=new',
                                'x-api-key'        => $API_KEY,
                                'x-client-profile' => 'Generic_v1.1',
                                'x-xsrf-token'     => $xCSRFToken,
                            ],
                            'json'    => [
                                'categorySpecializedFlowEnabled' => 'true',
                                'id'                             => '',
                                'elementValues'                  => [
                                    'CategoryLevel0' => '3517',
                                    'CategoryLevel1' => '3530',
                                    'Cars_ModelYear' => $year,
                                    'Cars_Brand'     => $brand[ 'id' ],
                                    'Cars_Series'    => $serie[ 'id' ],
                                    'Cars_FuelType'  => $fuel[ 'id' ],
                                ],
                            ]
                        ] );

                        $bodies = collect( end( json_decode( $res->getBody()->getContents(), true )[ 'response' ][ 'sections' ][ 0 ][ 'elements' ] )[ 'enumValues' ] )->transform( function ( $item ) {

                            return [
                                'id'    => $item[ 'id' ],
                                'label' => $item[ 'label' ],
                            ];

                        } )->values();

                        foreach ( $bodies as $body )
                        {

                            $res = $client->post( 'https://banaozel.sahibinden.com/sahibinden-ral/rest/my/classifieds/wizard', [
                                'cookies' => $notXCSRFToken2,
                                'headers' => [
                                    'Content-Type'     => 'application/json;charset=UTF-8',
                                    'Accept'           => 'application/json, text/plain, */*',
                                    'Referer'          => 'https://banaozel.sahibinden.com/ilan-ver/adim-1/?state=new',
                                    'x-api-key'        => $API_KEY,
                                    'x-client-profile' => 'Generic_v1.1',
                                    'x-xsrf-token'     => $xCSRFToken,
                                ],
                                'json'    => [
                                    'categorySpecializedFlowEnabled' => 'true',
                                    'id'                             => '',
                                    'elementValues'                  => [
                                        'CategoryLevel0' => '3517',
                                        'CategoryLevel1' => '3530',
                                        'Cars_ModelYear' => $year,
                                        'Cars_Brand'     => $brand[ 'id' ],
                                        'Cars_Series'    => $serie[ 'id' ],
                                        'Cars_FuelType'  => $fuel[ 'id' ],
                                        'Cars_BodyType'  => $body[ 'id' ],
                                    ],
                                ]
                            ] );

                            $models = collect( end( json_decode( $res->getBody()->getContents(), true )[ 'response' ][ 'sections' ][ 0 ][ 'elements' ] )[ 'enumValues' ] )->transform( function ( $item ) {

                                return [
                                    'id'    => $item[ 'id' ],
                                    'label' => $item[ 'label' ],
                                ];

                            } )->values();

                            foreach ( $models as $model )
                            {

                                $res = $client->post( 'https://banaozel.sahibinden.com/sahibinden-ral/rest/my/classifieds/wizard', [
                                    'cookies' => $notXCSRFToken2,
                                    'headers' => [
                                        'Content-Type'     => 'application/json;charset=UTF-8',
                                        'Accept'           => 'application/json, text/plain, */*',
                                        'Referer'          => 'https://banaozel.sahibinden.com/ilan-ver/adim-1/?state=new',
                                        'x-api-key'        => $API_KEY,
                                        'x-client-profile' => 'Generic_v1.1',
                                        'x-xsrf-token'     => $xCSRFToken,
                                    ],
                                    'json'    => [
                                        'categorySpecializedFlowEnabled' => 'true',
                                        'id'                             => '',
                                        'elementValues'                  => [
                                            'CategoryLevel0' => '3517',
                                            'CategoryLevel1' => '3530',
                                            'Cars_ModelYear' => $year,
                                            'Cars_Brand'     => $brand[ 'id' ],
                                            'Cars_Series'    => $serie[ 'id' ],
                                            'Cars_FuelType'  => $fuel[ 'id' ],
                                            'Cars_BodyType'  => $body[ 'id' ],
                                            'Cars_Model'     => $model[ 'id' ],
                                        ],
                                    ]
                                ] );

                                $response = json_decode( $res->getBody()->getContents(), true );

                                if ( ! isset( end( $response[ 'response' ][ 'sections' ][ 0 ][ 'elements' ] )[ 'tableValues' ] ) )
                                {

                                    // and versions
                                    $versions = collect( end( $response[ 'response' ][ 'sections' ][ 0 ][ 'elements' ] )[ 'enumValues' ] )->transform( function ( $item ) {

                                        return [
                                            'id'    => $item[ 'id' ],
                                            'label' => $item[ 'label' ],
                                        ];

                                    } )->values();

                                    foreach ( $versions as $version )
                                    {

                                        $res = $client->post( 'https://banaozel.sahibinden.com/sahibinden-ral/rest/my/classifieds/wizard', [
                                            'cookies' => $notXCSRFToken2,
                                            'headers' => [
                                                'Content-Type'     => 'application/json;charset=UTF-8',
                                                'Accept'           => 'application/json, text/plain, */*',
                                                'Referer'          => 'https://banaozel.sahibinden.com/ilan-ver/adim-1/?state=new',
                                                'x-api-key'        => $API_KEY,
                                                'x-client-profile' => 'Generic_v1.1',
                                                'x-xsrf-token'     => $xCSRFToken,
                                            ],
                                            'json'    => [
                                                'categorySpecializedFlowEnabled' => 'true',
                                                'id'                             => '',
                                                'elementValues'                  => [
                                                    'CategoryLevel0' => '3517',
                                                    'CategoryLevel1' => '3530',
                                                    'Cars_ModelYear' => $year,
                                                    'Cars_Brand'     => $brand[ 'id' ],
                                                    'Cars_Series'    => $serie[ 'id' ],
                                                    'Cars_FuelType'  => $fuel[ 'id' ],
                                                    'Cars_BodyType'  => $body[ 'id' ],
                                                    'Cars_Model'     => $model[ 'id' ],
                                                    'Cars_Version'   => $version[ 'id' ],
                                                ],
                                            ]
                                        ] );

                                        $response = json_decode( $res->getBody()->getContents(), true );

                                        $this->createCar( $response, $year, $brand, $serie, $fuel, $body, $model, $version );

                                    }

                                }

                                $this->createCar( $response, $year, $brand, $serie, $fuel, $body, $model );


                            }

                        }

                    }

                }

            }

        }

        $this->info( 'Bitti' );

    }

    private function increase ()
    {
        $this->counter++;

        $this->comment( $this->counter );
    }

    /**
     * @param $response
     * @param $year
     * @param $brand
     * @param $serie
     * @param $fuel
     * @param $body
     * @param $model
     *
     * @param null $version
     *
     * @return mixed
     */
    private function createCar ( $response, $year, $brand, $serie, $fuel, $body, $model, $version = null )
    {

        try
        {

            $values = end( $response[ 'response' ][ 'sections' ][ 0 ][ 'elements' ] )[ 'tableValues' ];

            $headers = collect( $values[ 'headers' ] )->transform( function ( $item ) {
                return $item[ 'label' ];
            } )->values()->toArray();

            foreach ( $values[ 'rows' ] as $key => $row )
            {

                $values = collect( $row[ 'cells' ] )->transform( function ( $item ) {

                    return $item[ 'label' ];

                } )->values()->toArray();

                if ( Car::whereUid( $row[ 'id' ] )->exists() ) continue;

                $data = array_combine( $headers, $values );

                Car::create( [
                    'uid'     => $row[ 'id' ],
                    'year'    => (int) $year,
                    'brand'   => $brand[ 'label' ],
                    'series'  => $serie[ 'label' ],
                    'fuel'    => $fuel[ 'label' ],
                    'body'    => $body[ 'label' ],
                    'model'   => $model[ 'label' ],
                    'version' => $version ? $version[ 'label' ] : null,
                    'data'    => $data
                ] );

                $this->increase();

            }

        } catch ( \Exception $err )
        {

            $this->error( 'Arac eklenemedi' );

        }


    }
}
