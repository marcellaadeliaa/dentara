<?php
namespace App\Core;

use App\Core\Database;
use PDO;

abstract class Model {
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function all(): array {
        return Database::fetchAll("SELECT * FROM " . static::$table . " ORDER BY " . static::$primaryKey . " DESC");
    }

    public static function find($id): ?array {
        return Database::fetchOne("SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id LIMIT 1", [':id' => $id]);
    }

    public static function where(string $column, $value): array {
        return Database::fetchAll("SELECT * FROM " . static::$table . " WHERE {$column} = :val", [':val' => $value]);
    }

    public static function create(array $data): string {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = ':' . implode(', :', $keys);

        $sql = "INSERT INTO " . static::$table . " ({$fields}) VALUES ({$placeholders})";
        $stmt = Database::getConnection()->prepare($sql);
        
        $params = [];
        foreach ($data as $k => $v) {
            $params[':' . $k] = $v;
        }
        $stmt->execute($params);
        return Database::lastInsertId();
    }

    public static function update($id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $k => $v) {
            $fields[] = "{$k} = :{$k}";
            $params[':' . $k] = $v;
        }
        $sql = "UPDATE " . static::$table . " SET " . implode(', ', $fields) . " WHERE " . static::$primaryKey . " = :id";
        return Database::query($sql, $params)->rowCount() > 0;
    }

    public static function delete($id): bool {
        return Database::query("DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id", [':id' => $id])->rowCount() > 0;
    }
}
