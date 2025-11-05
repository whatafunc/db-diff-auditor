# Gemini Project Guide

## Project Overview

A PHP-based, framework-agnostic library for database schema auditing and diffing, designed to work with MySQL and PostgreSQL.

## Key Technologies

*   **Language:** PHP ^7.0
*   **Core Library Dependencies:** None
*   **Development Dependencies:** `symfony/console`, `vlucas/phpdotenv`

## Coding Conventions

*   **Style:** PSR-12 (the modern standard for PHP code style).
*   **Naming:** `PascalCase` for classes, `camelCase` for methods and variables.
*   **Comments:** PHPDoc blocks for all classes, methods, and properties to ensure clarity.

## Architectural Decisions

*   **Library First:** This is a framework-agnostic library. The core logic should not depend on any specific application framework.
*   **CLI as a Dev Tool:** The command-line interface is a development-time tool for testing and interacting with the library. Its dependencies are in `require-dev`.
*   **JSON for Snapshots:** Snapshots are stored as human-readable JSON files to ensure interoperability and ease of debugging.

## Development Setup

1.  **Install Dependencies:** Run `composer install` to install both library and development dependencies.
2.  **Configure Environment:** Copy `.env.example` to `.env` and fill in your local database credentials. This is only for the development CLI tool.

## Testing

*   **Framework:** PHPUnit is recommended for unit and integration tests.
*   **How to run tests:** (To be determined, e.g., `composer test`).

## Project Gotchas

*   **`composer.json` Backslash Escaping:** When defining `psr-4` namespaces in `composer.json`, remember that backslashes must be escaped. For example, `DbDiffAuditor\` must be written as `DbDiffAuditor\\`.