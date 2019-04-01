<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\DataCollection\Change;

use ParpV1\LdapBundle\DataCollection\CollectorInterface;

/**
 * Obiekt zmiany dowolnego elementu.
 * Przechowuje wartość przed i po zmianie.
 */
class Change implements CollectorInterface
{
    /**
     * Klucz przechowujący nową wartość.
     *
     * @var string
     */
    const NEW_KEY = 'new';

    /**
     * Klucz przechowujący starą wartość.
     *
     * @var string
     */
    const OLD_KEY = 'old';

    /**
     * Typ kolekcji.
     */
    protected $rootType = 'change';

    /**
     * @var string
     */
    private $type;

    /**
     * @var mixed
     */
    private $old;

    /**
     * @var mixed
     */
    private $new;

    /**
     * Czego dotyczy zmiana.
     *
     * @var string|null
     */
    private $target;

    /**
     * Opcjonalne informacje o obiekcie.
     *
     * @var mixed
     */
    private $vars;

    /**
     * @param mixed $old
     * @param mixed $new
     * @param string $target
     * @param mixed $vars
     */
    public function __construct($old, $new, string $target = null, $vars = null)
    {
        $this->old = $old;
        $this->new = $new;
        $this->vars = $vars;
        $this->target = $target;
    }
    /**
     * @see CollectorInterface
     */
    public function getRootType(): string
    {
        return $this->rootType;
    }

    /**
     * Set new
     *
     * @param mixed $new
     *
     * @return self
     */
    public function setNew($new): self
    {
        $this->new = $new;

        return $this;
    }

    /**
     * Get new
     *
     * @return mixed
     */
    public function getNew()
    {
        return $this->new;
    }

    /**
     * Set old
     *
     * @param mixed $new
     *
     * @return self
     */
    public function setOld($old): self
    {
        $this->old = $old;

        return $this;
    }

    /**
     * Get old
     *
     * @return mixed
     */
    public function getOld()
    {
        return $this->old;
    }

    /**
     * Get vars
     *
     * @return mixed
     */
    public function getVars()
    {
        return $this->subject;
    }

    /**
     * Set vars
     *
     * @param string $vars
     *
     * @return self
     */
    public function setVars($vars): self
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * Get target
     *
     * @return string|null
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set target
     *
     * @param string $target
     *
     * @return self
     */
    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }
}
