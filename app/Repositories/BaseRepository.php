<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseRepository implements IRepository
{
	protected $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function all()
	{
		return $this->model->all();
	}

	public function find($id)
	{
		return $this->model->find($id);
	}

	public function create(array $data)
	{
		return $this->model->create($data);
	}

	public function update($id, array $data)
	{
		$record = $this->model->find($id);
		$record->update($data);

		return $record->fresh();
	}

	public function delete($id)
	{
		return $this->model->destroy($id);
	}

	public function paginate(int $perPage)
	{
		return $this->model->paginate($perPage);
	}

	public function checkIsExists(int | array $id): bool
	{
		$isExists = false;

		if (is_array($id)) {
			foreach ($id as $modelId) {
				$model = $this->model->find($modelId);
				if ($model) $isExists = true;
			}

			return $isExists;
		}

		return boolval($this->model->find($id));
	}

	protected function format(array $data): array
	{
		$saveCopy = [];

		foreach ($data as $key => $value) {
			$saveCopy[Str::snake($key)] = $value;
		}

		return $saveCopy;
	}
}
