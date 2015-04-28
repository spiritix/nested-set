<?php
/**
 * This file is part of the spiritix/nested-set package.
 *
 * @copyright Copyright (c) Matthias Isler <mi@matthias-isler.ch>
 * @license   MIT
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiritix\NestedSet;

/**
 * A comprehensive library implementing the nested set pattern in PHP using PDO.
 *
 * @todo Finish this library
 *
 * @package Spiritix\HtmlToPdf
 * @author  Matthias Isler <mi@matthias-isler.ch>
 */
class NestedSet
{
    /**
     * Get node level
     */
    const READ_OPTION_LEVEL = 'readOptionLevel';

    /**
     * Get quantity of node children
     */
    const READ_OPTION_CHILDREN = 'readOptionChildren';

    /**
     * Get quantity of brother nodes on the left side
     */
    const READ_OPTION_LOWER = 'readOptionLower';

    /**
     * Get quantity of brother nodes on the right side
     */
    const READ_OPTION_UPPER = 'readOptionUpper';

    /**
     * This is going to be the initial node
     */
    const WRITE_TYPE_ROOT = 'writeTypeRoot';

    /**
     * This is going to be the first child of a node
     */
    const WRITE_TYPE_CHILD = 'writeTypeChild';

    /**
     * This node has to be placed on the left
     */
    const WRITE_TYPE_LEFT = 'writeTypeLeft';

    /**
     * This node has to be placed on the right
     */
    const WRITE_TYPE_RIGHT = 'writeTypeRight';

    /**
     * Table name
     * @var string
     */
    protected $_table = '';

    /**
     * Primary key column name
     * @var string
     */
    protected $_keyColumn = 'id';

    /**
     * Left value column name
     * @var string
     */
    protected $_leftColumn = 'lft';

    /**
     * Right value column name
     * @var string
     */
    protected $_rightColumn = 'rgt';

    /**
     * Asset column names
     * @var array
     */
    protected $_assetColumns = array();

    /**
     * Object of PDO
     * @var null|object
     */
    private $_pdoInstance = null;

    /**
     * Prefix for values provided by this class
     * @var string
     */
    private $_prefix = 'ns_';

    /**
     * Instead of using the setters for configuration you can pass the values to the constructor
     *
     * @param array $options An option is valid as soon as there is a setter/getter for it
     * @throws NestedSetException if an option is not valid
     */
    public function __construct($options = array())
    {
        foreach ($options as $option => $value) {
            $setterName = 'set' . ucfirst($option);

            if (method_exists($this, $setterName)) {
                $this->$setterName($value);
            }
            else {
                throw new NestedSetException($option . ' is not a valid option');
            }
        }
    }

    /**
     * Set PDO instance which will be used for all DB transactions
     *
     * @param object $pdoInstance Instance of PDO
     * @throws NestedSetException if $pdoInstance is not an instance of PDO
     * @return object $this
     */
    public function setPdoInstance($pdoInstance)
    {
        if (!$pdoInstance instanceof \PDO) {
            throw new NestedSetException('$pdoInstance is not an instance of PDO');
        }

        $this->_pdoInstance = $pdoInstance;
        return $this;
    }

    /**
     * Getter for $_pdoInstance;
     *
     * @return bool|null Returns false if PDO instance has not been set
     */
    public function getPdoInstance()
    {
        return ($this->_pdoInstance) ? $this->_pdoInstance : false;
    }

    /**
     * Set name of nested set table
     *
     * @param string $table
     * @throws NestedSetException If $table is not valid
     * @return object $this
     */
    public function setTable($table)
    {
        if (empty($table) || !is_string($table)) {
            throw new NestedSetException('$table is not a valid table name');
        }

        $this->_table = $table;
        return $this;
    }

    /**
     * Getter for $_table
     *
     * @return bool|string Returns false if table has not been set
     */
    public function getTable()
    {
        return (!empty($this->_table)) ? $this->_table : false;
    }

    /**
     * Set name of primary key column
     *
     * @param string $keyColumn
     * @throws NestedSetException If $keyColumn is invalid
     * @return object $this;
     */
    public function setKeyColumn($keyColumn)
    {
        if (empty($keyColumn) || !is_string($keyColumn)) {
            throw new NestedSetException('$keyColumn is not a valid column name');
        }

        $this->_keyColumn = $keyColumn;
        return $this;
    }

    /**
     * Getter for $_keyColumn
     *
     * @return bool|string Returns false if key column has not been set
     */
    public function getKeyColumn()
    {
        return (!empty($this->_keyColumn)) ? $this->_keyColumn : false;
    }

