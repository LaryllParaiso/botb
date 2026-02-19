<?php
/**
 * CRUDInterface — Interface Segregation Principle
 * Used by services that manage entities (bands, judges)
 */
interface CRUDInterface
{
    public function getAll(): array;
    public function getById(int $id): ?array;
    public function create(array $data): bool;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
