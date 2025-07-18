<?php

namespace DoctrineORMModule\Proxy\__CG__\User\Entity;


/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class User extends \User\Entity\User implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array<string, null> properties to be lazy loaded, indexed by property name
     */
    public static $lazyPropertiesNames = array (
);

    /**
     * @var array<string, mixed> default values of properties to be lazy loaded, with keys being the property names
     *
     * @see \Doctrine\Common\Proxy\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array (
);



    public function __construct(?\Closure $initializer = null, ?\Closure $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }







    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return ['__isInitialized__', '' . "\0" . 'User\\Entity\\User' . "\0" . 'iduser', '' . "\0" . 'User\\Entity\\User' . "\0" . 'email', '' . "\0" . 'User\\Entity\\User' . "\0" . 'roles', '' . "\0" . 'User\\Entity\\User' . "\0" . 'password', '' . "\0" . 'User\\Entity\\User' . "\0" . 'firstname', '' . "\0" . 'User\\Entity\\User' . "\0" . 'lastname', '' . "\0" . 'User\\Entity\\User' . "\0" . 'member', '' . "\0" . 'User\\Entity\\User' . "\0" . 'admin', '' . "\0" . 'User\\Entity\\User' . "\0" . 'blacklist', '' . "\0" . 'User\\Entity\\User' . "\0" . 'isActive', '' . "\0" . 'User\\Entity\\User' . "\0" . 'birthdate', '' . "\0" . 'User\\Entity\\User' . "\0" . 'nickname'];
        }

        return ['__isInitialized__', '' . "\0" . 'User\\Entity\\User' . "\0" . 'iduser', '' . "\0" . 'User\\Entity\\User' . "\0" . 'email', '' . "\0" . 'User\\Entity\\User' . "\0" . 'roles', '' . "\0" . 'User\\Entity\\User' . "\0" . 'password', '' . "\0" . 'User\\Entity\\User' . "\0" . 'firstname', '' . "\0" . 'User\\Entity\\User' . "\0" . 'lastname', '' . "\0" . 'User\\Entity\\User' . "\0" . 'member', '' . "\0" . 'User\\Entity\\User' . "\0" . 'admin', '' . "\0" . 'User\\Entity\\User' . "\0" . 'blacklist', '' . "\0" . 'User\\Entity\\User' . "\0" . 'isActive', '' . "\0" . 'User\\Entity\\User' . "\0" . 'birthdate', '' . "\0" . 'User\\Entity\\User' . "\0" . 'nickname'];
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (User $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy::$lazyPropertiesDefaults as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', []);
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load(): void
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', []);
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized(): bool
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized): void
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(?\Closure $initializer = null): void
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer(): ?\Closure
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(?\Closure $cloner = null): void
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner(): ?\Closure
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @deprecated no longer in use - generated code now relies on internal components rather than generated public API
     * @static
     */
    public function __getLazyProperties(): array
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function getIdUser(): ?int
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIdUser', []);

        return parent::getIdUser();
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEmail', []);

        return parent::getEmail();
    }

    /**
     * {@inheritDoc}
     */
    public function setEmail(string $email): \User\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEmail', [$email]);

        return parent::setEmail($email);
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRoles', []);

        return parent::getRoles();
    }

    /**
     * {@inheritDoc}
     */
    public function setRoles(?string $roles): \User\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRoles', [$roles]);

        return parent::setRoles($roles);
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPassword', []);

        return parent::getPassword();
    }

    /**
     * {@inheritDoc}
     */
    public function setPassword(string $password): \User\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPassword', [$password]);

        return parent::setPassword($password);
    }

    /**
     * {@inheritDoc}
     */
    public function getFirstname(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFirstname', []);

        return parent::getFirstname();
    }

    /**
     * {@inheritDoc}
     */
    public function setFirstname(string $firstname): \User\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setFirstname', [$firstname]);

        return parent::setFirstname($firstname);
    }

    /**
     * {@inheritDoc}
     */
    public function getLastname(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLastname', []);

        return parent::getLastname();
    }

    /**
     * {@inheritDoc}
     */
    public function setLastname(string $lastname): \User\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLastname', [$lastname]);

        return parent::setLastname($lastname);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsMember(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIsMember', []);

        return parent::getIsMember();
    }

    /**
     * {@inheritDoc}
     */
    public function setIsMember(bool $member): \User\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIsMember', [$member]);

        return parent::setIsMember($member);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsAdmin(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIsAdmin', []);

        return parent::getIsAdmin();
    }

    /**
     * {@inheritDoc}
     */
    public function setIsAdmin(bool $admin): \User\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIsAdmin', [$admin]);

        return parent::setIsAdmin($admin);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsBlacklist(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIsBlacklist', []);

        return parent::getIsBlacklist();
    }

    /**
     * {@inheritDoc}
     */
    public function setIsBlacklist(bool $blacklist): \User\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIsBlacklist', [$blacklist]);

        return parent::setIsBlacklist($blacklist);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsActive(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIsActive', []);

        return parent::getIsActive();
    }

    /**
     * {@inheritDoc}
     */
    public function setIsActive(bool $isActive): \User\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIsActive', [$isActive]);

        return parent::setIsActive($isActive);
    }

    /**
     * {@inheritDoc}
     */
    public function getBirthdate(): ?\DateTimeInterface
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBirthdate', []);

        return parent::getBirthdate();
    }

    /**
     * {@inheritDoc}
     */
    public function setBirthdate(?\DateTimeInterface $birthdate): \User\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBirthdate', [$birthdate]);

        return parent::setBirthdate($birthdate);
    }

    /**
     * {@inheritDoc}
     */
    public function getNickname(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getNickname', []);

        return parent::getNickname();
    }

    /**
     * {@inheritDoc}
     */
    public function setNickname(?string $nickname): \User\Entity\User
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setNickname', [$nickname]);

        return parent::setNickname($nickname);
    }

    /**
     * {@inheritDoc}
     */
    public function isInRoles($role): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isInRoles', [$role]);

        return parent::isInRoles($role);
    }

}
