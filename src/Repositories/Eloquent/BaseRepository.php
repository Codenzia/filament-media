<?php

namespace Codenzia\FilamentMedia\Repositories\Eloquent;

use Codenzia\FilamentMedia\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;

abstract class BaseRepository implements RepositoryInterface
{
    protected $model;

    protected $originalModel;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->originalModel = $model;
    }

    public function applyBeforeExecuteQuery($data, bool $isSingle = false)
    {
        return $data;
    }

    public function setModel(MediaFile|Builder $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getTable(): string
    {
        return $this->model->getTable();
    }

    public function make(array $with = [])
    {
        return $this->model->with($with);
    }

    public function getFirstBy(array $condition = [], array $select = [], array $with = [])
    {
        $query = $this->model->where($condition)->with($with);
        if ($select) {
            $query->select($select);
        }
        return $this->applyBeforeExecuteQuery($query->first(), true);
    }

    public function findById($id, array $with = [])
    {
        return $this->applyBeforeExecuteQuery($this->model->with($with)->find($id), true);
    }

    public function findOrFail($id, array $with = [])
    {
        return $this->applyBeforeExecuteQuery($this->model->with($with)->findOrFail($id), true);
    }

    public function pluck(string $column, $key = null, array $condition = [])
    {
        return $this->model->where($condition)->pluck($column, $key);
    }

    public function all(array $with = [])
    {
        return $this->applyBeforeExecuteQuery($this->model->with($with)->get());
    }

    public function allBy(array $condition, array $with = [], array $select = ['*'])
    {
        return $this->applyBeforeExecuteQuery($this->model->with($with)->select($select)->where($condition)->get());
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function createOrUpdate($data, array $condition = [])
    {
        if (is_array($data)) {
            if (empty($condition)) {
                $item = new $this->model;
                foreach ($data as $key => $value) {
                    $item->{$key} = $value;
                }
                $item->save();
                return $item;
            }
            return $this->model->updateOrCreate($condition, $data);
        }
        return false;
    }

    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }

    public function firstOrCreate(array $data, array $with = [])
    {
        return $this->model->with($with)->firstOrCreate($data);
    }

    public function update(array $condition, array $data): int
    {
        return $this->model->where($condition)->update($data);
    }

    public function select(array $select = ['*'], array $condition = [])
    {
        return $this->applyBeforeExecuteQuery($this->model->where($condition)->select($select)->get());
    }

    public function deleteBy(array $condition = []): bool
    {
        return $this->model->where($condition)->delete();
    }

    public function count(array $condition = []): int
    {
        return $this->model->where($condition)->count();
    }

    public function getByWhereIn($column, array $value = [], array $args = [])
    {
        return $this->applyBeforeExecuteQuery($this->model->whereIn($column, $value)->get());
    }

    public function advancedGet(array $params = [])
    {
        $params = array_merge([
            'condition' => [],
            'order_by' => [],
            'take' => null,
            'paginate' => [
                'per_page' => null,
                'current_paged' => 1,
            ],
            'select' => ['*'],
            'with' => [],
        ], $params);

        $query = $this->model->with($params['with'])->select($params['select']);

        if ($params['condition']) {
            foreach ($params['condition'] as $key => $condition) {
                if (is_array($condition)) {
                    if (count($condition) === 3) {
                        $query->where($condition[0], $condition[1], $condition[2]);
                    } elseif (count($condition) === 2) {
                        if($condition[1] === 'IN') {
                            // Handled if needed or assumed whereIn if 2nd arg is array?
                            // But Eloquent whereIn is different.
                            // Assuming standard where:
                             $query->where($condition[0], $condition[1]);
                        } else {
                            $query->where($condition[0], $condition[1]);
                        }
                    }
                } else {
                    $query->where($key, $condition);
                }
            }
        }

        if ($params['order_by']) {
            foreach ($params['order_by'] as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }

        if ($params['take'] == 1) {
            return $this->applyBeforeExecuteQuery($query->first(), true);
        }

        if ($params['take']) {
            return $this->applyBeforeExecuteQuery($query->take($params['take'])->get());
        }

        if ($params['paginate']['per_page']) {
            return $this->applyBeforeExecuteQuery(
                $query->paginate(
                    $params['paginate']['per_page'],
                    ['*'],
                    'page',
                    $params['paginate']['current_paged']
                )
            );
        }

        return $this->applyBeforeExecuteQuery($query->get());
    }

    public function forceDelete(array $condition = [])
    {
        return $this->model->where($condition)->forceDelete();
    }

    public function restoreBy(array $condition = [])
    {
        return $this->model->withTrashed()->where($condition)->restore();
    }

    public function getFirstByWithTrash(array $condition = [], array $select = [])
    {
        $query = $this->model->withTrashed()->where($condition);
        if ($select) {
            $query->select($select);
        }
        return $this->applyBeforeExecuteQuery($query->first(), true);
    }

    public function insert(array $data): bool
    {
        return $this->model->insert($data);
    }

    public function firstOrNew(array $condition)
    {
        return $this->model->firstOrNew($condition);
    }
}
