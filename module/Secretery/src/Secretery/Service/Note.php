<?php
/**
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * PHP Version 5
 *
 * @category Service
 * @package  Secretery
 * @author   Michael Scholl <michael@wesrc.com>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @version  GIT: <git_id>
 * @link     https://github.com/wesrc/secretery
 */

namespace Secretery\Service;

use Doctrine\Common\Collections\ArrayCollection;
use DoctrineORMModule\Form\Annotation\AnnotationBuilder;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Secretery\Entity\Note as NoteEntity;
use Secretery\Entity\User as UserEntity;
use Secretery\Entity\Group as GroupEntity;
use Secretery\Entity\User2Note as User2NoteEntity;
use Secretery\Form\GroupSelect as GroupSelectForm;
use Secretery\Form\KeyRequest as KeyRequestForm;

/**
 * Note Mapper
 *
 * @category Mapper
 * @package  Secretery
 * @author   Michael Scholl <michael@wesrc.com>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @version  GIT: <git_id>
 * @link     https://github.com/wesrc/secretery
 */
class Note extends Base
{
    /**
     * @var Encryption
     */
    protected $encryptionService;

    /**
     * @var \Secretery\Form\GroupSelect
     */
    protected $groupForm;

    /**
     * @var \Zend\Form\Form
     */
    protected $noteForm;


    /**
     * @var \Secretery\Form\KeyRequest
     */
    protected $keyRequestForm;

    /**
     * @param Encryption $encryptionService
     */
    public function setEncryptionService(Encryption $encryptionService)
    {
        $this->encryptionService = $encryptionService;
        return $this;
    }

    /**
     * @return Encryption
     */
    public function getEncryptionService()
    {
        return $this->encryptionService;
    }

    /**
     * @param GroupForm $groupForm
     */
    public function setGroupForm(GroupForm $groupForm)
    {
        $this->groupForm = $groupForm;
        return $this;
    }

    /**
     * @param  int    $userId
     * @param  string $url
     * @return \Zend\Form\Form
     */
    public function getGroupForm($userId, $url = '')
    {
        if (is_null($this->groupForm)) {
            $this->groupForm = new GroupSelectForm($userId, $url);
            $this->groupForm->setObjectManager($this->getEntityManager());
            $this->groupForm->init();
        }
        return $this->groupForm;
    }

    /**
     * @param NoteForm $noteForm
     */
    public function setNoteForm(NoteForm $noteForm)
    {
        $this->noteForm = $noteForm;
        return $this;
    }

    /**
     * @param  \Secretery\Entity\Note $noteRecord
     * @param  string                 $url
     * @param  string                 $action
     * @param  array                  $members
     * @return \Zend\Form\Form
     */
    public function getNoteForm(NoteEntity $note, $url = '', $action = 'add', $members = null)
    {
        if (is_null($this->noteForm)) {
            $builder        = new AnnotationBuilder($this->getEntityManager());
            $this->noteForm = $builder->createForm($note);
            $this->noteForm->setAttribute('action', $url);
            $this->noteForm->setAttribute('id', 'noteForm');
            $this->noteForm->setHydrator(new DoctrineObject(
                $this->getEntityManager(),
                'Secretery\Entity\Note'
            ));
            $this->noteForm->bind($note);
            if ($action == 'edit' && $note->getPrivate() === false) {
                $this->noteForm->remove('private');
                $group         = $note->getGroup();
                $membersString = $this->getMembersString(array_keys($members));
                $this->noteForm->get('group')->setValue($group->getId());
                $this->noteForm->get('members')->setValue($membersString);
            } else {
                $this->noteForm->get('private')->setAttribute('required', false);
                $this->noteForm->getInputFilter()->get('private')->setRequired(false);
            }
        }
        return $this->noteForm;
    }

    /**
     * @param KeyRequestForm $keyRequestForm
     */
    public function setKeyRequestForm(KeyRequestForm $keyRequestForm)
    {
        $this->keyRequestForm = $keyRequestForm;
        return $this;
    }

    /**
     * @param  string $url
     * @return KeyRequestForm
     */
    public function getKeyRequestForm($url = '')
    {
        if (is_null($this->keyRequestForm)) {
            $this->keyRequestForm = new KeyRequestForm($url);
        }
        return $this->keyRequestForm;
    }

