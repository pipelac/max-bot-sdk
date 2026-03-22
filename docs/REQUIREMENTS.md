# Требования к SDK-проекту

## 1. Архитектура и SOLID

- [x] **SRP** — каждый класс имеет одну ответственность (Client ≠ HttpClient ≠ Resource)
  > `Client` — фасад, `CurlHttpClient` — транспорт, `ResponseDecoder` — парсинг, `RetryHandler` — retry, `Resource/*` — API-методы
- [x] **OCP** — добавление новых ресурсов/функциональности без модификации существующего кода
  > 6 интерфейсов в `Contracts/`: `ClientInterface`, `ConfigInterface`, `HttpClientInterface`, `ResponseDecoderInterface`, `LoggerInterface`, `ResourceInterface`
- [x] **LSP** — все реализации интерфейсов полностью взаимозаменяемы
  > `PaginatedResult::fromArray()` теперь совпадает с `AbstractDto::fromArray()`. Для расширенной логики — `fromApiResponse()`
- [x] **ISP** — интерфейсы узкие, клиенты не зависят от методов, которые им не нужны
  > `HttpClientInterface` (3 метода), `LoggerInterface` (4 метода), `ConfigInterface` (8 getters)
- [x] **DIP** — зависимость от абстракций (интерфейсов), не от конкретных классов
  > `Client(ConfigInterface, HttpClientInterface, ResponseDecoderInterface, RetryHandler)`
- [x] Отсутствие God-классов (>500 строк — проверить обоснованность)
  > Самый большой файл — `Client.php` (~290 строк), `CurlHttpClient.php` (~460 строк). Все в рамках нормы.
- [x] Composition over Inheritance (наследование только для исключений и DTO)
  > Наследование: `MaxException → leaf`, `AbstractDto → DTO`, `ResourceAbstract → Resource`. Всё остальное — композиция.
- [x] Иммутабельность конфигурации после инициализации
  > `Config` — нет setters, все значения через конструктор.
- [x] Ленивая инициализация ресурсов (lazy loading)
  > `Client::getResource()` с кэшем в `$resourceInstances[]`.
- [x] Фабрики для создания сложных объектов (Factory pattern)
  > `ClientFactory` с 5 фабричными методами: `create()`, `createFromIni()`, `createFromEnvironment()`, `createFromBuilder()`, `createFromConfig()`.

## 2. Контракты и интерфейсы

- [x] Интерфейсы для всех ключевых зависимостей
  > 6 интерфейсов: `ClientInterface`, `ConfigInterface`, `HttpClientInterface`, `ResponseDecoderInterface`, `LoggerInterface`, `ResourceInterface`
- [x] Отсутствие пустых/мёртвых интерфейсов
  > Все 6 используются в конструкторах и type-hints.
- [x] PHPDoc на каждом методе интерфейса с `@param`, `@return`, `@throws`
  > Все методы в каждом интерфейсе задокументированы.
- [x] `@since` аннотации при изменении контрактов
  > `@since 1.0.0` на всех классах.
- [x] Интерфейсы сгруппированы в отдельной директории (`Contracts/`)
  > `src/Contracts/` — 6 файлов.

## 3. Типизация и PHPDoc

- [x] Type hints на всех параметрах (для PHP 5.6 — через PHPDoc)
  > Все классы используют type-hints для интерфейсов + PHPDoc `@param` для scalar.
- [x] `@return` типы на всех методах
  > Проверено: `Client`, `Config`, `ConfigBuilder`, `CurlHttpClient`, все DTO, все Resources.
- [x] `@throws` с FQCN (Fully Qualified Class Name)
  > `@throws \MaxBotSdk\Exception\MaxApiException`, `MaxConnectionException`, и т.д.
- [x] `@param` описания на всех параметрах
  > Все публичные и приватные методы документированы.
- [x] `@var` на всех свойствах класса
  > Проверено на всех 44 классах.
- [x] Единый язык PHPDoc (только русский или только английский)
  > Русский — единый язык PHPDoc, комментариев, исключений.

## 4. Обработка ошибок

- [x] Иерархия исключений с единым базовым классом SDK
  > `MaxException → MaxApiException, MaxConnectionException, MaxConfigException, MaxFileException, MaxValidationException`.
