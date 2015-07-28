<?php

namespace db;

class MySQLException extends PDOException
{
    public function __construct(PDOException $e, $msg = "")
    {
        parent::__construct($msg ? $msg : $e->getMessage(), $e->errorInfo[1], $e);
    }
}

class DuplicateEntryException extends MySQLException
{
    public $key, $value;

    public function __construct(PDOException $e)
    {
        preg_match('/.*Duplicate entry \'(.*)\' for key \'(.*)\'/', $e->getMessage(), $matches);
        parent::__construct($e, "A duplicate value was encountered for {$matches[2]} key");
        $this->value = $matches[1];
        $this->key = $matches[2];
    }
}

class ForeignKeyConstraintException extends MySQLException
{
    public function __construct(PDOException $e)
    {
        preg_match('/.*a foreign key constraint fails \((.*), CONSTRAINT (.*) FOREIGN KEY \((.*)\) REFERENCES (.*) \((.*)\)\)$/', $e->getMessage(), $matches);
        $this->foreignTable = $this->normalize($matches[1]);
        $this->constraintName = $this->normalize($matches[2]);
        $this->constraintColumn = $this->normalize($matches[3]);
        $this->offendingTable = $this->normalize($matches[4]);
        $this->offendingColumn = $this->normalize($matches[5]);
        parent::__construct($e, "A foreign key constraint \"{$this->constraintName}\" from " . implode('.', $this->foreignTable) . " fails on `{$this->offendingTable}`.`{$this->offendingColumn}`");
    }

    private function normalize($sqlRef)
    {
        $parts = explode('.', $sqlRef);
        if (count($parts) === 1) {
            return str_replace('`', '', $parts[0]);
        }
        $arr = array();
        foreach ($parts as $part) {
            $arr[] = $this->normalize($part);
        }
        return $arr;
    }
}


class Chain
{
    private $db;
    private $command;
    private $data;
    private $allowed;
    private $queries;
    private $must_row_count;

    public function __construct(PDO $db, $query, array $allowed, array $data = array(), $must_row_count = false)
    {
        $this->db = $db;
        $this->command = $query;
        $this->data = $data;
        $this->allowed = $allowed;
        $this->queries = array();
        foreach ($allowed as $fn) {
            $this->queries[$fn] = array("query" => "", "data" => array());
        }
        $this->must_row_count = $must_row_count;
    }

    private function _where($column, $op, $value = null, $bracket = null, $type = 'AND')
    {
        MySQL::validIdentifier($column);
        if (!preg_match('/^([<>=&~|^%]|<=>|>=|<>|IS(( NOT)?( NULL)?)?|LIKE|!=)$/i', $op))
            throw new Exception("Unknown or bad operator '$op'");
        $type = strtoupper($type);
        if ($type != 'AND' && $type != 'OR')
            $type = 'AND';
        $type = ' ' . $type;
        $openBracket = strpos($bracket, '(') !== false ? '(' : '';
        $closeBracket = strpos($bracket, ')') !== false ? ')' : '';
        if (!$this->queries['where']['query']) {
            // The first time this is called, use WHERE instead of AND or OR (starting value)
            $type = 'WHERE';
        }
        $valMarker = $value !== null ? ' ?' : ' NULL';
        return array("query" => "{$type} $openBracket$column $op$valMarker$closeBracket",
                     "mode" => "a",
                     "data" => $value !== null ? array($value) : array());
    }

    private function _orWhere($key, $op, $value, $bracket = null)
    {
        return $this->_where($key, $op, $value, $bracket, 'OR');
    }

    private function _limit($from, $to = null)
    {
        $query = 'LIMIT ?';
        $data = array($from);
        if ($to !== null) {
            $query .= ", ?";
            $data[] = $to;
        }
        return array("query" => $query, "data" => $data);
    }

    private function _groupBy($column)
    {
        MySQL::validIdentifier($column);
        return array("query" => "GROUP BY `$column`");
    }

    // TODO: Order By multiple columns
    private function _orderBy($column, $order)
    {
        MySQL::validIdentifier($column);
        if (!preg_match('/^\s*(ASC|DESC)+\s*$/i', $order))
            throw new Exception("Unknown order by sort: $order");
        return array("query" => "ORDER BY `$column` $order");
    }