    /**
     * @param  int $userId
     * @param  int $noteId
     * @return bool
     */
    public function checkNoteEditPermission($userId, $noteId)
    {
        /* @var $user2noteRecord User2NoteEntity */
        $user2noteRecord = $this->getUser2NoteRepository()->fetchUserNote($userId, $noteId);
        if (empty($user2noteRecord)) {
            return false;
        }
        if (true === $user2noteRecord->getOwner() ||
            true === $user2noteRecord->getWritePermission())
        {
            return true;
        }
        return false;
    }

    /**
     * @param  int $userId
     * @param  int $noteId
     * @return bool
     */
    public function checkNoteViewPermission($userId, $noteId)
    {
        /* @var $user2noteRecord User2NoteEntity */
        $user2noteRecord = $this->getUser2NoteRepository()->fetchUserNote($userId, $noteId);
        if (empty($user2noteRecord)) {
            return false;
        }
        if (true === $user2noteRecord->getOwner() ||
            true === $user2noteRecord->getReadPermission())
        {
            return true;
        }
        return false;
    }

    /**
     * @param  \Secretery\Entity\User $user
     * @param  \Secretery\Entity\Note $note
     * @return void
     */
    public function deleteUserNote($userId, $noteId)
    {
        $note = $this->fetchNote($noteId);
        $user2Note = $this->getUser2NoteRepository()->findOneBy(
            array('userId' => $userId, 'noteId' => $noteId)
        );
        $this->em->remove($user2Note);
        $this->em->remove($note);
        $this->em->flush();
        return;
    }

    /**
     * @param  int    $noteId
     * @param  int    $userId
     * @param  string $keyCert
     * @param  string $passphrase
     * @return array  With 'note' and 'decrypted' keys
     * @throws \LogicException If key is not readable
     * @throws \LogicException If note could note be decrypted
     */
    public function doNoteEncryption($noteId, $userId, $keyCert, $passphrase)
    {
        $validationCheck = $this->validateKey($keyCert, $passphrase);
        // Show key read error
        if (false === $validationCheck) {
            throw new \LogicException('Your key is not readable');
        }
        // Fetch Note
        $note      = $this->fetchNoteWithUserData($noteId, $userId);
        $decrypted = $this->decryptNote(
            $note['content'],
            $note['eKey'],
            $keyCert,
            $passphrase
        );
        // Show key read error
        if (false === $decrypted) {
            throw new \LogicException('Note could not be decrypted');
        }
        return array(
            'note'      => $note,
            'decrypted' => $decrypted
         );
    }

    /**
     * @param  int $id
     * @return \Secretery\Entity\Note
     */
    public function fetchNote($id)
    {
        return $this->getNoteRepository()->fetchNote($id);
    }

    /**
     * @param  int $noteId
     * @param  int $userId
     * @return \Secretery\Entity\Note
     */
    public function fetchNoteWithUserData($noteId, $userId)
    {
        return $this->getNoteRepository()->fetchNoteWithUserData($noteId, $userId);
    }

    /**
     * @param  int $userId
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function fetchUserNotes($userId)
    {
        return $this->getNoteRepository()->fetchUserNotes($userId);
    }

    /**
     * @param  int $userId
     * @param  int $groupId
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function fetchGroupNotes($userId, $groupId = null)
    {
        return $this->getNoteRepository()->fetchGroupNotes($userId, $groupId);
    }

    /**
     * @param  \Secretery\Entity\User  $user
     * @param  \Secretery\Entity\Group $group
     * @return void
     */
    public function deleteUserFromGroupNotes(UserEntity $user, GroupEntity $group)
    {
        $groupNotes = $this->getNoteRepository()->fetchGroupNotes(
            $user->getId(), $group->getId(), \Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT
        );
        if (empty($groupNotes)) {
            return;
        }

        $groupOwner = $this->getUserRepository()->find($group->getOwner());
        foreach ($groupNotes as $note) {
            $this->getUser2NoteRepository()->checkNoteOwnershipForLeavingUser(
                $note, $user->getId(), $groupOwner->getId()
            );
        }

        return;
    }

