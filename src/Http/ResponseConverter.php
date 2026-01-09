<?php

declare(strict_types=1);

namespace OasFakePHP\Http;

use GuzzleHttp\Psr7\Response;

use function is_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use Psr\Http\Message\ResponseInterface;
use VCR\Response as VCRResponse;

final class ResponseConverter
{
    public function vcrToPsr7(VCRResponse $vcrResponse): ResponseInterface
    {
        $statusCode = $vcrResponse->getStatusCode();
        $headers = $vcrResponse->getHeaders();
        $body = $vcrResponse->getBody();

        return new Response($statusCode, $headers, $body);
    }

    public function psr7ToVcr(ResponseInterface $psrResponse): VCRResponse
    {
        $statusCode = $psrResponse->getStatusCode();

        /** @var array<string, string> $flatHeaders */
        $flatHeaders = [];
        foreach ($psrResponse->getHeaders() as $name => $values) {
            $flatHeaders[(string) $name] = implode(', ', $values);
        }

        $body = (string) $psrResponse->getBody();

        // @phpstan-ignore argument.type (VCR Response accepts array{code:int,message:string} but PHPDoc is wrong)
        return new VCRResponse(['code' => $statusCode, 'message' => ''], $flatHeaders, $body);
    }

    /**
     * @param array<string, mixed>|list<mixed>|scalar|null $data
     * @param array<string, string> $headers
     */
    public function arrayToVcr(
        array|string|int|float|bool|null $data,
        int $statusCode = 200,
        array $headers = [],
    ): VCRResponse {
        if (is_array($data)) {
            $body = json_encode($data, JSON_THROW_ON_ERROR);
            $headers['Content-Type'] ??= 'application/json';
        } else {
            $body = (string) $data;
        }

        // @phpstan-ignore argument.type (VCR Response accepts array{code:int,message:string} but PHPDoc is wrong)
        return new VCRResponse(['code' => $statusCode, 'message' => ''], $headers, $body);
    }

    /**
     * @param array<string, mixed>|list<mixed>|scalar|null $data
     * @param array<string, string> $headers
     */
    public function arrayToPsr7(
        array|string|int|float|bool|null $data,
        int $statusCode = 200,
        array $headers = [],
    ): ResponseInterface {
        if (is_array($data)) {
            $body = json_encode($data, JSON_THROW_ON_ERROR);
            $headers['Content-Type'] ??= 'application/json';
        } else {
            $body = (string) $data;
        }

        return new Response($statusCode, $headers, $body);
    }
}