    public function __call($name, array $arguments)
    {
        $afunc = $func = preg_replace('/(\w)_(\w)/e', '"$1" . strtoupper("$2")', $name);
        if ($afunc == 'orWhere') {
            // _orWhere extends the base _where
            $afunc = 'where';
        }
        if (!in_array($afunc, $this->allowed))
            throw new Exception($afunc . " not allowed for query type.");
        if (($res = call_user_func_array(array($this, "_$func"), $arguments)) === false) {
            throw new BadMethodCallException("Unknown function or wrong parameters ($name)");
        }
        if (isset($res["query"])) {
            $res["mode"] = isset($res["mode"]) ? $res["mode"] : "w";
            if (!isset($res["data"]))
                $res["data"] = array();
            if ($res["mode"] == "w") {
                $this->queries[$afunc]["query"] = $res["query"];
                $this->queries[$afunc]["data"] = $res["data"];
            } elseif ($res["mode"] == "a") {
                $this->queries[$afunc]["query"] .= $res["query"];
                $this->queries[$afunc]["data"] = array_merge($this->queries[$afunc]["data"], $res["data"]);
            }
        }
        return $this;
    }

    private function buildQuery()
    {
        $query = $this->command;
        $data = $this->data;
        foreach ($this->allowed as $fn) {
            if ($this->queries[$fn]['query'] != "") {
                $query .= ' ' . $this->queries[$fn]['query'];
                $data = array_merge($data, $this->queries[$fn]['data']);
            }
        }
        return array($query, $data);
    }

    public function _($row_count = false, $insert_id = null)
    {
        list($query, $data) = $this->buildQuery();
        $statement = $this->db->prepare($query);
        foreach ($data as $i => $value) {
            switch(gettype($value)) {
                case 'integer':
                case 'double':
                    $type = PDO::PARAM_INT;
                    break;
                case 'boolean':
                    $type = PDO::PARAM_BOOL;
                    break;
                case 'NULL':
                    $type = PDO::PARAM_NULL;
                case 'string':
                default:
                    $type = PDO::PARAM_STR;
                    break;
            }
            $statement->bindValue($i + 1, $value, $type);
        }
        try {
            if ($statement->execute()) {
                if ($insert_id) {
                    return $this->db->lastInsertId($insert_id);
                }
                if ($row_count || $this->must_row_count) {
                    return $statement->rowCount();
                }
                return $statement->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                if ($e->errorInfo[1] === 1062) {
                    throw new DuplicateEntryException($e);
                }
                if ($e->errorInfo[1] === 1451) {
                    throw new ForeignKeyConstraintException($e);
                }
            }
            throw new MySQLException($e);
        }
        return array();
    }
}

class JoinChain
{
    private $query = array();
    private $data = array();

    private function addQuery($column, $op, $value, $type, $isColumn = false)
    {
        MySQL::validIdentifier($column);
        if ($isColumn)
            MySQL::validIdentifier($value);
        if (!preg_match('/^([<>=&~|^%]|<=>|>=|<>|IS(( NOT)?( NULL)?)?|LIKE|!=)$/i', $op))
            throw new Exception("Unknown or bad operator '$op'");
        if (count($this->query) == 0)
            $type = '';
        $this->query[] = "$type $column $op " . ($isColumn ? $value : '?');
        if (!$isColumn)
            $this->data[] = $value;
        return $this;
    }

    public function on($column, $op, $value)
    {
        return $this->addQuery($column, $op, $value, 'AND');
    }

    public function orOn($column, $op, $value)
    {
        return $this->addQuery($column, $op, $value, 'OR');
    }

    public function onColumn($column1, $op, $column2)
    {
        return $this->addQuery($column1, $op, $column2, 'AND', true);
    }

    public function orOnColumn($column1, $op, $column2)
    {
        return $this->addQuery($column1, $op, $column2, 'OR', true);
    }

    public function getQuery()
    {
        if (count($this->query) == 0)
            throw new Exception('No join conditions declared');
        return 'ON (' . implode(' ', $this->query) . ')';
    }

