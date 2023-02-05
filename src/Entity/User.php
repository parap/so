<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"username"}, message="There is already an account with this username")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=1024)
     */
    private $email;

    /**
     * @ORM\OneToMany(targetEntity=Answer::class, mappedBy="user", orphanRemoval=true)
     */
    private $answers;

    /**
     * @ORM\OneToMany(targetEntity=Question::class, mappedBy="user", orphanRemoval=true)
     */
    private $questions;

    /**
     * @ORM\OneToMany(targetEntity=Rating::class, mappedBy="user", orphanRemoval=true)
     */
    private $ratings;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
        $this->questions = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, Answer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers[] = $answer;
            $answer->setUser($this);
        }

        return $this;
    }

    public function removeAnswer(Answer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            // set the owning side to null (unless already changed)
            if ($answer->getUser() === $this) {
                $answer->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions[] = $question;
            $question->setUser($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getUser() === $this) {
                $question->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(Rating $rating): self
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings[] = $rating;
            $rating->setUser($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): self
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getUser() === $this) {
                $rating->setUser(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return (string)$this->username;
    }

    /**
     * @param Question $question
     * @return Answer|bool
     */
    public function hasAnswerTo(Question $question)
    {
        foreach ($question->getAnswers() as $answer) {
            if ($answer->getUser() === $this) {
                return $answer;
            }
        }

        return false;
    }

    public function canAnswerQuestion(Question $question): bool
    {
        if ($this->questions->contains($question)) {
            return false;
        }

        if ($this->hasAnswerTo($question) instanceof Answer) {
            return false;
        }

        return $question->getUser()->getId() <> $this->getId();
    }


    public function canVoteUp(Answer $answer)
    {
        if ($answer->getUser() === $this) {
            return false;
        }

        foreach ($this->ratings as $userRating) {
            foreach ($answer->getRatings() as $answerRating) {
                if ($userRating === $answerRating && $userRating->getValue() == '1') {
                    return false;
                }
            }
        }

        return true;
    }

    public function canVoteDown(Answer $answer)
    {
        if ($answer->getUser() === $this) {
            return false;
        }

        foreach ($this->ratings as $userRating) {
            foreach ($answer->getRatings() as $answerRating) {
                if ($userRating === $answerRating && $userRating->getValue() == '-1') {
                    return false;
                }
            }
        }

        return true;
    }

    public function voteUp(Answer $answer)
    {
        if (!$this->canVoteUp($answer)) {
            return false;
        }

        foreach ($this->ratings as $userRating) {
            foreach ($answer->getRatings() as $answerRating) {
                if ($userRating === $answerRating) {
                    return $userRating->setValue((int)$userRating->getValue() + 1);
                }
            }
        }

        $rating = new Rating();

        return $rating
            ->setUser($this)
            ->setAnswer($answer)
            ->setValue('1');
    }

    public function voteDown(Answer $answer)
    {
        if (!$this->canVoteDown($answer)) {
            return false;
        }

        foreach ($this->ratings as $userRating) {
            foreach ($answer->getRatings() as $answerRating) {
                if ($userRating === $answerRating) {
                    return $userRating->setValue((int)$userRating->getValue() - 1);
                }
            }
        }

        $rating = new Rating();

        return $rating
            ->setUser($this)
            ->setAnswer($answer)
            ->setValue('-1');
    }

}
