<?php
/**
 * Wesrc Copyright 2013
 * Modifying, copying, of code contained herein that is not specifically
 * authorized by Wesrc UG ("Company") is strictly prohibited.
 * Violators will be prosecuted.
 *
 * This restriction applies to proprietary code developed by WsSrc. Code from
 * third-parties or open source projects may be subject to other licensing
 * restrictions by their respective owners.
 *
 * Additional terms can be found at http://www.wesrc.com/company/terms
 *
 * PHP Version 5
 *
 * @category Entity
 * @package  Secretery
 * @author   Michael Scholl <michael@wesrc.com>
 * @license  http://www.wesrc.com/company/terms Terms of Service
 * @link     http://www.wesrc.com
 */

namespace Secretery\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
//use Doctrine\Common\Persistence\PersistentObject;

/**
 * Key Entity
 *
 * @category Entity
 * @package  Secretery
 * @author   Michael Scholl <michael@wesrc.com>
 * @license  http://www.wesrc.com/company/terms Terms of Service
 * @version  Release: @package_version@
 * @link     http://www.wesrc.com
 *
 * @ORM\Table(name="user2note")
 * @ORM\Entity()
 */
class User2Note //extends PersistentObject //implements InputFilterAwareInterface
{
    /**
     * @ORM\Column(name="user_id", type="integer")
     * @ORM\Id
     */
    protected $userId;

    /**
     * @ORM\Column(name="note_id", type="integer")
     * @ORM\Id
     */
    protected $noteId;

    /**
     * @ORM\Column(name="ekey", type="text")
     */
    protected $eKey;

    /**
     * @ORM\Column(name="read_permission", type="boolean")
     */
    protected $readPermission = false;

    /**
     * @ORM\Column(name="write_permission", type="boolean")
     */
    protected $writePermission = false;

    /**
     * @ORM\Column(name="owner", type="boolean")
     */
    protected $owner = false;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="date_created", type="datetime")
     */
    protected $dateCreated;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="date_updated", type="datetime")
     */
    protected $dateUpdated;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="user2note", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Note", inversedBy="user2note", cascade={"persist"})
     * @ORM\JoinColumn(name="note_id", referencedColumnName="id")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    protected $note;

    /**
     * @param  Note $note
     * @return self
     */
    public function setNote($note)
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return Note
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param  int $noteId
     * @return self
     */
    public function setNoteId($noteId)
    {
        $this->noteId = $noteId;
        return $this;
    }

    /**
     * @return int
     */
    public function getNoteId()
    {
        return $this->noteId;
    }

    /**
     * @param  User $user
     * @return self
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param  int $userId
     * @return self
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }


    /**
     * @param  string $eKey
     * @return self
     */
    public function setEkey($eKey)
    {
        $this->eKey = $eKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getEkey()
    {
        return $this->eKey;
    }

    /**
     * @param  bool $owner
     * @return self
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @return bool
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param  bool $readPermission
     * @return self
     */
    public function setReadPermission($readPermission)
    {
        $this->readPermission = $readPermission;
        return $this;
    }

    /**
     * @return bool
     */
    public function getReadPermission()
    {
        return $this->readPermission;
    }

    /**
     * @param  bool $writePermission
     * @return self
     */
    public function setWritePermission($writePermission)
    {
        $this->writePermission = $writePermission;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWritePermission()
    {
        return $this->writePermission;
    }

    /**
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @return \DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    /**
     * Convert the object to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $array                = get_object_vars($this);
        $array['dateCreated'] = $array['dateCreated']->format('Y-m-d H:i:s');
        $array['dateUpdated'] = $array['dateUpdated']->format('Y-m-d H:i:s');
        unset($array['user']);
        unset($array['note']);
        return $array;
    }

}