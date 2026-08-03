<?php

declare(strict_types=1);

namespace BlackPrint\Suppliers\Amrod;

use BlackPrint\Suppliers\Http\HttpResponse;

final class AmrodHttpClient
{
    public function __construct(
        private readonly AmrodConfig $config
    ) {
    }

    public function get(
        string $endpoint,
        array $query = []
    ): HttpResponse {

        $url = $this->buildUrl(
            $endpoint,
            $query
        );

        return $this->request(
            'GET',
            $url
        );
    }

    private function request(
        string $method,
        string $url
    ): HttpResponse {

        $attempt = 0;

        do {

            $attempt++;

            $started = microtime(true);

            $response = wp_remote_request(

                $url,

                [

                    'method'  => $method,

                    'timeout' => $this->config->timeout(),

                    'headers' => [

                        'Accept' => 'application/json',

                        'Authorization' => 'Basic ' . base64_encode(

                            $this->config->username()

                            . ':'

                            . $this->config->password()

                        ),

                    ],

                ]

            );

            $duration = (int) round(

                (microtime(true) - $started) * 1000

            );

            if (! is_wp_error($response)) {

                $status = wp_remote_retrieve_response_code(

                    $response

                );

                $body = wp_remote_retrieve_body(

                    $response

                );

                $headers = wp_remote_retrieve_headers(

                    $response

                );

                if ($status >= 200 && $status < 300) {

                    $decoded = json_decode(

                        $body,

                        true

                    );

                    if (! is_array($decoded)) {

                        throw new AmrodException(

                            'Invalid JSON response.'

                        );
                    }

                    return new HttpResponse(

                        body: $decoded,

                        status: $status,

                        durationMs: $duration,

                        headers: $headers->getAll()

                    );
                }
            }

            if ($attempt >= $this->config->retries()) {

                $message = is_wp_error($response)

                    ? $response->get_error_message()

                    : sprintf(

                        'HTTP request failed (%d)',

                        $status ?? 0

                    );

                throw new AmrodException($message);
            }

            sleep(1);

        } while (true);
    }

    private function buildUrl(
        string $endpoint,
        array $query = []
    ): string {

        $url = rtrim(

            $this->config->baseUrl(),

            '/'

        );

        $url .= '/' . ltrim(

            $endpoint,

            '/'

        );

        if (! empty($query)) {

            $url = add_query_arg(

                $query,

                $url

            );
        }

        return $url;
    }
}