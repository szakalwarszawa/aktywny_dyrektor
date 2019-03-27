<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\MessageCollector\Message;

/**
 * MessageInterface
 */
interface MessageInterface
{
    /**
     * Zwraca wiadomość.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Ustawia wiadomość
     *
     * @param string $message
     *
     * @return self
     */
    public function setMessage(string $message);

    /**
     * Zwraca typ
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Ustawia Typ
     *
     * @param string $message
     *
     * @return self
     */
    public function setType(string $type);

    /**
     * Zwraca czego dotyczy wiadomość.
     *
     * @return string|null
     */
    public function getTarget();

    /**
     * Ustawia czego dotyczy wiadomość
     *
     * @param string $message
     *
     * @return self
     */
    public function setTarget(string $target);
}
