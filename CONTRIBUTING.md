# Contributing to Max Bot SDK

Спасибо за интерес к проекту! Ниже описан процесс внесения изменений.

## Требования к окружению

- **PHP** 8.1+
- **Extensions:** `json`, `mbstring`, `curl`
- **Composer** для управления зависимостями

## Установка

```bash
git clone <repository-url>
cd Max
composer install
```

## Код-стайл

Проект следует **PER-CS 2.0** с синтаксисом `[]` (short array syntax).

```bash
# Проверка стиля
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff

# Автоматическое исправление
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php
```

## Тестирование

```bash
# Запуск всех тестов
vendor/bin/phpunit --configuration phpunit.xml.dist

# С покрытием (требуется Xdebug)
vendor/bin/phpunit --configuration phpunit.xml.dist --coverage-html build/coverage
```

## Статический анализ

```bash
vendor/bin/phpstan analyse --configuration phpstan.neon
```

## Pull Request Checklist

- [ ] Все тесты проходят (`vendor/bin/phpunit`)
- [ ] PHPStan не выдаёт ошибок
- [ ] Код-стайл проверен (`php-cs-fixer --dry-run`)
- [ ] PHPDoc обновлён для новых/изменённых публичных методов
- [ ] Новые классы помечены `final` (если не предназначены для наследования)
- [ ] Новые leaf-exceptions наследуют `MaxException`
- [ ] DTO следуют паттерну: `private __construct` + `fromArray()` + `toArray()`

## Архитектурные принципы

Подробный список требований: [docs/REQUIREMENTS.md](docs/REQUIREMENTS.md)

- **Immutability** — Config и DTO без setters
- **DI** — зависимости через конструктор
- **Final by default** — все leaf-классы `final`
- **PER-CS 2.0** — единый стиль кода
- **PHP 8.1+** — typed properties, readonly, enums, match, constructor promotion
