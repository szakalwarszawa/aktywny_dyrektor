<?php declare(strict_types=1);

namespace ParpV1\LdapBundle\MessageCollector;

use Doctrine\Common\Collections\ArrayCollection;
use ParpV1\LdapBundle\MessageCollector\Message\MessageInterface;
use ParpV1\LdapBundle\MessageCollector\Constants\Types;

/**
 * Kolektor błędów/informacji/ostrzeżeń.
 * Jest to przyjazny zamiennik wyjątku z wiadomością do użytkownika.
 *
 */
class Collector
{
    /**
     * @var ArrayCollection
     */
    public $collection;

    /**
     * Publiczny konstruktor
     */
    public function __construct()
    {
        $this->collection = new ArrayCollection();
    }

    /**
     * Dodaje wiadomość do kolekcji.
     *
     * @param MessageInterface
     *
     * @return void
     */
    public function add(MessageInterface $message): void
    {
        $this
            ->collection
            ->add($message)
        ;
    }

    /**
     * Zwraca wszystkie wiadomości.
     *
     * @param string $type - jeżeli jest określony typ
     *      to zwraca tylko wiadomośći z określonym typem.
     *
     * @return ArrayCollection
     */
    public function getMessages(string $type = null): ArrayCollection
    {
        if (null !== $type) {
            if (in_array($type, Types::getTypes())) {
                $tempCollection = new ArrayCollection();
                foreach ($this->collection as $element) {
                    if ($type === $element->getType()) {
                        $tempCollection->add($element);
                    }
                }

                return $tempCollection;
            }
        }

        return $this->collection;
    }

    /**
     * Usuwa wiadomośc z kolekcji.
     *
     * @param MessageInterface
     *
     * @return void
     */
    public function remove(MessageInterface $message): void
    {
        $this
            ->collection
            ->remove($message)
        ;
    }
}
