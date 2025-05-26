<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected Model $model;

    /**
     * Create a new repository instance.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->get();
    }

    /**
     * Find a record by ID.
     *
     * @param int $id
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->select($columns)->find($id);
    }

    /**
     * Find a record by ID or fail.
     *
     * @param int $id
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->select($columns)->findOrFail($id);
    }

    /**
     * Find records by criteria.
     *
     * @param array $criteria
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection
    {
        $query = $this->model->select($columns);
        
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->get();
    }

    /**
     * Find a single record by criteria.
     *
     * @param array $criteria
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model
    {
        $query = $this->model->select($columns);
        
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->first();
    }

    /**
     * Create a new record.
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Delete a record.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->model->destroy($id) > 0;
    }

    /**
     * Get paginated records.
     *
     * @param int $perPage
     * @param array $columns
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->select($columns)->paginate($perPage);
    }

    /**
     * Count records by criteria.
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int
    {
        $query = $this->model->newQuery();
        
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->count();
    }
}
