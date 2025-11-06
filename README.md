# db-diff-auditor

A PHP-based database schema auditing tool designed to work with MySQL and PostgreSQL. Its main purpose is to track changes in your database structure over time.

## Philosophy and Intended Use Case

While there are many powerful database migration frameworks available, `db-diff-auditor` is not intended to replace them. Instead, it is designed to fill a specific niche for developers who value simplicity, visibility, and control, especially in the following scenarios:

1.  **For the Hands-On Developer:** Many developers prefer to make schema changes directly in their database client (like Sequel Pro, DBeaver, or the command line) and then want a quick and easy way to capture those changes in a version-controllable format. This tool is perfect for that workflow. It provides a code-based confirmation of your manual changes and generates the SQL to replicate them elsewhere.

2.  **For AI-Assisted Development:** As AI tools become more common in the development process, there is a growing need for simple, focused tools to audit the changes they make. This tool provides a clear and auditable trail of any schema changes suggested or applied by an AI, allowing developers to maintain full visibility and control over their database structure.

In short, `db-diff-auditor` is about providing a lightweight, straightforward, and auditable bridge between your database and your codebase.

# For Library Consumers

This section explains how to install and use this library in your own projects.

## Installation

Before installing this library, ensure you have Composer, the PHP dependency manager, installed on your system.

To check if Composer is installed, open your terminal or command prompt and run:

```bash
composer -V
```

If Composer is not installed, you can follow the official installation guide: [https://getcomposer.org/download/](https://getcomposer.org/download/)

You can then install the library via Composer:

```bash
composer require whatafunc/db-diff-auditor
```

## Usage

You can use the `DbDiffAuditor` class in your own PHP projects to programmatically handle schema auditing.

```php
<?php

require 'vendor/autoload.php';

use DbDiffAuditor\DbDiffAuditor;

$config = [
    'connection' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'mydatabase',
        'username' => 'root',
        'password' => '',
        'port' => 3306,
    ],
    'snapshot_path' => '.db-snapshots',
    'ignore_tables' => ['migrations', 'sessions'],
];

$auditor = new DbDiffAuditor($config);

// Create a snapshot
$auditor->createSnapshot('My first snapshot');

// Get changes
$changes = $auditor->getChanges();

// Export changes to SQL
$sql = $auditor->exportChanges($changes);
echo $sql;
```

You can also use the command-line tool provided by this library. After installing the library, the `db-diff` command will be available in your project's `vendor/bin` directory.

```bash
vendor/bin/db-diff db:check
```

# For Library Contributors

This section explains how to set up the development environment, run the development CLI tool, and run the tests for this library.

## Development Setup

1.  **Clone the repository:** `git clone https://github.com/whatafunc/db-diff-auditor.git`
2.  **Install Dependencies:** Run `composer install` to install both library and development dependencies.
3.  **Configure Environment:** Copy `.env.example` to `.env` and fill in your local database credentials. This is only for the development CLI tool.

## Development CLI Tool

This project includes a command-line tool for development and testing purposes. You can run the commands using Composer's `scripts` feature.

### Available Commands

#### Creating a Snapshot

```bash
composer db-diff db:snapshot
```

#### Checking for Changes

```bash
composer db-diff db:check
```

#### Comparing the Last Two Snapshots

```bash
composer db-diff db:compare-last
```

You can also use the `--help` flag with any command to get more information, for example:
```bash
composer db-diff db:check --help
```

## Testing

### Testing Approach

The project uses **unit tests** to ensure the core logic is reliable and works as expected. The main principles of the testing approach are:

*   **Isolation:** Tests are designed to run in isolation, without the need for a live database connection. This makes them fast and reliable.
*   **Mock Data:** The tests use simple PHP arrays to simulate database schema snapshots. This mock data is managed in a base `TestCase` (`tests/TestCase.php`) to promote reuse and keep the tests clean.
*   **Maintainability:** By using a base test case and clear, descriptive test methods, the test suite is designed to be easy to maintain and extend.

### Running Tests

You can run the test suite using Composer:
```bash
composer test
```

## Continuous Integration

This project uses GitHub Actions to automatically run the test suite on every push and pull request to the `main` branch.

*   **Workflow:** The CI workflow is defined in the `.github/workflows/ci.yml` file.
*   **Environment:** The tests are run in a minimal `php:8.1-alpine` Docker container to ensure a consistent and lightweight testing environment.