    /**
     * Save user note
     *
     * @param  \Secretery\Entity\User $owner
     * @param  \Secretery\Entity\Note $note
     * @param  int                    $groupId
     * @param  string                 $members
     * @return \Secretery\Entity\Note
     */
    public function saveGroupNote(UserEntity $owner, NoteEntity $note, $groupId, $members)
    {
        $members   = $this->getMembersArray($members);
        $usersKeys = $this->getUsersWithKeys($members, $owner, $groupId);
        $users     = $usersKeys['users'];
        $keys      = $usersKeys['keys'];

        $encryptData = $this->getEncryptionService()->encryptForMultipleKeys(
            $note->getContent(),
            $keys
        );

        $note->setContent($encryptData['content']);
        $this->em->persist($note);
        $this->em->flush();

        $note = $this->saveUser2NoteRelations($users, $note, $owner, $encryptData);

        $this->events->trigger('sendMail', 'note-add', array(
            'note'  => $note,
            'owner' => $owner,
            'users' => $users
        ));

        return $note;
    }

    /**
     * Save user note
     *
     * @param  \Secretery\Entity\User $owner
     * @param  \Secretery\Entity\Note $note
     * @param  int                    $groupId
     * @param  string                 $members
     * @return \Secretery\Entity\Note
     */
    public function updateGroupNote(UserEntity $owner, NoteEntity $note, $groupId, $members)
    {
        $members   = $this->getMembersArray($members);
        $usersKeys = $this->getUsersWithKeys($members, $owner, $groupId);
        $users     = $usersKeys['users'];
        $keys      = $usersKeys['keys'];

        $encryptData = $this->getEncryptionService()->encryptForMultipleKeys(
            $note->getContent(),
            $keys
        );

        // Remove Associations
        $this->getUser2NoteRepository()->removeUsersFromNote($note->getId());

        // Save Note
        $note->setContent($encryptData['content']);
        $this->em->persist($note);
        $this->em->flush();

        $note = $this->saveUser2NoteRelations($users, $note, $owner, $encryptData);

        $this->events->trigger('sendMail', 'note-edit', array(
            'note'  => $note,
            'owner' => $owner,
            'users' => $users
        ));

        return $note;
    }

    /**
     * Save user note
     *
     * @param  \Secretery\Entity\User $user
     * @param  \Secretery\Entity\Note $note
     * @return \Secretery\Entity\Note
     */
    public function saveUserNote(UserEntity $user, NoteEntity $note)
    {
        $encryptData = $this->getEncryptionService()->encryptForSingleKey(
            $note->getContent(),
            $user->getKey()->getPubKey()
        );
        $note->setContent($encryptData['content']);

        $this->em->persist($note);
        $this->em->flush();

        $user2Note = new User2NoteEntity();
        $user2Note->setUser($user)
            ->setUserId($user->getId())
            ->setNote($note)
            ->setNoteId($note->getId())
            ->setEkey($encryptData['ekey'])
            ->setOwner(true)
            ->setReadPermission(true)
            ->setWritePermission(true);

        $note->addUser2Note($user2Note);

        $this->em->persist($note);
        $this->em->flush();
        return $note;
    }

    /**
     * @param  \Secretery\Entity\User $user
     * @param  \Secretery\Entity\Note $note
     * @return \Secretery\Entity\Note
     */
    public function updateUserNote(UserEntity $user, NoteEntity $note)
    {
        $encryptData = $this->getEncryptionService()->encryptForSingleKey(
            $note->getContent(),
            $user->getKey()->getPubKey()
        );
        $note->setContent($encryptData['content']);

        $this->em->persist($note);
        $this->em->flush();

        $user2Note = $this->getUser2NoteRepository()->findOneBy(
            array('userId' => $user->getId(), 'noteId' => $note->getId())
        );
        $user2Note->setEkey($encryptData['ekey']);
        $note->addUser2Note($user2Note);

        $this->em->persist($note);
        $this->em->flush();
        return $note;
    }


    /**
     * @param  string $contentCrypted
     * @param  string $eKey
     * @param  string $keyCert
     * @param  string $passphrase
     * @return false/string
     */
    protected function decryptNote($contentCrypted, $eKey, $keyCert, $passphrase)
    {
        try {
            return $this->getEncryptionService()->decrypt(
                $contentCrypted,
                $eKey,
                $keyCert,
                $passphrase
            );
        } catch(\Exception $e) {
            //@todo logging?
        }
        return false;
    }

