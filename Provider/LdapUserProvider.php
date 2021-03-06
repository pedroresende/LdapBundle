<?php

namespace PEDRORESENDE\LdapBundle\Provider;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException,
    Symfony\Component\Security\Core\Exception\UsernameNotFoundException,
    Symfony\Component\Security\Core\User\UserInterface,
    Symfony\Component\Security\Core\User\UserProviderInterface;

use PEDRORESENDE\LdapBundle\Manager\LdapManagerUserInterface,
    PEDRORESENDE\LdapBundle\User\LdapUserInterface;

/**
 * LDAP User Provider
 *
 * @author Boris Morel
 * @author Juti Noppornpitak <jnopporn@shiroyuki.com>
 */
class LdapUserProvider implements UserProviderInterface
{
    /**
     * @var \PEDRORESENDE\LdapBundle\Manager\LdapManagerUserInterface
     */
    private $ldapManager;

    /**
     * @var string
     */
    private $bindUsernameBefore;

    /**
     * The class name of the User model
     * @var string
     */
    private $userClass;

    /**
     * Constructor
     *
     * @param \PEDRORESENDE\LdapBundle\Manager\LdapManagerUserInterface $ldapManager
     * @param bool|string                                       $bindUsernameBefore
     * @param string                                            $userClass
     */
    public function __construct(LdapManagerUserInterface $ldapManager, $bindUsernameBefore = false, $userClass)
    {
        $this->ldapManager = $ldapManager;
        $this->bindUsernameBefore = $bindUsernameBefore;
        $this->userClass = $userClass;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        // Throw the exception if the username is not provided.
        if (empty($username)) {
            throw new UsernameNotFoundException('The username is not provided.');
        }

        if (true === $this->bindUsernameBefore) {
            $ldapUser = $this->simpleUser($username);
        } else {
            $ldapUser = $this->anonymousSearch($username);
        }

        return $ldapUser;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof LdapUserInterface) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        if (false === $this->bindUsernameBefore) {
            return $this->loadUserByUsername($user->getUsername());
        } else {
            return $this->bindedSearch($user->getUsername());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return is_subclass_of($class, '\PEDRORESENDE\LdapBundle\User\LdapUserInterface');
    }

    private function simpleUser($username)
    {
        $ldapUser = new $this->userClass;
        $ldapUser->setUsername($username);

        return $ldapUser;
    }

    private function anonymousSearch($username)
    {
        $this->ldapManager->exists($username);

        $lm = $this->ldapManager
            ->setUsername($username)
            ->doPass();

        $ldapUser = new $this->userClass;

        $ldapUser
            ->setUsername($lm->getUsername())
            ->setEmail($lm->getEmail())
            ->setRoles($lm->getRoles())
            ->setDn($lm->getDn())
            ->setCn($lm->getCn())
            ->setAttributes($lm->getAttributes())
            ->setGivenName($lm->getGivenName())
            ->setSurname($lm->getSurname())
            ->setDisplayName($lm->getDisplayName())
            ;

        return $ldapUser;
    }

    private function bindedSearch($username)
    {
        return $this->anonymousSearch($username);
    }
}
