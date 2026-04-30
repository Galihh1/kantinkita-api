<?php

$host = '127.0.0.1';
$db   = 'kantinkita_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$outputFile = 'kantinkita_postgres_ready.sql';
$fp = fopen($outputFile, 'w');

// Disable foreign key checks for the import
fwrite($fp, "-- PostgreSQL Export\n");
fwrite($fp, "SET standard_conforming_strings = on;\n");
fwrite($fp, "SET check_function_bodies = false;\n");
fwrite($fp, "SET client_min_messages = warning;\n\n");

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    if ($table === 'migrations') continue; // Skip migrations table if you want fresh start

    echo "Processing table: $table\n";
    
    // Get Table Creation
    $createResult = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
    $createSql = $createResult['Create Table'];

    // Convert MySQL Create to Postgres (Basic conversion)
    $pgSql = convertMysqlToPostgres($table, $createSql, $pdo);
    fwrite($fp, $pgSql . ";\n\n");

    // Get Data
    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
    if (count($rows) > 0) {
        fwrite($fp, "-- Data for $table\n");
        foreach ($rows as $row) {
            $columns = array_keys($row);
            $values = array_values($row);
            
            $escapedValues = array_map(function($v) use ($pdo) {
                if ($v === null) return 'NULL';
                if (is_bool($v)) return $v ? 'TRUE' : 'FALSE';
                if (is_numeric($v) && !is_string($v)) return $v;
                // Basic string escaping for Postgres
                return "'" . str_replace("'", "''", $v) . "'";
            }, $values);

            $insertSql = "INSERT INTO \"$table\" (\"" . implode('", "', $columns) . "\") VALUES (" . implode(', ', $escapedValues) . ");\n";
            fwrite($fp, $insertSql);
        }
        fwrite($fp, "\n");
    }
}

fclose($fp);
echo "Done! File saved to $outputFile\n";

function convertMysqlToPostgres($table, $mysqlSql, $pdo) {
    // 1. Rename Table to Quoted
    $sql = "CREATE TABLE \"$table\" (\n";
    
    // Get Column details instead of parsing CREATE TABLE string (more reliable)
    $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll();
    $lines = [];
    $primaryKeys = [];
    
    foreach ($columns as $col) {
        $name = $col['Field'];
        $type = $col['Type'];
        $null = $col['Null'] === 'YES' ? '' : ' NOT NULL';
        $default = $col['Default'];
        $extra = $col['Extra'];

        // Convert Type
        $pgType = $type;
        if (strpos($extra, 'auto_increment') !== false) {
            $pgType = 'SERIAL';
        } elseif (strpos($type, 'int') !== false) {
            if (strpos($type, 'tinyint(1)') !== false) {
                $pgType = 'BOOLEAN';
                if ($default !== null) $default = $default == 1 ? 'TRUE' : 'FALSE';
            } else {
                $pgType = 'INTEGER';
            }
        } elseif (strpos($type, 'varchar') !== false) {
            $pgType = str_replace('varchar', 'VARCHAR', $type);
        } elseif (strpos($type, 'text') !== false) {
            $pgType = 'TEXT';
        } elseif (strpos($type, 'decimal') !== false) {
            $pgType = str_replace('decimal', 'NUMERIC', $type);
        } elseif (strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false) {
            $pgType = 'TIMESTAMP';
            if ($default === 'CURRENT_TIMESTAMP') $default = 'CURRENT_TIMESTAMP';
        } elseif (strpos($type, 'json') !== false) {
            $pgType = 'JSONB';
        } elseif (strpos($type, 'enum') !== false) {
            // Postgres enums are complex, simpler to use VARCHAR + check constraint or just VARCHAR
            $pgType = 'VARCHAR(255)';
        }

        $line = "    \"$name\" $pgType$null";
        if ($default !== null && $pgType !== 'SERIAL') {
            if ($default === 'CURRENT_TIMESTAMP') {
                $line .= " DEFAULT CURRENT_TIMESTAMP";
            } else {
                $line .= " DEFAULT " . (is_numeric($default) ? $default : "'$default'");
            }
        }
        
        $lines[] = $line;
        
        if ($col['Key'] === 'PRI') {
            $primaryKeys[] = $name;
        }
    }
    
    $sql .= implode(",\n", $lines);
    
    if (!empty($primaryKeys)) {
        $sql .= ",\n    PRIMARY KEY (\"" . implode('", "', $primaryKeys) . "\")";
    }
    
    $sql .= "\n)";
    return $sql;
}

// Add sequence updates at the end of the file
$fp = fopen($outputFile, 'a');
fwrite($fp, "-- Update sequences for all tables\n");
foreach ($tables as $table) {
    if ($table === 'migrations') continue;
    $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll();
    foreach ($columns as $col) {
        if (strpos($col['Extra'], 'auto_increment') !== false) {
            $colName = $col['Field'];
            fwrite($fp, "SELECT setval(pg_get_serial_sequence('\"$table\"', '$colName'), coalesce(max(\"$colName\"), 1), max(\"$colName\") IS NOT null) FROM \"$table\";\n");
        }
    }
}
fclose($fp);