- [x] Базовое исключение extends `\Exception` или `\RuntimeException`
  > `MaxException extends RuntimeException`.
- [x] Специализированные исключения: Api, Connection, Configuration, File, Validation
  > 5 leaf-исключений в `src/Exception/`.
- [x] Цепочка `$previous` при re-throw
  > `MaxApiException::__construct($msg, $code, $desc, $errCode, $previous)`, `MaxFileException` re-throws с `$previous`.
- [x] HTTP контекст в исключениях запросов (statusCode, responseBody)
  > `MaxApiException::getStatusCode()`, `getDescription()`, `getErrorCode()`.
- [x] Конфигурационные ошибки через отдельный `ConfigException`
  > `MaxConfigException` — пустой токен, невалидные значения.
- [x] Валидационные ошибки через отдельный `ValidationException`
  > `MaxValidationException` — невалидные входные параметры.
- [x] Все сообщения исключений на едином языке
  > Русский.
- [x] Leaf-исключения помечены `final`
  > Все 5: `final class MaxApiException`, `final class MaxConnectionException`, и т.д.

## 5. DTO / Value Objects

- [x] Иммутабельность (нет setters после создания)
  > `private function __construct()` + нет setters во всех 13 DTO.
- [x] `fromArray()` factory — статический метод создания из массива API
  > Все DTO: `User::fromArray()`, `Chat::fromArray()`, и т.д.
- [x] `toArray()` serialize — обратная сериализация в массив
  > Все DTO реализуют `toArray()`.
- [x] Типизированные getters (`getString()`, `getInt()`, `getBool()`, `*OrNull()`)
  > `AbstractDto`: `getString`, `getStringOrNull`, `getInt`, `getIntOrNull`, `getBool`, `getArray`, `getArrayOrNull`.
- [x] `final` классы — все DTO помечены `final`
  > Все 13: User, Chat, Message, Update, Attachment, ChatMember, Subscription, UploadResult, VideoInfo, ActionResult, PaginatedResult, UpdatesResult.
- [x] Абстрактный базовый класс (`AbstractDto`) с общими хелперами
  > `abstract class AbstractDto` с 7 protected helper-методами.
- [x] Коллекции — типизированные массивы DTO
  > `PaginatedResult` и `UpdatesResult` реализуют `Countable` + `IteratorAggregate`.

## 6. Безопасность

- [x] SSL валидация по умолчанию (`verify_ssl = true`)
  > `Config::DEFAULT_VERIFY_SSL = true`, `CURLOPT_SSL_VERIFYPEER + CURLOPT_SSL_VERIFYHOST`.
- [x] Маскирование чувствительных данных в URL и логах (password, token, api_key)
  > `InputValidator::maskToken()` — маскирует середину токена звёздочками.
- [x] Отсутствие секретов в логах
  > Логирование через `CurlHttpClient::logRequest()` использует `maskToken()`.
- [x] Timing-safe сравнение (`hash_equals()`) для webhook secret
  > `WebhookHandler::verifySecret()` — `hash_equals($secret, $bodyHash)`.
- [x] Чувствительные файлы (config) в `.gitignore`
  > `cfg/config.ini`, `*.local`, `.env` — в `.gitignore`.
- [x] Валидация входных данных перед API-вызовами
  > `InputValidator`: `validateNotEmpty`, `validateId`, `validateText`, `validateUploadType`, `validateCallbackId`.
- [x] No redirect (`CURLOPT_FOLLOWLOCATION = false`) при необходимости
  > `curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false)` в `CurlHttpClient`.

## 7. Производительность

- [x] Ленивая инициализация (lazy loading) для ресурсов
  > `Client::getResource()` создаёт при первом обращении.
- [x] Кэширование вычислений (маскирование URL, маппинги)
  > Ресурсы кэшируются в `$resourceInstances[]`.
- [x] Exponential backoff + jitter для retry-механизма
  > `RetryHandler::calculateDelay()` — `min(pow(2, $attempt) * base, maxDelay)` + `mt_rand() jitter`.
- [x] Ограничение максимального количества retry
  > `Config::MAX_RETRIES = 10`, `Config::clampRetries()`.
