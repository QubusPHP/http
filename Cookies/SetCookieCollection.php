<?php

/**
 * Qubus\Http
 *
 * @link       https://github.com/QubusPHP/http
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @copyright  2015 Beau Simensen <beau@dflydev.com>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Http\Cookies;

use DateTime;
use DateTimeInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\SameSite;
use Qubus\Http\Cookies\Util;

use function array_shift;
use function count;
use function explode;
use function gmdate;
use function implode;
use function is_int;
use function is_numeric;
use function is_string;
use function sprintf;
use function strtolower;
use function strtotime;
use function urlencode;

final class SetCookieCollection
{
    private ?string $name = null;
    private ?string $value = null;
    private int $expires = 0;
    private int $maxAge = 0;
    private ?string $path = null;
    private ?string $domain = null;
    private bool $secure = false;
    private bool $httpOnly = false;
    private ?SameSite $sameSite = null;

    private function __construct(string $name, ?string $value = null)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * Retrieve name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieve value.
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Retrieve expiry.
     */
    public function getExpires(): int
    {
        return $this->expires;
    }

    /**
     * Retrieve max age.
     */
    public function getMaxAge(): int
    {
        return $this->maxAge;
    }

    /**
     * Retrieve path.
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Retrieve domain.
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Is SetCookieCollection set to secure?
     */
    public function getSecure(): bool
    {
        return $this->secure;
    }

    /**
     * Check if set to http only.
     */
    public function getHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * Retrieve samesite.
     */
    public function getSameSite(): ?SameSite
    {
        return $this->sameSite;
    }

    /**
     * Return an instance with the provided value.
     */
    public function withValue(?string $value = null): self
    {
        $clone = clone $this;

        $clone->value = $value;

        return $clone;
    }

    /**
     * @param int|DateTimeInterface|string|null $expires
     */
    private function resolveExpires($expires = null): int
    {
        if ($expires === null) {
            return 0;
        }

        if ($expires instanceof DateTimeInterface) {
            return $expires->getTimestamp();
        }

        if (is_numeric($expires)) {
            return (int) $expires;
        }

        $time = strtotime($expires);

        if (! is_int($time)) {
            throw new TypeException(
                sprintf(
                    'Invalid expires "%s" provided.',
                    $expires
                )
            );
        }

        return $time;
    }

    /**
     * Return an instance with the provided expiry.
     *
     * @param int|string|DateTimeInterface|null $expires
     */
    public function withExpires($expires = null): self
    {
        $expires = $this->resolveExpires($expires);

        $clone = clone $this;

        $clone->expires = $expires;

        return $clone;
    }

    public function rememberForever(): self
    {
        return $this->withExpires(new DateTime('+5 years'));
    }

    public function expire(): self
    {
        return $this->withExpires(new DateTime('-5 years'));
    }

    /**
     * Return an instance with the provided max age.
     */
    public function withMaxAge(?int $maxAge = null): self
    {
        $clone = clone $this;

        $clone->maxAge = (int) $maxAge;

        return $clone;
    }

    /**
     * Return an instance with the provided path.
     */
    public function withPath(?string $path = null): self
    {
        $clone = clone $this;

        $clone->path = $path;

        return $clone;
    }

    /**
     * Return an instance with the provided domain.
     */
    public function withDomain(?string $domain = null): self
    {
        $clone = clone $this;

        $clone->domain = $domain;

        return $clone;
    }

    /**
     * Return an instance with/without
     */
    public function withSecure(bool $secure = true): self
    {
        $clone = clone $this;

        $clone->secure = $secure;

        return $clone;
    }

    public function withHttpOnly(bool $httpOnly = true): self
    {
        $clone = clone $this;

        $clone->httpOnly = $httpOnly;

        return $clone;
    }

    public function withSameSite(SameSite $sameSite): self
    {
        $clone = clone $this;

        $clone->sameSite = $sameSite;

        return $clone;
    }

    public function withoutSameSite(): self
    {
        $clone = clone $this;

        $clone->sameSite = null;

        return $clone;
    }

    public function __toString(): string
    {
        $cookieStringParts = [
            urlencode($this->name) . '=' . urlencode((string) $this->value),
        ];

        $cookieStringParts = $this->appendFormattedDomainPartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedPathPartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedExpiresPartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedMaxAgePartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedSecurePartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedHttpOnlyPartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedSameSitePartIfSet($cookieStringParts);

        return implode('; ', $cookieStringParts);
    }

    public static function create(string $name, ?string $value = null): self
    {
        return new static($name, $value);
    }

    public static function createRememberedForever(string $name, ?string $value = null): self
    {
        return static::create($name, $value)->rememberForever();
    }

    public static function createExpired(string $name): self
    {
        return static::create($name)->expire();
    }

    public static function fromSetCookieString(string $string): self
    {
        $rawAttributes = Util::splitOnAttributeDelimiter($string);

        $rawAttribute = array_shift($rawAttributes);

        if (! is_string($rawAttribute)) {
            throw new TypeException(
                sprintf(
                    'The provided cookie string "%s" must have at least one attribute.',
                    $string
                )
            );
        }

        [$cookieName, $cookieValue] = Util::splitCookiePair($rawAttribute);

        /** @var SetCookieCollection $setCookie */
        $setCookie = new static($cookieName);

        if ($cookieValue !== null) {
            $setCookie = $setCookie->withValue($cookieValue);
        }

        while ($rawAttribute = array_shift($rawAttributes)) {
            $rawAttributePair = explode('=', $rawAttribute, 2);

            $attributeKey   = $rawAttributePair[0];
            $attributeValue = count($rawAttributePair) > 1 ? $rawAttributePair[1] : null;

            $attributeKey = strtolower($attributeKey);

            switch ($attributeKey) {
                case 'expires':
                    $setCookie = $setCookie->withExpires($attributeValue);
                    break;
                case 'max-age':
                    $setCookie = $setCookie->withMaxAge((int) $attributeValue);
                    break;
                case 'domain':
                    $setCookie = $setCookie->withDomain($attributeValue);
                    break;
                case 'path':
                    $setCookie = $setCookie->withPath($attributeValue);
                    break;
                case 'secure':
                    $setCookie = $setCookie->withSecure(true);
                    break;
                case 'httponly':
                    $setCookie = $setCookie->withHttpOnly(true);
                    break;
                case 'samesite':
                    $setCookie = $setCookie->withSameSite(SameSite::fromString((string) $attributeValue));
                    break;
            }
        }

        return $setCookie;
    }

    /**
     * @param string[] $cookieStringParts
     * @return string[]
     */
    private function appendFormattedDomainPartIfSet(array $cookieStringParts): array
    {
        if ($this->domain) {
            $cookieStringParts[] = sprintf('Domain=%s', $this->domain);
        }

        return $cookieStringParts;
    }

    /**
     * @param string[] $cookieStringParts
     * @return string[]
     */
    private function appendFormattedPathPartIfSet(array $cookieStringParts): array
    {
        if ($this->path) {
            $cookieStringParts[] = sprintf('Path=%s', $this->path);
        }

        return $cookieStringParts;
    }

    /**
     * @param string[] $cookieStringParts
     * @return string[]
     */
    private function appendFormattedExpiresPartIfSet(array $cookieStringParts): array
    {
        if ($this->expires) {
            $cookieStringParts[] = sprintf('Expires=%s', gmdate('D, d M Y H:i:s T', $this->expires));
        }

        return $cookieStringParts;
    }

    /**
     * @param string[] $cookieStringParts
     * @return string[]
     */
    private function appendFormattedMaxAgePartIfSet(array $cookieStringParts): array
    {
        if ($this->maxAge) {
            $cookieStringParts[] = sprintf('Max-Age=%s', $this->maxAge);
        }

        return $cookieStringParts;
    }

    /**
     * @param string[] $cookieStringParts
     * @return string[]
     */
    private function appendFormattedSecurePartIfSet(array $cookieStringParts): array
    {
        if ($this->secure) {
            $cookieStringParts[] = 'Secure';
        }

        return $cookieStringParts;
    }

    /**
     * @param string[] $cookieStringParts
     * @return string[]
     */
    private function appendFormattedHttpOnlyPartIfSet(array $cookieStringParts): array
    {
        if ($this->httpOnly) {
            $cookieStringParts[] = 'HttpOnly';
        }

        return $cookieStringParts;
    }

    /**
     * @param string[] $cookieStringParts
     * @return string[]
     */
    private function appendFormattedSameSitePartIfSet(array $cookieStringParts): array
    {
        if ($this->sameSite === null) {
            return $cookieStringParts;
        }

        $cookieStringParts[] = $this->sameSite->asString();

        return $cookieStringParts;
    }
}
