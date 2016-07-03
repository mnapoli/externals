<?php
declare(strict_types = 1);

namespace Externals\User;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class User implements \Serializable
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
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string
     */
    public function serialize()
    {
        return serialize([
            'id' => $this->id,
            'githubId' => $this->githubId,
            'name' => $this->name,
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        if (!$data) {
            throw new \Exception('Unable to unserialize user');
        }
        $this->id = $data['id'];
        $this->githubId = $data['githubId'];
        $this->name = $data['name'];
    }
}