    /**
     * @param  string $members
     * @return array
     * @throws \LogicException If no members are given
     */
    protected function getMembersString(array $members)
    {
        if (empty($members)) {
            throw new \LogicException('No members given');
        }
        $membersString  = implode(',', $members);
        $membersString .= ',';
        return $membersString;
    }

    /**
     * @param  string $members
     * @return array
     * @throws \LogicException If no members are given
     */
    protected function getMembersArray($members)
    {
        if (empty($members)) {
            throw new \LogicException('No members given');
        }
        $membersArray = explode(',', trim($members, ','));
        $membersArray = array_unique($membersArray);
        if (empty($membersArray)) {
            throw new \LogicException('You must provide at least one note member');
        }
        return $membersArray;
    }

    /**
     * @param  string     $members
     * @param  UserEntity $owner
     * @param  int        $groupId
     * @return array $members
     * @throws \LogicException If given User(Member) ID does not
     * @throws \LogicException If given User(Member) has not set key
     * @throws \LogicException If given User(Member) is not member of given group
     */
    protected function getUsersWithKeys($members, UserEntity $owner, $groupId)
    {
        $users = array();
        $keys  = array();
        $group = $this->getGroupRepository()->find((int) $groupId);
        foreach ($members as $member) {
            /* @var $user \Secretery\Entity\User */
            $user = $this->getUserRepository()->find((int) $member);
            if (false === $user->getGroups()->contains($group)) {
                $this->events->trigger('logViolation', __METHOD__ . '::l42', array(
                    'message' => sprintf('User: %s wants to add user: %s to group: %s',
                        $this->identity->getEmail(),
                        $user->getEmail(),
                        $group->getName()
                    )
                ));
                throw new \LogicException('User does not belong to selected group');
            }
            if (empty($user)) {
                throw new \LogicException('User does not exists: ' . $member);
            }
            $key = $user->getKey();
            if (empty($key)) {
                throw new \LogicException('User key does not exists: ' . $member);
            }
            $users[$user->getId()] = $user;
            $keys[$user->getId()]  = $key->getPubKey();
        }
        $users[$owner->getId()] = $owner;
        $keys[$owner->getId()]  = $owner->getKey()->getPubKey();
        return array(
            'users' => $users,
            'keys'  => $keys
        );
    }

    /**
     * @param  array      $users
     * @param  NoteEntity $note
     * @param  UserEntity $owner
     * @param  array      $encryptData
     * @return Note
     */
    protected function saveUser2NoteRelations(array $users, NoteEntity $note,
                                              UserEntity $owner, array $encryptData)
    {
        $i = 0;
        // Save User2Note entries
        foreach ($users as $user) {
            $ownerCheck = false;
            if ($owner->getId() == $user->getId()) {
                $ownerCheck = true;
            }
            $user2Note = new User2NoteEntity();
            $user2Note->setUser($user)
                ->setUserId($user->getId())
                ->setNote($note)
                ->setNoteId($note->getId())
                ->setEkey($encryptData['ekeys'][$i])
                ->setOwner($ownerCheck)
                ->setReadPermission(true)
                ->setWritePermission($ownerCheck);

            $note->addUser2Note($user2Note);

            $this->em->persist($note);
            $i++;
        }
        $this->em->flush();
        return $note;
    }

    /**
     * @param  string $keyCert
     * @param  string $passphrase
     * @return bool
     */
    protected function validateKey($keyCert, $passphrase)
    {
        try {
            $this->getEncryptionService()->validateKey($keyCert, $passphrase);
            return true;
        } catch(\Exception $e) {
            //@todo logging?
        }
        return false;
    }

    /**
     * @return \Secretery\Entity\Repository\Group
     */
    protected function getGroupRepository()
    {
        return $this->em->getRepository('Secretery\Entity\Group');
    }

    /**
     * @return \Secretery\Entity\Repository\Note
     */
    protected function getNoteRepository()
    {
        return $this->em->getRepository('Secretery\Entity\Note');
    }

    /**
     * @return \Secretery\Entity\Repository\User
     */
    protected function getUserRepository()
    {
        return $this->em->getRepository('Secretery\Entity\User');
    }

    /**
     * @return \Secretery\Entity\Repository\User2Note
     */
    protected function getUser2NoteRepository()
    {
        return $this->em->getRepository('Secretery\Entity\User2Note');
    }

}
