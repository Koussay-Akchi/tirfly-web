<?php
// reverse-engineer.php
require_once 'vendor/autoload.php';

// Database configuration - MODIFY THESE VALUES
$dbHost = 'localhost';
$dbName = 'pidev';
$dbUser = 'root';
$dbPass = '';
$dbPort = 3306;

// Entity namespace and output directory
$namespace = 'App\\Entity';
$outputDir = __DIR__ . '/src/Entity';

// Create output directory if it doesn't exist
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Connect to the database
try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully!\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Get all tables
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Store table information for relationship processing
$tableInfo = [];
$foreignKeys = [];
$manyToManyTables = [];
$uniqueConstraints = [];

// First pass: collect table information, foreign keys, and unique constraints
foreach ($tables as $table) {
    // Skip migration tables
    if (strpos($table, 'migration') !== false || strpos($table, 'doctrine') !== false) {
        continue;
    }

    echo "Analyzing table structure: $table\n";

    // Convert table name to class name
    $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
    if (substr($className, -1) === 's' && substr($className, -2) !== 'ss') {
        $className = substr($className, 0, -1); // Remove trailing 's' for singular class name
    }

    // Get table columns
    $stmt = $pdo->query("DESCRIBE `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get primary key
    $primaryKey = null;
    foreach ($columns as $column) {
        if ($column['Key'] === 'PRI') {
            $primaryKey = $column['Field'];
            break;
        }
    }

    $tableInfo[$table] = [
        'className' => $className,
        'columns' => $columns,
        'primaryKey' => $primaryKey
    ];

    // Get foreign keys
    try {
        $stmt = $pdo->query("
            SELECT 
                COLUMN_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE 
                TABLE_SCHEMA = '$dbName' AND
                TABLE_NAME = '$table' AND
                REFERENCED_TABLE_NAME IS NOT NULL
        ");

        $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fks as $fk) {
            $foreignKeys[$table][] = [
                'column' => $fk['COLUMN_NAME'],
                'refTable' => $fk['REFERENCED_TABLE_NAME'],
                'refColumn' => $fk['REFERENCED_COLUMN_NAME']
            ];
        }
    } catch (PDOException $e) {
        echo "Warning: Could not retrieve foreign keys for table $table: " . $e->getMessage() . "\n";
    }

    // Get unique constraints (for OneToOne relationships)
    try {
        $stmt = $pdo->query("
            SELECT 
                COLUMN_NAME,
                INDEX_NAME
            FROM 
                INFORMATION_SCHEMA.STATISTICS
            WHERE 
                TABLE_SCHEMA = '$dbName' AND
                TABLE_NAME = '$table' AND
                NON_UNIQUE = 0 AND
                INDEX_NAME != 'PRIMARY'
        ");

        $uniques = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($uniques as $unique) {
            $uniqueConstraints[$table][] = [
                'column' => $unique['COLUMN_NAME'],
                'indexName' => $unique['INDEX_NAME']
            ];
        }
    } catch (PDOException $e) {
        echo "Warning: Could not retrieve unique constraints for table $table: " . $e->getMessage() . "\n";
    }
}

// Identify potential ManyToMany join tables
foreach ($tables as $table) {
    // Skip migration tables
    if (strpos($table, 'migration') !== false || strpos($table, 'doctrine') !== false) {
        continue;
    }

    // Check if this might be a join table
    if (isset($foreignKeys[$table]) && count($foreignKeys[$table]) >= 2) {
        $columns = $tableInfo[$table]['columns'];

        // If the table has only foreign keys and maybe some metadata columns, it's likely a join table
        $nonFkColumns = count($columns);
        $fkColumns = 0;

        foreach ($foreignKeys[$table] as $fk) {
            $fkColumns++;
        }

        // If most columns are foreign keys, consider it a join table
        if ($fkColumns >= 2 && $fkColumns >= ($nonFkColumns - 2)) {
            echo "Detected potential ManyToMany join table: $table\n";

            // Store the join table information
            $manyToManyTables[$table] = [
                'foreignKeys' => $foreignKeys[$table]
            ];
        }
    }
}

// Second pass: generate entity classes with relationships
foreach ($tables as $table) {
    // Skip migration tables and join tables (we'll handle them differently)
    if (strpos($table, 'migration') !== false || strpos($table, 'doctrine') !== false || isset($manyToManyTables[$table])) {
        continue;
    }

    echo "Generating entity for table: $table\n";

    $className = $tableInfo[$table]['className'];
    $columns = $tableInfo[$table]['columns'];
    $primaryKey = $tableInfo[$table]['primaryKey'];

    // Start building the entity class
    $entityCode = "<?php\n\n";
    $entityCode .= "namespace $namespace;\n\n";
    $entityCode .= "use Doctrine\\ORM\\Mapping as ORM;\n";
    $entityCode .= "use Doctrine\\Common\\Collections\\ArrayCollection;\n";
    $entityCode .= "use Doctrine\\Common\\Collections\\Collection;\n\n";
    $entityCode .= "use App\\Repository\\" . $className . "Repository;\n\n";
    $entityCode .= "#[ORM\\Entity(repositoryClass: " . $className . "Repository::class)]\n";
    // $entityCode .= "#[ORM\\Entity]\n";
    $entityCode .= "#[ORM\\Table(name: '$table')]\n";
    $entityCode .= "class $className\n";
    $entityCode .= "{\n";

    // Add properties
    foreach ($columns as $column) {
        $fieldName = $column['Field'];
        $fieldType = mapMySQLTypeToPhpType($column['Type']);
        $doctrineType = mapMySQLTypeToDoctrineType($column['Type']);

        // Check if this column is a foreign key
        $isForeignKey = false;
        $relationshipCode = "";

        if (isset($foreignKeys[$table])) {
            foreach ($foreignKeys[$table] as $fk) {
                if ($fk['column'] === $fieldName) {
                    $isForeignKey = true;
                    $refTableClassName = $tableInfo[$fk['refTable']]['className'];

                    // Check if this foreign key has a unique constraint (OneToOne)
                    $isOneToOne = false;
                    if (isset($uniqueConstraints[$table])) {
                        foreach ($uniqueConstraints[$table] as $unique) {
                            if ($unique['column'] === $fieldName) {
                                $isOneToOne = true;
                                break;
                            }
                        }
                    }

                    if ($isOneToOne) {
                        // Add OneToOne relationship
                        $relationshipCode .= "    #[ORM\\OneToOne(targetEntity: $refTableClassName::class, inversedBy: '" . lcfirst($className) . "')]\n";
                        $relationshipCode .= "    #[ORM\\JoinColumn(name: '$fieldName', referencedColumnName: '{$fk['refColumn']}', unique: true)]\n";
                        $relationshipCode .= "    private ?$refTableClassName \$" . lcfirst($refTableClassName) . " = null;\n\n";

                        // Add getter
                        $relationshipCode .= "    public function get" . $refTableClassName . "(): ?$refTableClassName\n";
                        $relationshipCode .= "    {\n";
                        $relationshipCode .= "        return \$this->" . lcfirst($refTableClassName) . ";\n";
                        $relationshipCode .= "    }\n\n";

                        // Add setter
                        $relationshipCode .= "    public function set" . $refTableClassName . "(?$refTableClassName \$" . lcfirst($refTableClassName) . "): self\n";
                        $relationshipCode .= "    {\n";
                        $relationshipCode .= "        \$this->" . lcfirst($refTableClassName) . " = \$" . lcfirst($refTableClassName) . ";\n";
                        $relationshipCode .= "        return \$this;\n";
                        $relationshipCode .= "    }\n\n";
                    } else {
                        // Add ManyToOne relationship
                        $relationshipCode .= "    #[ORM\\ManyToOne(targetEntity: $refTableClassName::class, inversedBy: '" . lcfirst($className) . "s')]\n";
                        $relationshipCode .= "    #[ORM\\JoinColumn(name: '$fieldName', referencedColumnName: '{$fk['refColumn']}')]\n";
                        $relationshipCode .= "    private ?$refTableClassName \$" . lcfirst($refTableClassName) . " = null;\n\n";

                        // Add getter
                        $relationshipCode .= "    public function get" . $refTableClassName . "(): ?$refTableClassName\n";
                        $relationshipCode .= "    {\n";
                        $relationshipCode .= "        return \$this->" . lcfirst($refTableClassName) . ";\n";
                        $relationshipCode .= "    }\n\n";

                        // Add setter
                        $relationshipCode .= "    public function set" . $refTableClassName . "(?$refTableClassName \$" . lcfirst($refTableClassName) . "): self\n";
                        $relationshipCode .= "    {\n";
                        $relationshipCode .= "        \$this->" . lcfirst($refTableClassName) . " = \$" . lcfirst($refTableClassName) . ";\n";
                        $relationshipCode .= "        return \$this;\n";
                        $relationshipCode .= "    }\n\n";
                    }

                    break;
                }
            }
        }

        // If not a foreign key, add as regular property
        if (!$isForeignKey) {
            // Add property with attributes
            $entityCode .= "    #[ORM\\";

            if ($fieldName === $primaryKey) {
                $entityCode .= "Id]\n";
                $entityCode .= "    #[ORM\\GeneratedValue]\n";
                $entityCode .= "    #[ORM\\Column(type: '$doctrineType')]\n";
            } else {
                $nullable = $column['Null'] === 'YES' ? 'true' : 'false';
                $entityCode .= "Column(type: '$doctrineType', nullable: $nullable)]\n";
            }

            $entityCode .= "    private ?$fieldType \$$fieldName = null;\n\n";

            // Add getter
            $entityCode .= "    public function ";
            if ($fieldType === 'bool') {
                $entityCode .= ($fieldName === 'is' || strpos($fieldName, 'is_') === 0) ? $fieldName : "is" . ucfirst($fieldName);
            } else {
                $entityCode .= "get" . ucfirst($fieldName);
            }
            $entityCode .= "(): ?$fieldType\n";
            $entityCode .= "    {\n";
            $entityCode .= "        return \$this->$fieldName;\n";
            $entityCode .= "    }\n\n";

            // Add setter
            $setterName = 'set' . ucfirst($fieldName);
            $entityCode .= "    public function $setterName(";
            if ($column['Null'] === 'YES') {
                $entityCode .= "?";
            }
            $entityCode .= "$fieldType \$$fieldName): self\n";
            $entityCode .= "    {\n";
            $entityCode .= "        \$this->$fieldName = \$$fieldName;\n";
            $entityCode .= "        return \$this;\n";
            $entityCode .= "    }\n\n";
        } else {
            // Add the relationship code
            $entityCode .= $relationshipCode;
        }
    }

    // Add OneToMany and inverse OneToOne relationships
    foreach ($tables as $otherTable) {
        if (isset($foreignKeys[$otherTable]) && !isset($manyToManyTables[$otherTable])) {
            foreach ($foreignKeys[$otherTable] as $fk) {
                if ($fk['refTable'] === $table) {
                    $otherClassName = $tableInfo[$otherTable]['className'];

                    // Check if this is a OneToOne inverse relationship
                    $isOneToOne = false;
                    if (isset($uniqueConstraints[$otherTable])) {
                        foreach ($uniqueConstraints[$otherTable] as $unique) {
                            if ($unique['column'] === $fk['column']) {
                                $isOneToOne = true;
                                break;
                            }
                        }
                    }

                    if ($isOneToOne) {
                        // Add inverse OneToOne relationship
                        $entityCode .= "    #[ORM\\OneToOne(targetEntity: $otherClassName::class, mappedBy: '" . lcfirst($className) . "')]\n";
                        $entityCode .= "    private ?$otherClassName \$" . lcfirst($otherClassName) . " = null;\n\n";

                        // Add getter
                        $entityCode .= "    public function get" . $otherClassName . "(): ?$otherClassName\n";
                        $entityCode .= "    {\n";
                        $entityCode .= "        return \$this->" . lcfirst($otherClassName) . ";\n";
                        $entityCode .= "    }\n\n";

                        // Add setter (simplified)
                        $entityCode .= "    public function set" . $otherClassName . "(?$otherClassName \$" . lcfirst($otherClassName) . "): self\n";
                        $entityCode .= "    {\n";
                        $entityCode .= "        \$this->" . lcfirst($otherClassName) . " = \$" . lcfirst($otherClassName) . ";\n";
                        $entityCode .= "        return \$this;\n";
                        $entityCode .= "    }\n\n";
                    } else {
                        // Add OneToMany relationship
                        $collectionVar = lcfirst($otherClassName) . 's';
                        $singularVar = lcfirst($otherClassName);

                        $entityCode .= "    #[ORM\\OneToMany(targetEntity: $otherClassName::class, mappedBy: '" . lcfirst($className) . "')]\n";
                        $entityCode .= "    private Collection \$$collectionVar;\n\n";

                        // Add getter with lazy initialization
                        $entityCode .= "    /**\n";
                        $entityCode .= "     * @return Collection<int, $otherClassName>\n";
                        $entityCode .= "     */\n";
                        $entityCode .= "    public function get" . ucfirst($collectionVar) . "(): Collection\n";
                        $entityCode .= "    {\n";
                        $entityCode .= "        if (!\$this->$collectionVar instanceof Collection) {\n";
                        $entityCode .= "            \$this->$collectionVar = new ArrayCollection();\n";
                        $entityCode .= "        }\n";
                        $entityCode .= "        return \$this->$collectionVar;\n";
                        $entityCode .= "    }\n\n";

                        // Add add method (simplified)
                        $entityCode .= "    public function add" . ucfirst($singularVar) . "($otherClassName \$$singularVar): self\n";
                        $entityCode .= "    {\n";
                        $entityCode .= "        if (!\$this->get" . ucfirst($collectionVar) . "()->contains(\$$singularVar)) {\n";
                        $entityCode .= "            \$this->get" . ucfirst($collectionVar) . "()->add(\$$singularVar);\n";
                        $entityCode .= "        }\n";
                        $entityCode .= "        return \$this;\n";
                        $entityCode .= "    }\n\n";

                        // Add remove method (simplified)
                        $entityCode .= "    public function remove" . ucfirst($singularVar) . "($otherClassName \$$singularVar): self\n";
                        $entityCode .= "    {\n";
                        $entityCode .= "        \$this->get" . ucfirst($collectionVar) . "()->removeElement(\$$singularVar);\n";
                        $entityCode .= "        return \$this;\n";
                        $entityCode .= "    }\n\n";
                    }
                }
            }
        }
    }

    // Add ManyToMany relationships
    foreach ($manyToManyTables as $joinTable => $joinInfo) {
        $fks = $joinInfo['foreignKeys'];

        // Check if this entity is part of the ManyToMany relationship
        $isPartOfRelationship = false;
        $otherTableName = null;
        $otherTableClassName = null;
        $thisTableFk = null;
        $otherTableFk = null;

        foreach ($fks as $fk) {
            if ($fk['refTable'] === $table) {
                $isPartOfRelationship = true;
                $thisTableFk = $fk;

                // Find the other table in the relationship
                foreach ($fks as $otherFk) {
                    if ($otherFk['refTable'] !== $table) {
                        $otherTableName = $otherFk['refTable'];
                        $otherTableClassName = $tableInfo[$otherTableName]['className'];
                        $otherTableFk = $otherFk;
                        break;
                    }
                }

                break;
            }
        }

        if ($isPartOfRelationship && $otherTableName && $thisTableFk && $otherTableFk) {
            $collectionVar = lcfirst($otherTableClassName) . 's';
            $singularVar = lcfirst($otherTableClassName);

            // Add ManyToMany relationship with detailed JoinTable configuration
            $entityCode .= "    #[ORM\\ManyToMany(targetEntity: $otherTableClassName::class, inversedBy: '" . lcfirst($className) . "s')]\n";
            $entityCode .= "    #[ORM\\JoinTable(\n";
            $entityCode .= "        name: '$joinTable',\n";
            $entityCode .= "        joinColumns: [\n";
            $entityCode .= "            new ORM\\JoinColumn(name: '{$thisTableFk['column']}', referencedColumnName: '{$thisTableFk['refColumn']}')\n";
            $entityCode .= "        ],\n";
            $entityCode .= "        inverseJoinColumns: [\n";
            $entityCode .= "            new ORM\\JoinColumn(name: '{$otherTableFk['column']}', referencedColumnName: '{$otherTableFk['refColumn']}')\n";
            $entityCode .= "        ]\n";
            $entityCode .= "    )]\n";
            $entityCode .= "    private Collection \$$collectionVar;\n\n";

            // Add getter with lazy initialization
            $entityCode .= "    /**\n";
            $entityCode .= "     * @return Collection<int, $otherTableClassName>\n";
            $entityCode .= "     */\n";
            $entityCode .= "    public function get" . ucfirst($collectionVar) . "(): Collection\n";
            $entityCode .= "    {\n";
            $entityCode .= "        if (!\$this->$collectionVar instanceof Collection) {\n";
            $entityCode .= "            \$this->$collectionVar = new ArrayCollection();\n";
            $entityCode .= "        }\n";
            $entityCode .= "        return \$this->$collectionVar;\n";
            $entityCode .= "    }\n\n";

            // Add add method
            $entityCode .= "    public function add" . ucfirst($singularVar) . "($otherTableClassName \$$singularVar): self\n";
            $entityCode .= "    {\n";
            $entityCode .= "        if (!\$this->get" . ucfirst($collectionVar) . "()->contains(\$$singularVar)) {\n";
            $entityCode .= "            \$this->get" . ucfirst($collectionVar) . "()->add(\$$singularVar);\n";
            $entityCode .= "        }\n";
            $entityCode .= "        return \$this;\n";
            $entityCode .= "    }\n\n";

            // Add remove method
            $entityCode .= "    public function remove" . ucfirst($singularVar) . "($otherTableClassName \$$singularVar): self\n";
            $entityCode .= "    {\n";
            $entityCode .= "        \$this->get" . ucfirst($collectionVar) . "()->removeElement(\$$singularVar);\n";
            $entityCode .= "        return \$this;\n";
            $entityCode .= "    }\n\n";
        }
    }

    $entityCode .= "}\n";

    // Write the entity class to a file
    $filePath = "$outputDir/$className.php";
    file_put_contents($filePath, $entityCode);

    echo "Generated entity: $filePath\n";
}

echo "Entity generation complete with relationships (including OneToOne, OneToMany, ManyToOne, and ManyToMany)!\n";

// Helper functions to map MySQL types to PHP and Doctrine types
function mapMySQLTypeToPhpType($mysqlType)
{
    if (strpos($mysqlType, 'tinyint(1)') !== false) {
        return 'bool';
    } elseif (strpos($mysqlType, 'int') !== false) {
        return 'int';
    } elseif (strpos($mysqlType, 'float') !== false || strpos($mysqlType, 'double') !== false || strpos($mysqlType, 'decimal') !== false) {
        return 'float';
    } elseif (strpos($mysqlType, 'datetime') !== false || strpos($mysqlType, 'timestamp') !== false) {
        return '\\DateTimeInterface';
    } elseif (strpos($mysqlType, 'date') !== false) {
        return '\\DateTimeInterface';
    } elseif (strpos($mysqlType, 'blob') !== false || strpos($mysqlType, 'binary') !== false) {
        return 'string';
    } else {
        return 'string';
    }
}

function mapMySQLTypeToDoctrineType($mysqlType)
{
    if (strpos($mysqlType, 'tinyint(1)') !== false) {
        return 'boolean';
    } elseif (strpos($mysqlType, 'int') !== false) {
        return 'integer';
    } elseif (strpos($mysqlType, 'float') !== false) {
        return 'float';
    } elseif (strpos($mysqlType, 'double') !== false || strpos($mysqlType, 'decimal') !== false) {
        return 'decimal';
    } elseif (strpos($mysqlType, 'datetime') !== false || strpos($mysqlType, 'timestamp') !== false) {
        return 'datetime';
    } elseif (strpos($mysqlType, 'date') !== false) {
        return 'date';
    } elseif (strpos($mysqlType, 'time') !== false) {
        return 'time';
    } elseif (strpos($mysqlType, 'blob') !== false || strpos($mysqlType, 'binary') !== false) {
        return 'blob';
    } elseif (strpos($mysqlType, 'text') !== false) {
        return 'text';
    } else {
        return 'string';
    }
}
