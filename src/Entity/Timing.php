<?php

namespace App\Entity;

use App\Repository\TimingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TimingRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Timing
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    private $created_at;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $php_version;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $sample_size;

    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $function_cast_duration;

    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $trad_cast_duration;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $function_cast_memory;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $trad_cast_memory;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $from_type;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $to_type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): void
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getPhpVersion(): ?string
    {
        return $this->php_version;
    }

    /**
     * @ORM\PrePersist
     */
    public function setPhpVersion(): self
    {
        $this->php_version = PHP_VERSION;

        return $this;
    }

    public function getSampleSize(): ?int
    {
        return $this->sample_size;
    }

    public function setSampleSize(int $sample_size): self
    {
        $this->sample_size = $sample_size;

        return $this;
    }

    public function getFunctionCastDuration(): ?float
    {
        return $this->function_cast_duration;
    }

    public function setFunctionCastDuration(float $function_cast_duration): self
    {
        $this->function_cast_duration = $function_cast_duration;

        return $this;
    }

    public function getTradCastDuration(): ?float
    {
        return $this->trad_cast_duration;
    }

    public function setTradCastDuration(float $trad_cast_duration): self
    {
        $this->trad_cast_duration = $trad_cast_duration;

        return $this;
    }

    public function getFunctionCastMemory(): ?int
    {
        return $this->function_cast_memory;
    }

    public function setFunctionCastMemory(int $function_cast_memory): self
    {
        $this->function_cast_memory = $function_cast_memory;

        return $this;
    }

    public function getTradCastMemory(): ?int
    {
        return $this->trad_cast_memory;
    }

    public function setTradCastMemory(int $trad_cast_memory): self
    {
        $this->trad_cast_memory = $trad_cast_memory;

        return $this;
    }

    public function getFromType(): ?string
    {
        return $this->from_type;
    }

    public function setFromType(string $from_type): self
    {
        $this->from_type = $from_type;

        return $this;
    }

    public function getToType(): ?string
    {
        return $this->to_type;
    }

    public function setToType(string $to_type): self
    {
        $this->to_type = $to_type;

        return $this;
    }
}