    /**
     * Set name of right value column
     *
     * @param string $rightColumn
     * @throws NestedSetException If $rightColumn is invalid
     * @return object $this
     */
    public function setRightColumn($rightColumn)
    {
        if (empty($rightColumn) || !is_string($rightColumn)) {
            throw new NestedSetException('$rightColumn is not a valid column name');
        }

        $this->_rightColumn = $rightColumn;
        return $this;
    }

    /**
     * Getter for $_rightColumn
     *
     * @return bool|string Returns false if right column has not been set
     */
    public function getRightColumn()
    {
        return (!empty($this->_rightColumn)) ? $this->_rightColumn : false;
    }

    /**
     * Set name of left value column
     *
     * @param string $leftColumn
     * @throws NestedSetException If $leftColumn is invalid
     * @return object $this;
     */
    public function setLeftColumn($leftColumn)
    {
        if (empty($leftColumn) || !is_string($leftColumn)) {
            throw new NestedSetException('$leftColumn is not a valid column name');
        }

        $this->_leftColumn = $leftColumn;
        return $this;
    }

    /**
     * Getter for $_leftColumn
     *
     * @return bool|string Returns false if left column has not been set
     */
    public function getLeftColumn()
    {
        return (!empty($this->_leftColumn)) ? $this->_leftColumn : false;
    }

    /**
     * Set names of asset columns
     * This columns will be fetched with every read result
     * Additionally they will be excepted from the write methods
     *
     * @param array $assetColumns
     * @throws NestedSetException If $assetColumns are inavlid
     * @return object $this
     */
    public function setAssetColumns($assetColumns)
    {
        if (empty($assetColumns) || !is_array($assetColumns)) {
            throw new NestedSetException('$rightColumn is not a valid column name');
        }

        $this->_assetColumns = $assetColumns;
        return $this;
    }

    /**
     * Getter for $_assetColumns
     *
     * @return array|bool Returns false if asset columns have not been set
     */
    public function getAssetColumns()
    {
        return (is_array($this->_assetColumns)) ? $this->_assetColumns : false;
    }

    /**
     * Set prefix for columns provided by this class
     *
     * @param string $prefix
     * @throws NestedSetException If $prefix is invalid
     * @return object $this
     */
    public function setPrefix($prefix)
    {
        if (!is_string($prefix)) {
            throw new NestedSetException('$prefix is not a valid prefix');
        }

        $this->_prefix = $prefix;
        return $this;
    }

    /**
     * Getter for $_prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * Get a single node
     *
     * @see NestedSet::_getNodes()
     * @param int $keyValue
     * @param array $options
     * @return mixed
     */
    public function getNode($keyValue, $options = array())
    {
        return $this->_getNodes($keyValue, $options);
    }

    /**
     * Get all nodes
     *
     * @see NestedSet::_getNodes()
     * @param array $options
     * @return mixed
     */
    public function getAllNodes($options = array())
    {
        return $this->_getNodes(null, $options);
    }

    /**
     * Create SQL column triggers from $_assetColumns
     *
     * @param string $tableAlias
     * @return string SQL partial statement
     */
    private function _getAssetColumnsSql($tableAlias)
    {
        $assetColumnSql = '';
        $assetColumns = $this->getAssetColumns();

        $tableAlias = (empty($tableAlias)) ? '' : '.' . $tableAlias;

        if ($assetColumns) {
            foreach ($assetColumns as $column) {

                $assetColumnSql .= $tableAlias . '`' . $column . '`, ';
            }
        }

        return substr($assetColumnSql, 0, -2);
    }

    /**
     * Read one or multiple nodes from DB
     * Additional information can be requested with option triggers
     *
     * @param int $keyValue Primary key of node
     * @param array $options Accepts all read options
     * @return mixed Depends on the fetch style, false on failure
     */
    private function _getNodes($keyValue, $options = array())
    {
        $sql = "SELECT
                    t1.`" . $this->getKeyColumn() . "`,
                    t1.`" . $this->getLeftColumn() . "`,
                    t1.`" . $this->getRightColumn() . "`,
                    " . $this->_getAssetColumnsSql('t1') . ", ";

        switch(true) {
            // Calculate level
            case isset($options[NestedSet::READ_OPTION_LEVEL]):
                $sql .= "COUNT(*) - 1 + (t1.`" . $this->getLeftColumn() . "` > 1)
                         AS " . $this->getPrefix() . "level, ";

            // Calculate quantity of children
            case isset($options[NestedSet::READ_OPTION_CHILDREN]):
                $sql .= "ROUND ((
                            t1.`" . $this->getRightColumn() . "`
                            - t1.`" . $this->getLeftColumn() . "`
                            - 1
                        ) / 2, 0
                    ) AS " .$this->getPrefix() ."children, ";

