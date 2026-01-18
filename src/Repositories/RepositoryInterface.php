<?php

namespace Codenzia\FilamentMedia\Repositories;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
/**
 * @method Model|BaseQueryBuilder|null query()
 */
interface RepositoryInterface
{
    public function applyBeforeExecuteQuery($data, bool $isSingle = false);

    public function setModel(MediaFile|Builder $model): self;

    public function getModel();

    public function getTable(): string;

    public function make(array $with = []);

    public function getFirstBy(array $condition = [], array $select = [], array $with = []);

    public function findById($id, array $with = []);

    public function findOrFail($id, array $with = []);

    public function pluck(string $column, $key = null, array $condition = []);

    public function all(array $with = []);

    public function allBy(array $condition, array $with = [], array $select = ['*']);

    public function create(array $data);

    public function createOrUpdate($data, array $condition = []);

    public function delete(Model $model): ?bool;

    public function firstOrCreate(array $data, array $with = []);

    public function update(array $condition, array $data): int;

    public function select(array $select = ['*'], array $condition = []);

    public function deleteBy(array $condition = []): bool;

    public function count(array $condition = []): int;

    public function getByWhereIn($column, array $value = [], array $args = []);

    public function advancedGet(array $params = []);

    public function forceDelete(array $condition = []);

    public function restoreBy(array $condition = []);

    public function getFirstByWithTrash(array $condition = [], array $select = []);

    public function insert(array $data): bool;

    public function firstOrNew(array $condition);
}