    public function getData()
    {
        return $this->data;
    }
}

class SelectJoiner
{
    private $joins = array();

    private function addJoin($method, $table)
    {
        MySQL::validIdentifier($table);
        $chain = new JoinChain();
        $this->joins[] = array('method' => $method, 'table' => $table, 'conditions' => $chain);
        return $chain;
    }

    public function __call($joinFunc, array $arguments)
    {
        if (count($arguments) != 1)
            throw new InvalidArgumentException('Must provide one argument, the table, to join');
        $func = strtoupper(preg_replace('/([a-z])([A-Z])/', '$1 $2', $joinFunc));
        if (strpos($func, 'JOIN') === false)
            throw new BadMethodCallException('method call is not a join');
        return $this->addJoin($func, $arguments[0]);
    }

    public function getQuery()
    {
        $query = '';
        $data = array();
        foreach ($this->joins as $join) {
            $query .= "{$join['method']} `{$join['table']}` ";
            $query .= $join['conditions']->getQuery() . ' ';
            $data = array_merge($data, $join['conditions']->getData());
        }
        return array($query, $data);
    }
}

class MySQL
{
    private $db;
    private $macros;

    public static function getInstance()
    {
        static $instance;
        if ($instance === null) {
            $instance = new self(DB_HOST, DB_NAME, DB_USER, DB_PASS);
        }
        return $instance;
    }

