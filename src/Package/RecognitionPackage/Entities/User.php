<?php

namespace bblue\ruby\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use bblue\ruby\Component\Entity\Entity;
use bblue\ruby\Package\RecognitionPackage\Entities\UsergroupUserAssociationTrait;
use bblue\ruby\Package\RecognitionPackage\Entities\ObserverUserAssociationTrait;
use bblue\ruby\Package\RecognitionPackage\Entities\FollowerUserAssociationTrait;
use bblue\ruby\Component\Security\PasswordHelper;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="user_type", type="string")
 * @DiscriminatorMap({"crawler" = "Crawler", "member" = "Member", "guest" = "Guest"})
 * @todo friends, followers og slikt må ikke være tilgjengelig hos en guest eller crawler. Flytte disse til Member
 * @todo vurdere om jeg skal lage en admin user
 **/
abstract class User extends Entity
{
    use UsergroupUserAssociationTrait;//@todo Tror ikke jeg har noen effekt ut av å bruke traits her
    use ObserverUserAssociationTrait;
    use FollowerUserAssociationTrait;
    
    public abstract function isGuest();
    
    ####################################################
    ##################### DOCTRINE #####################
    ####################################################  
      
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;
    
    /**
     * @Column(type="string", nullable=true)
     * @var string
     */
    protected $firstname;
    
    /**
     * @Column(type="string", nullable=true)
     * @var string
     */
    protected $lastname;
    
    /**
     * @Column(type="string")
     * @var string
     */
    protected $username;
    
    /**
     * @OneToMany(targetEntity="bblue\ruby\Entities\LoginAttempt", mappedBy="user")
     * @var LoginAttempt[]
     */
    protected $loginAttempts = null;
    
    /**
     * The login hash is a unique key used when logging in. By refreshing this hash, the user can be forced to login again.
     * Column(type="string")
     * @var string
     */
    protected $loginHash;
    
    /**
     * @Column(name="password_hash", type="string", nullable=true)
     * @var string
     */
    protected $passwordHash;
        
    /**
     * Usergroups this user belongs to
     * 
     * @OneToMany(targetEntity="bblue\ruby\Entities\UsergroupUserAssociation", mappedBy="user")
     * @var UsergroupUserAssociation[]
     */
    protected $assignedUsergroupUserAssociations = null;

    /**
     * Users following this entity
     * 
     * @OneToMany(targetEntity="bblue\ruby\Entities\FollowerUserAssociation", mappedBy="follower")
     * @var FollowerUserAssociation[]
     */
    protected $assignedFollowerUserAssociations = null;

    /**
     * Users followed by this entity
     * 
     * @OneToMany(targetEntity="bblue\ruby\Entities\FollowerUserAssociation", mappedBy="user")
     * @var FollowerUserAssociation[]
     */
    protected $assignedUserFollowerAssociations = null;
    
    /**
     * Users observing this entity
     * 
     * @OneToMany(targetEntity="bblue\ruby\Entities\ObserverUserAssociation", mappedBy="observer")
     * @var ObserverUserAssociation[]
     */
    protected $assignedObserverUserAssociations = null;   

    /**
     * Users observed by this entity
     * 
     * @OneToMany(targetEntity="bblue\ruby\Entities\ObserverUserAssociation", mappedBy="user")
     * @var ObserverUserAssociation[]
     */
    protected $assignedUserObserverAssociations = null;

    /**
     * Users blocking this entity
     *
     * @OneToMany(targetEntity="bblue\ruby\Entities\BlockedUserAssociation", mappedBy="blocked")
     * @var BlockedUserAssociation[]
     */
    protected $assignedBlockedUserAssociations = null;

    /**
     * Users blocked by this entity
     *
     * @OneToMany(targetEntity="bblue\ruby\Entities\BlockedUserAssociation", mappedBy="user")
     * @var BlockedUserAssociation[]
     */
    protected $assignedUserBlockedAssociations = null;    
    
    /**
     * The sponsor for this entity
     * 
     * @OneToOne(targetEntity="bblue\ruby\Entities\User")
     * @JoinColumn(name="sponsor_id", referencedColumnName="id")
     * @var User
     * @todo Dette skal være one-to-many
     */
    private $sponsor;
    
    /**
     * @ManyToMany(targetEntity="bblue\ruby\Entities\User", mappedBy="myFriends")
     **/
    private $friendsWithMe;
    
    /**
     * @ManyToMany(targetEntity="bblue\ruby\Entities\User", inversedBy="friendsWithMe")
     * @JoinTable(name="friends",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="friend_user_id", referencedColumnName="id")}
     *      )
     **/
    private $myFriends;    
    
    public function __construct()
    {
        //@todo jeg tror ikke at jeg lenger trenger dette om jeg bruker metoden med friends
        $this->assignedUsergroupUserAssociations = new ArrayCollection();
        $this->assignedFollowerUserAssociations = new ArrayCollection();
        $this->assignedObserverUserAssociations = new ArrayCollection();
        $this->assignedUserFollowerAssociations = new ArrayCollection();
        $this->assignedUserObserverAssociations = new ArrayCollection();
        $this->assignedBlockedUserAssociations = new ArrayCollection();
        $this->assignedUserBlockedAssociations = new ArrayCollection();
        
        $this->friendsWithMe = new ArrayCollection();
        $this->myFriends = new ArrayCollection();
        
        $this->auths = new ArrayCollection();
        $this->loginAttempts = new ArrayCollection();
    }

    
    /**
     * Method to retrieve the firstname
     * @return string
     */
    public function getFirstname() { return $this->firstname; }
    public function setFirstname($name) { $this->firstname = $name; }
    
    public function getLoginAttempts() { return $this->loginAttempts; }
    public function setLoginAttempts($loginAttempts) { $this->loginAttempts = $loginAttempts; return $this; }
    
    public function getLastname() { return $this->lastname; }
    
    public function getUsername() { return $this->username; }
    public function setUsername($username) { $this->username = $username; return $this; }
    
    public function getId() { return $this->id; }
    
    public function getPassword()  {  return $this->getPasswordHash(); }
    
    public function getLoginHash() { return $this->loginHash; }
    public function refreshLoginHash()
    {
        $this->loginHash = md5(time() . $this->username . 'humdidumpty');
    }
    
    public function matchPassword($submittedPassword) 
    {
        return PasswordHelper::matchPasswords($submittedPassword, $this->getPassword());
    }
    
    public function isLocked()
    {
        //@todo
    }
    
    public function isBlocked()
    {
        //@todo;
    }
    
    public function getAuthAttempts()
    {
        //@todo: dette skal sannsynligvis være et repository med entities, slik at jeg må kalle ->count() på de som kaller metoden
        return 0;
    }
    
    public function addLoginAttempt(LoginAttempt $loginAttempt)
    {
        $this->loginAttempts[] = $loginAttempt;
    }
}