- [x] Cleanup ресурсов (temp файлы, curl handles) через `finally`
  > `finally { curl_close($ch); $this->cleanupTempFiles(); }`.
- [x] Стриминг файлов (CURLFile вместо file_get_contents)
  > `Uploads::uploadFileToUrl()`: `'filepath' => $filePath` → `CURLFile`.

## 8. Тестирование

- [x] Unit-тесты на все классы
  > 214 тестов, 430 assertions. Классы: Config, ConfigBuilder, ClientFactory, Client, ResponseDecoder, RetryHandler, CurlHttpClient, InputValidator, KeyboardBuilder, WebhookHandler, все DTO, все Resources.
- [x] Тесты DTO (fromArray, toArray, getters, immutability)
  > `DtoTest.php` — 35+ тестов на все 13 DTO.
- [x] Тесты ресурсов (все эндпоинты, параметры, HTTP-методы)
  > `ResourceTest.php` — все 7 ресурсов: Bot, Chats, Members, Messages, Subscriptions, Uploads, Callbacks.
- [x] Тесты конфигурации (Config, Builder, fromEnvironment, fromIni)
  > `ConfigTest.php`, `ConfigBuilderTest.php` — defaults, boundaries, env, ini, logger.
- [x] Тесты исключений (все типы, коды, сообщения)
  > `ExceptionTest.php` — все 6 типов исключений.
- [x] Code coverage конфигурация в `phpunit.xml.dist`
  > `<coverage>` блок с `<include><directory>src</directory></include>`.
- [x] Современная схема `phpunit.xml.dist` (`<coverage><include>`)
  > PHPUnit 9.6 XML schema.
- [x] Параметризованные тесты (`@dataProvider`) где уместно
  > `@dataProvider` в `InputValidatorTest`, `ConfigTest`.
- [x] Тесты работают без внешних API (моки / тестовые дублёры)
  > `MockHttpClient`, `TestRetryHandler` — все тесты offline.
- [x] Strict mode (`beStrictAboutTestsThatDoNotTestAnything`)
  > `beStrictAboutTestsThatDoNotTestAnything="true"` в `phpunit.xml.dist`.

## 9. Инфраструктура и DX (Developer Experience)

- [x] `composer.json` с PSR-4 autoload и scripts
  > `"MaxBotSdk\\": "src/"`, scripts: `test`, `phpstan`, `cs-check`, `cs-fix`.
- [x] Composer scripts: `test`, `analyse`, `cs-check`, `cs-fix`
  > `"test"`, `"phpstan"`, `"cs-check"`, `"cs-fix"` в `composer.json`.
- [x] PHPStan — уровень ≥5
  > `phpstan.neon`: `level: 5`, paths: `src/`, phpVersion: `80100`.
- [x] PHP-CS-Fixer — актуальная версия (v3+) с `.php-cs-fixer.dist.php`
  > `.php-cs-fixer.dist.php` с PSR-2 правилами.
- [x] PHPUnit — актуальная версия с современной XML-схемой
  > PHPUnit 9.6.34, `phpunit.xml.dist` с XML schema.
- [x] CI/CD — GitHub Actions с матрицей PHP версий (7.3, 7.4, 8.0, 8.1, 8.2, 8.3)
  > `.github/workflows/ci.yml` — 6 PHP версий, `fail-fast: false`.
- [x] CI — отдельные jobs для тестов, статического анализа, code style
  > 3 jobs: `phpunit`, `phpstan`, `code-style`.
- [x] `.gitignore` — vendor, coverage, cache, IDE, OS
  > vendor, build, .phpunit.result.cache, cfg/config.ini, .env, .idea, .vscode.
- [x] CHANGELOG — формат Keep a Changelog, все версии документированы
  > `CHANGELOG.md` в корне проекта.
- [x] README — описание, установка, примеры, бейджи
  > `README.md` с установкой, примерами, ссылками на документацию.

## 10. Документация

- [x] Полные гайды в `docs/` (quickstart, конфигурация, API reference)
  > 6 гайдов: `01-getting-started`, `02-working-with-messages`, `03-webhooks-and-polling`, `04-file-uploads`, `05-error-handling`, `06-advanced-features`.
