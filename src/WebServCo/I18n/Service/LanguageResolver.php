<?php

declare(strict_types=1);

namespace WebServCo\I18n\Service;

use Override;
use Psr\Http\Message\ServerRequestInterface;
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Contract\LanguageListInterface;
use WebServCo\I18n\Contract\LanguageResolverInterface;
use WebServCo\I18n\Contract\PreferredLanguageProviderInterface;
use WebServCo\I18n\DataTransfer\LanguageResolution;
use WebServCo\I18n\Enum\LanguageSource;

use function array_slice;
use function explode;
use function is_numeric;
use function is_string;
use function str_starts_with;
use function strtolower;
use function substr;
use function trim;

/**
 * The language of a request, in this order: `?lang=`, the preferred language (when a provider is given), the language
 * cookie, the `Accept-Language` header, the default language.
 *
 * An unknown value is ignored, and the next source decides.
 * The result is always a language of the list (the same object), whatever the source.
 */
final class LanguageResolver implements LanguageResolverInterface
{
    public const string COOKIE_NAME = 'lang';
    public const string QUERY_PARAMETER = 'lang';

    public function __construct(
        private LanguageListInterface $languageList,
        private ?PreferredLanguageProviderInterface $preferredLanguageProvider = null,
    ) {
    }

    #[Override]
    public function resolve(ServerRequestInterface $request): LanguageResolution
    {
        $language = $this->getParameterLanguage($request->getQueryParams(), self::QUERY_PARAMETER);
        if ($language !== null) {
            return new LanguageResolution($language, LanguageSource::Query);
        }

        $language = $this->getPreferredLanguage($request);
        if ($language !== null) {
            return new LanguageResolution($language, LanguageSource::Preference);
        }

        $language = $this->getParameterLanguage($request->getCookieParams(), self::COOKIE_NAME);
        if ($language !== null) {
            return new LanguageResolution($language, LanguageSource::Cookie);
        }

        $language = $this->getAcceptLanguage($request->getHeaderLine('Accept-Language'));
        if ($language !== null) {
            return new LanguageResolution($language, LanguageSource::AcceptLanguage);
        }

        return new LanguageResolution($this->languageList->getDefaultLanguage(), LanguageSource::Fallback);
    }

    /**
     * The supported language with the highest quality value; the first one listed on a tie.
     * Region and script subtags are ignored (`de-AT` is `de`); `*` and `q=0` select nothing.
     */
    private function getAcceptLanguage(string $header): ?LanguageInterface
    {
        $result = null;
        $resultQuality = 0.0;

        foreach (explode(',', $header) as $range) {
            $parts = explode(';', $range);
            $language = $this->languageList->findByCode(strtolower(explode('-', trim($parts[0]))[0]));
            $quality = $this->getQuality(array_slice($parts, 1));
            if ($language === null || $quality <= $resultQuality) {
                continue;
            }

            $result = $language;
            $resultQuality = $quality;
        }

        return $result;
    }

    /**
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $parameters Query or cookie parameters.
     * @phpcs:enable
     */
    private function getParameterLanguage(array $parameters, string $name): ?LanguageInterface
    {
        if (!isset($parameters[$name]) || !is_string($parameters[$name])) {
            return null;
        }

        return $this->languageList->findByCode($parameters[$name]);
    }

    /**
     * The language of the list with the code of the stored preference, if any.
     */
    private function getPreferredLanguage(ServerRequestInterface $request): ?LanguageInterface
    {
        $preferredLanguage = $this->preferredLanguageProvider?->getPreferredLanguage($request);
        if ($preferredLanguage === null) {
            return null;
        }

        return $this->languageList->findByCode($preferredLanguage->getCode());
    }

    /**
     * Quality value of a language range: 1 without `q`, 0 when invalid.
     *
     * @param list<string> $parameters The parameters of the range (after the language tag).
     */
    private function getQuality(array $parameters): float
    {
        foreach ($parameters as $parameter) {
            $parameter = trim($parameter);
            if (str_starts_with(strtolower($parameter), 'q=')) {
                return $this->parseQuality(substr($parameter, 2));
            }
        }

        return 1.0;
    }

    private function parseQuality(string $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }

        $quality = (float) $value;

        return $quality >= 0 && $quality <= 1
            ? $quality
            : 0.0;
    }
}
