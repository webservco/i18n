# webservco/i18n

A PHP component/library. Please see [webservco/sandbox](https://webservco.github.io/sandbox/) for an example project using these types of components.

Translates and formats texts with ICU in the language of a request: languages, message catalogs and their checks, dates and numbers, the language switch, localized views.

- Implements: [PSR-15: HTTP Server Request Handlers](https://www.php-fig.org/psr/psr-15/) (`LanguageMiddleware`)
- Requires: `ext-intl` (ICU), a directory of catalogs
- Related: `webservco/view` (`AbstractLocalizedView` is a view), `webservco/configuration` (`PHPConfigurationLoader` reads the catalogs), `webservco/http-request-handler` (`StackHandler` runs the middleware)

---

## Installation

```shell
composer require webservco/i18n
```

---

## Usage

### Languages and catalogs

The project describes its languages with any class that implements `LanguageInterface` (an enum is the usual choice), and builds the list once.

```php
use WebServCo\I18n\Contract\LanguageInterface;
use WebServCo\I18n\Service\LanguageList;

enum Language: string implements LanguageInterface
{
    // In the order of the language switch.
    case German = 'de';
    case English = 'en';

    public const Language DEFAULT = self::English;

    public function getCode(): string
    {
        return $this->value;
    }

    // `en_GB`: European formats in English too.
    public function getLocale(): string
    {
        return $this === self::English ? 'en_GB' : $this->value;
    }

    public function getNativeName(): string
    {
        return match ($this) {
            self::German => 'Deutsch',
            self::English => 'English',
        };
    }
}

// \WebServCo\I18n\Contract\LanguageListInterface
$languageList = new LanguageList(Language::cases(), Language::DEFAULT);
```

A catalog is a file `{directory}/{language code}/{catalog}.php` that returns `name => message`:

```php
// resources/i18n/en/example.php
return [
    'records' => '{count, plural, one {# record} other {# records}}',
    'title' => 'Records',
];
```

### Language of a request

```php
use WebServCo\I18n\Service\LanguageMiddleware;
use WebServCo\I18n\Service\LanguageResolver;

// Before the exception handlers, so that error and 404 pages are translated too.
$stackHandler->addMiddleware(
    new LanguageMiddleware(
        // \WebServCo\I18n\Contract\LanguageResolverInterface
        new LanguageResolver($languageList),
        // The language cookie is `Secure`: the site is HTTPS.
        true,
    ),
);
```

### Translating and formatting

```php
use WebServCo\Configuration\Service\PHPConfigurationLoader;
use WebServCo\I18n\Factory\LocalizationContainerFactory;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\RequestLanguageReader;

// \WebServCo\I18n\Contract\LocalizationContainerFactoryInterface
$localizationContainerFactory = new LocalizationContainerFactory(
    // \WebServCo\I18n\Contract\CatalogLoaderInterface
    new CatalogLoader(new PHPConfigurationLoader(), $projectPath . 'resources/i18n/'),
    $languageList->getDefaultLanguage(),
    // \Psr\Log\LoggerInterface: a key missing in a language is a warning.
    $logger,
);
// \WebServCo\I18n\Contract\RequestLanguageReaderInterface: the language `LanguageMiddleware` gave the request.
$requestLanguageReader = new RequestLanguageReader();
// \WebServCo\I18n\Contract\LocalizationContainerInterface
$localizationContainer = $localizationContainerFactory->createLocalizationContainer(
    $requestLanguageReader->getLanguage($request),
    // The display time zone.
    new DateTimeZone('UTC'),
);

$title = $localizationContainer->getTranslator()->translate('example.title');
$date = $localizationContainer->getFormatter()->formatDate($dateTime);
```

A command or an email names its own language and time zone: `createLocalizationContainer(Language::German, $timeZone)`.

A view that extends `AbstractLocalizedView` takes the container as its first constructor argument. Its template:

```php
<h1><?=$view->text('example.title')?></h1>
<p><?=$view->text('example.records', ['count' => $count])?>, <?=$view->date($dateTime)?></p>
```

### Language switch and search engines

```php
use WebServCo\I18n\Factory\AlternateLinkViewFactory;
use WebServCo\I18n\Factory\LanguageLinkViewFactory;

// list<LanguageLinkView>: code, current or not, native name, URL `?lang={code}`.
$languageLinkViewFactory = new LanguageLinkViewFactory($languageList);
$languageLinks = $languageLinkViewFactory->createLanguageLinks($requestLanguageReader->getLanguage($request));

// Indexable pages only. list<AlternateLinkView>: `hreflang` and absolute URL, one per language and `x-default`.
$alternateLinkViewFactory = new AlternateLinkViewFactory($languageList);
$alternateLinks = $alternateLinkViewFactory->createAlternateLinks('https://example.com/records');
```

### Checking the catalogs

The checks return problems instead of failing, so a project runs them in one test class, one method per check: a failure names its check. With a PHPUnit configuration that requires coverage metadata, the class has `#[CoversNothing]` (the code it runs is in `vendor/`).

```php
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use WebServCo\Configuration\Service\PHPConfigurationLoader;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\Check\CatalogReader;
use WebServCo\I18n\Service\Check\LiteralKeyCheck;
use WebServCo\I18n\Service\Check\StructureCheck;
use WebServCo\I18n\Service\LanguageList;

#[CoversNothing]
final class CatalogTest extends TestCase
{
    public function testStructure(): void
    {
        self::assertSame([], (new StructureCheck($this->createCatalogReader()))->check());
    }

    public function testLiteralKeys(): void
    {
        $check = new LiteralKeyCheck($this->createCatalogReader(), $this->getProjectPath(), ['config', 'src']);

        self::assertSame([], $check->check());
    }

    // One method for each other check.

    private function createCatalogReader(): CatalogReader
    {
        $directoryPath = $this->getProjectPath() . 'resources/i18n/';

        return new CatalogReader(
            new CatalogLoader(new PHPConfigurationLoader(), $directoryPath),
            new LanguageList(Language::cases(), Language::DEFAULT),
            $directoryPath,
        );
    }

    // The directory of `composer.json`, from `tests/Unit/`.
    private function getProjectPath(): string
    {
        return dirname(__DIR__, 2) . '/';
    }
}
```

---

## Contracts

| Interface | Implementation | Purpose |
| --- | --- | --- |
| `Contract\LanguageInterface` | the project's (an enum) | A language: code, ICU locale, native name. |
| `Contract\LanguageListInterface` | `Service\LanguageList` | The languages in the order of the language switch, the default one, a language by code. |
| `Contract\CatalogLoaderInterface` | `Service\CatalogLoader` | Loads one catalog of one language. |
| `Contract\TranslatorInterface` | `Service\Translator` | Translates a key, with its parameters, into one language. |
| `Contract\FormatterInterface` | `Service\Formatter` | Formats dates, times and numbers in one language and time zone. |
| `Contract\LocalizationContainerInterface` | `Container\LocalizationContainer` | The translator and the formatter of one language and time zone. |
| `Contract\LocalizationContainerFactoryInterface` | `Factory\LocalizationContainerFactory` | Creates `LocalizationContainerInterface`. |
| `Contract\LanguageResolverInterface` | `Service\LanguageResolver` | The language of a request, and where it comes from (`Enum\LanguageSource`). |
| `Contract\PreferredLanguageProviderInterface` | the project's | The language the user chose and the project stored (eg. a user setting). |
| `Contract\RequestLanguageReaderInterface` | `Service\RequestLanguageReader` | The language `LanguageMiddleware` gave the request. |
| `\Psr\Http\Server\MiddlewareInterface` | `Service\LanguageMiddleware` | Resolves the language, gives it to the request, sets the cookie and the response headers. |
| `Contract\CatalogCheckInterface` | `Service\Check\*Check` | Checks the catalogs, returns a list of `DataTransfer\CatalogProblem`. |

Namespaces are relative to `WebServCo\I18n\`. Without a contract: `View\AbstractLocalizedView`, `Factory\LanguageLinkViewFactory`, `Factory\AlternateLinkViewFactory`, `Service\Check\CatalogReader`, `Service\MessagePatternParser`.

---

## Keys and catalogs

- A language code is a primary language subtag (`de`): the catalog directory, the `?lang=` value, the `lang` attribute, `Content-Language`. A region belongs to the ICU locale (`en_GB`), not to the code. `LanguageList` refuses other codes, a code given twice, and a default language that isn't in the list.
- A key is `{catalog}.{name}`: `layout.footer.contact` is the message `footer.contact` in `{directory}/{code}/layout.php`.
- Catalog names are lowercase words joined by underscores (`layout`, `play_log`). Message names are such words in dot-separated groups (`footer.contact`).
- The default language is the reference: it has every key. Every other language has the same catalogs and the same names.
- A key missing in a language falls back to the default language and is logged (warning). A key missing in the default language throws `OutOfBoundsException`: a programming error. A missing catalog file is an empty catalog.
- Write every key in full, as one string: `LiteralKeyCheck` finds keys in the code by their form, so a key built at runtime (`'log.' . $name`) isn't checked.

## Messages

- [ICU MessageFormat](https://unicode-org.github.io/icu/userguide/format_parse/messages/): `Hello, {name}!`, `{count, plural, one {# record} other {# records}}`. Plurals and numbers follow the language of the message, with its ICU locale; a message that falls back is formatted in the default language.
- A plural (`plural`, `selectordinal`) has exactly the [plural categories](https://www.unicode.org/cldr/charts/latest/supplemental/language_plural_rules.html) of its language, plus any exact values (`=0`) it needs. ICU doesn't check them: a missing category falls back to `other`, and one the language doesn't have is never used. English and German have `one` and `other`; Spanish adds `many` (whole millions), Romanian `few` (0, 2 to 19, 101 to 119, ...).
- Plain text only: `$view->text()` escapes the whole result, parameters included. Markup stays in the template.
- An apostrophe is literal (`Don't`), unless it quotes ICU syntax: `'{'` is a literal brace.
- No leading or trailing whitespace.
- Give every parameter: a missing one stays in the output as `{name}`.
- A date inside a message is formatted first (`formatDate()`) and given as a string: ICU's own `{date, date}` uses the server's time zone.

## Formats

| View method | Formatter | English (`en_GB`) | German |
| --- | --- | --- | --- |
| `date($dateTime)` | `formatDate()`: medium date | 5 Nov 2026 | 05.11.2026 |
| `time($dateTime)` | `formatTime()`: short time | 21:07 | 21:07 |
| `dateTime($dateTime)` | `formatDateTime()`: both | 5 Nov 2026, 21:07 | 05.11.2026, 21:07 |
| `number($number)` | `formatNumber()`: grouping | 1,234,568 | 1.234.568 |
| `number($number, 2)` | fraction digits, always shown | 1,234,567.89 | 1.234.567,89 |

- Every date and time is shown in the display time zone of the container, whatever the time zone of the value. The time zone data is PHP's, so every `DateTimeZone` works, also one that ICU's own data doesn't have.
- Numbers are rounded half up (`2.5` is `3`), and a value rounded to zero has no minus sign.
- The styles are fixed. The view methods escape their result; the formatter's is plain text.

## Language of a request

`LanguageResolver`, first match wins. An unknown value is ignored, and the next source decides. The result is always the list's own object.

1. `?lang={code}`.
2. The preferred language, when `LanguageResolver` has a `PreferredLanguageProviderInterface` (its second argument). The provider runs where `LanguageMiddleware` is in the stack: a session middleware further down hasn't run yet.
3. The language cookie (`lang`).
4. `Accept-Language`: the language of the list with the highest `q`. Region subtags don't matter (`de-AT` is `de`); `*` and `q=0` select nothing.
5. The default language.

`LanguageMiddleware` gives the language to the request as the attribute `language` (`LanguageMiddleware::REQUEST_ATTRIBUTE`, read it with `RequestLanguageReader`). Project code that needs its own enum checks `instanceof Language`. Each response gets:

- `Content-Language: {code}`;
- with `?lang=`: `Set-Cookie: lang={code}; Path=/; Max-Age=31536000; SameSite=Lax; HttpOnly`, plus `Secure` when the middleware is told so;
- otherwise `Vary: Cookie, Accept-Language` (a preference is read with the session cookie).

The language links are relative and query-only (`?lang=de`): they keep the current path, and drop other query parameters. The alternate links drop the query and the fragment of the page URL: an indexable page's content depends on its path only.

## Catalog checks

Each check returns a list of `CatalogProblem` (the check's class, the language code, the key, a description); an empty list passes. All checks read through one `CatalogReader`. A catalog the loader refuses (a name or a message that isn't a string, an empty message) throws.

| Check (`Service\Check\`) | Reports |
| --- | --- |
| `StructureCheck` | A language without directory, a directory without language, the default language without catalogs; a catalog or a message name the default language lacks, or has alone. |
| `MessageSyntaxCheck` | An invalid catalog or message name, surrounding whitespace, an ICU error (errors only: `{count, number}` gives a warning), a valid message the other checks can't read. |
| `LookalikeLetterCheck` | Letters that only look right: Hungarian `õ`, `ô`, `û` for `ő`, `ű`; Romanian `ş`, `ţ` (cedilla) for `ș`, `ț` (comma below), `ã` for `ă`. Other languages: its second argument. |
| `ParameterCheck` | Other argument names, types or select cases than the default language. |
| `PluralCategoryCheck` | A plural without exactly the categories ICU uses in its language. They come from ICU, so a new language needs no change. |
| `DuplicateCaseCheck` | A plural or select case given twice (ICU uses the first). |
| `SpellingCheck` | Words of one language (its second argument) that match a list of patterns. By default common US spellings, for British English: -ize, -or, -er, -og, one l (canceled), a few words (gray, defense). A list, not a dictionary. |
| `LiteralKeyCheck` | A key written in the code that the default language lacks. Arguments: the project path and the source directories. It reads PHP strings, not inline HTML or comments, and reports when it finds no key at all. |

Every project can run the first six with the reader alone; `SpellingCheck` and `LiteralKeyCheck` need arguments. A wrong catalog directory doesn't pass: `StructureCheck` reports every language without a directory.

---

## Development

```shell
# Start project.
ddev start
# Install dependencies.
ddev c u
# Run all validations.
ddev c c
# Run unit tests, with coverage (needs Xdebug, which DDEV leaves off).
ddev xdebug on && ddev c test:dox; ddev xdebug off
```

---

## License

[MIT](LICENSE.md)