- [x] Примеры в `docs/07-examples.md` с расширенными сценариями.
  > Документ `docs/07-examples.md` содержит полноценные примеры: эхо-бот, клавиатуры, загрузка файлов, polling, модерация.
- [x] PHPDoc class-level с `@code` примерами
  > `Client`, `ClientFactory`, `ConfigBuilder`, `KeyboardBuilder`, `WebhookHandler` — все с `<code>` примерами.
- [x] `@since` аннотации на класс-уровне
  > Все классы имеют `@since 1.0.0`.
- [x] `@method` аннотации для magic-методов
  > N/A — проект не использует magic-методы (все методы явные).
- [x] Отсутствие устаревших/удалённых ссылок в документации
  > Проверено.
- [x] API Reference (ручной или автогенерированный)
  > Документация в `docs/` является ручным API reference.

## 11. Единый язык

- [x] PHPDoc описания на едином языке
  > Русский — во всех PHPDoc блоках.
- [x] Сообщения исключений на едином языке
  > Русский — все throw messages.
- [x] Сообщения логирования на едином языке
  > Русский — `'Клиент инициализирован'`, `'HTTP запрос'`, `'Ответ API'`.
- [x] Inline-комментарии на едином языке
  > Русский — все inline-комментарии.
- [x] `@since` аннотации на едином языке
  > Формат `@since X.Y.Z` — язык-нейтральный.
- [x] Технические термины (cookie, token, HTTP, JSON, API, DTO, PSR) — допускаются на английском как общепринятые
  > Используются: token, HTTP, JSON, API, cURL, webhook, SSL, PSR — все общепринятые.

## 12. Code Style

- [x] Единый стиль кода (PSR-2/PSR-12)
  > PSR-2 с PHP-CS-Fixer. Проверяется в CI.
- [x] Short array syntax `[]` вместо `array()`
  > `[]` — единообразно во всём проекте. PHP 5.4+ совместимость.
- [x] Упорядоченные `use`-импорты
  > Алфавитный порядок во всех файлах.
- [x] Отсутствие неиспользуемых импортов
  > Проверено по всем 44 файлам.
- [x] Trailing comma в multiline arrays
  > Trailing comma используется в multiline arrays где уместно.
- [x] Именованные константы вместо magic numbers
  > `Config::DEFAULT_TIMEOUT = 30`, `MIN_TIMEOUT = 1`, `MAX_TIMEOUT = 300`, `DEFAULT_RETRIES = 3`, `MAX_RETRIES = 10`.
- [x] Единый стиль конкатенации строк (пробелы вокруг `.`)
  > `' . '` — единообразно с пробелами.

## 13. Версионирование

- [x] Semantic Versioning (MAJOR.MINOR.PATCH)
  > `@since 1.0.0`.
- [x] CHANGELOG с документированными Breaking Changes
  > `CHANGELOG.md` с описанием изменений.
- [x] `@since` аннотации при изменении API
  > На всех классах.
- [x] `@deprecated` для устаревших методов (с указанием замены)
  > N/A — нет устаревших методов (проект в стадии активной разработки).

## 14. Robustness / Edge Cases

- [x] Guard clauses — early return / throw для невалидных входов
  > `Client::log()` — whitelist проверка, `Config::__construct()` — throw для пустого токена, `InputValidator` — early throw.
- [x] `finally`-блоки — cleanup ресурсов при любом исходе
  > `CurlHttpClient::request()`: `finally { curl_close($ch); $this->cleanupTempFiles(); }`.
- [x] Defensive coding — `isset()` проверки для необязательных полей API
  > Все DTO используют `isset()` + `is_array()` в `fromArray()`. `CurlHttpClient` — проверки `$options`.
- [x] Unicode в JSON — `JSON_UNESCAPED_UNICODE` для корректной кириллицы
  > `json_encode($options['json'], JSON_UNESCAPED_UNICODE)` в `CurlHttpClient::setBody()`.
- [x] Temp file cleanup — автоматическая очистка временных файлов
  > `$this->tempFiles[]` tracking + `cleanupTempFiles()` в `finally`.

