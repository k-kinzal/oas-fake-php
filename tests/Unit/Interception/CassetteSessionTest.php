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

    public function testStopClosesCassette(): void
    {
        $session = new CassetteSession(sys_get_temp_dir(), 'closed-session');
        $session->start();
        $session->stop();

        $this->expectException(ReplayMismatchError::class);
        $session->playback(new Request('GET', 'https://example.com/pets', []));
    }

    public function testPlaybackRejectsMissingRecording(): void
    {
        $path = sys_get_temp_dir() . '/oas-fake-cassette-session-' . uniqid('', true);
        mkdir($path, 0777, true);
        $session = new CassetteSession($path, 'missing');
        $session->start();

        try {
            $this->expectException(ReplayMismatchError::class);
            $session->playback(new Request('GET', 'https://example.com/missing', []));
        } finally {
            $session->stop();
            @unlink($path . '/missing');
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
