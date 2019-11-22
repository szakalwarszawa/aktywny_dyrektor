<?php

namespace ParpV1\AuthBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

class ParpUser implements UserInterface, EquatableInterface, \Serializable
{
    private $username;
    private $password;
    private $salt;
    private $roles;

    public function __construct($username, $password, $salt, array $roles)
    {
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->roles = $roles;
    }

    public function getRoles()
    {
        //$this->roles = ["PARP_ADMIN"];//hack by dalo sie nadac uprawnienia jak nikt nie ma
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function eraseCredentials()
    {
    }

    public function getRolesHtml()
    {
        // pomija w wyświetlaniu grupy nadawane dynamicznie 'PARP_D_'
        $role = preg_grep("/^PARP_D_/", $this->getRoles(), PREG_GREP_INVERT);
        $ret = implode("</li><li class='list-group-item'>", $role);

        return "<ul class='list-group'><li class='list-group-item'>" . $ret . "</li></ul>";
    }

    public function hasRole($role)
    {
        if (in_array($role, $this->getRoles())) {
            return true;
        }
        return false;
    }

    /**
     * @see https://github.com/symfony/symfony/blob/3.4/src/Symfony/Component/Security/Core/User/EquatableInterface.php
     */
    public function isEqualTo(UserInterface $user)
    {
        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    /**
     * @see https://symfony.com/doc/3.4/security/entity_provider.html#security-serialize-equatable
     */
    public function serialize()
    {
        return serialize(array(
            $this->username,
            $this->password,
            $this->salt,
            $this->roles,
        ));
    }

    /**
     * @see \Serializable::unserialize()
     * @see https://symfony.com/doc/3.4/security/entity_provider.html#security-serialize-equatable
    */
    public function unserialize($serialized)
    {
        list (
            $this->username,
            $this->password,
            $this->salt,
            $this->roles,
        ) = unserialize($serialized, array('allowed_classes' => false));
    }

    public function __toString()
    {
        return $this->username;
    }
}