    private function __construct($host, $database, $username, $password)
    {
        $this->db = new PDO("mysql:dbname={$database};host={$host};charset=utf8", $username, $password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->macros = array();
    }

    public static function validIdentifier($identifier)
    {
        if (!is_string($identifier))
            throw new Exception("Identifier must be a string. Got " . gettype($identifier) . "($identifier)");
        foreach (explode('.', $identifier) as $component)
            if (!preg_match('/^\s*[\w$]+\s*$/', $component))
                throw new Exception("Invalid identifier format '$identifier'");
        return $identifier;
    }

    public function select($rows, $table, Closure $joinCallback = null)
    {
        self::validIdentifier($table);
        if ($rows != '*')
            foreach (preg_split('/,\s*/', $rows) as $row)
                self::validIdentifier($row);
        $query = "SELECT $rows FROM `$table`";
        $data = array();
        if ($joinCallback !== null) {
            $callback = $joinCallback->bindTo($joiner = new SelectJoiner());
            $callback();
            list($joinQuery, $joinData) = $joiner->getQuery();
            $query .= ' ' . $joinQuery;
            $data = array_merge($data, $joinData);
        }
        return new Chain($this->db, $query, array('where', 'groupBy', 'orderBy', 'limit'), $data);
    }

    public function count($table, $col = '*')
    {
        self::validIdentifier($table);
        if ($col !== '*') {
            $col = '`' . self::validIdentifier($col) . '`';
        }
        return new Chain($this->db, "SELECT COUNT({$col}) AS 'count' FROM `{$table}`", array('where'), array());
    }

    public function update($table, $data, $unsafeData = '')
    {
        self::validIdentifier($table);
        if (count($data) == 0) {
            throw new Exception('Must provide data to update');
        }
        $set = implode(' = ?, ', array_map('MySQL::validIdentifier', array_keys($data))) . ' = ?';
        $values = array_values($data);
        $unsafeData = $unsafeData ? ', ' . $unsafeData : '';
        return new Chain($this->db, "UPDATE `$table` SET $set $unsafeData", array('where', 'orderBy', 'limit'), $values, true);
    }

    public function insert($table, $data)
    {
        return $this->insertBatch($table, array($data));
    }

    public function insert_batch($table, $data)
    {
        return $this->insertBatch($table, $data);
    }

    public function insertBatch($table, $data)
    {
        self::validIdentifier($table);
        $row_count = count($data);
        if ($row_count == 0) {
            throw new Exception('Must insert at least 1 row');
        }
        $columns = array();
        $i = 0;
        foreach ($data as $row) {
            foreach($row as $column => $value) {
                self::validIdentifier($column);
                if (!isset($columns[$column])) {
                    if ($i > 0) {
                        $columns[$column] = array_fill(0, $i, null);
                    } else {
                        $columns[$column] = array();
                    }
                }
                $columns[$column][$i] = $value;
            }
            $i++;
        }
        $akeys = array_keys($columns);
        $keys = '(' . implode(', ', $akeys) . ')';
        $values = implode(', ', array_fill(0, count($data), '(' . implode(', ', array_fill(0, count($akeys), '?')) . ')'));
        $d_values = array();
        for ($i = 0; $i < count($data); $i++) {
            foreach ($columns as $cdata) {
                $d_values[] = isset($cdata[$i]) ? $cdata[$i] : null;
            }
        }
        return new Chain($this->db, "INSERT INTO `$table` $keys VALUES $values", array(), $d_values, true);
    }

    public function delete($table)
    {
        self::validIdentifier($table);
        return new Chain($this->db, "DELETE FROM `$table`", array('where', 'orderBy', 'limit'), array(), true);
    }

    public function exec($sql)
    {
        trigger_error("Calling MySQL->exec is bad! It could open you database to SQL injections. Please use a safe function.", E_USER_WARNING);
        // It's also unable to detect invalid SQL before sent to the database.
        try {
            return $this->db->exec($sql);
        } catch (PDOException $e) {
            throw new MySQLException($e);
        }
    }

    public function query($sql)
    {
        trigger_error("Calling MySQL->query is bad! It could open you database to SQL injections. Please use a safe function.", E_USER_WARNING);
        $statement = $this->db->prepare($sql);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createTable($table, array $fields, array $indexes = array(), array $options = array())
    {
        $validFieldType = function ($type)
        {
            if (!preg_match('/^[A-Za-z]{3,}(\(\d+\))?$/', $type))
                throw new Exception("Invalid field type '$type'");
        };

        self::validIdentifier($table);
        $fieldNames = array();
        $fmtFields = array();
        foreach ($fields as $field) {
            if (!is_array($field) || count($field) < 2) {
                throw new Exception("Invalid field '" . print_r($field, true) . "'");
            }
            $fieldNames[] = $field[0];
            $field[0] = '`' . self::validIdentifier($field[0]) . '`';
            $validFieldType($field[1]);
            $fmtFields[] = implode(' ', $field);
        }
        $keys = array();
        foreach ($indexes as $key => $columns) {
            $arr = array();
            $key = strtoupper($key);
            if (!in_array($key, array('PRIMARY', 'INDEX', 'UNIQUE', 'FULLTEXT')))
                throw new Exeption('Unknown index type ' . $key);
            if ($key === 'INDEX')
                $key = '';
            foreach ($columns as $column) {
                $size = false;
                if (preg_match('/(.*)\((\d+)\)/', $column, $m)) {
                    $column = $m[1];
                    $size = (int) $m[2];
                }
                self::validIdentifier($column);
                if (!in_array($column, $fieldNames)) {
                    throw new Exception("Cannot create index for '$column' as it does not exist.");
                }
                $str = "`$column`";
                if ($size !== false)
                    $str .= "($size)";
                if (!in_array($str, $arr)) {
                    $arr[] = $str;
                }
            }
            $keys[$key] = $arr;
        }
        foreach ($keys as $type => $values) {
            if (count($values) === 0)
                continue;
            $fmtFields[] = $type . ' KEY (' . implode(', ', $values) . ')';
        }
        $opts = "";
        foreach ($options as $key => $value) {
            $opts .= " $key=$value";
        }
        try {
            return $this->db->exec("CREATE TABLE `$table` (\n" . implode(", \n", $fmtFields) . "\n)$opts") !== false;
        } catch (PDOException $e) {
            throw new MySQLException($e);
        }
    }

    public function deleteTable($table)
    {
        self::validIdentifier($table);
        try {
            return $this->db->exec("DROP TABLE $table") !== false;
        } catch (PDOException $e) {
            throw new MySQLException($e);
        }
    }

    public function defineMacro($name, Closure $function)
    {
        $this->macros[$name] = $function->bindTo($this);
    }

    public function getMacro($name)
    {
        return $this->macros[$name];
    }

    public function callMacro($name)
    {
        $args = func_get_args();
        array_shift($args);
        return call_user_func_array($this->getMacro($name), $args);
    }
}
