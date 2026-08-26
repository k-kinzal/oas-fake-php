<?php

declare(strict_types=1);

namespace OasFake\Tests\Unit;

use JsonException;
use OasFake\CassetteSession;
use OasFake\Exception\ReplayMismatchError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VCR\Request;
use VCR\Response;

/**
 * @covers \OasFake\CassetteSession
 *
 * @uses \OasFake\Exception\ReplayMismatchError
 */
#[CoversClass(CassetteSession::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ReplayMismatchError::class)]
final class CassetteSessionTest extends TestCase
{
    public function testStartOpensCassetteForRecording(): void
    {
        $path = sys_get_temp_dir() . '/oas-fake-cassette-session-' . uniqid('', true);
        mkdir($path, 0777, true);
        $session = new CassetteSession($path, 'session');

        try {
            $session->start();
            $session->record(new Request('GET', 'https://example.com/pets', []), new Response('200', [], '[]'));

            self::assertFileExists($path . '/session');
        } finally {
            $session->stop();
            @unlink($path . '/session');
            @rmdir($path);
        }
    }

    public function testStopCanReleaseAnAlreadyStoppedSession(): void
    {
        $session = new CassetteSession(sys_get_temp_dir(), 'stopped-session');

        $session->stop();
        $session->stop();

        $this->addToAssertionCount(1);
    }

    public function testPlaybackReturnsTheRecordedResponse(): void
    {
        $path = sys_get_temp_dir() . '/oas-fake-cassette-session-' . uniqid('', true);
        mkdir($path, 0777, true);
        $session = new CassetteSession($path, 'playback');
        $request = new Request('GET', 'https://example.com/pets', []);

        try {
            $session->start();
            $session->record($request, new Response('201', [], '[{"id":1}]'));
            $session->stop();
            $session->start();

            $response = $session->playback($request);

            self::assertSame(201, $response->getStatusCode());
            self::assertSame('[{"id":1}]', $response->getBody());
        } finally {
            $session->stop();
            @unlink($path . '/playback');
            @rmdir($path);
        }
    }

    /**
     * @throws JsonException when the cassette cannot be decoded
     */
    public function testRecordPersistsResponse(): void
    {
        $path = sys_get_temp_dir() . '/oas-fake-cassette-session-' . uniqid('', true);
        mkdir($path, 0777, true);
        $session = new CassetteSession($path, 'recorded');
        $session->start();

        try {
            $session->record(new Request('GET', 'https://example.com/pets', []), new Response('200', [], '[]'));

            $recordings = json_decode((string) file_get_contents($path . '/recorded'), true, 512, JSON_THROW_ON_ERROR);

            self::assertIsArray($recordings);
            self::assertArrayHasKey(0, $recordings);
            self::assertIsArray($recordings[0]);
            self::assertArrayHasKey('request', $recordings[0]);
            self::assertIsArray($recordings[0]['request']);
            self::assertSame('https://example.com/pets', $recordings[0]['request']['url'] ?? null);
        } finally {
            $session->stop();
            @unlink($path . '/recorded');
            @rmdir($path);
        }
    }

    public function testNextIndexAdvancesPerRequestSignature(): void
    {
        $session = new CassetteSession(sys_get_temp_dir(), 'index');
        $getPets = new Request('GET', 'https://example.com/pets', []);
        $postPets = new Request('POST', 'https://example.com/pets', []);
        $getOwners = new Request('GET', 'https://example.com/owners', []);

        self::assertSame(0, $session->nextIndex($getPets));
        self::assertSame(0, $session->nextIndex($postPets));
        self::assertSame(0, $session->nextIndex($getOwners));
        self::assertSame(1, $session->nextIndex($getPets));
        self::assertSame(1, $session->nextIndex($postPets));
        self::assertSame(1, $session->nextIndex($getOwners));
    }
}
