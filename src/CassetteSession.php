<?php

declare(strict_types=1);

namespace OasFake;

use LogicException;
use OasFake\Exception\ReplayMismatchError;
use VCR\Cassette;
use VCR\Configuration;
use VCR\Request as VcrRequest;
use VCR\Response as VcrResponse;
use VCR\Storage\Json;

/**
 * Owns one interceptor's cassette and per-request playback indexes.
 */
final class CassetteSession
{
    private ?Cassette $cassette = null;

    /** @var array<string, int> */
    private array $indexTable = [];

    /**
     * Create a cassette session for a storage path and cassette name.
     */
    public function __construct(private string $path, private string $name)
    {
    }

    /**
     * Open the configured cassette.
     */
    public function start(): void
    {
        $this->cassette = new Cassette(
            $this->name,
            new Configuration(),
            new Json($this->path, $this->name),
        );
    }

    /**
     * Release cassette state and playback positions.
     */
    public function stop(): void
    {
        $this->cassette = null;
        $this->indexTable = [];
    }

    /**
     * Replay the next matching response.
     *
     * @throws ReplayMismatchError when the cassette is closed or has no matching response
     */
    public function playback(VcrRequest $request): VcrResponse
    {
        if ($this->cassette === null) {
            throw ReplayMismatchError::forRequest($request, new LogicException('No cassette loaded for replay'));
        }

        $response = $this->cassette->playback($request, $this->nextIndex($request));
        if ($response === null) {
            throw ReplayMismatchError::forRequest($request, new LogicException('No matching cassette recording'));
        }

        return $response;
    }

    /**
     * Record a response when the cassette is open.
     */
    public function record(VcrRequest $request, VcrResponse $response): void
    {
        if ($this->cassette !== null) {
            $this->cassette->record($request, $response, $this->nextIndex($request));
        }
    }

    /**
     * Advance and return the index for an equivalent request signature.
     */
    public function nextIndex(VcrRequest $request): int
    {
        $key = $request->getMethod() . ' ' . ($request->getUrl() ?? '');
        if (!isset($this->indexTable[$key])) {
            $this->indexTable[$key] = -1;
        }

        return ++$this->indexTable[$key];
    }
}
