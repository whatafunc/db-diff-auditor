# db-diff-auditor

A PHP-based database schema auditing tool designed to work with MySQL and PostgreSQL. Its main purpose is to track changes in your database structure over time.

## Core Functionality

*   **Database Introspection:** It can connect to your database and analyze its structure, including tables, columns, indexes, and foreign keys.
*   **Snapshot Management:** It allows you to create snapshots of your database schema at any given point. These snapshots are saved as JSON files in the `.db-snapshots` directory.
    *   **Why JSON for Snapshots?**
        The tool uses JSON (JavaScript Object Notation) for storing database snapshots due to its balance of readability, interoperability, and safety, which are crucial for a schema auditing tool:
        *   **Human Readability:** JSON files are plain text and easily readable, allowing developers to quickly inspect and understand the schema structure without special tools. This aids in manual review and debugging.
        *   **Interoperability:** As a language-agnostic format, JSON ensures that these snapshots can be easily consumed and processed by other programming languages or tools, enhancing the flexibility and integration possibilities of the auditor.
        *   **Security:** Unlike PHP's native serialization, JSON is generally safer for data exchange as it doesn't allow for arbitrary code execution upon deserialization, reducing potential vulnerabilities.
        *   **Efficiency Trade-offs:** While PHP's `serialize()` might offer marginal performance or size benefits for complex PHP objects, the advantages of JSON in terms of human readability, broad interoperability, and inherent security outweigh these for a schema definition. The size and processing speed of schema snapshots are typically not a performance bottleneck for this application.
*   **Difference Generation:** It can compare two database schema snapshots (e.g., the current state vs. the last snapshot) and identify the differences.
*   **SQL Export:** It can generate the necessary SQL statements to represent the detected changes, such as `CREATE TABLE`, `ALTER TABLE`, etc.

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

## Usage as a Library

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

## Development CLI Tool

This project includes a command-line tool for development and testing purposes.

### Configuration

The CLI tool is configured via a `.env` file in the root of the project.

1.  Copy the `.env.example` file to a new file named `.env`.
2.  Edit the `.env` file to match your local database credentials.

### Available Commands

You can run the commands using Composer's `scripts` feature.

#### Creating a Snapshot

```bash
composer db-diff db:snapshot
```

#### Checking for Changes

```bash
composer db-diff db:check
```

This command compares the current state of your database with the most recent snapshot and generates a `changes.sql` file.

#### Comparing the Last Two Snapshots

```bash
composer db-diff db:compare-last
```

You can also use the `--help` flag with any command to get more information, for example:
```bash
composer db-diff db:check --help
```