<?php

declare(strict_types=1);

namespace App\Services\Nntp;

use RuntimeException;

/**
 * Minimal NNTP (RFC 977/3977) client — enough for synchronizing the
 * `php.internals` newsgroup from `news.php.net`.
 *
 * Replaces rvdv/nntp, which is unmaintained and does not support PHP 8+.
 */
class NntpClient
{
    /** @var resource|null */
    private $socket;

    public function __construct(
        private readonly string $host,
        private readonly int $port = 119,
        private readonly int $timeout = 15,
    ) {}

    public function connect(): void
    {
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if ($socket === false) {
            throw new RuntimeException("Cannot connect to NNTP server {$this->host}:{$this->port}: $errstr ($errno)");
        }
        stream_set_timeout($socket, $this->timeout);
        $this->socket = $socket;

        $greeting = $this->readLine();
        if (! str_starts_with($greeting, '20')) {
            throw new RuntimeException("Unexpected NNTP greeting: $greeting");
        }
    }

    public function disconnect(): void
    {
        if ($this->socket !== null) {
            @fwrite($this->socket, "QUIT\r\n");
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Select a newsgroup. Returns ['count' => int, 'first' => int, 'last' => int, 'name' => string].
     */
    public function group(string $name): array
    {
        $response = $this->sendCommand("GROUP $name");
        if (! str_starts_with($response, '211')) {
            throw new RuntimeException("Cannot select group $name: $response");
        }

        $parts = explode(' ', mb_trim($response));

        // 211 count first last name
        return [
            'count' => (int) ($parts[1] ?? 0),
            'first' => (int) ($parts[2] ?? 0),
            'last' => (int) ($parts[3] ?? 0),
            'name' => $parts[4] ?? $name,
        ];
    }

    /**
     * Fetch an article by its message number.
     *
     * @throws ArticleNotFoundException when the article cannot be retrieved.
     */
    public function article(int $number): string
    {
        $response = $this->sendCommand("ARTICLE $number");
        if (! str_starts_with($response, '220')) {
            throw new ArticleNotFoundException("Cannot fetch article $number: $response");
        }

        $lines = [];
        while (true) {
            $line = $this->readLine();
            if ($line === ".\r\n" || $line === ".\n" || mb_rtrim($line, "\r\n") === '.') {
                break;
            }
            // RFC 3977 §3.1.1: lines starting with a dot are dot-stuffed
            if (str_starts_with($line, '..')) {
                $line = mb_substr($line, 1);
            }
            $lines[] = $line;
        }

        return implode('', $lines);
    }

    private function sendCommand(string $command): string
    {
        if ($this->socket === null) {
            throw new RuntimeException('NNTP client is not connected');
        }
        if (fwrite($this->socket, $command . "\r\n") === false) {
            throw new RuntimeException("Failed to send NNTP command: $command");
        }

        return $this->readLine();
    }

    private function readLine(): string
    {
        if ($this->socket === null) {
            throw new RuntimeException('NNTP client is not connected');
        }
        $line = fgets($this->socket);
        if ($line === false) {
            $meta = stream_get_meta_data($this->socket);
            if (! empty($meta['timed_out'])) {
                throw new RuntimeException('NNTP read timed out');
            }
            throw new RuntimeException('NNTP connection closed unexpectedly');
        }

        return $line;
    }
}
