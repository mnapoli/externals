<?php
declare(strict_types = 1);

namespace Externals\User;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class User implements \JsonSerializable
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $githubId;

    /**
     * @var string
     */
    private $name;

    public function __construct(int $id, string $githubId, string $name)
    {
        $this->id = $id;
        $this->githubId = $githubId;
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGithubId(): string
    {
        return $this->githubId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return string
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'githubId' => $this->githubId,
            'name' => $this->name,
        ];
    }

    public static function fromData($data)
    {
        if ($data === null) {
            return null;
        }

        return new self($data['id'], $data['githubId'], $data['name']);
    }
}
