<?php

namespace ParpV1\AuthBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class ParpUser implements UserInterface
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
        $ret = implode("</li><li class='list-group-item'>", $this->getRoles());
        return "<ul class='list-group'><li class='list-group-item'>".$ret."</li></ul>";
    }
    public function hasRole($role)
    {
        if (in_array($role, $this->getRoles())) {
            return true;
        }
        return false;
    }
}