            // Calculate quantity of left brothers
            case isset($options[NestedSet::READ_OPTION_LOWER]):
                $sql .= "(((
                        t1.`" . $this->getLeftColumn() . "`
                        - max(t2.`" . $this->getLeftColumn() . "`)
                        > 1
                    )))
                        AS " . $this->getPrefix() . "lower, ";

            // Calculate quantity of right brothers
            case isset($options[NestedSet::READ_OPTION_UPPER]):
                $sql .= "(
                        (
                            CAST(
                                min(t2.`" . $this->getRightColumn() . "`)
                                - t1.`" . $this->getRightColumn() . "`
                            AS SIGNED)
                            - (t1.`" . $this->getLeftColumn() . "` > 1)
                        )
                        / 2
                    ) > 0
                        AS " . $this->getPrefix() . "upper, ";
        }

        $sql = substr($sql, 0, -2) . " ";

        // Main algorithmus part 1
        $sql .= "FROM
                    `" . $this->getTable() . "` AS t1,
                    `" . $this->getTable() . "` AS t2
                 WHERE
                    t1.`" . $this->getLeftColumn() . "`
                        BETWEEN t2.`" . $this->getLeftColumn() . "`
                        AND t2.`" . $this->getRightColumn() . "`
                    AND (
                        t2.`" . $this->getKeyColumn() . "` != t1.`" . $this->getKeyColumn() . "`
                        OR t1.`" . $this->getLeftColumn() . "` = 1
                    ) ";

        // If single node
        if ($keyValue) {
            $sql .= "AND t1.`" . $this->getKeyColumn() . "` = :key_value ";
        }

        // Main algorithmus part 2
        $sql .= "GROUP BY t1.`" . $this->getKeyColumn() . "`
                  ORDER BY t1.`" . $this->getLeftColumn() . "` ";

        $statement = $this->_pdoInstance->prepare($sql);

        // If single node
        if ($keyValue) {
            $statement->bindValue(':key_value', $keyValue);
        }

        $statement->execute();

        // If single node
        if ($keyValue) {
            return $statement->fetch();
        }

        return $statement->fetchAll();
    }

    /**
     * Get node without any kind of additional information or children
     *
     * @param int $keyValue Primary key of node
     * @return mixed Depends on fetch style, false on failure
     */
    public function getSimpleNode($keyValue)
    {
        $sql = "SELECT
                    `" . $this->getKeyColumn() . "`,
                    `" . $this->getLeftColumn() . "`,
                    `" . $this->getRightColumn() . "`,
                    " . $this->_getAssetColumnsSql('') . "
                FROM `" . $this->getTable() . "`
                WHERE `" . $this->getKeyColumn() . "` = :key_value
                LIMIT 1";

        $statement = $this->_pdoInstance->prepare($sql);
        $statement->bindParam(':key_value', $keyValue);

        $statement->execute();
        return $statement->fetch();
    }

    /**
     * Get parent nodes
     * Provided node will also be returned
     *
     * @param int $keyValue Primary key of node
     * @return mixed Depends on fetch style, false on failure
     */
    public function getParentNodes($keyValue)
    {
        $sql = "SELECT
                    t2.`" . $this->getKeyColumn() . "`,
                    t2.`" . $this->getLeftColumn() . "`,
                    t2.`" . $this->getRightColumn() . "`,
                    " . $this->_getAssetColumnsSql('t2') . "
                FROM `" . $this->getTable() . "` AS t1,
                     `" . $this->getTable() . "` AS t2
                WHERE
                    t1.`" . $this->getLeftColumn() . "`
                        BETWEEN t2.`" . $this->getLeftColumn() . "`
                        AND t2.`" . $this->getRightColumn() . "`
                AND t1.`" . $this->getKeyColumn() . "` = :key_value
                ORDER BY t1.`" . $this->getLeftColumn() . "`";

        $statement = $this->_pdoInstance->prepare($sql);
        $statement->bindValue(':key_value', $keyValue);

        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Get child nodes
     * Provided node will also be returned
     *
     * @param int $keyValue Primary key of node
     * @return mixed Depends on fetch style, false on failure
     */
    public function getChildNodes($keyValue)
    {
        $sql = "SELECT
                    t3.`" . $this->getKeyColumn() . "`,
                    t3.`" . $this->getLeftColumn() . "`,
                    t3.`" . $this->getRightColumn() . "`,
                    " . $this->_getAssetColumnsSql('t3') . "
                FROM
                    `" . $this->getTable() . "` AS t1,
                    `" . $this->getTable() . "` AS t2,
                    `" . $this->getTable() . "` AS t3
                WHERE t3.`" . $this->getLeftColumn() . "`
                    BETWEEN t2.`" . $this->getLeftColumn() . "`
                    AND t2.`" . $this->getRightColumn() . "`
                AND t3.`" . $this->getLeftColumn() . "`
                    BETWEEN t1.`" . $this->getLeftColumn() . "`
                    AND t1.`" . $this->getRightColumn() . "`
                AND t1.`" . $this->getKeyColumn() . "` = :key_value
                GROUP BY t3.`" . $this->getLeftColumn() . "`
                ORDER BY t3.`" . $this->getLeftColumn() . "`";

        $statement = $this->_pdoInstance->prepare($sql);
        $statement->bindValue(':key_value', $keyValue);

        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Insert a new node
     * Specify with which method you want to place the node and if necessary the primary key of related node
     *
     * @todo Remove stop/start of transaction within try/catch block
     * @param string $type Accepts all write types
     * @param null|int $keyValue Primary key of node, must not be specified if the root node should be created
     * @param array $assetValues Values of asset columns
     * @throws NestedSetException On failure, multiple possibilities
     * @return bool If it happend or not
     */
    public function insertNode($type, $keyValue = null, $assetValues = array())
    {
        $assetColumns = $this->getAssetColumns();

        // Write the first (root) node
        if ($type == NestedSet::WRITE_TYPE_ROOT) {

            // Check if there is already a root node
            $sql = "SELECT 1 FROM `" . $this->getTable() . "` LIMIT 1";
            $rowCount = $this->_pdoInstance->query($sql)->fetchColumn();

            if ($rowCount) {
                throw new NestedSetException('There can be only one root node');
            }

            $leftValue = 1;
            $rightValue = 2;
        }
        // if we are not writing the root node the primary key value must be specified
        elseif (!$keyValue) {
            throw new NestedSetException('Please provide a key value');
        }

        // Main insert Statement
        $sql = "INSERT INTO `" . $this->getTable() . "`
                SET
                    `" . $this->getLeftColumn() . "` = :left_value,
                    `" . $this->getRightColumn() . "` = :right_value";

        // Add asset columns
        if (!empty($assetValues)) {
            $sql .= ", ";

            foreach ($assetValues as $column => $value) {
                // Check if column exists resp. has been set
                if (!in_array($column, $assetColumns)) {
                    throw new NestedSetException($column . ' is not a member of $assetColumns');
                }

                $sql .= "`" . $column . "` = :" . $column . "_value, ";
            }

            $sql = substr($sql, 0, -2);
        }

        try {
            // Start transaction
            $this->_pdoInstance->beginTransaction();
            $this->_lockTables();

            // If we want to place the node to the left of $keyValue
            if ($type == NestedSet::WRITE_TYPE_LEFT) {
                $brother = $this->getNodeSimple($keyValue);

                // Update other nodes
                $updateSql = "UPDATE `" . $this->getTable() . "`
                              SET
                                  `" . $this->getRightColumn() . "` = `" . $this->getRightColumn() . "` + 2,
                                  `" . $this->getLeftColumn() . "` = `" . $this->getLeftColumn() . "` + 2
                              WHERE
                                `" . $this->getLeftColumn() . "` >=
                                '" . $brother[$this->getLeftColumn()] . "'";

                $this->_pdoInstance->exec($updateSql);

                // Set left an right values for the main statement
                $leftValue = $brother[$this->getLeftColumn()];
                $rightValue = $brother[$this->getRightColumn()];
            }

            // If we want to place the node to the left of $keyValue
            if ($type == NestedSet::WRITE_TYPE_RIGHT) {
                $brother = $this->getNodeSimple($keyValue);

                // Update other nodes
                $updateSql = "UPDATE `" . $this->getTable() . "`
                              SET
                                  `" . $this->getRightColumn() . "` = `" . $this->getRightColumn() . "` + 2,
                                  `" . $this->getLeftColumn() . "` = `" . $this->getLeftColumn() . "` + 2
                              WHERE
                                `" . $this->getRightColumn() . "` >
                                '" . $brother[$this->getRightColumn()] . "'";

                $this->_pdoInstance->exec($updateSql);

                // This is not beautiful but it wont atm. work without it
                $this->_pdoInstance->commit();
                $this->_pdoInstance->beginTransaction();

                // Set left an right values for the main statement
                $leftValue = $brother[$this->getRightColumn()] + 1;
                $rightValue = $brother[$this->getRightColumn()] + 2;
            }

            // If we want to place the node as a child of $keyValue
            if ($type == NestedSet::WRITE_TYPE_CHILD) {

                // Check if there is already a child
                // Multiple placing of children without determining the brother /may/ work but should not
                $children = $this->getChildNodes($keyValue);
                if (!empty($children)) {

                    throw new NestedSetException('There can only be one child node');
                }

                $updateSql = "";
                $parents = $this->getParentNodes($keyValue);

                // Loop trough parents
                foreach ($parents as $parent) {
                    if ($parent[$this->getKeyColumn()] == $keyValue) {

                        // If we found the parent set left/right values for the main statement
                        $leftValue = $parent[$this->getRightColumn()];
                        $rightValue = $parent[$this->getRightColumn()] + 1;
                    }

                    $updateSql .= "`" . $this->getKeyColumn() . "` = '" . $parent[$this->getKeyColumn()] . "' OR ";
                }

                // Update other nodes
                $updateSql = "UPDATE `" . $this->getTable() . "`
                              SET `" . $this->getRightColumn() . "` = `" . $this->getRightColumn() . "` + 2
                              WHERE " . substr($updateSql, 0, -3);

                $this->_pdoInstance->exec($updateSql);
            }

            $statement = $this->_pdoInstance->prepare($sql);

            $statement->bindValue(':left_value', $leftValue);
            $statement->bindValue(':right_value', $rightValue);

            // Add asset alues
            if (!empty($assetValues)) {
                foreach ($assetValues as $column => $value) {

                    $statement->bindValue(':' . $column . '_value', $value);
                }
            }

            // Execute main statement, free tables and so on...
            $statement->execute();
            $rowId = $this->_pdoInstance->lastInsertId($this->_keyColumn);

            $this->_pdoInstance->exec("UNLOCK TABLES");
            $this->_pdoInstance->commit();

            return $rowId;
        }
            // If something went wrong roll back everything we did
        catch(\PDOException $e) {

            $this->_pdoInstance->rollBack();
            return false;
        }
    }

    /**
     * @todo Implement this
     */
    public function moveNode() { }

    /**
     * Delete a node and it's children
     *
     * @param int $keyValue Primary key of node
     * @return bool If it happend or not
     */
    public function deleteNode($keyValue)
    {
        try {
            $this->_pdoInstance->beginTransaction();
            $this->_lockTables();

            $node = $this->getSimpleNode($keyValue);

            // Delete the node
            $deleteSql = "DELETE FROM `" . $this->getTable() . "`
                          WHERE
                              `" . $this->getLeftColumn() . "`
                                  BETWEEN '" . $node[$this->getLeftColumn()] . "'
                                  AND '" . $node[$this->getRightColumn()] . "'";

            $this->_pdoInstance->exec($deleteSql);

            // Update left values of other nodes
            $updateSql = "UPDATE `" . $this->getTable() . "`
                          SET `" . $this->getLeftColumn() . "` = `" . $this->getLeftColumn() . "` - ROUND(
                              ('" . $node[$this->getRightColumn()] . "' - '" . $node[$this->getLeftColumn()] . "' + 1)
                          )
                          WHERE `" . $this->getLeftColumn() . "` > '" . $node[$this->getRightColumn()] . "'";

            $this->_pdoInstance->exec($updateSql);

            // Update right values of other nodes
            $updateSql = "UPDATE `" . $this->getTable() . "`
                          SET `" . $this->getRightColumn() . "` = `" . $this->getRightColumn() . "` - ROUND(
                              ('" . $node[$this->getRightColumn()] . "' - '" . $node[$this->getLeftColumn()] . "' + 1)
                          )
                          WHERE `" . $this->getRightColumn() . "` > '" . $node[$this->getRightColumn()] . "'";

            $this->_pdoInstance->exec($updateSql);

            $this->_pdoInstance->exec("UNLOCK TABLES");
            $this->_pdoInstance->commit();

            return true;
        }
        catch(\PDOException $e) {

            $this->_pdoInstance->rollBack();
            return false;
        }
    }

    /**
     * Lock tables and aliases
     *
     * @return bool If it happend or not
     */
    private function _lockTables()
    {
        $lockSql = "LOCK TABLES
                            `" . $this->getTable() . "` AS t1 WRITE,
                            `" . $this->getTable() . "` AS t2 WRITE,
                            `" . $this->getTable() . "` AS t3 WRITE,
                            `" . $this->getTable() . "` WRITE";

        return $this->_pdoInstance->exec($lockSql);
    }
}