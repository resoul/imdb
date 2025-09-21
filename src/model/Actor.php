<?php
/**
 * Actor/Crew Model Class
 *
 * Represents a cast or crew member with their role and character information.
 *
 * @package resoul\imdb\model
 * @author resoul
 * @version 0.1.3
 * @since 0.1.0
 */
namespace resoul\imdb\model;

use resoul\imdb\model\enum\RoleEnum;

class Actor
{
    /**
     * Character name (for actors) or null for crew
     */
    private ?string $roleName;

    /**
     * Role type (Actor, Director, Writer, etc.)
     */
    private RoleEnum $role;

    /**
     * Person's real name
     */
    private string $original;

    /**
     * IMDB Pro URL for this person
     */
    private string $uri;

    /**
     * Profile image URL
     */
    private ?string $poster;

    /**
     * Initialize a new cast or crew member.
     *
     * @param string $original Person's real name
     * @param string $uri IMDB Pro URL for this person
     * @param RoleEnum $role Role type (Actor, Director, Writer, etc.)
     * @param string|null $roleName Character name (for actors only)
     * @param string|null $poster Profile image URL
     *
     * @example Create an actor:
     * ```php
     * $actor = new Actor(
     *     original: 'Tom Cruise',
     *     uri: 'https://pro.imdb.com/name/nm0000129/',
     *     role: RoleEnum::ACTOR,
     *     roleName: 'Pete "Maverick" Mitchell'
     * );
     * ```
     *
     * @example Create a director:
     * ```php
     * $director = new Actor(
     *     original: 'Joseph Kosinski',
     *     uri: 'https://pro.imdb.com/name/nm0468407/',
     *     role: RoleEnum::DIRECTOR
     * );
     * ```
     *
     * @since 0.1.0
     */
    public function __construct(
        string $original,
        string $uri,
        RoleEnum $role,
        ?string $roleName = null,
        ?string $poster = null,
    ) {
        $this->original = $original;
        $this->uri = $uri;
        $this->role = $role;
        $this->roleName = $roleName;
        $this->poster = $poster;
    }

    /**
     * Get the IMDB Pro URL for this person.
     *
     * @return string IMDB Pro person URL
     *
     * @example
     * ```php
     * echo '<a href="' . $actor->getUri() . '">' . $actor->getOriginal() . '</a>';
     * ```
     *
     * @since 0.1.0
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the person's real name.
     *
     * @return string Person's name
     *
     * @example
     * ```php
     * echo "Starring: " . $actor->getOriginal();
     * ```
     *
     * @since 0.1.0
     */
    public function getOriginal(): string
    {
        return $this->original;
    }

    /**
     * Get the person's role type.
     *
     * @return RoleEnum Role enumeration (Actor, Director, Writer, etc.)
     *
     * @example
     * ```php
     * echo $actor->getRole()->name . ": " . $actor->getOriginal();
     * ```
     *
     * @since 0.1.0
     */
    public function getRole(): RoleEnum
    {
        return $this->role;
    }

    /**
     * Get the character name (for actors).
     *
     * @return string|null Character name or null if not applicable
     *
     * @example
     * ```php
     * if ($character = $actor->getRoleName()) {
     *     echo $actor->getOriginal() . " as " . $character;
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getRoleName(): ?string
    {
        return $this->roleName;
    }

    /**
     * Get the profile image URL.
     *
     * @return string|null Profile image URL or null if not available
     *
     * @example
     * ```php
     * if ($poster = $actor->getPoster()) {
     *     echo '<img src="' . $poster . '" alt="' . $actor->getOriginal() . '">';
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getPoster(): ?string
    {
        return $this->poster;
    }
}